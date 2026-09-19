<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Models\ExamSession;
use App\Models\ExamAttempt;
use App\Models\AnswerOption;
use App\Models\ExamToken;
use App\Models\Manpower;
use App\Models\Partner;
use App\Models\PermitApplication;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\SimperCategory;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamSessionPerApplicationTest extends TestCase
{
    use RefreshDatabase;

    private Partner $ownerA;
    private Partner $ownerB;
    private Partner $partnerA;
    private User $hseA;
    private User $hseB;
    private User $safetyA;
    private Manpower $manpower;
    private SimperCategory $simperCategoryA;
    private SimperCategory $simperCategoryB;
    private QuestionCategory $questionCategoryA;
    private QuestionCategory $questionCategoryB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->ownerA = $this->owner('Owner A', 'OA');
        $this->ownerB = $this->owner('Owner B', 'OB');
        $this->partnerA = Partner::create([
            'owner_id' => $this->ownerA->id,
            'organization_kind' => Partner::KIND_PARTNER,
            'legal_name' => 'Mitra A', 'short_name' => 'MA', 'email' => 'ma@example.test',
            'status' => 'active', 'level' => 'contractor',
        ]);
        $this->hseA = $this->user($this->ownerA, 'HSE A');
        $this->hseB = $this->user($this->ownerB, 'HSE B');
        $this->safetyA = User::factory()->create(['name' => 'Safety Mitra A', 'partner_id' => $this->partnerA->id]);
        $this->safetyA->assignRole('safety_mitra');
        $this->manpower = Manpower::create([
            'partner_id' => $this->partnerA->id, 'owner_id' => $this->ownerA->id,
            'name' => 'Peserta A', 'nik' => 'NIK-EXAM-A', 'contact_number' => '0812', 'blood_type' => 'O+',
        ]);
        $this->simperCategoryA = SimperCategory::create(['owner_id' => $this->ownerA->id, 'name' => 'Dump Truck']);
        $this->simperCategoryB = SimperCategory::create(['owner_id' => $this->ownerA->id, 'name' => 'Excavator']);
        $this->questionCategoryA = QuestionCategory::create([
            'owner_id' => $this->ownerA->id, 'name' => 'Teori DT', 'description' => 'Teori DT',
            'measured' => 'Kompetensi', 'measurable' => 'Nilai',
        ]);
        $this->questionCategoryB = QuestionCategory::create([
            'owner_id' => $this->ownerA->id, 'name' => 'Teori EX', 'description' => 'Teori EX',
            'measured' => 'Kompetensi', 'measurable' => 'Nilai',
        ]);
        $this->questions($this->questionCategoryA, 3);
        $this->questions($this->questionCategoryB, 2);
    }

    public function test_hse_creates_and_activates_blueprint_with_different_quotas(): void
    {
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);

        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 3],
            [$this->simperCategoryB, $this->questionCategoryB, 2],
        ]) + ['activate' => 1])->assertRedirect(route('dashboard.permit-applications.show', $application));

        $session = ExamSession::where('permit_application_id', $application->id)->firstOrFail();
        $this->assertTrue($session->is_active);
        $this->assertSame('active', $session->status);
        $this->assertSame(Status::ExamScheduled, $application->fresh()->status);
        $this->assertDatabaseHas('exam_session_blueprints', [
            'exam_session_id' => $session->id, 'simper_category_id' => $this->simperCategoryA->id,
            'question_category_id' => $this->questionCategoryA->id, 'question_count' => 3,
        ]);
        $this->assertDatabaseHas('exam_session_categories', [
            'exam_session_id' => $session->id, 'category_id' => $this->questionCategoryB->id, 'question_count' => 2,
        ]);
    }

    public function test_one_simper_category_can_use_multiple_question_categories(): void
    {
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup, [$this->simperCategoryA]);

        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.show', $application))
            ->assertOk()
            ->assertSee('Tambah kategori soal')
            ->assertSee('Setiap kategori SIMPER dapat memakai beberapa kategori bank soal');

        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 2],
            [$this->simperCategoryA, $this->questionCategoryB, 1],
        ]) + ['activate' => 1])->assertRedirect(route('dashboard.permit-applications.show', $application));

        $session = ExamSession::where('permit_application_id', $application->id)->firstOrFail();
        $this->assertSame(2, $session->blueprints()->count());
        $this->assertDatabaseHas('exam_session_categories', [
            'exam_session_id' => $session->id,
            'category_id' => $this->questionCategoryA->id,
            'question_count' => 2,
        ]);
        $this->assertDatabaseHas('exam_session_categories', [
            'exam_session_id' => $session->id,
            'category_id' => $this->questionCategoryB->id,
            'question_count' => 1,
        ]);

        $tokenResponse = $this->actingAs($this->hseA)
            ->post(route('dashboard.exam-sessions.generate-token', $session));
        $plainToken = $tokenResponse->getSession()->get('issued_exam_token');
        auth()->logout();
        $this->post(route('exam.authenticate'), ['nik' => $this->manpower->nik, 'token' => $plainToken]);
        $this->post(route('exam.begin'))->assertRedirect();

        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $this->assertSame(3, $attempt->total_questions);
        $this->assertSame(
            [$this->questionCategoryA->id, $this->questionCategoryB->id],
            $attempt->questions()->with('question')->get()->pluck('question.category_id')->unique()->sort()->values()->all(),
        );
    }

    public function test_activation_rejects_insufficient_stock_and_missing_mapping(): void
    {
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $payload = $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 4],
            [$this->simperCategoryB, $this->questionCategoryB, 2],
        ]);
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $payload + ['activate' => 1])
            ->assertSessionHasErrors('blueprints');
        $this->assertDatabaseHas('exam_sessions', ['permit_application_id' => $application->id, 'status' => 'draft']);

        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $payload = $this->payload($application, [[$this->simperCategoryA, $this->questionCategoryA, 1]]);
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $payload)
            ->assertSessionHasErrors('blueprints');
        $this->assertDatabaseMissing('exam_sessions', ['permit_application_id' => $application->id]);
    }

    public function test_mine_permit_and_cross_owner_session_configuration_are_rejected(): void
    {
        $minePermit = $this->application(Type::MinePermitOnly, Status::KttReview, []);
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $this->payload($minePermit, []))
            ->assertForbidden();

        $combined = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $this->actingAs($this->hseB)->post(route('dashboard.exam-sessions.store'), $this->payload($combined, [
            [$this->simperCategoryA, $this->questionCategoryA, 1],
            [$this->simperCategoryB, $this->questionCategoryB, 1],
        ]))->assertForbidden();
    }

    public function test_invalid_schedule_and_passing_grade_are_rejected(): void
    {
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $payload = $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 1],
            [$this->simperCategoryB, $this->questionCategoryB, 1],
        ]);
        $payload['passing_score'] = 79;
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $payload)
            ->assertSessionHasErrors('passing_score');

        $payload['passing_score'] = 80;
        $payload['scheduled_start_at'] = now()->subHours(3)->format('Y-m-d H:i:s');
        $payload['scheduled_end_at'] = now()->subHour()->format('Y-m-d H:i:s');
        $payload['activate'] = 1;
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $payload)
            ->assertSessionHasErrors('scheduled_end_at');
        $this->assertSame('draft', ExamSession::where('permit_application_id', $application->id)->value('status'));
    }

    public function test_token_is_hashed_bound_and_rotation_revokes_previous_token(): void
    {
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 1],
            [$this->simperCategoryB, $this->questionCategoryB, 1],
        ]) + ['activate' => 1]);
        $session = ExamSession::where('permit_application_id', $application->id)->firstOrFail();

        $response = $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.generate-token', $session));
        $plain = $response->getSession()->get('issued_exam_token');
        $first = ExamToken::firstOrFail();
        $this->assertNotSame($plain, $first->token);
        $this->assertSame(ExamToken::digest($plain), $first->token);
        $this->assertSame($plain, $first->display_token);
        $this->assertNotSame($plain, DB::table('exam_tokens')->whereKey($first->id)->value('display_token'));
        $this->assertSame($application->id, $first->permit_application_id);
        $this->assertSame($session->id, $first->exam_session_id);

        $this->actingAs($this->hseA)->get(route('dashboard.permit-applications.show', $application))
            ->assertOk()->assertSee($plain);
        $this->actingAs($this->safetyA)->get(route('dashboard.permit-applications.show', $application))
            ->assertOk()->assertSee($plain);

        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.generate-token', $session))->assertRedirect();
        $this->assertNotNull($first->fresh()->revoked_at);
        $this->assertSame(2, ExamToken::count());
    }

    public function test_generating_token_opens_a_future_session_for_immediate_use(): void
    {
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $startsAt = now()->addMinutes(30)->startOfMinute();
        $payload = $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 1],
            [$this->simperCategoryB, $this->questionCategoryB, 1],
        ]);
        $payload['scheduled_start_at'] = $startsAt->format('Y-m-d H:i:s');
        $payload['scheduled_end_at'] = now()->addHours(2)->format('Y-m-d H:i:s');

        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $payload + ['activate' => 1]);
        $session = ExamSession::where('permit_application_id', $application->id)->firstOrFail();
        $response = $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.generate-token', $session));
        $plain = $response->getSession()->get('issued_exam_token');

        $this->assertTrue($session->fresh()->scheduled_start_at->lessThanOrEqualTo(now()));
        $this->assertTrue(ExamToken::firstOrFail()->expired_at->between(now()->addMinutes(59), now()->addMinutes(61)));

        // Compatibility for a token generated before the old future schedule was corrected.
        $session->update(['scheduled_start_at' => $startsAt]);
        $this->post(route('exam.authenticate'), ['nik' => $this->manpower->nik, 'token' => $plain])
            ->assertRedirect(route('exam.start'));
    }

    public function test_participant_uses_the_application_session_and_only_its_blueprint_questions(): void
    {
        $unrelated = QuestionCategory::create([
            'owner_id' => $this->ownerA->id, 'name' => 'Tidak Relevan', 'description' => 'Tidak Relevan',
            'measured' => 'Kompetensi', 'measurable' => 'Nilai',
        ]);
        $this->questions($unrelated, 5);
        $application = $this->application(Type::MinePermitSimper, Status::WaitingExamSetup);
        $this->actingAs($this->hseA)->post(route('dashboard.exam-sessions.store'), $this->payload($application, [
            [$this->simperCategoryA, $this->questionCategoryA, 2],
            [$this->simperCategoryB, $this->questionCategoryB, 1],
        ]) + ['activate' => 1]);
        $session = ExamSession::where('permit_application_id', $application->id)->firstOrFail();
        $tokenResponse = $this->actingAs($this->hseA)
            ->post(route('dashboard.exam-sessions.generate-token', $session));
        $plainToken = $tokenResponse->getSession()->get('issued_exam_token');

        auth()->logout();
        $this->post(route('exam.authenticate'), ['nik' => $this->manpower->nik, 'token' => $plainToken])
            ->assertRedirect(route('exam.start'));
        $this->post(route('exam.begin'))->assertRedirect();

        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $this->assertSame($session->id, $attempt->exam_session_id);
        $this->assertSame(3, $attempt->total_questions);
        $categoryIds = $attempt->questions()->with('question')->get()->pluck('question.category_id')->unique()->sort()->values()->all();
        $this->assertSame([$this->questionCategoryA->id, $this->questionCategoryB->id], $categoryIds);
        $this->assertSame(Status::ExamInProgress, $application->fresh()->status);
    }

    private function owner(string $name, string $shortName): Partner
    {
        return Partner::create([
            'organization_kind' => Partner::KIND_OWNER, 'legal_name' => $name, 'short_name' => $shortName,
            'email' => strtolower($shortName).'@example.test', 'status' => 'active', 'level' => 'owner',
        ]);
    }

    private function user(Partner $owner, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $owner->id]);
        $user->assignRole('hse_owner');

        return $user;
    }

    /** @param array<int, SimperCategory> $categories */
    private function application(Type $type, Status $status, ?array $categories = null): PermitApplication
    {
        $categories ??= [$this->simperCategoryA, $this->simperCategoryB];
        $snapshotCategories = collect($categories)->map(fn ($category) => [
            'id' => $category->id, 'name' => $category->name, 'level' => 'F',
        ])->all();

        return PermitApplication::create([
            'application_number' => 'APP-'.uniqid(), 'type' => $type, 'status' => $status,
            'owner_id' => $this->ownerA->id, 'partner_id' => $this->partnerA->id,
            'manpower_id' => $this->manpower->id, 'created_by' => $this->hseA->id,
            'submission_version' => 1, 'submitted_snapshot' => [
                'name' => $this->manpower->name, 'nik' => $this->manpower->nik,
                'partner_name' => $this->partnerA->legal_name, 'categories' => $snapshotCategories,
            ],
        ]);
    }

    /** @param array<int, array{0:SimperCategory,1:QuestionCategory,2:int}> $mappings */
    private function payload(PermitApplication $application, array $mappings): array
    {
        return [
            'permit_application_id' => $application->id, 'name' => 'Sesi '.$application->application_number,
            'scheduled_start_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'scheduled_end_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'duration' => 60, 'passing_score' => 80, 'max_attempts' => 2,
            'blueprints' => collect($mappings)->map(fn ($mapping) => [
                'simper_category_id' => $mapping[0]->id,
                'question_category_id' => $mapping[1]->id,
                'question_count' => $mapping[2],
            ])->all(),
        ];
    }

    private function questions(QuestionCategory $category, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $question = Question::create([
                'category_id' => $category->id, 'question' => $category->name.' '.$i,
                'type' => 'multiple_choice', 'score' => 1,
            ]);
            AnswerOption::create(['question_id' => $question->id, 'label' => 'A', 'answer' => 'Benar', 'is_correct' => true]);
            AnswerOption::create(['question_id' => $question->id, 'label' => 'B', 'answer' => 'Salah', 'is_correct' => false]);
        }
    }
}
