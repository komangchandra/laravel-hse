<?php

namespace App\Services;

use App\Enums\PermitApplicationStatus as ApplicationStatus;
use App\Models\ApplicationStatusHistory;
use App\Models\PermitApplication;
use App\Models\PermitIssuance;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PermitIssuanceService
{
    public function __construct(
        private readonly OperationalAuditService $audit,
        private readonly WorkflowNotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $metadata */
    public function issue(PermitApplication $application, array $metadata = []): PermitIssuance
    {
        return DB::transaction(function () use ($application, $metadata) {
            $existing = PermitIssuance::query()->where('permit_application_id', $application->id)->first();
            if ($existing) {
                return $existing;
            }

            /** @var PermitApplication $current */
            $current = PermitApplication::query()->with([
                'owner', 'partner', 'manpower', 'reviews', 'categories', 'accessAreas',
            ])->lockForUpdate()->findOrFail($application->id);
            if ($current->status !== ApplicationStatus::Approved) {
                throw new DomainException('Permit hanya dapat diterbitkan setelah persetujuan KTT.');
            }

            $approval = $current->reviews->where('stage', 'ktt')->where('decision', 'approved')
                ->where('submission_version', $current->submission_version)->sortByDesc('reviewed_at')->first();
            if (! $approval || blank($approval->approval_claim_token)) {
                throw new DomainException('Bukti persetujuan KTT tidak tersedia.');
            }

            $issuedAt = now();
            $validFrom = ($current->submitted_at ?? $issuedAt)->toDateString();
            $expiresAt = Carbon::parse($validFrom)->addYear()->toDateString();
            $simperExpiresAt = $current->type->requiresExam()
                ? $this->simperExpiry($current, Carbon::parse($expiresAt))->toDateString()
                : null;
            $publicId = (string) Str::uuid();
            $minePermitNumber = $this->nextNumber($current, 'mine_permit', $issuedAt);
            $simpolNumber = $current->type->requiresExam()
                ? $this->nextNumber($current, 'simpol', $issuedAt)
                : null;

            $snapshot = $this->printSnapshot(
                $current,
                $approval->reviewer_snapshot ?? [],
                $approval->approval_claim_token,
                $approval->reviewed_at,
                $publicId,
                $minePermitNumber,
                $simpolNumber,
                $validFrom,
                $expiresAt,
                $simperExpiresAt,
            );

            $issuance = PermitIssuance::create([
                'permit_application_id' => $current->id,
                'owner_id' => $current->owner_id,
                'partner_id' => $current->partner_id,
                'manpower_id' => $current->manpower_id,
                'approval_review_id' => $approval->id,
                'replaces_issuance_id' => $metadata['replaces_issuance_id'] ?? null,
                'type' => $current->type->value,
                'mine_permit_number' => $minePermitNumber,
                'simpol_number' => $simpolNumber,
                'public_id' => $publicId,
                'status' => 'active',
                'violation_indicator' => 'green',
                'print_snapshot' => $snapshot,
                'issued_at' => $issuedAt,
                'valid_from' => $validFrom,
                'expires_at' => $expiresAt,
                'simper_expires_at' => $simperExpiresAt,
            ]);

            $version = $current->version;
            $updated = PermitApplication::query()->whereKey($current->id)
                ->where('status', ApplicationStatus::Approved->value)
                ->where('version', $version)
                ->update([
                    'status' => ApplicationStatus::Issued->value,
                    'version' => $version + 1,
                    'issued_at' => $issuedAt,
                    'issued_snapshot' => $snapshot + ['issuance_id' => $issuance->id],
                    'updated_at' => $issuedAt,
                ]);
            if ($updated !== 1) {
                throw new DomainException('Status pengajuan berubah ketika permit diterbitkan.');
            }

            ApplicationStatusHistory::create([
                'permit_application_id' => $current->id,
                'from_status' => ApplicationStatus::Approved,
                'to_status' => ApplicationStatus::Issued,
                'action' => 'issue',
                'actor_type' => 'system',
                'notes' => 'Issuance dibuat otomatis setelah persetujuan KTT.',
                'metadata' => [
                    'issuance_id' => $issuance->id,
                    'mine_permit_number' => $minePermitNumber,
                    'simpol_number' => $simpolNumber,
                ],
                'application_version' => $version + 1,
                'transitioned_at' => $issuedAt,
            ]);

            $this->audit->record(
                'permit_issuance.created',
                $issuance,
                null,
                $current->owner_id,
                ApplicationStatus::Approved->value,
                ApplicationStatus::Issued->value,
                [
                    'application_id' => $current->id,
                    'mine_permit_number' => $minePermitNumber,
                    'simpol_number' => $simpolNumber,
                ],
            );
            $this->notifications->issuance($issuance->load('application'));

            if ($issuance->replaces_issuance_id) {
                $this->markReplaced($issuance->replaces_issuance_id, $issuance, $issuedAt);
            }

            return $issuance;
        }, 3);
    }

    public function revoke(PermitIssuance $issuance, User $actor, string $reason): PermitIssuance
    {
        if (trim($reason) === '') {
            throw new DomainException('Alasan pencabutan wajib diisi.');
        }
        if (! $actor->isDeveloper() && (! $actor->hasRole('ktt') || ! $actor->can('permit-card.revoke') || $actor->partner_id !== $issuance->owner_id)) {
            throw new AuthorizationException('Anda tidak berwenang mencabut permit ini.');
        }

        return DB::transaction(function () use ($issuance, $actor, $reason) {
            $current = PermitIssuance::query()->lockForUpdate()->findOrFail($issuance->id);
            if ($current->status !== 'active') {
                throw new DomainException('Hanya permit aktif yang dapat dicabut.');
            }
            $now = now();
            $current->update([
                'status' => 'revoked',
                'violation_indicator' => 'red',
                'revoked_at' => $now,
                'revoked_by' => $actor->id,
                'revocation_reason' => trim($reason),
            ]);
            $this->transitionApplication($current, ApplicationStatus::Issued, ApplicationStatus::Revoked, 'revoke', $actor, $reason, $now);
            $this->audit->record(
                'permit_issuance.revoked',
                $current,
                $actor,
                $current->owner_id,
                'active',
                'revoked',
                ['mine_permit_number' => $current->mine_permit_number],
            );

            return $current->fresh();
        }, 3);
    }

    public function expireDue(): int
    {
        $count = 0;
        PermitIssuance::query()->where('status', 'active')->whereDate('expires_at', '<', today())
            ->eachById(function (PermitIssuance $issuance) use (&$count) {
                if ($this->expire($issuance)) {
                    $count++;
                }
            });

        return $count;
    }

    public function expire(PermitIssuance $issuance): bool
    {
        return DB::transaction(function () use ($issuance) {
            $current = PermitIssuance::query()->lockForUpdate()->findOrFail($issuance->id);
            if ($current->status !== 'active') {
                return false;
            }
            if (! $current->expires_at->isPast()) {
                throw new DomainException('Permit belum melewati masa berlaku.');
            }
            $current->update(['status' => 'expired']);
            $this->transitionApplication($current, ApplicationStatus::Issued, ApplicationStatus::Expired, 'expire', null, null, now());
            $this->audit->record(
                'permit_issuance.expired',
                $current,
                null,
                $current->owner_id,
                'active',
                'expired',
                ['mine_permit_number' => $current->mine_permit_number],
            );

            return true;
        });
    }

    private function nextNumber(PermitApplication $application, string $kind, Carbon $at): string
    {
        $year = (int) $at->format('Y');
        DB::table('permit_number_sequences')->insertOrIgnore([
            'owner_id' => $application->owner_id,
            'year' => $year,
            'kind' => $kind,
            'last_number' => 0,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        DB::table('permit_number_sequences')->where('owner_id', $application->owner_id)
            ->where('year', $year)->where('kind', $kind)
            ->increment('last_number', 1, ['updated_at' => $at]);
        $number = (int) DB::table('permit_number_sequences')->where('owner_id', $application->owner_id)
            ->where('year', $year)->where('kind', $kind)->value('last_number');
        $prefix = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $application->owner->permit_prefix ?: $application->owner->short_name));

        if ($kind === 'simpol') {
            return sprintf('%s-SIMPOL-%04d-%d', $prefix, $number, $year);
        }

        $department = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', data_get($application->submitted_snapshot, 'application_data.assignment_department', 'GEN')));

        return sprintf('%s-HSE-%s-%04d-%d', $prefix, $department ?: 'GEN', $number, $year);
    }

    private function simperExpiry(PermitApplication $application, Carbon $minePermitExpiry): Carbon
    {
        $documentExpiries = collect(data_get($application->submitted_snapshot, 'documents', []))
            ->whereIn('type', ['driver_license', 'operator_certificate'])
            ->pluck('expires_at')->filter()->map(fn ($date) => Carbon::parse($date));

        return $documentExpiries->push($minePermitExpiry)->sort()->first()->copy();
    }

    /** @return array<string, mixed> */
    private function printSnapshot(PermitApplication $application, array $reviewer, string $approvalToken, Carbon $approvedAt, string $publicId, string $minePermitNumber, ?string $simpolNumber, string $validFrom, string $expiresAt, ?string $simperExpiresAt): array
    {
        $submitted = $application->submitted_snapshot ?? [];
        $driverLicense = collect($submitted['documents'] ?? [])->firstWhere('type', 'driver_license');

        return [
            'schema_version' => 1,
            'application_number' => $application->application_number,
            'application_type' => $application->type->value,
            'submission_version' => $application->submission_version,
            'mine_permit_number' => $minePermitNumber,
            'simpol_number' => $simpolNumber,
            'public_id' => $publicId,
            'verification_url' => route('permits.verify', $publicId),
            'owner' => [
                'id' => $application->owner_id,
                'name' => $application->owner->legal_name,
                'short_name' => $application->owner->short_name,
                'site_name' => $application->owner->site_name ?? data_get($submitted, 'application_data.site_name'),
                'emergency_phone' => $application->owner->emergency_phone,
                'logo_data_uri' => $this->fileDataUri('public', $application->owner->logo_path, public_path('logo.png')),
            ],
            'manpower' => [
                'name' => data_get($submitted, 'name'),
                'partner_name' => data_get($submitted, 'partner_name'),
                'position' => data_get($submitted, 'application_data.assignment_position', data_get($submitted, 'position')),
                'department' => data_get($submitted, 'application_data.assignment_department', data_get($submitted, 'department')),
                'blood_type' => data_get($submitted, 'blood_type'),
                'photo_data_uri' => $this->fileDataUri('local', data_get($submitted, 'photo_path')),
            ],
            'access_areas' => collect($submitted['access_areas'] ?? [])->pluck('name')->values()->all(),
            'categories' => collect($submitted['categories'] ?? [])->map(fn ($category) => [
                'name' => $category['name'],
                'level' => $category['level'],
                'restrictions' => $category['restrictions'] ?? null,
            ])->values()->all(),
            'driver_license' => [
                'number' => $driverLicense['document_number'] ?? null,
                'class' => data_get($driverLicense, 'metadata.license_class'),
                'expires_at' => $driverLicense['expires_at'] ?? null,
            ],
            'valid_from' => $validFrom,
            'expires_at' => $expiresAt,
            'simper_expires_at' => $simperExpiresAt,
            'violation_indicator' => 'green',
            'ktt_approval' => [
                'reviewer' => $reviewer,
                'approved_at' => $approvedAt->toIso8601String(),
                'approval_claim_token' => $approvalToken,
            ],
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function fileDataUri(string $disk, ?string $path, ?string $fallback = null): ?string
    {
        $contents = null;
        $mime = null;
        if ($path && Storage::disk($disk)->exists($path)) {
            $contents = Storage::disk($disk)->get($path);
            $mime = Storage::disk($disk)->mimeType($path);
        } elseif ($fallback && is_file($fallback)) {
            $contents = file_get_contents($fallback);
            $mime = mime_content_type($fallback);
        }

        return $contents === null ? null : 'data:'.($mime ?: 'application/octet-stream').';base64,'.base64_encode($contents);
    }

    private function markReplaced(int $oldId, PermitIssuance $replacement, Carbon $at): void
    {
        $old = PermitIssuance::query()->lockForUpdate()->findOrFail($oldId);
        if ($old->owner_id !== $replacement->owner_id || $old->manpower_id !== $replacement->manpower_id || $old->status !== 'active') {
            throw new DomainException('Permit yang diganti tidak valid atau sudah tidak aktif.');
        }
        $old->update(['status' => 'replaced', 'revoked_at' => $at, 'revocation_reason' => 'Digantikan oleh '.$replacement->mine_permit_number]);
        $this->transitionApplication(
            $old,
            ApplicationStatus::Issued,
            ApplicationStatus::Revoked,
            'replace',
            null,
            'Digantikan oleh '.$replacement->mine_permit_number,
            $at,
        );
    }

    private function transitionApplication(PermitIssuance $issuance, ApplicationStatus $from, ApplicationStatus $to, string $action, ?User $actor, ?string $notes, Carbon $at): void
    {
        $application = PermitApplication::query()->lockForUpdate()->findOrFail($issuance->permit_application_id);
        if ($application->status !== $from) {
            throw new DomainException('Status pengajuan tidak sesuai dengan status permit.');
        }
        $version = $application->version;
        $application->update(['status' => $to, 'version' => $version + 1]);
        ApplicationStatusHistory::create([
            'permit_application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'actor_type' => $actor ? 'user' : 'system',
            'actor_id' => $actor?->id,
            'notes' => $notes,
            'metadata' => ['issuance_id' => $issuance->id],
            'application_version' => $version + 1,
            'transitioned_at' => $at,
        ]);
    }
}
