<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Models\AccessArea;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermitApplicationFormTest extends TestCase
{
    use RefreshDatabase;

    private Partner $ownerA;

    private Partner $ownerB;

    private Partner $partnerA;

    private Partner $partnerB;

    private User $safetyA;

    private User $safetyB;

    private User $hseA;

    private User $kttA;

    private Manpower $partnerManpower;

    private Manpower $otherManpower;

    private Manpower $ownerManpower;

    private AccessArea $areaA;

    private AccessArea $areaB;

    private SimperCategory $categoryA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);
        $this->ownerA = $this->organization('Owner Alpha', 'OA', Partner::KIND_OWNER);
        $this->ownerB = $this->organization('Owner Beta', 'OB', Partner::KIND_OWNER);
        $this->partnerA = $this->organization('Mitra Alpha', 'MA', Partner::KIND_PARTNER, $this->ownerA);
        $this->partnerB = $this->organization('Mitra Beta', 'MB', Partner::KIND_PARTNER, $this->ownerB);
        $this->safetyA = $this->user('Safety Alpha', $this->partnerA, 'safety_mitra');
        $this->safetyB = $this->user('Safety Beta', $this->partnerB, 'safety_mitra');
        $this->hseA = $this->user('HSE Alpha', $this->ownerA, 'hse_owner');
        $this->kttA = $this->user('KTT Alpha', $this->ownerA, 'ktt');
        $this->partnerManpower = $this->manpower($this->partnerA, $this->ownerA, 'Partner Worker', 'NIK-PA');
        $this->otherManpower = $this->manpower($this->partnerB, $this->ownerB, 'Other Worker', 'NIK-PB');
        $this->ownerManpower = $this->manpower($this->ownerA, $this->ownerA, 'Owner Worker', 'NIK-OA');
        $this->areaA = AccessArea::create(['owner_id' => $this->ownerA->id, 'code' => 'pit', 'name' => 'PIT']);
        $this->areaB = AccessArea::create(['owner_id' => $this->ownerB->id, 'code' => 'port', 'name' => 'Port']);
        $this->categoryA = SimperCategory::create(['owner_id' => $this->ownerA->id, 'name' => 'Light Vehicle']);
    }

    public function test_safety_and_hse_can_save_partial_drafts_only_for_their_own_manpower(): void
    {
        $this->actingAs($this->safetyA)->post(route('dashboard.permit-applications.store'), [
            'action' => 'draft', 'manpower_id' => $this->partnerManpower->id, 'type' => Type::MinePermitOnly->value,
        ])->assertRedirect();
        $this->assertDatabaseHas('permit_applications', [
            'manpower_id' => $this->partnerManpower->id, 'partner_id' => $this->partnerA->id, 'status' => Status::Draft->value,
        ]);

        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.store'), [
            'action' => 'draft', 'manpower_id' => $this->ownerManpower->id, 'type' => Type::MinePermitSimper->value,
        ])->assertRedirect();
        $this->assertDatabaseHas('permit_applications', [
            'manpower_id' => $this->ownerManpower->id, 'partner_id' => $this->ownerA->id, 'status' => Status::Draft->value,
        ]);

        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.store'), [
            'action' => 'draft', 'manpower_id' => $this->partnerManpower->id, 'type' => Type::MinePermitOnly->value,
        ])->assertForbidden();
        $this->actingAs($this->safetyA)->post(route('dashboard.permit-applications.store'), [
            'action' => 'draft', 'manpower_id' => $this->otherManpower->id, 'type' => Type::MinePermitOnly->value,
        ])->assertForbidden();
    }

    public function test_partial_submit_rolls_back_and_returns_specific_errors(): void
    {
        $this->actingAs($this->safetyA)->post(route('dashboard.permit-applications.store'), [
            'action' => 'submit', 'manpower_id' => $this->partnerManpower->id, 'type' => Type::MinePermitSimper->value,
        ])->assertSessionHasErrors([
            'site_name', 'planned_start_date', 'truth_declaration', 'processing_consent',
            'access_area_ids', 'categories', 'documents',
        ]);
        $this->assertDatabaseCount('permit_applications', 0);
    }

    public function test_assignment_comes_from_manpower_and_validity_is_one_year_from_submission(): void
    {
        $this->createDocuments($this->partnerManpower);
        $submittedAt = now()->setDate(2026, 9, 19)->setTime(10, 30);
        $this->travelTo($submittedAt);

        $this->actingAs($this->safetyA)->get(route('dashboard.permit-applications.create'))
            ->assertOk()
            ->assertDontSee('Kebutuhan berlaku sampai')
            ->assertSee('Masa berlaku otomatis 1 tahun sejak pengajuan dikirim.');

        $payload = $this->completePayload($this->partnerManpower, Type::MinePermitOnly, $this->areaA);
        $payload['assignment_position'] = 'Jabatan hasil manipulasi';
        $payload['assignment_department'] = 'Departemen hasil manipulasi';
        $payload['requested_valid_until'] = '2099-12-31';

        $this->actingAs($this->safetyA)->post(route('dashboard.permit-applications.store'), $payload)
            ->assertRedirect();

        $application = PermitApplication::sole();
        $this->assertSame($this->partnerManpower->position, $application->assignment_position);
        $this->assertSame($this->partnerManpower->department, $application->assignment_department);
        $this->assertSame($submittedAt->toDateString(), $application->submitted_at->toDateString());
        $this->assertSame($submittedAt->copy()->addYear()->toDateString(), $application->requested_valid_until->toDateString());
        $this->assertSame($this->partnerManpower->position, data_get($application->submitted_snapshot, 'application_data.assignment_position'));
        $this->assertSame($this->partnerManpower->department, data_get($application->submitted_snapshot, 'application_data.assignment_department'));
    }

    public function test_safety_can_submit_combined_application_and_form_rejects_foreign_area(): void
    {
        $this->createDocuments($this->partnerManpower);
        $foreignAreaPayload = $this->completePayload($this->partnerManpower, Type::MinePermitSimper, $this->areaB);
        $this->actingAs($this->safetyA)->post(route('dashboard.permit-applications.store'), $foreignAreaPayload)
            ->assertSessionHasErrors('access_area_ids');
        $this->assertDatabaseCount('permit_applications', 0);

        $this->actingAs($this->safetyA)->post(route('dashboard.permit-applications.store'), $this->completePayload($this->partnerManpower, Type::MinePermitSimper, $this->areaA))
            ->assertRedirect();
        $application = PermitApplication::sole();
        $this->assertSame(Status::HseReview, $application->status);
        $this->assertSame('F', $application->categories()->sole()->pivot->level);
        $this->assertSame($this->areaA->id, $application->accessAreas()->sole()->id);
    }

    public function test_pending_documents_can_be_submitted_and_are_validated_during_hse_review(): void
    {
        $this->createDocuments($this->partnerManpower);
        $this->partnerManpower->documents()->update([
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->actingAs($this->safetyA)
            ->post(
                route('dashboard.permit-applications.store'),
                $this->completePayload($this->partnerManpower, Type::MinePermitSimper, $this->areaA),
            )
            ->assertRedirect();

        $application = PermitApplication::sole();
        $this->assertSame(Status::HseReview, $application->status);
        $this->assertSame(
            ['pending'],
            $application->documentSnapshots()->get()->pluck('metadata.verification_status')->unique()->values()->all(),
        );

        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.hse-approve', $application), [
            'version' => $application->version,
            'notes' => 'Data dan seluruh berkas valid.',
        ])->assertRedirect();

        $this->assertSame(Status::WaitingExamSetup, $application->fresh()->status);
        $this->assertSame(
            ['verified'],
            $this->partnerManpower->documents()->get()->pluck('verification_status')->unique()->values()->all(),
        );
        $this->assertSame(
            [$this->hseA->id],
            $this->partnerManpower->documents()->get()->pluck('verified_by')->unique()->values()->all(),
        );
    }

    public function test_hse_can_submit_and_self_review_internal_application_but_cannot_edit_after_submit(): void
    {
        $this->createDocuments($this->ownerManpower);
        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.store'), $this->completePayload($this->ownerManpower, Type::MinePermitOnly, $this->areaA))
            ->assertRedirect();
        $application = PermitApplication::sole();

        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.edit', $application))->assertForbidden();
        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.hse-approve', $application), [
            'version' => $application->version, 'notes' => 'Valid',
        ])
            ->assertRedirect(route('dashboard.permit-applications.show', $application));

        $this->assertSame(Status::KttReview, $application->fresh()->status);
        $this->assertDatabaseHas('application_reviews', ['permit_application_id' => $application->id, 'reviewer_id' => $this->hseA->id]);
        $this->assertFalse($this->hseA->can('review-ktt', $application));
    }

    public function test_rejected_application_can_be_edited_but_stale_form_cannot_overwrite_newer_version(): void
    {
        $this->createDocuments($this->partnerManpower);
        $application = app(PermitApplicationWorkflow::class)->createDraft(
            $this->safetyA, $this->partnerManpower, Type::MinePermitOnly, [], $this->draftData(), [$this->areaA->id],
        );
        $application = app(PermitApplicationWorkflow::class)->submit($application, $this->safetyA);
        $application = app(PermitApplicationWorkflow::class)->rejectHse($application, $this->hseA, 'Perbaiki data');

        $this->actingAs($this->safetyA)->get(route('dashboard.permit-applications.edit', $application))->assertOk()->assertSee('Perbaiki Pengajuan');
        $payload = $this->completePayload($this->partnerManpower, Type::MinePermitOnly, $this->areaA, 'draft') + ['version' => $application->version];
        $this->actingAs($this->safetyA)->put(route('dashboard.permit-applications.update', $application), $payload)->assertRedirect();
        $this->actingAs($this->safetyA)->put(route('dashboard.permit-applications.update', $application), $payload)
            ->assertSessionHasErrors('application');
    }

    public function test_lists_details_and_review_queue_are_tenant_isolated(): void
    {
        $appA = app(PermitApplicationWorkflow::class)->createDraft($this->safetyA, $this->partnerManpower, Type::MinePermitOnly);
        $appB = app(PermitApplicationWorkflow::class)->createDraft($this->safetyB, $this->otherManpower, Type::MinePermitOnly);

        $this->actingAs($this->safetyA)->get(route('dashboard.permit-applications.index'))
            ->assertOk()->assertSee($appA->application_number)->assertDontSee($appB->application_number);
        $this->actingAs($this->safetyA)->get(route('dashboard.permit-applications.show', $appB))->assertForbidden();
        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.index', ['queue' => 'review']))
            ->assertOk()->assertDontSee($appB->application_number);
    }

    public function test_hse_review_queue_can_filter_by_organization_type_and_waiting_age(): void
    {
        $this->createDocuments($this->partnerManpower);
        $oldApplication = app(PermitApplicationWorkflow::class)->submit(
            app(PermitApplicationWorkflow::class)->createDraft(
                $this->safetyA, $this->partnerManpower, Type::MinePermitOnly, [], $this->draftData(), [$this->areaA->id]
            ),
            $this->safetyA
        );
        $oldApplication->update(['submitted_at' => now()->subDays(4)]);

        $this->createDocuments($this->ownerManpower);
        $recentApplication = app(PermitApplicationWorkflow::class)->submit(
            app(PermitApplicationWorkflow::class)->createDraft(
                $this->hseA,
                $this->ownerManpower,
                Type::MinePermitSimper,
                [['category_id' => $this->categoryA->id, 'level' => 'F']],
                $this->draftData(),
                [$this->areaA->id]
            ),
            $this->hseA
        );

        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.index', [
            'queue' => 'review',
            'organization_id' => $this->partnerA->id,
            'type' => Type::MinePermitOnly->value,
            'age_days' => 3,
        ]))->assertOk()
            ->assertSee('Antrean Review HSE')
            ->assertSee($oldApplication->application_number)
            ->assertDontSee($recentApplication->application_number)
            ->assertSee('4 hari');
    }

    public function test_hse_reviews_the_frozen_snapshot_and_snapshot_file_is_tenant_protected(): void
    {
        Storage::fake('local');
        $this->createDocuments($this->partnerManpower);
        $application = app(PermitApplicationWorkflow::class)->submit(
            app(PermitApplicationWorkflow::class)->createDraft(
                $this->safetyA, $this->partnerManpower, Type::MinePermitOnly, [], $this->draftData(), [$this->areaA->id]
            ),
            $this->safetyA
        );
        $document = $application->documentSnapshots()->firstOrFail();
        Storage::disk('local')->put($document->file_path, 'snapshot-content');

        $this->partnerManpower->update(['name' => 'Nama Profil Setelah Submit']);
        $application->update(['site_name' => 'Site Diubah Setelah Submit']);

        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.show', $application))
            ->assertOk()
            ->assertSee('Mode pemeriksaan snapshot')
            ->assertSee('Partner Worker')
            ->assertSee('Site Alpha')
            ->assertDontSee('Nama Profil Setelah Submit')
            ->assertDontSee('Site Diubah Setelah Submit')
            ->assertSee(route('dashboard.permit-applications.documents.show', [$application, $document]), false);

        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.documents.show', [$application, $document]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $hseB = $this->user('HSE Beta', $this->ownerB, 'hse_owner');
        $this->actingAs($hseB)->get(route('dashboard.permit-applications.documents.show', [$application, $document]))
            ->assertForbidden();
    }

    public function test_reject_requires_reason_and_stale_review_page_cannot_create_second_decision(): void
    {
        $this->createDocuments($this->partnerManpower);
        $application = app(PermitApplicationWorkflow::class)->submit(
            app(PermitApplicationWorkflow::class)->createDraft(
                $this->safetyA, $this->partnerManpower, Type::MinePermitOnly, [], $this->draftData(), [$this->areaA->id]
            ),
            $this->safetyA
        );
        $pageVersion = $application->version;

        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.hse-reject', $application), [
            'version' => $pageVersion,
        ])->assertSessionHasErrors('notes');
        $this->assertDatabaseCount('application_reviews', 0);

        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.hse-approve', $application), [
            'version' => $pageVersion, 'notes' => 'Dokumen valid',
        ])->assertRedirect(route('dashboard.permit-applications.show', $application));

        $this->actingAs($this->hseA)->post(route('dashboard.permit-applications.hse-approve', $application), [
            'version' => $pageVersion, 'notes' => 'Klik dari tab lama',
        ])->assertSessionHasErrors('application');

        $this->assertSame(1, $application->reviews()->where('stage', 'hse')->count());
        $this->assertSame(1, $application->statusHistories()->where('action', 'approve_hse')->count());
        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.show', $application->fresh()))
            ->assertOk()->assertDontSee('Setujui HSE');
    }

    public function test_hse_can_manage_owner_manpower_but_not_partner_manpower(): void
    {
        Storage::fake('local');
        $payload = [
            'partner_id' => $this->partnerA->id,
            'nik' => 'NIK-OWNER-NEW', 'name' => 'Internal Baru', 'position' => 'Operator', 'department' => 'OPR',
            'birth_place' => 'Jakarta', 'birth_date' => '1990-01-01', 'contact_number' => '0812',
            'emergency_contact_name' => 'Kontak', 'emergency_contact_number' => '0813', 'blood_type' => 'O+',
            'is_active' => 1, 'photo_path' => UploadedFile::fake()->image('photo.jpg', 300, 400),
        ];
        $this->actingAs($this->hseA)->post(route('dashboard.manpowers.store'), $payload)->assertRedirect();
        $internal = Manpower::where('nik', 'NIK-OWNER-NEW')->firstOrFail();
        $this->assertSame($this->ownerA->id, $internal->partner_id);
        $this->assertSame($this->ownerA->id, $internal->owner_id);

        $documentPayload = [
            'type' => 'identity', 'document_number' => 'KTP-OWNER', 'issued_at' => now()->subYear()->toDateString(),
            'file' => UploadedFile::fake()->create('identity.pdf', 100, 'application/pdf'),
        ];
        $this->actingAs($this->hseA)->post(route('dashboard.manpowers.documents.store', $internal), $documentPayload)
            ->assertRedirect();
        $this->assertDatabaseHas('manpower_documents', [
            'manpower_id' => $internal->id,
            'type' => 'identity',
            'uploaded_by' => $this->hseA->id,
        ]);

        $this->actingAs($this->hseA)->get(route('dashboard.manpowers.edit', $this->partnerManpower))->assertForbidden();
        $this->actingAs($this->hseA)->post(route('dashboard.manpowers.documents.store', $this->partnerManpower), $documentPayload)
            ->assertForbidden();
    }

    private function completePayload(Manpower $manpower, Type $type, AccessArea $area, string $action = 'submit'): array
    {
        $payload = ['action' => $action, 'manpower_id' => $manpower->id, 'type' => $type->value]
            + $this->draftData() + ['access_area_ids' => [$area->id]];
        if ($type->requiresExam()) {
            $payload['categories'] = [$this->categoryA->id => ['selected' => 1, 'level' => 'F']];
        }

        return $payload;
    }

    private function draftData(): array
    {
        return [
            'site_name' => 'Site Alpha', 'planned_start_date' => now()->addDay()->toDateString(),
            'applicant_notes' => 'Data lengkap', 'truth_declaration' => 1, 'processing_consent' => 1,
        ];
    }

    private function createDocuments(Manpower $manpower): void
    {
        foreach (array_merge(Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS, ['driver_license', 'operator_certificate', 'training_certificate', 'initial_assessment']) as $index => $type) {
            ManpowerDocument::create([
                'manpower_id' => $manpower->id, 'type' => $type, 'document_number' => 'DOC-'.$index,
                'issued_at' => now()->subMonth(), 'expires_at' => now()->addYear(), 'file_path' => 'private/'.$index.'.pdf',
                'original_name' => 'doc.pdf', 'mime_type' => 'application/pdf', 'file_size' => 100,
                'checksum' => hash('sha256', $manpower->id.$type), 'version' => 1, 'uploaded_by' => $this->safetyA->id,
                'verification_status' => 'verified', 'verified_by' => $this->hseA->id, 'verified_at' => now(),
            ]);
        }
    }

    private function organization(string $name, string $shortName, string $kind, ?Partner $owner = null): Partner
    {
        return Partner::create([
            'owner_id' => $owner?->id, 'partner_type_id' => $kind === Partner::KIND_PARTNER ? PartnerType::where('code', 'rental')->value('id') : null,
            'organization_kind' => $kind, 'legal_name' => $name, 'short_name' => $shortName,
            'permit_prefix' => $shortName, 'email' => strtolower($shortName).'@example.test', 'status' => 'active',
            'level' => $kind === Partner::KIND_OWNER ? 'owner' : 'contractor',
        ]);
    }

    private function manpower(Partner $organization, Partner $owner, string $name, string $nik): Manpower
    {
        return Manpower::create([
            'partner_id' => $organization->id, 'owner_id' => $owner->id, 'nik' => $nik, 'name' => $name,
            'position' => 'Operator', 'department' => 'OPR', 'contact_number' => '0812', 'blood_type' => 'O+', 'is_active' => true,
        ]);
    }

    private function user(string $name, Partner $partner, string $role): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $partner->id]);
        $user->assignRole($role);

        return $user;
    }
}
