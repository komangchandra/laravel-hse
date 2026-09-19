<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Models\AccessArea;
use App\Models\AuditLog;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\PermitApplication;
use App\Models\SimperCategory;
use App\Models\User;
use App\Services\KttApprovalClaimService;
use App\Services\PermitApplicationWorkflow;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KttApprovalTest extends TestCase
{
    use RefreshDatabase;

    private PermitApplicationWorkflow $workflow;

    private Partner $ownerA;

    private Partner $ownerB;

    private Partner $partnerA;

    private Manpower $manpower;

    private User $safety;

    private User $hse;

    private User $ktt;

    private User $kttOtherOwner;

    private AccessArea $area;

    private SimperCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);
        $this->workflow = app(PermitApplicationWorkflow::class);

        $this->ownerA = $this->organization('Owner Alpha', 'OA', Partner::KIND_OWNER);
        $this->ownerB = $this->organization('Owner Beta', 'OB', Partner::KIND_OWNER);
        $this->partnerA = $this->organization('Mitra Alpha', 'MA', Partner::KIND_PARTNER, $this->ownerA);
        $this->safety = $this->user('Safety Alpha', $this->partnerA, 'safety_mitra');
        $this->hse = $this->user('HSE Alpha', $this->ownerA, 'hse_owner');
        $this->ktt = $this->user('KTT Alpha', $this->ownerA, 'ktt');
        $this->kttOtherOwner = $this->user('KTT Beta', $this->ownerB, 'ktt');
        $this->manpower = Manpower::create([
            'nik' => 'NIK-KTT-001',
            'name' => 'Operator KTT',
            'contact_number' => '08123456789',
            'blood_type' => 'O',
            'partner_id' => $this->partnerA->id,
            'owner_id' => $this->ownerA->id,
            'position' => 'Operator',
            'department' => 'OPR',
            'is_active' => true,
        ]);
        $this->area = AccessArea::create([
            'owner_id' => $this->ownerA->id,
            'code' => 'pit',
            'name' => 'PIT',
        ]);
        $this->category = SimperCategory::create([
            'owner_id' => $this->ownerA->id,
            'name' => 'Light Vehicle',
        ]);
        $this->createDocuments();
    }

    public function test_ktt_queue_is_owner_scoped_and_mine_permit_approval_creates_immutable_encrypted_evidence(): void
    {
        $application = $this->readyForKtt(Type::MinePermitOnly);

        $this->actingAs($this->ktt)->get(route('dashboard.permit-applications.index', ['queue' => 'ktt']))
            ->assertOk()
            ->assertSee('Antrean Persetujuan KTT')
            ->assertSee($application->application_number);
        $this->actingAs($this->kttOtherOwner)->get(route('dashboard.permit-applications.index', ['queue' => 'ktt']))
            ->assertOk()
            ->assertDontSee($application->application_number);

        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-approve', $application), [
            'version' => $application->version,
            'notes' => 'Disetujui untuk diterbitkan',
        ])->assertRedirect(route('dashboard.permit-applications.show', $application));

        $application->refresh();
        $review = $application->reviews()->where('stage', 'ktt')->sole();
        $claim = app(KttApprovalClaimService::class)->verify($review->approval_claim_token);

        $this->assertSame(Status::Issued, $application->status);
        $this->assertNotNull($application->issuance);
        $this->assertNotNull($application->approved_at);
        $this->assertNotNull($application->issuance_requested_at);
        $this->assertSame('approved', $claim['decision']);
        $this->assertSame($application->application_number, $claim['application_number']);
        $this->assertSame($application->submission_version, $claim['submission_version']);
        $this->assertSame('KTT Alpha', $review->reviewer_snapshot['name']);
        $this->assertSame($this->ownerA->legal_name, $review->reviewer_snapshot['organization_name']);
        $decisionAudit = AuditLog::where('action', 'permit_application.approve_ktt')->sole();
        $this->assertSame($this->ktt->id, $decisionAudit->actor_id);
        $this->assertSame($this->ownerA->id, $decisionAudit->actor_organization_id);
        $this->assertSame(Status::KttReview->value, $decisionAudit->from_status);
        $this->assertSame(Status::Approved->value, $decisionAudit->to_status);
        $this->assertNotNull($decisionAudit->ip_address);

        $this->ktt->update(['name' => 'Nama KTT Setelah Approval']);
        $this->assertSame('KTT Alpha', $review->fresh()->reviewer_snapshot['name']);

        $this->expectException(DomainException::class);
        app(KttApprovalClaimService::class)->verify($review->approval_claim_token.'rusak');
    }

    public function test_ktt_reject_requires_reason_and_is_visible_to_applicant(): void
    {
        $application = $this->readyForKtt(Type::MinePermitOnly);

        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-reject', $application), [
            'version' => $application->version,
        ])->assertSessionHasErrors('notes');
        $this->assertSame(Status::KttReview, $application->fresh()->status);

        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-reject', $application), [
            'version' => $application->version,
            'notes' => 'Perbaiki area kerja yang diajukan',
        ])->assertRedirect(route('dashboard.permit-applications.show', $application));

        $this->assertSame(Status::KttRevisionRequired, $application->fresh()->status);
        $this->assertDatabaseHas('application_reviews', [
            'permit_application_id' => $application->id,
            'stage' => 'ktt',
            'decision' => 'rejected',
            'notes' => 'Perbaiki area kerja yang diajukan',
        ]);
        $this->actingAs($this->safety)->get(route('dashboard.permit-applications.show', $application))
            ->assertOk()
            ->assertSee('Perbaiki area kerja yang diajukan');
    }

    public function test_cross_owner_and_non_ktt_users_cannot_decide(): void
    {
        $application = $this->readyForKtt(Type::MinePermitOnly);
        $payload = ['version' => $application->version, 'notes' => 'Tidak berwenang'];

        $this->actingAs($this->kttOtherOwner)
            ->post(route('dashboard.permit-applications.ktt-approve', $application), $payload)
            ->assertForbidden();
        $this->actingAs($this->hse)
            ->post(route('dashboard.permit-applications.ktt-approve', $application), $payload)
            ->assertForbidden();

        $this->assertSame(Status::KttReview, $application->fresh()->status);
        $this->assertDatabaseMissing('application_reviews', [
            'permit_application_id' => $application->id,
            'stage' => 'ktt',
        ]);
    }

    public function test_combined_application_cannot_be_approved_without_a_finalized_passing_attempt(): void
    {
        $application = $this->readyForKttWithoutAttempt();

        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-approve', $application), [
            'version' => $application->version,
            'notes' => 'Coba setujui',
        ])->assertSessionHasErrors('application');

        $this->assertSame(Status::KttReview, $application->fresh()->status);
        $this->assertNull($application->fresh()->approved_at);
        $this->assertDatabaseMissing('application_reviews', [
            'permit_application_id' => $application->id,
            'stage' => 'ktt',
        ]);
    }

    public function test_combined_application_shows_all_attempts_and_can_be_approved_after_passing(): void
    {
        $application = $this->readyForKttWithoutAttempt();
        $session = ExamSession::create([
            'owner_id' => $this->ownerA->id,
            'permit_application_id' => $application->id,
            'name' => 'Ujian Operator',
            'duration' => 60,
            'passing_score' => 80,
            'max_attempts' => 2,
            'status' => 'completed',
            'is_active' => false,
        ]);
        ExamAttempt::create([
            'exam_session_id' => $session->id,
            'permit_application_id' => $application->id,
            'attempt_number' => 1,
            'total_questions' => 10,
            'correct_answers' => 9,
            'wrong_answers' => 1,
            'blank_answers' => 0,
            'score' => 90,
            'raw_score' => 9,
            'max_score_snapshot' => 10,
            'passing_score_snapshot' => 80,
            'is_passed' => true,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'finalized_at' => now(),
            'completion_reason' => 'submitted',
        ]);

        $this->actingAs($this->ktt)->get(route('dashboard.permit-applications.show', $application))
            ->assertOk()
            ->assertSee('Ringkasan seluruh attempt ujian')
            ->assertSee('90.00%')
            ->assertSee('Lulus');
        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-approve', $application), [
            'version' => $application->version,
        ])->assertRedirect(route('dashboard.permit-applications.show', $application));

        $application->refresh();
        $issuance = $application->issuance;
        $this->assertSame(Status::Issued, $application->status);
        $this->assertNotNull($issuance->simpol_number);
        $this->assertSame('Light Vehicle', data_get($issuance->print_snapshot, 'categories.0.name'));
        $this->assertSame('F', data_get($issuance->print_snapshot, 'categories.0.level'));
        $template = view('dashboard.permit-cards.pdf', [
            'issuance' => $issuance,
            'snapshot' => $issuance->print_snapshot,
            'qrSvg' => base64_encode('<svg></svg>'),
        ])->render();
        $this->assertStringContainsString('Light Vehicle', $template);
        $this->assertStringNotContainsString('TIDAK MEMILIKI SIMPER', $template);
    }

    public function test_resubmitting_a_stale_decision_creates_only_one_ktt_review(): void
    {
        $application = $this->readyForKtt(Type::MinePermitOnly);
        $staleVersion = $application->version;

        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-approve', $application), [
            'version' => $staleVersion,
        ])->assertRedirect();
        $this->actingAs($this->ktt)->post(route('dashboard.permit-applications.ktt-reject', $application), [
            'version' => $staleVersion,
            'notes' => 'Keputusan dari tab lama',
        ])->assertSessionHasErrors('application');

        $this->assertSame(1, $application->reviews()->where('stage', 'ktt')->count());
        $this->assertSame(1, $application->statusHistories()->whereIn('action', ['approve_ktt', 'reject_ktt'])->count());
    }

    private function readyForKtt(Type $type): PermitApplication
    {
        $categories = $type->requiresExam()
            ? [['category_id' => $this->category->id, 'level' => 'F']]
            : [];
        $application = $this->workflow->createDraft(
            $this->safety,
            $this->manpower,
            $type,
            $categories,
            $this->completeData(),
            [$this->area->id],
        );
        $application = $this->workflow->submit($application, $this->safety);

        return $this->workflow->approveHse($application, $this->hse);
    }

    private function readyForKttWithoutAttempt(): PermitApplication
    {
        $application = $this->readyForKtt(Type::MinePermitSimper);
        $application->update(['status' => Status::ExamPassed->value]);

        return $this->workflow->submitToKtt($application->fresh(), $this->hse);
    }

    /** @return array<string, mixed> */
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

    private function organization(string $name, string $shortName, string $kind, ?Partner $owner = null): Partner
    {
        return Partner::create([
            'owner_id' => $owner?->id,
            'partner_type_id' => $kind === Partner::KIND_PARTNER ? PartnerType::where('code', 'rental')->value('id') : null,
            'legal_name' => 'PT '.$name,
            'short_name' => $shortName,
            'permit_prefix' => $shortName,
            'email' => strtolower($shortName).'@example.test',
            'status' => 'active',
            'level' => $kind === Partner::KIND_OWNER ? 'owner' : 'contractor',
            'organization_kind' => $kind,
        ]);
    }

    private function user(string $name, Partner $partner, string $role): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $partner->id]);
        $user->assignRole($role);

        return $user;
    }

    private function createDocuments(): void
    {
        $types = array_merge(Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS, [
            'driver_license', 'operator_certificate', 'training_certificate', 'initial_assessment',
        ]);
        foreach ($types as $index => $type) {
            ManpowerDocument::create([
                'manpower_id' => $this->manpower->id,
                'type' => $type,
                'document_number' => 'DOC-'.$index,
                'issued_at' => now()->subMonth(),
                'expires_at' => now()->addYear(),
                'file_path' => 'private/doc-'.$index.'.pdf',
                'original_name' => 'doc.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'checksum' => hash('sha256', $type),
                'version' => 1,
                'uploaded_by' => $this->safety->id,
                'verification_status' => 'verified',
                'verified_by' => $this->hse->id,
                'verified_at' => now(),
            ]);
        }
    }
}
