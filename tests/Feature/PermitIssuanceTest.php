<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Models\AccessArea;
use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\User;
use App\Services\PermitApplicationWorkflow;
use App\Services\PermitIssuanceService;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermitIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private PermitApplicationWorkflow $workflow;

    private Partner $owner;

    private Partner $partner;

    private User $safety;

    private User $hse;

    private User $ktt;

    private AccessArea $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);
        $this->workflow = app(PermitApplicationWorkflow::class);
        $this->owner = $this->organization('Owner Alpha', 'OA', Partner::KIND_OWNER);
        $this->owner->update(['site_name' => 'Site Alpha', 'emergency_phone' => '081577777777']);
        $this->partner = $this->organization('Mitra Alpha', 'MA', Partner::KIND_PARTNER, $this->owner);
        $this->safety = $this->user('Safety Alpha', $this->partner, 'safety_mitra');
        $this->hse = $this->user('HSE Alpha', $this->owner, 'hse_owner');
        $this->ktt = $this->user('KTT Alpha', $this->owner, 'ktt');
        $this->area = AccessArea::create(['owner_id' => $this->owner->id, 'code' => 'pit', 'name' => 'PIT']);
    }

    public function test_ktt_approval_issues_exactly_one_auditable_mine_permit_with_atomic_number(): void
    {
        $first = $this->approve($this->manpower('NIK-001', 'Operator Satu'));
        $second = $this->approve($this->manpower('NIK-002', 'Operator Dua'));
        $firstIssuance = $first->issuance;
        $secondIssuance = $second->issuance;

        $this->assertSame(Status::Issued, $first->status);
        $this->assertSame('OA-HSE-OPR-0001-'.now()->year, $firstIssuance->mine_permit_number);
        $this->assertSame('OA-HSE-OPR-0002-'.now()->year, $secondIssuance->mine_permit_number);
        $this->assertSame($first->submitted_at->toDateString(), $firstIssuance->valid_from->toDateString());
        $this->assertSame($first->submitted_at->copy()->addYear()->toDateString(), $firstIssuance->expires_at->toDateString());
        $this->assertNotSame($firstIssuance->public_id, $secondIssuance->public_id);
        $this->assertSame('Operator Satu', data_get($firstIssuance->print_snapshot, 'manpower.name'));
        $this->assertSame(['PIT'], data_get($firstIssuance->print_snapshot, 'access_areas'));
        $this->assertSame('green', $firstIssuance->violation_indicator);
        $this->assertDatabaseCount('permit_issuances', 2);

        $retried = app(PermitIssuanceService::class)->issue($first);
        $this->assertSame($firstIssuance->id, $retried->id);
        $this->assertDatabaseCount('permit_issuances', 2);
        $this->assertDatabaseHas('permit_number_sequences', [
            'owner_id' => $this->owner->id,
            'year' => now()->year,
            'kind' => 'mine_permit',
            'last_number' => 2,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permit_issuance.created',
            'subject_id' => $firstIssuance->id,
            'organization_id' => $this->owner->id,
        ]);
    }

    public function test_public_barcode_verification_uses_opaque_id_and_exposes_only_safe_fields(): void
    {
        $application = $this->approve($this->manpower('NIK-003', 'Operator Validasi'));
        $issuance = $application->issuance;
        $url = data_get($issuance->print_snapshot, 'verification_url');

        $this->assertStringContainsString($issuance->public_id, $url);
        $this->assertNotSame('/permit/verify/'.$issuance->id, parse_url($url, PHP_URL_PATH));
        $this->get($url)->assertOk()
            ->assertSee('Valid — pengajuan ini disetujui KTT')
            ->assertSee('Operator Validasi')
            ->assertSee($issuance->mine_permit_number)
            ->assertDontSee('NIK-003')
            ->assertDontSee('08123456789');
        $this->get(route('permits.verify', '00000000-0000-4000-8000-000000000000'))->assertNotFound();
    }

    public function test_pdf_is_two_sided_100_by_150_mm_contains_branding_barcode_and_every_print_is_logged(): void
    {
        $application = $this->approve($this->manpower('NIK-004', 'Operator Cetak'));
        $issuance = $application->issuance;

        $template = view('dashboard.permit-cards.pdf', [
            'issuance' => $issuance,
            'snapshot' => $issuance->print_snapshot,
            'qrSvg' => base64_encode('<svg></svg>'),
        ])->render();
        $this->assertStringContainsString('@page { size: 100mm 150mm;', $template);
        $this->assertStringContainsString('SCAN BARCODE', $template);
        $this->assertStringContainsString('UNTUK VALIDASI', $template);
        $this->assertStringContainsString('TIDAK MEMILIKI SIMPER', $template);

        $pdfResponse = $this->actingAs($this->safety)->get(route('dashboard.permit-cards.pdf', $issuance));
        $pdfResponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());
        $this->assertMatchesRegularExpression(
            '/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+283\.46\d*\s+425\.19\d*\s*\]/',
            $pdfResponse->getContent(),
        );
        $this->actingAs($this->safety)->get(route('dashboard.permit-cards.pdf', $issuance))
            ->assertOk();

        $this->assertDatabaseHas('permit_print_logs', ['permit_issuance_id' => $issuance->id, 'action' => 'initial_print']);
        $this->assertDatabaseHas('permit_print_logs', ['permit_issuance_id' => $issuance->id, 'action' => 'reprint']);
        $this->assertSame(2, $issuance->printLogs()->count());
        $this->assertDatabaseHas('audit_logs', ['subject_id' => $issuance->id, 'action' => 'permit_card.initial_print']);
        $this->assertDatabaseHas('audit_logs', ['subject_id' => $issuance->id, 'action' => 'permit_card.reprint']);
    }

    public function test_cross_owner_cannot_download_and_revoked_card_is_preserved_but_no_longer_printable(): void
    {
        $application = $this->approve($this->manpower('NIK-005', 'Operator Cabut'));
        $issuance = $application->issuance;
        $otherOwner = $this->organization('Owner Beta', 'OB', Partner::KIND_OWNER);
        $otherKtt = $this->user('KTT Beta', $otherOwner, 'ktt');

        $this->actingAs($otherKtt)->get(route('dashboard.permit-cards.pdf', $issuance))->assertForbidden();
        $this->actingAs($this->ktt)->post(route('dashboard.permit-cards.revoke', $issuance), [
            'reason' => 'Pelanggaran keselamatan berat',
        ])->assertRedirect(route('dashboard.permit-cards.show', $issuance));

        $issuance->refresh();
        $this->assertSame('revoked', $issuance->status);
        $this->assertSame('red', $issuance->violation_indicator);
        $this->assertSame(Status::Revoked, $application->fresh()->status);
        $this->assertDatabaseCount('permit_issuances', 1);
        $this->actingAs($this->safety)->get(route('dashboard.permit-cards.pdf', $issuance))->assertForbidden();
        $this->get(route('permits.verify', $issuance->public_id))->assertOk()->assertSee('REVOKED')->assertSee('telah dicabut');
    }

    public function test_expiry_updates_online_status_without_deleting_history(): void
    {
        $application = $this->approve($this->manpower('NIK-006', 'Operator Expired'));
        $application->issuance->update(['expires_at' => today()->subDay()]);

        $this->assertSame(1, app(PermitIssuanceService::class)->expireDue());
        $this->assertSame('expired', $application->issuance->fresh()->status);
        $this->assertSame(Status::Expired, $application->fresh()->status);
        $this->assertDatabaseCount('permit_issuances', 1);
    }

    public function test_issued_card_snapshot_does_not_change_when_source_master_data_changes(): void
    {
        $manpower = $this->manpower('NIK-007', 'Operator Snapshot');
        $application = $this->approve($manpower);
        $issuance = $application->issuance;
        $originalSnapshot = $issuance->print_snapshot;
        $originalIssuedSnapshot = $application->issued_snapshot;

        $manpower->update([
            'name' => 'Nama Setelah Terbit',
            'position' => 'Supervisor',
            'blood_type' => 'AB',
        ]);
        $this->owner->update([
            'legal_name' => 'PT Owner Setelah Terbit',
            'site_name' => 'Site Baru',
            'emergency_phone' => '080000000000',
        ]);
        $this->area->update(['name' => 'AREA BARU']);
        $this->ktt->update(['name' => 'KTT Setelah Terbit']);

        $this->assertSame($originalSnapshot, $issuance->fresh()->print_snapshot);
        $this->assertSame($originalIssuedSnapshot, $application->fresh()->issued_snapshot);
        $this->assertSame('Operator Snapshot', data_get($issuance->fresh()->print_snapshot, 'manpower.name'));
        $this->assertSame('PT Owner Alpha', data_get($issuance->fresh()->print_snapshot, 'owner.name'));
        $this->assertSame(['PIT'], data_get($issuance->fresh()->print_snapshot, 'access_areas'));
        $this->assertSame('KTT Alpha', data_get($issuance->fresh()->print_snapshot, 'ktt_approval.reviewer.name'));
    }

    private function approve(Manpower $manpower)
    {
        $this->createDocuments($manpower);
        $application = $this->workflow->createDraft(
            $this->safety,
            $manpower,
            Type::MinePermitOnly,
            [],
            [
                'site_name' => 'Site Alpha',
                'assignment_position' => 'Operator',
                'assignment_department' => 'OPR',
                'planned_start_date' => now()->addDay()->toDateString(),
                'requested_valid_until' => now()->addYear()->toDateString(),
                'truth_declaration' => true,
                'processing_consent' => true,
            ],
            [$this->area->id],
        );
        $application = $this->workflow->submit($application, $this->safety);
        $application = $this->workflow->approveHse($application, $this->hse);

        return $this->workflow->approveKtt($application, $this->ktt);
    }

    private function manpower(string $nik, string $name): Manpower
    {
        return Manpower::create([
            'nik' => $nik,
            'name' => $name,
            'contact_number' => '08123456789',
            'blood_type' => 'O',
            'partner_id' => $this->partner->id,
            'owner_id' => $this->owner->id,
            'position' => 'Operator',
            'department' => 'OPR',
            'is_active' => true,
        ]);
    }

    private function createDocuments(Manpower $manpower): void
    {
        foreach (Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS as $index => $type) {
            ManpowerDocument::create([
                'manpower_id' => $manpower->id,
                'type' => $type,
                'document_number' => 'DOC-'.$manpower->id.'-'.$index,
                'issued_at' => now()->subMonth(),
                'expires_at' => now()->addYear(),
                'file_path' => 'private/doc-'.$index.'.pdf',
                'original_name' => 'doc.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'checksum' => hash('sha256', $manpower->id.$type),
                'version' => 1,
                'uploaded_by' => $this->safety->id,
                'verification_status' => 'verified',
                'verified_by' => $this->hse->id,
                'verified_at' => now(),
            ]);
        }
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
}
