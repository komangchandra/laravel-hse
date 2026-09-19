<?php

namespace Tests\Feature;

use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\Partner;
use App\Models\Simper;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManpowerDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private Partner $ownerA;

    private Partner $ownerB;

    private Partner $partnerA;

    private Partner $partnerB;

    private User $safetyA;

    private User $safetyB;

    private User $hseA;

    private User $hseB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $this->ownerA = $this->owner('Owner A', 'OWA');
        $this->ownerB = $this->owner('Owner B', 'OWB');
        $this->partnerA = $this->partner($this->ownerA, 'Mitra A', 'MTA');
        $this->partnerB = $this->partner($this->ownerB, 'Mitra B', 'MTB');
        $this->safetyA = $this->user($this->partnerA, 'safety_mitra');
        $this->safetyB = $this->user($this->partnerB, 'safety_mitra');
        $this->hseA = $this->user($this->ownerA, 'hse_owner');
        $this->hseB = $this->user($this->ownerB, 'hse_owner');
    }

    public function test_safety_creates_worker_only_for_its_own_partner_even_when_request_is_forged(): void
    {
        $response = $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.store'), $this->profilePayload([
            'partner_id' => $this->partnerB->id,
        ]));

        $manpower = Manpower::where('nik', 'NIK-001')->firstOrFail();
        $response->assertRedirect(route('dashboard.manpowers.edit', $manpower));
        $this->assertSame($this->partnerA->id, $manpower->partner_id);
        $this->assertSame($this->ownerA->id, $manpower->owner_id);
        Storage::disk('local')->assertExists($manpower->photo_path);
    }

    public function test_profile_rejects_invalid_photo_dimensions_and_duplicate_nik_within_owner(): void
    {
        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.store'), $this->profilePayload([
            'photo_path' => UploadedFile::fake()->image('persegi.jpg', 300, 300),
        ]))->assertSessionHasErrors('photo_path');

        $this->manpower($this->partnerA, 'NIK-SAMA');
        $otherPartner = $this->partner($this->ownerA, 'Mitra A2', 'MTA2');
        $otherSafety = $this->user($otherPartner, 'safety_mitra');
        $this->actingAs($otherSafety)->post(route('dashboard.manpowers.store'), $this->profilePayload([
            'nik' => 'NIK-SAMA',
        ]))->assertSessionHasErrors('nik');

        $this->actingAs($this->safetyB)->post(route('dashboard.manpowers.store'), $this->profilePayload([
            'nik' => 'NIK-SAMA',
        ]))->assertSessionHasNoErrors();
    }

    public function test_documents_are_private_versioned_and_tenant_isolated(): void
    {
        $manpower = $this->manpower($this->partnerA);
        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.documents.store', $manpower), $this->documentPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.documents.store', $manpower), $this->documentPayload([
            'document_number' => 'KTP-002',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $documents = $manpower->documents()->orderBy('version')->get();
        $this->assertSame([1, 2], $documents->pluck('version')->all());
        $this->assertNotSame($documents[0]->file_path, $documents[1]->file_path);
        Storage::disk('local')->assertExists($documents[0]->file_path);

        $this->actingAs($this->safetyA)->get(route('dashboard.manpowers.documents.download', [$manpower, $documents[0]]))->assertOk();
        $this->actingAs($this->hseA)->get(route('dashboard.manpowers.documents.download', [$manpower, $documents[0]]))->assertOk();
        $this->actingAs($this->safetyB)->get(route('dashboard.manpowers.documents.download', [$manpower, $documents[0]]))->assertForbidden();
        $this->actingAs($this->hseB)->get(route('dashboard.manpowers.documents.download', [$manpower, $documents[0]]))->assertForbidden();
    }

    public function test_document_upload_accepts_only_pdf_up_to_two_mb_without_expiry(): void
    {
        $manpower = $this->manpower($this->partnerA);

        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.documents.store', $manpower), $this->documentPayload([
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ]))->assertSessionHasErrors('file');

        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.documents.store', $manpower), $this->documentPayload([
            'file' => UploadedFile::fake()->image('foto.jpg', 600, 800),
        ]))->assertSessionHasErrors('file');

        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.documents.store', $manpower), $this->documentPayload([
            'file' => UploadedFile::fake()->create('besar.pdf', 2049, 'application/pdf'),
        ]))->assertSessionHasErrors('file');

        $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.documents.store', $manpower), $this->documentPayload([
            'type' => 'medical_checkup',
            'expires_at' => today()->subDay()->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertNull($manpower->documents()->where('type', 'medical_checkup')->sole()->expires_at);
    }

    public function test_required_document_check_ignores_expiry_but_rejects_rejected_document(): void
    {
        $manpower = $this->manpower($this->partnerA);
        foreach (Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS as $type) {
            $this->document($manpower, $type);
        }

        $this->assertSame([], $manpower->fresh()->missingRequiredDocumentTypes());
        $manpower->documents()->where('type', 'medical_checkup')->update(['expires_at' => today()->subDay()]);
        $this->assertSame([], $manpower->fresh()->missingRequiredDocumentTypes());

        $manpower->documents()->where('type', 'medical_checkup')->update(['verification_status' => 'rejected']);
        $this->assertContains('medical_checkup', $manpower->fresh()->missingRequiredDocumentTypes());
    }

    public function test_document_requirements_follow_application_and_a2b_category(): void
    {
        $this->assertSame([
            'assignment_letter', 'identity', 'safety_induction', 'medical_checkup', 'hse_compliance',
            'driver_license', 'operator_certificate', 'initial_assessment',
        ], array_keys(ManpowerDocument::TYPES));

        $lightVehicle = Manpower::requiredDocumentTypesForCategories([(object) ['name' => 'Simper Light Vehicle']]);
        $dumpTruck = Manpower::requiredDocumentTypesForCategories([(object) ['name' => 'Simper Dump Truck']]);
        $a2b = Manpower::requiredDocumentTypesForCategories([(object) ['name' => 'Simper Excavator']]);

        $this->assertContains('driver_license', $lightVehicle);
        $this->assertContains('initial_assessment', $lightVehicle);
        $this->assertNotContains('operator_certificate', $lightVehicle);
        $this->assertNotContains('operator_certificate', $dumpTruck);
        $this->assertContains('operator_certificate', $a2b);
    }

    public function test_standalone_document_verification_is_disabled_until_application_review(): void
    {
        $manpower = $this->manpower($this->partnerA);
        $document = $this->document($manpower);

        $this->actingAs($this->hseB)->patch(route('dashboard.manpowers.documents.verify', [$manpower, $document]), [
            'verification_status' => 'verified',
        ])->assertForbidden();

        $this->actingAs($this->hseA)->patch(route('dashboard.manpowers.documents.verify', [$manpower, $document]), [
            'verification_status' => 'rejected',
            'verification_note' => 'Tidak lengkap',
        ])->assertForbidden();

        $this->actingAs($this->hseA)->patch(route('dashboard.manpowers.documents.verify', [$manpower, $document]), [
            'verification_status' => 'verified',
        ])->assertForbidden();
        $this->assertSame('pending', $document->fresh()->verification_status);
        $this->assertNull($document->fresh()->verified_by);
    }

    public function test_archiving_preserves_profile_documents_and_existing_snapshots(): void
    {
        $manpower = $this->manpower($this->partnerA);
        $document = $this->document($manpower);
        $snapshot = $manpower->snapshot();
        $simper = Simper::create([
            'partner_id' => $this->partnerA->id,
            'manpower_id' => $manpower->id,
            'manpower_snapshot' => $snapshot,
            'code' => 'SIMPER-SNAPSHOT',
            'status' => 'pengajuan',
        ]);

        $manpower->update(['name' => 'Nama Setelah Snapshot']);
        $this->assertSame('Pekerja Uji', $snapshot['name']);
        $this->assertSame('Pekerja Uji', $simper->fresh()->manpower_snapshot['name']);

        $this->actingAs($this->safetyA)->delete(route('dashboard.manpowers.destroy', $manpower))
            ->assertRedirect(route('dashboard.manpowers.index'));

        $this->assertSoftDeleted('manpowers', ['id' => $manpower->id]);
        $this->assertDatabaseHas('manpower_documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($document->file_path);
        $this->actingAs($this->safetyA)->get(route('dashboard.manpowers.show', $manpower))->assertOk();
        $this->actingAs($this->safetyA)->get(route('dashboard.manpowers.documents.download', [$manpower, $document]))->assertOk();
    }

    private function owner(string $name, string $shortName): Partner
    {
        return Partner::create([
            'legal_name' => $name, 'short_name' => $shortName, 'email' => strtolower($shortName).'@example.test',
            'status' => 'active', 'level' => 'owner', 'organization_kind' => Partner::KIND_OWNER,
        ]);
    }

    private function partner(Partner $owner, string $name, string $shortName): Partner
    {
        return Partner::create([
            'owner_id' => $owner->id, 'legal_name' => $name, 'short_name' => $shortName,
            'email' => strtolower($shortName).'@example.test', 'status' => 'active',
            'level' => 'contractor', 'organization_kind' => Partner::KIND_PARTNER,
        ]);
    }

    private function user(Partner $partner, string $role): User
    {
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $user->assignRole($role);

        return $user;
    }

    private function manpower(Partner $partner, string $nik = 'NIK-MODEL'): Manpower
    {
        return Manpower::create([
            ...$this->profilePayload(['nik' => $nik, 'photo_path' => 'manpowers/photos/test.jpg']),
            'partner_id' => $partner->id,
            'owner_id' => $partner->owner_id,
        ]);
    }

    private function document(Manpower $manpower, string $type = 'identity', ?string $expiresAt = null): ManpowerDocument
    {
        $path = "manpowers/{$manpower->id}/documents/{$type}/test.pdf";
        Storage::disk('local')->put($path, 'pdf');

        return ManpowerDocument::create([
            'manpower_id' => $manpower->id, 'type' => $type, 'document_number' => strtoupper($type).'-001',
            'issued_at' => today()->subMonth(), 'expires_at' => $expiresAt, 'file_path' => $path,
            'original_name' => 'test.pdf', 'mime_type' => 'application/pdf', 'file_size' => 3,
            'checksum' => hash('sha256', 'pdf'), 'version' => 1, 'uploaded_by' => $this->safetyA->id,
            'verification_status' => 'pending',
        ]);
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_replace([
            'partner_id' => $this->partnerA->id,
            'nik' => 'NIK-001', 'name' => 'Pekerja Uji', 'position' => 'Operator', 'department' => 'Produksi',
            'birth_place' => 'Palembang', 'birth_date' => '1990-01-01', 'contact_number' => '08123456789',
            'emergency_contact_name' => 'Kontak Darurat', 'emergency_contact_number' => '08129876543',
            'blood_type' => 'O+', 'is_active' => 1,
            'photo_path' => UploadedFile::fake()->image('foto.jpg', 600, 800),
        ], $overrides);
    }

    private function documentPayload(array $overrides = []): array
    {
        return array_replace([
            'type' => 'identity', 'document_number' => 'KTP-001', 'issued_at' => today()->subYear()->toDateString(),
            'expires_at' => null, 'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ], $overrides);
    }
}
