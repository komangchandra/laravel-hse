<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Exceptions\StaleApplicationVersionException;
use App\Models\AccessArea;
use App\Models\ExamSession;
use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\PermitApplication;
use App\Models\SimperCategory;
use App\Models\User;
use App\Services\PermitApplicationWorkflow;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PermitApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private PermitApplicationWorkflow $workflow;

    private Partner $owner;

    private Partner $partner;

    private Manpower $manpower;

    private Manpower $ownerManpower;

    private User $safety;

    private User $hse;

    private User $ktt;

    private SimperCategory $category;

    private AccessArea $accessArea;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);
        $this->workflow = app(PermitApplicationWorkflow::class);
        $this->owner = Partner::create([
            'legal_name' => 'PT Owner Alpha', 'short_name' => 'OA', 'permit_prefix' => 'OA',
            'email' => 'owner@example.test', 'status' => 'active', 'level' => 'owner',
            'organization_kind' => Partner::KIND_OWNER,
        ]);
        $this->partner = Partner::create([
            'owner_id' => $this->owner->id, 'partner_type_id' => PartnerType::where('code', 'rental')->value('id'),
            'legal_name' => 'PT Mitra Alpha', 'short_name' => 'MA', 'email' => 'mitra@example.test',
            'status' => 'active', 'level' => 'contractor', 'organization_kind' => Partner::KIND_PARTNER,
        ]);
        $this->safety = $this->user('Safety', $this->partner, 'safety_mitra');
        $this->hse = $this->user('HSE', $this->owner, 'hse_owner');
        $this->ktt = $this->user('KTT', $this->owner, 'ktt');
        $this->manpower = Manpower::create([
            'nik' => 'NIK-001', 'name' => 'Operator Satu', 'contact_number' => '08123', 'blood_type' => 'O',
            'partner_id' => $this->partner->id, 'owner_id' => $this->owner->id, 'position' => 'Operator',
            'department' => 'OPR', 'is_active' => true,
        ]);
        $this->ownerManpower = Manpower::create([
            'nik' => 'NIK-OWNER-001', 'name' => 'Karyawan Owner', 'contact_number' => '08456', 'blood_type' => 'A',
            'partner_id' => $this->owner->id, 'owner_id' => $this->owner->id, 'position' => 'Operator',
            'department' => 'MNO', 'is_active' => true,
        ]);
        $this->category = SimperCategory::create(['owner_id' => $this->owner->id, 'name' => 'Light Vehicle']);
        $this->accessArea = AccessArea::create(['owner_id' => $this->owner->id, 'code' => 'pit', 'name' => 'PIT']);
        $this->createDocuments($this->manpower);
        $this->createDocuments($this->ownerManpower);
    }

    public function test_transition_table_for_mine_permit_only_skips_exam(): void
    {
        $application = $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly);
        $this->assertMatchesRegularExpression('/^OA-MP-\d{4}-000001$/', $application->application_number);

        $application = $this->workflow->submit($application, $this->safety, 'Data lengkap');
        $application = $this->workflow->approveHse($application, $this->hse, 'Lolos verifikasi');
        $this->assertSame(Status::KttReview, $application->status);
        $application = $this->workflow->approveKtt($application, $this->ktt, 'Disetujui');

        $this->assertSame(Status::Issued, $application->status);
        $this->assertNotNull($application->submitted_snapshot);
        $this->assertNotNull($application->issued_snapshot);
        $this->assertSame(4, $application->statusHistories()->count());
    }

    public function test_combined_application_cannot_reach_ktt_before_passing_exam(): void
    {
        $application = $this->combinedDraft();
        $application = $this->workflow->submit($application, $this->safety);
        $application = $this->workflow->approveHse($application, $this->hse);
        $this->assertSame(Status::WaitingExamSetup, $application->status);

        $this->expectException(DomainException::class);
        $this->workflow->submitToKtt($application, $this->hse);
    }

    public function test_combined_application_follows_exam_path_before_ktt(): void
    {
        $application = $this->workflow->approveHse(
            $this->workflow->submit($this->combinedDraft(), $this->safety),
            $this->hse
        );
        $session = ExamSession::create([
            'owner_id' => $this->owner->id, 'name' => 'Ujian Operator', 'duration' => 60,
            'passing_score' => 80, 'is_active' => true,
        ]);
        $application = $this->workflow->scheduleExam($application, $this->hse, ['exam_session_id' => $session->id]);
        $application = $this->workflow->startExam($application, ['attempt_id' => 200]);
        $application = $this->workflow->recordExamResult($application, true, false, ['attempt_id' => 200, 'score' => 90]);
        $application = $this->workflow->submitToKtt($application, $this->hse);

        $this->assertSame(Status::KttReview, $application->status);
    }

    public function test_wrong_role_and_wrong_origin_status_are_rejected(): void
    {
        $application = $this->workflow->submit(
            $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly),
            $this->safety
        );

        try {
            $this->workflow->approveHse($application, $this->ktt);
            $this->fail('KTT tidak boleh melakukan review HSE.');
        } catch (AuthorizationException) {
            $this->assertSame(Status::HseReview, $application->fresh()->status);
        }

        $this->expectException(DomainException::class);
        $this->workflow->approveKtt($application->fresh(), $this->ktt);
    }

    public function test_two_reviewers_using_same_version_only_create_one_transition(): void
    {
        $application = $this->workflow->submit(
            $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly),
            $this->safety
        );
        $reviewerOneCopy = PermitApplication::findOrFail($application->id);
        $reviewerTwoCopy = PermitApplication::findOrFail($application->id);

        $this->workflow->approveHse($reviewerOneCopy, $this->hse);

        try {
            $this->workflow->approveHse($reviewerTwoCopy, $this->hse);
            $this->fail('Versi stale seharusnya ditolak.');
        } catch (StaleApplicationVersionException) {
            $this->assertSame(1, $application->reviews()->where('stage', 'hse')->count());
            $this->assertSame(1, $application->statusHistories()->where('action', 'approve_hse')->count());
        }
    }

    public function test_review_and_document_history_survives_multiple_reject_resubmit_cycles(): void
    {
        $application = $this->workflow->submit(
            $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly),
            $this->safety
        );
        $application = $this->workflow->rejectHse($application, $this->hse, 'Perbaiki surat tugas');
        $application = $this->workflow->submit($application, $this->safety, 'Sudah diperbaiki');
        $application = $this->workflow->approveHse($application, $this->hse);
        $application = $this->workflow->rejectKtt($application, $this->ktt, 'Perbaiki area kerja');
        $application = $this->workflow->submit($application, $this->safety, 'Area kerja diperbaiki');

        $this->assertSame(3, $application->submission_version);
        $this->assertSame(3, $application->reviews()->count());
        $this->assertSame(6, $application->statusHistories()->count());
        $this->assertSame(3 * $this->manpower->documents()->count(), $application->documentSnapshots()->count());
    }

    public function test_hse_rejection_can_be_fixed_resubmitted_and_completed_without_losing_history(): void
    {
        $application = $this->workflow->submit(
            $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly),
            $this->safety,
            'Pengajuan awal',
        );
        $application = $this->workflow->rejectHse($application, $this->hse, 'Perbaiki surat tugas');

        $application = $this->workflow->saveDraft(
            $application,
            $this->safety,
            $this->completeData() + ['applicant_notes' => 'Surat tugas telah diperbaiki'],
            [],
            [$this->accessArea->id],
            $application->version,
        );
        $application = $this->workflow->submit($application, $this->safety, 'Dokumen sudah diperbaiki');
        $application = $this->workflow->approveHse($application, $this->hse, 'Perbaikan sesuai');
        $application = $this->workflow->approveKtt($application, $this->ktt, 'Disetujui KTT');

        $this->assertSame(Status::Issued, $application->status);
        $this->assertNotNull($application->issuance);
        $this->assertSame(2, $application->submission_version);
        $this->assertSame(3, $application->reviews()->count());
        $this->assertSame(2 * $this->manpower->documents()->count(), $application->documentSnapshots()->count());
        $this->assertSame(
            ['submit', 'reject_hse', 'submit', 'approve_hse', 'approve_ktt', 'issue'],
            $application->statusHistories()->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_hse_can_submit_and_self_review_both_application_types_for_owner_manpower(): void
    {
        $minePermit = $this->completeDraft($this->hse, $this->ownerManpower, Type::MinePermitOnly);
        $minePermit = $this->workflow->submit($minePermit, $this->hse, 'Pengajuan karyawan owner');
        $minePermit = $this->workflow->approveHse($minePermit, $this->hse, 'Self-review sesuai aturan bisnis');

        $this->assertSame(Status::KttReview, $minePermit->status);
        $this->assertSame($this->hse->id, $minePermit->created_by);
        $this->assertSame($this->hse->id, $minePermit->reviews()->sole()->reviewer_id);

        $combined = $this->workflow->createDraft($this->hse, $this->ownerManpower, Type::MinePermitSimper);
        $this->assertSame(Status::Draft, $combined->status);
        $this->assertTrue($combined->categories->isEmpty());

        try {
            $this->workflow->submit($combined, $this->hse, 'Draft belum lengkap');
            $this->fail('Submit gabungan tanpa kategori seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('categories', $exception->errors());
        }

        $combined = $this->workflow->saveDraft($combined, $this->hse, $this->completeData(), [
            ['category_id' => $this->category->id, 'level' => 'F'],
        ], [$this->accessArea->id], $combined->version);
        $combined = $this->workflow->submit($combined, $this->hse, 'Kategori telah lengkap');
        $combined = $this->workflow->approveHse($combined, $this->hse, 'Self-review pengajuan gabungan');

        $this->assertSame(Status::WaitingExamSetup, $combined->status);
        $this->assertSame($this->hse->id, $combined->reviews()->sole()->reviewer_id);
    }

    public function test_hse_cannot_apply_for_partner_manpower_or_another_owner_manpower(): void
    {
        try {
            $this->workflow->createDraft($this->hse, $this->manpower, Type::MinePermitOnly);
            $this->fail('HSE tidak boleh membuat pengajuan atas nama mitra bawahannya.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('permit_applications', 0);
        }

        $otherOwner = Partner::create([
            'legal_name' => 'PT Owner Beta', 'short_name' => 'OB', 'permit_prefix' => 'OB',
            'email' => 'owner-beta@example.test', 'status' => 'active', 'level' => 'owner',
            'organization_kind' => Partner::KIND_OWNER,
        ]);
        $otherManpower = Manpower::create([
            'nik' => 'NIK-OWNER-B', 'name' => 'Karyawan Owner Beta', 'contact_number' => '08789',
            'blood_type' => 'B', 'partner_id' => $otherOwner->id, 'owner_id' => $otherOwner->id,
            'position' => 'Operator', 'department' => 'OPR', 'is_active' => true,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->workflow->createDraft($this->hse, $otherManpower, Type::MinePermitOnly);
    }

    public function test_hse_cannot_make_ktt_decision_on_own_application(): void
    {
        $application = $this->workflow->approveHse(
            $this->workflow->submit(
                $this->completeDraft($this->hse, $this->ownerManpower, Type::MinePermitOnly),
                $this->hse,
                'Submit internal'
            ),
            $this->hse,
            'Review internal'
        );

        $this->expectException(AuthorizationException::class);
        $this->workflow->approveKtt($application, $this->hse, 'Tidak berwenang');
    }

    public function test_duplicate_active_application_is_rejected_but_new_draft_is_allowed_after_final_status(): void
    {
        $application = $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly);

        try {
            $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly);
            $this->fail('Pengajuan aktif ganda seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('manpower', $exception->errors());
        }

        $application = $this->workflow->cancel($application, $this->safety, 'Draft dibatalkan');
        $replacement = $this->completeDraft($this->safety, $this->manpower, Type::MinePermitOnly);

        $this->assertSame(Status::Cancelled, $application->status);
        $this->assertSame(Status::Draft, $replacement->status);
        $this->assertNotSame($application->application_number, $replacement->application_number);
    }

    private function combinedDraft(): PermitApplication
    {
        return $this->completeDraft($this->safety, $this->manpower, Type::MinePermitSimper, [
            ['category_id' => $this->category->id, 'level' => 'F'],
        ]);
    }

    private function completeDraft(User $actor, Manpower $manpower, Type $type, array $categories = []): PermitApplication
    {
        return $this->workflow->createDraft(
            $actor, $manpower, $type, $categories, $this->completeData(), [$this->accessArea->id],
        );
    }

    private function completeData(): array
    {
        return [
            'site_name' => 'Site Alpha',
            'assignment_position' => 'Operator',
            'assignment_department' => 'OPR',
            'planned_start_date' => now()->addDay()->toDateString(),
            'requested_valid_until' => now()->addYear()->toDateString(),
            'applicant_notes' => 'Pengajuan lengkap',
            'truth_declaration' => true,
            'processing_consent' => true,
        ];
    }

    private function user(string $name, ?Partner $partner, string $role): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $partner?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function createDocuments(Manpower $manpower): void
    {
        $types = array_merge(Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS, [
            'driver_license', 'operator_certificate', 'training_certificate', 'initial_assessment',
        ]);
        foreach ($types as $index => $type) {
            ManpowerDocument::create([
                'manpower_id' => $manpower->id, 'type' => $type, 'document_number' => 'DOC-'.$manpower->id.'-'.$index,
                'issued_at' => now()->subMonth(), 'expires_at' => now()->addYear(),
                'file_path' => 'private/doc-'.$index.'.pdf', 'original_name' => 'doc.pdf',
                'mime_type' => 'application/pdf', 'file_size' => 100, 'checksum' => hash('sha256', $manpower->id.$type),
                'version' => 1, 'uploaded_by' => $this->safety->id, 'verification_status' => 'verified',
                'verified_by' => $this->hse->id, 'verified_at' => now(),
            ]);
        }
    }
}
