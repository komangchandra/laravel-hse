<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Manpower;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\QuestionCategory;
use App\Models\Simper;
use App\Models\SimperCategory;
use App\Models\User;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Partner $ownerA;

    private Partner $ownerB;

    private Partner $partnerA1;

    private Partner $partnerA2;

    private Partner $partnerB1;

    private Partner $partnerB2;

    private User $developer;

    private User $hseA;

    private User $kttA;

    private User $safetyA;

    private User $safetyB;

    private Manpower $manpowerA;

    private Manpower $manpowerA2;

    private Manpower $manpowerB;

    private Simper $simperA;

    private Simper $simperB;

    private SimperCategory $category;

    private ExamSession $sessionA;

    private ExamSession $sessionB;

    private ExamAttempt $attemptA;

    private ExamAttempt $attemptB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);

        $this->ownerA = $this->owner('Owner Alpha', 'OWNA');
        $this->ownerB = $this->owner('Owner Beta', 'OWNB');
        $this->partnerA1 = $this->partner($this->ownerA, 'Mitra Alpha Satu', 'MA1');
        $this->partnerA2 = $this->partner($this->ownerA, 'Mitra Alpha Dua', 'MA2');
        $this->partnerB1 = $this->partner($this->ownerB, 'Mitra Beta Satu', 'MB1');
        $this->partnerB2 = $this->partner($this->ownerB, 'Mitra Beta Dua', 'MB2');

        $this->developer = $this->user('Developer Global', null, 'developer');
        $this->hseA = $this->user('HSE Alpha', $this->ownerA, 'hse_owner');
        $this->kttA = $this->user('KTT Alpha', $this->ownerA, 'ktt');
        $this->safetyA = $this->user('Safety Alpha', $this->partnerA1, 'safety_mitra');
        $this->safetyB = $this->user('Safety Beta', $this->partnerB1, 'safety_mitra');
        $this->user('HSE Beta', $this->ownerB, 'hse_owner');
        $this->user('KTT Beta', $this->ownerB, 'ktt');
        $this->user('Safety Alpha Dua', $this->partnerA2, 'safety_mitra');
        $this->user('Safety Beta Dua', $this->partnerB2, 'safety_mitra');

        $this->manpowerA = $this->manpower($this->partnerA1, 'Pekerja Alpha Satu', 'NIK-A1');
        $this->manpowerA2 = $this->manpower($this->partnerA2, 'Pekerja Alpha Dua', 'NIK-A2');
        $this->manpowerB = $this->manpower($this->partnerB1, 'Pekerja Beta', 'NIK-B1');
        $this->category = SimperCategory::create(['name' => 'Kategori Bersama', 'description' => 'Master bersama']);
        $this->simperA = $this->simper($this->partnerA1, $this->manpowerA, 'SIM-A');
        $this->simperB = $this->simper($this->partnerB1, $this->manpowerB, 'SIM-B');

        $this->sessionA = $this->examSession($this->ownerA, 'Sesi Alpha');
        $this->sessionB = $this->examSession($this->ownerB, 'Sesi Beta');
        $this->attemptA = $this->attempt($this->simperA, $this->sessionA);
        $this->attemptB = $this->attempt($this->simperB, $this->sessionB);
    }

    public function test_dashboard_denies_users_without_a_valid_role_and_organization_pair(): void
    {
        $roleless = User::factory()->create();
        $invalidSafety = $this->user('Safety Salah Organisasi', $this->ownerA, 'safety_mitra');

        $this->actingAs($roleless)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($invalidSafety)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($this->developer)->get(route('dashboard'))->assertOk();
    }

    public function test_manpower_queries_mutations_and_private_files_are_tenant_isolated(): void
    {
        $this->actingAs($this->safetyA)->get(route('dashboard.manpowers.index'))
            ->assertOk()->assertSee('Pekerja Alpha Satu')->assertDontSee('Pekerja Alpha Dua')->assertDontSee('Pekerja Beta');

        $this->actingAs($this->hseA)->get(route('dashboard.manpowers.index'))
            ->assertOk()->assertSee('Pekerja Alpha Satu')->assertSee('Pekerja Alpha Dua')->assertDontSee('Pekerja Beta');

        $this->actingAs($this->developer)->get(route('dashboard.manpowers.index'))
            ->assertOk()->assertSee('Pekerja Beta');

        $this->actingAs($this->safetyA)->get(route('dashboard.manpowers.edit', $this->manpowerB))->assertForbidden();
        Storage::fake('local');
        $hsePayload = $this->manpowerPayload($this->partnerA1);
        $hsePayload['nik'] = 'NIK-HSE-INTERNAL';
        $hsePayload['name'] = 'Pekerja Internal Owner';
        $this->actingAs($this->hseA)->post(route('dashboard.manpowers.store'), $hsePayload)->assertRedirect();
        $this->assertDatabaseHas('manpowers', [
            'nik' => 'NIK-HSE-INTERNAL',
            'partner_id' => $this->ownerA->id,
            'owner_id' => $this->ownerA->id,
        ]);

        $response = $this->actingAs($this->safetyA)->post(route('dashboard.manpowers.store'), $this->manpowerPayload($this->partnerB1));
        $createdManpower = Manpower::where('nik', 'NIK-NEW')->firstOrFail();
        $response->assertRedirect(route('dashboard.manpowers.edit', $createdManpower));
        $this->assertDatabaseHas('manpowers', ['nik' => 'NIK-NEW', 'partner_id' => $this->partnerA1->id]);

        Storage::disk('local')->put('manpowers/documents/private.pdf', 'private');
        $this->manpowerA->update(['document_path' => 'manpowers/documents/private.pdf']);
        $this->actingAs($this->safetyA)->get(route('dashboard.manpowers.document', $this->manpowerA))->assertOk();
        $this->actingAs($this->safetyB)->get(route('dashboard.manpowers.document', $this->manpowerA))->assertForbidden();
    }

    public function test_simper_mutations_and_exam_token_follow_separation_of_duties(): void
    {
        $this->actingAs($this->safetyA)->get(route('dashboard.simpers.index'))
            ->assertOk()->assertSee('SIM-A')->assertDontSee('SIM-B');
        $this->actingAs($this->safetyA)->get(route('dashboard.simpers.show', $this->simperB))->assertForbidden();

        $payload = $this->simperPayload($this->manpowerA, $this->partnerA1, 'SIM-A-UPDATED');
        $this->actingAs($this->safetyA)->put(route('dashboard.simpers.update', $this->simperA), $payload)
            ->assertRedirect(route('dashboard.simpers.index'));
        $this->assertDatabaseHas('simpers', ['id' => $this->simperA->id, 'code' => 'SIM-A-UPDATED']);

        $this->actingAs($this->hseA)->put(route('dashboard.simpers.update', $this->simperA), $payload)->assertForbidden();
        $this->actingAs($this->kttA)->put(route('dashboard.simpers.update', $this->simperA), $payload)->assertForbidden();
        $this->actingAs($this->safetyA)->post(route('dashboard.simpers.generate-token', $this->simperA))->assertForbidden();
        $this->actingAs($this->kttA)->post(route('dashboard.simpers.generate-token', $this->simperA))->assertForbidden();

        $this->actingAs($this->hseA)->post(route('dashboard.simpers.generate-token', $this->simperA))
            ->assertRedirect()->assertSessionHasErrors('token');
        $this->assertDatabaseMissing('exam_tokens', ['simper_id' => $this->simperA->id]);
        $this->actingAs($this->hseA)->post(route('dashboard.simpers.generate-token', $this->simperB))->assertForbidden();
    }

    public function test_exam_sessions_results_and_pdf_are_isolated_by_owner(): void
    {
        $this->actingAs($this->hseA)->get(route('dashboard.exam-sessions.index'))
            ->assertOk()->assertSee('Sesi Alpha')->assertDontSee('Sesi Beta');
        $this->actingAs($this->hseA)->get(route('dashboard.exam-sessions.show', $this->sessionB))->assertForbidden();
        $this->actingAs($this->kttA)->get(route('dashboard.exam-sessions.create'))->assertForbidden();
        $this->actingAs($this->safetyA)->get(route('dashboard.exam-sessions.index'))->assertForbidden();

        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), [
            'name' => 'Sesi Alpha Baru',
            'duration' => 60,
            'passing_score' => 80,
            'is_active' => 1,
        ])->assertSessionHasErrors('permit_application_id');
        $this->assertDatabaseMissing('exam_sessions', ['name' => 'Sesi Alpha Baru']);

        $this->actingAs($this->safetyA)->get(route('dashboard.exam-results.show', $this->attemptA))->assertOk();
        $this->actingAs($this->safetyA)->get(route('dashboard.exam-results.pdf', $this->attemptA))->assertOk();
        $this->actingAs($this->safetyA)->get(route('dashboard.exam-results.show', $this->attemptB))->assertForbidden();
        $this->actingAs($this->hseA)->get(route('dashboard.exam-results.show', $this->attemptB))->assertForbidden();
        $this->actingAs($this->developer)->get(route('dashboard.exam-results.show', $this->attemptB))->assertOk();
    }

    public function test_user_and_question_master_access_does_not_cross_owner(): void
    {
        $categoryA = QuestionCategory::create($this->questionCategoryPayload('Kategori Alpha', $this->ownerA));
        $categoryB = QuestionCategory::create($this->questionCategoryPayload('Kategori Beta', $this->ownerB));

        $this->actingAs($this->hseA)->get(route('users.index'))
            ->assertOk()->assertSee('Safety Alpha')->assertDontSee('Safety Beta');
        $this->actingAs($this->hseA)->get(route('dashboard.question-categories.index'))
            ->assertOk()->assertSee('Kategori Alpha')->assertDontSee('Kategori Beta');
        $this->actingAs($this->hseA)->get(route('dashboard.question-categories.edit', $categoryB))->assertForbidden();
        $this->actingAs($this->kttA)->get(route('dashboard.question-categories.create'))->assertForbidden();

        $this->actingAs($this->developer)->post(route('users.store'), [
            'name' => 'User Salah',
            'email' => 'wrong@example.test',
            'password' => 'SecurePassword123!',
            'roles' => ['safety_mitra'],
            'partner_id' => $this->ownerA->id,
        ])->assertSessionHasErrors('roles');

        $this->assertTrue($this->hseA->can('permit-application.review-hse'));
        $this->assertFalse($this->hseA->can('permit-application.review-ktt'));
        $this->assertTrue($this->kttA->can('permit-application.review-ktt'));
        $this->assertFalse($this->safetyA->can('permit-application.review-hse'));
        $this->assertTrue($this->safetyA->can('permit-card.download'));
        $this->assertTrue($categoryA->owner->is($this->ownerA));
    }

    public function test_participant_cannot_open_another_attempt_by_changing_url_id(): void
    {
        $token = ExamToken::create([
            'simper_id' => $this->simperA->id,
            'token' => 'TOKENA',
            'expired_at' => now()->addHour(),
        ]);

        $this->withSession(['exam_token_id' => $token->id, 'simper_id' => $this->simperA->id])
            ->get(route('exam.question', ['attempt' => $this->attemptB, 'number' => 1]))
            ->assertForbidden();
    }

    public function test_legacy_roles_are_migrated_without_preserving_generic_role_names(): void
    {
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $legacyUser = User::factory()->create(['partner_id' => $this->ownerA->id]);
        $legacyUser->assignRole('owner');
        $this->developer->update(['partner_id' => $this->ownerA->id]);
        $this->developer->assignRole('hse_owner');

        $this->seed(RoleSeeder::class);

        $this->assertTrue($legacyUser->fresh()->hasRole('hse_owner'));
        $this->assertFalse(Role::where('name', 'owner')->exists());
        $this->assertNull($this->developer->fresh()->partner_id);
        $this->assertSame(['developer'], $this->developer->fresh()->getRoleNames()->all());
    }

    private function owner(string $name, string $shortName): Partner
    {
        return Partner::create([
            'legal_name' => $name,
            'short_name' => $shortName,
            'email' => strtolower($shortName).'@example.test',
            'status' => 'active',
            'level' => 'owner',
            'organization_kind' => Partner::KIND_OWNER,
        ]);
    }

    private function partner(Partner $owner, string $name, string $shortName): Partner
    {
        return Partner::create([
            'owner_id' => $owner->id,
            'partner_type_id' => PartnerType::where('code', 'rental')->value('id'),
            'organization_kind' => Partner::KIND_PARTNER,
            'legal_name' => $name,
            'short_name' => $shortName,
            'email' => strtolower($shortName).'@example.test',
            'status' => 'active',
            'level' => 'rental',
        ]);
    }

    private function user(string $name, ?Partner $partner, string $role): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $partner?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function manpower(Partner $partner, string $name, string $nik): Manpower
    {
        return Manpower::create([
            'partner_id' => $partner->id,
            'name' => $name,
            'nik' => $nik,
            'contact_number' => '08123456789',
            'blood_type' => 'O+',
        ]);
    }

    private function simper(Partner $partner, Manpower $manpower, string $code): Simper
    {
        $simper = Simper::create([
            'partner_id' => $partner->id,
            'manpower_id' => $manpower->id,
            'code' => $code,
            'status' => 'pengajuan',
        ]);
        $simper->categories()->attach($this->category, ['level' => 'full']);

        return $simper;
    }

    private function examSession(Partner $owner, string $name): ExamSession
    {
        return ExamSession::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'duration' => 60,
            'passing_score' => 80,
            'is_active' => true,
        ]);
    }

    private function attempt(Simper $simper, ExamSession $session): ExamAttempt
    {
        return ExamAttempt::create([
            'simper_id' => $simper->id,
            'exam_session_id' => $session->id,
            'status' => 'completed',
            'score' => 80,
            'is_passed' => true,
            'finished_at' => now(),
        ]);
    }

    private function manpowerPayload(Partner $partner): array
    {
        return [
            'partner_id' => $partner->id,
            'nik' => 'NIK-NEW',
            'name' => 'Pekerja Baru',
            'position' => 'Operator',
            'department' => 'Operasional',
            'birth_place' => 'Palembang',
            'birth_date' => '1990-01-01',
            'contact_number' => '081299999999',
            'emergency_contact_name' => 'Kontak Darurat',
            'emergency_contact_number' => '081288888888',
            'blood_type' => 'A+',
            'is_active' => 1,
            'photo_path' => UploadedFile::fake()->image('foto.jpg', 600, 800),
        ];
    }

    private function simperPayload(Manpower $manpower, Partner $partner, string $code): array
    {
        return [
            'partner_id' => $partner->id,
            'manpower_id' => $manpower->id,
            'code' => $code,
            'categories' => [['id' => $this->category->id, 'level' => 'full']],
        ];
    }

    private function questionCategoryPayload(string $name, Partner $owner): array
    {
        return [
            'owner_id' => $owner->id,
            'name' => $name,
            'description' => 'Deskripsi',
            'measured' => 'Aspek',
            'measurable' => 'Ukuran',
        ];
    }
}
