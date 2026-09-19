<?php

namespace App\Services;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Exceptions\StaleApplicationVersionException;
use App\Models\AccessArea;
use App\Models\ApplicationReview;
use App\Models\ApplicationStatusHistory;
use App\Models\ExamSession;
use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\PermitApplication;
use App\Models\SimperCategory;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermitApplicationWorkflow
{
    /** @var list<string> */
    private const NON_ACTIVE_STATUSES = [
        'issued', 'expired', 'revoked', 'cancelled', 'exam_failed_final',
    ];

    public function __construct(
        private readonly KttApprovalClaimService $approvalClaims,
        private readonly PermitIssuanceService $permitIssuances,
        private readonly OperationalAuditService $audit,
        private readonly WorkflowNotificationService $notifications,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @param  array<string, mixed>  $data
     * @param  list<int>  $accessAreaIds
     */
    public function createDraft(User $actor, Manpower $manpower, Type $type, array $categories = [], array $data = [], array $accessAreaIds = []): PermitApplication
    {
        if (! $type->requiresExam() && $categories !== []) {
            throw ValidationException::withMessages(['categories' => 'Kategori SIMPER tidak boleh ditambahkan pada Mine Permit only.']);
        }

        return DB::transaction(function () use ($actor, $manpower, $type, $categories, $data, $accessAreaIds) {
            /** @var Manpower $currentManpower */
            $currentManpower = Manpower::withTrashed()->with('partner')->lockForUpdate()->findOrFail($manpower->id);
            $this->validateManpowerOrganization($currentManpower);
            $this->authorizeApplicantForManpower($actor, $currentManpower, 'permit-application.create');

            $duplicateExists = PermitApplication::query()
                ->where('owner_id', $currentManpower->owner_id)
                ->where('manpower_id', $currentManpower->id)
                ->where('type', $type->value)
                ->whereNotIn('status', self::NON_ACTIVE_STATUSES)
                ->exists();
            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'manpower' => 'Tenaga kerja ini sudah memiliki pengajuan aktif untuk owner dan jenis yang sama.',
                ]);
            }

            $application = PermitApplication::create([
                'application_number' => $this->nextApplicationNumber($currentManpower->owner_id, $type),
                'type' => $type,
                'owner_id' => $currentManpower->owner_id,
                'partner_id' => $currentManpower->partner_id,
                'manpower_id' => $currentManpower->id,
                'created_by' => $actor->id,
                ...$this->draftAttributes($data, $currentManpower),
            ]);

            $this->syncCategories($application, $categories);
            $this->syncAccessAreas($application, $accessAreaIds);

            return $application->load(['categories', 'accessAreas']);
        });
    }

    /**
     * @param  array<int, array{category_id:int, level:string}>  $categories
     */
    public function updateDraft(PermitApplication $application, User $actor, array $categories): PermitApplication
    {
        $application->loadMissing('accessAreas');

        return $this->saveDraft(
            $application,
            $actor,
            $application->only([
                'site_name', 'assignment_position', 'assignment_department', 'planned_start_date',
                'requested_valid_until', 'applicant_notes', 'truth_declared_at', 'processing_consented_at',
            ]),
            $categories,
            $application->accessAreas->modelKeys(),
            $application->version,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $categories
     * @param  list<int>  $accessAreaIds
     */
    public function saveDraft(PermitApplication $application, User $actor, array $data, array $categories, array $accessAreaIds, int $expectedVersion): PermitApplication
    {
        $expectedStatus = $application->status;

        return DB::transaction(function () use ($application, $actor, $data, $categories, $accessAreaIds, $expectedVersion, $expectedStatus) {
            /** @var PermitApplication $current */
            $current = PermitApplication::query()->with(['partner', 'manpower', 'categories', 'accessAreas'])->findOrFail($application->id);
            if ($current->version !== $expectedVersion || $current->status !== $expectedStatus) {
                throw new StaleApplicationVersionException;
            }
            if (! in_array($current->status, [Status::Draft, Status::HseRevisionRequired, Status::KttRevisionRequired], true)) {
                throw new DomainException('Pengajuan hanya dapat diubah saat draft atau revisi.');
            }
            $this->authorizeApplicantForApplication($actor, $current, 'permit-application.update');
            if (! $current->type->requiresExam() && $categories !== []) {
                throw ValidationException::withMessages(['categories' => 'Kategori SIMPER tidak boleh ditambahkan pada Mine Permit only.']);
            }

            $updated = PermitApplication::query()->whereKey($current->id)
                ->where('version', $expectedVersion)->where('status', $expectedStatus->value)
                ->update([
                    ...$this->draftAttributes($data, $current->manpower),
                    'version' => $expectedVersion + 1,
                    'updated_at' => now(),
                ]);
            if ($updated !== 1) {
                throw new StaleApplicationVersionException;
            }
            $this->syncCategories($current, $categories);
            $this->syncAccessAreas($current, $accessAreaIds);

            return $application->fresh(['categories', 'accessAreas']);
        }, 3);
    }

    public function submit(PermitApplication $application, User $actor, ?string $notes = null): PermitApplication
    {
        return $this->move($application, $actor, [Status::Draft, Status::HseRevisionRequired, Status::KttRevisionRequired], Status::HseReview, 'submit', $notes,
            function (PermitApplication $current, array &$changes): void {
                $this->validateCompleteness($current);
                $submittedAt = now();
                $validUntil = $submittedAt->copy()->addYear();
                $submissionVersion = $current->submission_version + 1;
                $snapshot = $current->manpower->snapshot();
                $snapshot['application_number'] = $current->application_number;
                $snapshot['application_type'] = $current->type->value;
                $snapshot['submission_version'] = $submissionVersion;
                $snapshot['application_data'] = [
                    ...$current->only([
                        'site_name', 'planned_start_date', 'applicant_notes', 'truth_declared_at', 'processing_consented_at',
                    ]),
                    'assignment_position' => $current->manpower->position,
                    'assignment_department' => $current->manpower->department,
                    'requested_valid_until' => $validUntil->toDateString(),
                ];
                $snapshot['access_areas'] = $current->accessAreas->map(fn (AccessArea $area) => [
                    'id' => $area->id,
                    'code' => $area->code,
                    'name' => $area->name,
                ])->values()->all();
                $snapshot['categories'] = $current->categories->map(fn (SimperCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'level' => $category->pivot->level,
                    'restrictions' => $category->pivot->restrictions,
                    'supervisor_name' => $category->pivot->supervisor_name,
                    'activity_start_date' => $category->pivot->activity_start_date,
                    'activity_end_date' => $category->pivot->activity_end_date,
                ])->values()->all();

                $changes['submission_version'] = $submissionVersion;
                $changes['submitted_at'] = $submittedAt;
                $changes['assignment_position'] = $current->manpower->position;
                $changes['assignment_department'] = $current->manpower->department;
                $changes['requested_valid_until'] = $validUntil->toDateString();
                $changes['submitted_snapshot'] = $snapshot;

                foreach ($current->manpower->documents as $document) {
                    $current->documentSnapshots()->create([
                        'manpower_document_id' => $document->id,
                        'submission_version' => $submissionVersion,
                        'type' => $document->type,
                        'document_number' => $document->document_number,
                        'issued_at' => $document->issued_at,
                        'expires_at' => $document->expires_at,
                        'file_path' => $document->file_path,
                        'checksum' => $document->checksum,
                        'source_version' => $document->version,
                        'metadata' => [
                            'original_name' => $document->original_name,
                            'mime_type' => $document->mime_type,
                            'verification_status' => $document->verification_status,
                        ],
                    ]);
                }
            }, 'applicant', 'permit-application.submit');
    }

    public function approveHse(PermitApplication $application, User $actor, ?string $notes = null, ?int $expectedVersion = null): PermitApplication
    {
        $to = $application->type->requiresExam() ? Status::WaitingExamSetup : Status::KttReview;

        return $this->reviewMove(
            $application,
            $actor,
            Status::HseReview,
            $to,
            'hse',
            'approved',
            'approve_hse',
            $notes,
            'hse_owner',
            'permit-application.review-hse',
            false,
            function (PermitApplication $current) use ($actor): void {
                $documentIds = $current->documentSnapshots()
                    ->where('submission_version', $current->submission_version)
                    ->whereNotNull('manpower_document_id')
                    ->pluck('manpower_document_id');

                ManpowerDocument::query()
                    ->where('manpower_id', $current->manpower_id)
                    ->whereIn('id', $documentIds)
                    ->update([
                        'verification_status' => 'verified',
                        'verification_note' => null,
                        'verified_by' => $actor->id,
                        'verified_at' => now(),
                    ]);
            },
            $expectedVersion,
        );
    }

    public function rejectHse(PermitApplication $application, User $actor, string $notes, ?int $expectedVersion = null): PermitApplication
    {
        return $this->reviewMove($application, $actor, Status::HseReview, Status::HseRevisionRequired, 'hse', 'rejected', 'reject_hse', $notes, 'hse_owner', 'permit-application.review-hse', true, null, $expectedVersion);
    }

    public function scheduleExam(PermitApplication $application, User $actor, array $metadata = [], ?string $notes = null): PermitApplication
    {
        if (! isset($metadata['exam_session_id'])) {
            throw ValidationException::withMessages(['exam_session_id' => 'Sesi ujian wajib dicatat.']);
        }
        if (! ExamSession::query()->whereKey($metadata['exam_session_id'])
            ->where(fn ($query) => $query->where('permit_application_id', $application->id)
                ->orWhereNull('permit_application_id'))
            ->where('owner_id', $application->owner_id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['exam_session_id' => 'Sesi ujian tidak aktif atau bukan milik owner pengajuan.']);
        }

        return $this->move($application, $actor, [Status::WaitingExamSetup, Status::ExamRetryRequired], Status::ExamScheduled, 'schedule_exam', $notes, null, 'hse_owner', 'permit-application.configure-exam', $metadata);
    }

    public function startExam(PermitApplication $application, array $metadata = []): PermitApplication
    {
        return $this->move($application, null, [Status::ExamScheduled], Status::ExamInProgress, 'start_exam', null, null, null, null, $metadata);
    }

    public function recordExamResult(PermitApplication $application, bool $passed, bool $attemptsRemain, array $metadata = []): PermitApplication
    {
        if (! isset($metadata['attempt_id'])) {
            throw ValidationException::withMessages(['attempt_id' => 'Attempt ujian wajib dicatat.']);
        }
        $to = $passed ? Status::ExamPassed : ($attemptsRemain ? Status::ExamRetryRequired : Status::ExamFailedFinal);

        return $this->move($application, null, [Status::ExamInProgress], $to, 'record_exam_result', null, null, null, null, $metadata);
    }

    public function submitToKtt(PermitApplication $application, User $actor, ?string $notes = null): PermitApplication
    {
        return $this->move($application, $actor, [Status::ExamPassed], Status::KttReview, 'submit_to_ktt', $notes, null, 'hse_owner', 'permit-application.submit-ktt');
    }

    public function approveKtt(PermitApplication $application, User $actor, ?string $notes = null, ?int $expectedVersion = null): PermitApplication
    {
        $approved = $this->reviewMove($application, $actor, Status::KttReview, Status::Approved, 'ktt', 'approved', 'approve_ktt', $notes, 'ktt', 'permit-application.review-ktt', false, null, $expectedVersion);
        $this->permitIssuances->issue($approved);

        return $approved->fresh(['categories', 'documentSnapshots', 'reviews', 'statusHistories', 'issuance']);
    }

    public function rejectKtt(PermitApplication $application, User $actor, string $notes, ?int $expectedVersion = null): PermitApplication
    {
        return $this->reviewMove($application, $actor, Status::KttReview, Status::KttRevisionRequired, 'ktt', 'rejected', 'reject_ktt', $notes, 'ktt', 'permit-application.review-ktt', true, null, $expectedVersion);
    }

    public function issue(PermitApplication $application, array $metadata = []): PermitApplication
    {
        $this->permitIssuances->issue($application, $metadata);

        return $application->fresh(['categories', 'documentSnapshots', 'reviews', 'statusHistories', 'issuance']);
    }

    public function cancel(PermitApplication $application, User $actor, string $notes): PermitApplication
    {
        return $this->move($application, $actor, [Status::Draft, Status::HseRevisionRequired, Status::KttRevisionRequired], Status::Cancelled, 'cancel', $notes, null, 'applicant', 'permit-application.update');
    }

    public function revoke(PermitApplication $application, User $actor, string $notes): PermitApplication
    {
        $issuance = $application->issuance()->firstOrFail();
        $this->permitIssuances->revoke($issuance, $actor, $notes);

        return $application->fresh(['issuance', 'statusHistories']);
    }

    public function expire(PermitApplication $application, array $metadata = []): PermitApplication
    {
        $this->permitIssuances->expire($application->issuance()->firstOrFail());

        return $application->fresh(['issuance', 'statusHistories']);
    }

    private function reviewMove(PermitApplication $application, User $actor, Status $from, Status $to, string $stage, string $decision, string $action, ?string $notes, string $role, string $permission, bool $notesRequired = false, ?callable $extra = null, ?int $expectedVersion = null): PermitApplication
    {
        if ($notesRequired && trim((string) $notes) === '') {
            throw ValidationException::withMessages(['notes' => 'Alasan penolakan wajib diisi.']);
        }

        return $this->move($application, $actor, [$from], $to, $action, $notes,
            function (PermitApplication $current, array &$changes) use ($actor, $stage, $decision, $notes, $extra): void {
                $reviewedAt = now();
                $actor->loadMissing(['partner', 'roles']);
                $reviewData = [
                    'permit_application_id' => $current->id,
                    'submission_version' => $current->submission_version,
                    'stage' => $stage,
                    'decision' => $decision,
                    'notes' => $notes,
                    'reviewer_id' => $actor->id,
                    'reviewer_snapshot' => [
                        'id' => $actor->id,
                        'name' => $actor->name,
                        'organization_id' => $actor->partner_id,
                        'organization_name' => $actor->partner?->legal_name,
                        'roles' => $actor->roles->pluck('name')->sort()->values()->all(),
                    ],
                    'reviewed_at' => $reviewedAt,
                ];

                if ($stage === 'ktt' && $decision === 'approved') {
                    $this->validateKttApprovalPrerequisites($current);
                    $evidence = $this->approvalClaims->issue($current, $actor, $reviewedAt);
                    $reviewData['approval_claim_token'] = $evidence['token'];
                    $changes['approved_at'] = $reviewedAt;
                    $changes['issuance_requested_at'] = $reviewedAt;
                }

                ApplicationReview::create($reviewData);
                if ($extra) {
                    $extra($current, $changes);
                }
            }, $role, $permission, [], $expectedVersion);
    }

    /**
     * @param  list<Status>  $from
     */
    private function move(PermitApplication $application, ?User $actor, array $from, Status $to, string $action, ?string $notes = null, ?callable $beforeUpdate = null, ?string $role = null, ?string $permission = null, array $metadata = [], ?int $clientExpectedVersion = null): PermitApplication
    {
        $expectedVersion = $clientExpectedVersion ?? $application->version;
        $expectedStatus = $application->status;

        return DB::transaction(function () use ($application, $actor, $from, $to, $action, $notes, $beforeUpdate, $role, $permission, $metadata, $expectedVersion, $expectedStatus) {
            /** @var PermitApplication $current */
            $current = PermitApplication::query()->with(['manpower.partner', 'manpower.owner', 'manpower.documents', 'categories', 'accessAreas'])->findOrFail($application->id);
            if ($current->version !== $expectedVersion || $current->status !== $expectedStatus) {
                throw new StaleApplicationVersionException;
            }
            if (! in_array($current->status, $from, true)) {
                throw new DomainException("Transisi dari status {$current->status->value} ke {$to->value} tidak diizinkan.");
            }
            if ($current->status->isFinal()) {
                throw new DomainException('Pengajuan berstatus final tidak dapat diproses lagi.');
            }
            $this->validateTypeAndSnapshotForTransition($current, $to);
            if ($actor) {
                if ($role === 'applicant') {
                    $this->authorizeApplicantForApplication($actor, $current, $permission);
                } else {
                    $scopeId = $role === 'ktt' ? $current->owner_id : $current->partner_id;
                    $this->authorize($actor, $role, $permission, $scopeId, $role === 'ktt');
                }
                if ($actor->isDeveloper() && trim((string) $notes) === '') {
                    throw ValidationException::withMessages(['notes' => 'Developer wajib mencatat alasan override.']);
                }
            } elseif ($role !== null || $permission !== null) {
                throw new AuthorizationException('Transisi ini membutuhkan pengguna yang terautentikasi.');
            }

            $changes = ['status' => $to->value, 'version' => $expectedVersion + 1, 'updated_at' => now()];
            if ($beforeUpdate) {
                $beforeUpdate($current, $changes);
            }

            $updated = PermitApplication::query()
                ->whereKey($current->id)
                ->where('version', $expectedVersion)
                ->where('status', $expectedStatus->value)
                ->update($changes);
            if ($updated !== 1) {
                throw new StaleApplicationVersionException;
            }

            ApplicationStatusHistory::create([
                'permit_application_id' => $current->id,
                'from_status' => $expectedStatus,
                'to_status' => $to,
                'action' => $action,
                'actor_type' => $actor ? 'user' : 'system',
                'actor_id' => $actor?->id,
                'notes' => $notes,
                'metadata' => $metadata,
                'application_version' => $expectedVersion + 1,
                'transitioned_at' => now(),
            ]);

            $this->audit->record(
                'permit_application.'.$action,
                $current,
                $actor,
                $current->owner_id,
                $expectedStatus->value,
                $to->value,
                [
                    'application_number' => $current->application_number,
                    'application_version' => $expectedVersion + 1,
                    'submission_version' => $current->submission_version,
                    'application_type' => $current->type->value,
                ],
            );
            $this->notifications->applicationTransition($current, $action, $expectedVersion + 1);

            return $application->fresh(['categories', 'documentSnapshots', 'reviews', 'statusHistories']);
        }, 3);
    }

    private function validateCompleteness(PermitApplication $application): void
    {
        $manpower = $application->manpower;
        $this->validateManpowerOrganization($manpower);
        $errors = [];
        if ($manpower->partner_id !== $application->partner_id || $manpower->owner_id !== $application->owner_id) {
            $errors['manpower'] = 'Data tenaga kerja tidak aktif atau tidak lagi sesuai organisasi pengajuan.';
        }
        foreach ([
            'site_name' => 'Site/lokasi kerja wajib diisi.',
            'planned_start_date' => 'Tanggal rencana mulai wajib diisi.',
        ] as $field => $message) {
            if (blank($application->{$field})) {
                $errors[$field] = $message;
            }
        }
        if (blank($manpower->position)) {
            $errors['assignment_position'] = 'Jabatan pada data manpower wajib diisi.';
        }
        if (blank($manpower->department)) {
            $errors['assignment_department'] = 'Departemen pada data manpower wajib diisi.';
        }
        if (! $application->truth_declared_at) {
            $errors['truth_declaration'] = 'Pernyataan kebenaran data wajib disetujui.';
        }
        if (! $application->processing_consented_at) {
            $errors['processing_consent'] = 'Persetujuan pemrosesan data wajib disetujui.';
        }
        if ($application->accessAreas->isEmpty()) {
            $errors['access_area_ids'] = 'Minimal satu area akses wajib dipilih.';
        }

        $requiredDocumentTypes = $application->type->requiresExam()
            ? Manpower::requiredDocumentTypesForCategories($application->categories)
            : Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS;
        $missing = $manpower->missingRequiredDocumentTypes($requiredDocumentTypes);
        if ($application->type->requiresExam()) {
            if ($application->categories->isEmpty()) {
                $errors['categories'] = 'Pengajuan gabungan wajib memiliki kategori SIMPER.';
            }
            foreach ($application->categories as $category) {
                $level = $category->pivot->level;
                if ($level === 'P' && blank($category->pivot->restrictions)) {
                    $errors['categories'] = 'Kategori berlevel P wajib memiliki catatan batasan.';
                }
                if (in_array($level, ['T', 'L'], true)
                    && (blank($category->pivot->supervisor_name) || blank($category->pivot->activity_start_date) || blank($category->pivot->activity_end_date))) {
                    $errors['categories'] = 'Kategori berlevel T/L wajib memiliki supervisor dan periode kegiatan.';
                }
            }
        } elseif ($application->categories->isNotEmpty()) {
            $errors['categories'] = 'Mine Permit only tidak boleh memiliki kategori SIMPER.';
        }

        if ($missing !== []) {
            $errors['documents'] = 'Dokumen belum lengkap: '.collect($missing)
                ->map(fn (string $type) => ManpowerDocument::TYPES[$type] ?? $type)->implode(', ').'.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateManpowerOrganization(Manpower $manpower): void
    {
        $organization = $manpower->partner;
        $expectedOwnerId = match (true) {
            $organization?->isOwner() => $organization->id,
            $organization?->isPartner() => $organization->owner_id,
            default => null,
        };

        if ($manpower->trashed() || ! $manpower->is_active || ! $organization
            || $organization->status !== 'active' || $expectedOwnerId === null
            || $manpower->owner_id !== $expectedOwnerId) {
            throw ValidationException::withMessages([
                'manpower' => 'Tenaga kerja atau organisasi tidak valid untuk pengajuan baru.',
            ]);
        }
    }

    private function authorizeApplicantForManpower(User $actor, Manpower $manpower, string $permission): void
    {
        if ($actor->isDeveloper()) {
            return;
        }

        $organization = $manpower->partner;
        $isSafetyApplicant = $actor->hasRole('safety_mitra')
            && $organization?->isPartner()
            && $actor->partner_id === $organization->id;
        $isHseApplicant = $actor->hasRole('hse_owner')
            && $organization?->isOwner()
            && $actor->partner_id === $organization->id;

        if (! $actor->hasValidOrganizationRole() || ! $actor->can($permission)
            || (! $isSafetyApplicant && ! $isHseApplicant)) {
            throw new AuthorizationException('Anda tidak berwenang membuat pengajuan untuk tenaga kerja ini.');
        }
    }

    private function authorizeApplicantForApplication(User $actor, PermitApplication $application, ?string $permission): void
    {
        if ($actor->isDeveloper()) {
            return;
        }

        $organization = $application->partner;
        $isSafetyApplicant = $actor->hasRole('safety_mitra')
            && $organization?->isPartner()
            && $actor->partner_id === $application->partner_id;
        $isHseApplicant = $actor->hasRole('hse_owner')
            && $organization?->isOwner()
            && $actor->partner_id === $application->partner_id;

        if (! $actor->hasValidOrganizationRole() || ($permission !== null && ! $actor->can($permission))
            || (! $isSafetyApplicant && ! $isHseApplicant)) {
            throw new AuthorizationException('Anda tidak berwenang mengubah atau mengirim pengajuan ini.');
        }
    }

    private function validateTypeAndSnapshotForTransition(PermitApplication $application, Status $to): void
    {
        $examStatuses = [
            Status::WaitingExamSetup, Status::ExamScheduled, Status::ExamInProgress,
            Status::ExamRetryRequired, Status::ExamFailedFinal, Status::ExamPassed,
        ];
        if (! $application->type->requiresExam() && in_array($to, $examStatuses, true)) {
            throw new DomainException('Mine Permit only tidak dapat memasuki alur ujian.');
        }
        if ($application->type->requiresExam() && $to === Status::KttReview && $application->status !== Status::ExamPassed) {
            throw new DomainException('Pengajuan gabungan hanya dapat masuk review KTT setelah ujian lulus.');
        }
        if (! in_array($to, [Status::HseReview, Status::Cancelled], true)
            && ($application->submission_version < 1 || $application->submitted_snapshot === null)) {
            throw new DomainException('Pengajuan belum memiliki snapshot submission yang valid.');
        }
    }

    private function validateKttApprovalPrerequisites(PermitApplication $application): void
    {
        $hasHseApproval = $application->reviews()
            ->where('submission_version', $application->submission_version)
            ->where('stage', 'hse')
            ->where('decision', 'approved')
            ->exists();

        if (! $hasHseApproval) {
            throw new DomainException('Pengajuan belum memiliki persetujuan HSE untuk versi submission ini.');
        }

        if ($application->type->requiresExam()) {
            $hasPassedAttempt = $application->examAttempts()
                ->where('status', 'completed')
                ->where('is_passed', true)
                ->whereNotNull('finished_at')
                ->whereNotNull('finalized_at')
                ->exists();

            if (! $hasPassedAttempt) {
                throw new DomainException('Pengajuan gabungan belum memiliki hasil ujian lulus yang telah difinalisasi.');
            }
        }
    }

    private function authorize(User $actor, ?string $role, ?string $permission, int $partnerId, bool $mustOwn = false): void
    {
        if ($actor->isDeveloper()) {
            return;
        }
        $roleAllowed = $role === null || $actor->hasRole($role);
        $permissionAllowed = $permission === null || $actor->can($permission);
        $scopeAllowed = $mustOwn ? $actor->partner_id === $partnerId : $actor->canAccessOrganizationId($partnerId);
        if (! $actor->hasValidOrganizationRole() || ! $roleAllowed || ! $permissionAllowed || ! $scopeAllowed) {
            throw new AuthorizationException('Anda tidak berwenang memproses pengajuan ini.');
        }
    }

    /** @param array<int, array<string, mixed>> $categories */
    private function syncCategories(PermitApplication $application, array $categories): void
    {
        $sync = [];
        foreach ($categories as $item) {
            $level = strtoupper($item['level'] ?? '');
            $category = SimperCategory::find($item['category_id'] ?? 0);
            if (! $category || ($category->owner_id !== null && $category->owner_id !== $application->owner_id) || ! in_array($level, ['F', 'P', 'T', 'L'], true)) {
                throw ValidationException::withMessages(['categories' => 'Kategori atau level SIMPER tidak valid untuk owner pengajuan.']);
            }
            $sync[$category->id] = [
                'level' => $level,
                'restrictions' => filled($item['restrictions'] ?? null) ? trim((string) $item['restrictions']) : null,
                'supervisor_name' => filled($item['supervisor_name'] ?? null) ? trim((string) $item['supervisor_name']) : null,
                'activity_start_date' => $item['activity_start_date'] ?? null,
                'activity_end_date' => $item['activity_end_date'] ?? null,
            ];
        }
        $application->categories()->sync($sync);
    }

    /** @param list<int> $accessAreaIds */
    private function syncAccessAreas(PermitApplication $application, array $accessAreaIds): void
    {
        $ids = collect($accessAreaIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $validIds = AccessArea::query()->forOwner($application->owner_id)->where('is_active', true)
            ->whereIn('id', $ids)->pluck('id');
        if ($validIds->count() !== $ids->count()) {
            throw ValidationException::withMessages(['access_area_ids' => 'Area akses tidak valid untuk owner pengajuan.']);
        }
        $application->accessAreas()->sync($validIds->all());
    }

    /** @param array<string, mixed> $data */
    private function draftAttributes(array $data, Manpower $manpower): array
    {
        $truthValue = $data['truth_declaration'] ?? $data['truth_declared_at'] ?? null;
        $consentValue = $data['processing_consent'] ?? $data['processing_consented_at'] ?? null;

        return [
            'site_name' => filled($data['site_name'] ?? null) ? trim((string) $data['site_name']) : null,
            'assignment_position' => $manpower->position,
            'assignment_department' => $manpower->department,
            'planned_start_date' => $data['planned_start_date'] ?? null,
            'requested_valid_until' => null,
            'applicant_notes' => filled($data['applicant_notes'] ?? null) ? trim((string) $data['applicant_notes']) : null,
            'truth_declared_at' => $truthValue ? ($data['truth_declared_at'] ?? now()) : null,
            'processing_consented_at' => $consentValue ? ($data['processing_consented_at'] ?? now()) : null,
        ];
    }

    private function nextApplicationNumber(int $ownerId, Type $type): string
    {
        $year = (int) now()->format('Y');
        DB::table('application_number_sequences')->insertOrIgnore([
            'owner_id' => $ownerId,
            'year' => $year,
            'type' => $type->value,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('application_number_sequences')->where('owner_id', $ownerId)
            ->where('year', $year)->where('type', $type->value)
            ->increment('last_number', 1, ['updated_at' => now()]);
        $sequence = DB::table('application_number_sequences')->where('owner_id', $ownerId)
            ->where('year', $year)->where('type', $type->value)->value('last_number');
        $prefix = DB::table('partners')->where('id', $ownerId)->value('permit_prefix')
            ?: DB::table('partners')->where('id', $ownerId)->value('short_name') ?: 'OWNER'.$ownerId;
        $kind = $type === Type::MinePermitOnly ? 'MP' : 'MPS';

        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $prefix))."-{$kind}-{$year}-".str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
