<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Models\AnswerOption;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Manpower;
use App\Models\Partner;
use App\Models\PermitApplication;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Services\ExamAttemptLifecycleService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSecurityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Partner $owner;

    private Partner $partner;

    private User $hse;

    private QuestionCategory $questionCategory;

    private Question $questionOne;

    private Question $questionTwo;

    private AnswerOption $questionOneCorrect;

    private AnswerOption $questionTwoCorrect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->owner = Partner::create([
            'organization_kind' => Partner::KIND_OWNER, 'legal_name' => 'Owner Aman', 'short_name' => 'OA',
            'email' => 'owner-aman@example.test', 'status' => 'active', 'level' => 'owner',
        ]);
        $this->partner = Partner::create([
            'owner_id' => $this->owner->id, 'organization_kind' => Partner::KIND_PARTNER,
            'legal_name' => 'Mitra Aman', 'short_name' => 'MA', 'email' => 'mitra-aman@example.test',
            'status' => 'active', 'level' => 'contractor',
        ]);
        $this->hse = User::factory()->create(['partner_id' => $this->owner->id]);
        $this->hse->assignRole('hse_owner');
        $this->questionCategory = QuestionCategory::create([
            'owner_id' => $this->owner->id, 'name' => 'Keselamatan', 'description' => 'Keselamatan',
            'measured' => 'Kompetensi', 'measurable' => 'Nilai',
        ]);
        [$this->questionOne, $this->questionOneCorrect] = $this->question('Pertanyaan pertama?', 50);
        [$this->questionTwo, $this->questionTwoCorrect] = $this->question('Pertanyaan kedua?', 50);
    }

    public function test_authentication_claims_token_and_binds_participant_session_to_pending_attempt(): void
    {
        [$application, $session, $plainToken] = $this->examContext('NIK-SEC-1');

        $this->post(route('exam.authenticate'), ['nik' => 'NIK-SEC-1', 'token' => $plainToken])
            ->assertRedirect(route('exam.start'))
            ->assertSessionHasAll([
                'application_id' => $application->id,
                'exam_session_id' => $session->id,
            ]);

        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $this->assertSame('pending', $attempt->status);
        $this->assertSame($attempt->id, session('attempt_id'));
        $this->assertSame($attempt->id, ExamToken::where('permit_application_id', $application->id)->value('exam_attempt_id'));
        $this->assertNotNull(ExamToken::where('permit_application_id', $application->id)->value('used_at'));
    }

    public function test_attempt_idor_is_rejected_between_two_participants(): void
    {
        [$applicationA, , $tokenA] = $this->examContext('NIK-IDOR-A');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-IDOR-A', 'token' => $tokenA]);
        $this->post(route('exam.begin'));
        $attemptA = ExamAttempt::where('permit_application_id', $applicationA->id)->firstOrFail();

        [, , $tokenB] = $this->examContext('NIK-IDOR-B');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-IDOR-B', 'token' => $tokenB]);
        $this->post(route('exam.begin'));

        $this->get(route('exam.question', ['attempt' => $attemptA, 'number' => 1]))->assertForbidden();
        $this->post(route('exam.save-answer', ['attempt' => $attemptA, 'number' => 1]), [
            'answer_option_id' => $this->questionOneCorrect->id,
        ])->assertForbidden();
    }

    public function test_foreign_option_is_rejected_instead_of_recorded_as_wrong_answer(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-OPTION');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-OPTION', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $firstQuestion = $attempt->questions()->where('order_no', 1)->firstOrFail();
        $foreignQuestion = $attempt->questions()->where('id', '!=', $firstQuestion->id)->firstOrFail();
        $foreignOption = collect($foreignQuestion->options_snapshot)->first()['id'];

        $this->post(route('exam.save-answer', ['attempt' => $attempt, 'number' => 1]), [
            'answer_option_id' => $foreignOption,
        ])->assertSessionHasErrors('answer_option_id');
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }

    public function test_double_begin_resumes_same_attempt_without_resetting_deadline_or_questions(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-DOUBLE');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-DOUBLE', 'token' => $plainToken]);
        $this->post(route('exam.begin'))->assertRedirect();
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $deadline = $attempt->deadline_at;
        $questionIds = $attempt->questions()->pluck('id')->all();

        $this->post(route('exam.begin'))->assertRedirect();

        $this->assertSame(1, ExamAttempt::where('permit_application_id', $application->id)->count());
        $this->assertTrue($deadline->equalTo($attempt->fresh()->deadline_at));
        $this->assertSame($questionIds, $attempt->questions()->pluck('id')->all());
    }

    public function test_server_deadline_finalizes_attempt_and_rejects_late_answers(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-TIMEOUT');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-TIMEOUT', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $attempt->update(['deadline_at' => now()->subSecond()]);

        $this->post(route('exam.save-answer', ['attempt' => $attempt, 'number' => 1]), [
            'answer_option_id' => $this->questionOneCorrect->id,
        ])->assertOk()->assertSee('Ujian Selesai');

        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertSame('timeout', $attempt->fresh()->completion_reason);
        $this->assertDatabaseCount('exam_attempt_answers', 0);
        $this->post(route('exam.save-answer', ['attempt' => $attempt, 'number' => 1]), [
            'answer_option_id' => $this->questionOneCorrect->id,
        ])->assertForbidden();
    }

    public function test_connection_resume_keeps_attempt_questions_and_original_deadline(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-RESUME');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-RESUME', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $deadline = $attempt->deadline_at;
        $questionIds = $attempt->questions()->pluck('id')->all();

        $this->post(route('exam.authenticate'), ['nik' => 'NIK-RESUME', 'token' => $plainToken])
            ->assertRedirect(route('exam.question', ['attempt' => $attempt, 'number' => 1]));

        $this->assertTrue($deadline->equalTo($attempt->fresh()->deadline_at));
        $this->assertSame($questionIds, $attempt->questions()->pluck('id')->all());
        $this->assertSame($attempt->id, session('attempt_id'));
    }

    public function test_question_and_option_snapshots_survive_master_deletion(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-SNAPSHOT');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-SNAPSHOT', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attemptQuestion = ExamAttempt::where('permit_application_id', $application->id)
            ->firstOrFail()->questions()->where('question_id', $this->questionOne->id)->firstOrFail();
        $snapshot = $attemptQuestion->question_snapshot;
        $options = $attemptQuestion->options_snapshot;

        $this->questionOne->delete();

        $attemptQuestion->refresh();
        $this->assertNull($attemptQuestion->question_id);
        $this->assertSame($snapshot, $attemptQuestion->question_snapshot);
        $this->assertSame($options, $attemptQuestion->options_snapshot);

        $attempt = $attemptQuestion->attempt;
        app(ExamAttemptLifecycleService::class)->finalize($attempt);
        $this->actingAs($this->hse)->get(route('dashboard.exam-results.show', $attempt))
            ->assertOk()->assertSee('Pertanyaan pertama?');
    }

    public function test_finalization_is_post_only_and_idempotent(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-FINISH');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-FINISH', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();

        $this->get(route('exam.finish', $attempt))->assertMethodNotAllowed();
        $this->post(route('exam.finish', $attempt))->assertOk()->assertSee('Ujian Selesai');
        $firstFinalizedAt = $attempt->fresh()->finalized_at;
        $summary = app(ExamAttemptLifecycleService::class)->finalize($attempt->fresh());

        $this->assertTrue($firstFinalizedAt->equalTo($summary['attempt']->finalized_at));
        $this->assertSame(1, $application->statusHistories()->where('action', 'record_exam_result')->count());
    }

    public function test_percentage_score_passes_exactly_at_threshold_and_persists_explanation(): void
    {
        $this->questionOne->update(['score' => 80]);
        $this->questionTwo->update(['score' => 20]);
        [$application, , $plainToken] = $this->examContext('NIK-SCORE-80');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-SCORE-80', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $first = $attempt->questions()->get()->firstWhere('question_snapshot.source_id', $this->questionOne->id);
        $second = $attempt->questions()->get()->firstWhere('question_snapshot.source_id', $this->questionTwo->id);
        $lifecycle = app(ExamAttemptLifecycleService::class);
        $lifecycle->saveAnswer($attempt, $first->order_no, $this->correctOptionId($first));
        $lifecycle->saveAnswer($attempt, $second->order_no, $this->wrongOptionId($second));
        $summary = $lifecycle->finalize($attempt);

        $result = $summary['attempt'];
        $this->assertSame('80.00', $result->score);
        $this->assertSame('80.00', $result->raw_score);
        $this->assertTrue($result->is_passed);
        $this->assertSame(1, $result->correct_answers);
        $this->assertSame(1, $result->wrong_answers);
        $this->assertSame(0, $result->blank_answers);
        $this->assertSame(80, $result->passing_score_snapshot);
        $this->assertSame('percentage-v1', $result->scoring_rule_version);
        $this->assertSame(Status::ExamPassed, $application->fresh()->status);
    }

    public function test_blank_answers_are_zero_and_counted_separately(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-BLANK');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-BLANK', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        $first = $attempt->questions()->firstOrFail();
        $lifecycle = app(ExamAttemptLifecycleService::class);
        $lifecycle->saveAnswer($attempt, $first->order_no, $this->correctOptionId($first));
        $result = $lifecycle->finalize($attempt)['attempt'];

        $this->assertSame('50.00', $result->score);
        $this->assertSame(1, $result->correct_answers);
        $this->assertSame(0, $result->wrong_answers);
        $this->assertSame(1, $result->blank_answers);
        $this->assertFalse($result->is_passed);
        $this->assertSame(Status::ExamRetryRequired, $application->fresh()->status);
    }

    public function test_failed_attempt_can_be_rescheduled_with_reason_then_pass_without_deleting_history(): void
    {
        [$application, $session, $plainToken] = $this->examContext('NIK-RETRY');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-RETRY', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $firstAttempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        app(ExamAttemptLifecycleService::class)->finalize($firstAttempt);
        $this->assertSame(Status::ExamRetryRequired, $application->fresh()->status);

        $this->actingAs($this->hse)->post(route('dashboard.exam-sessions.retry', $session), [
            'scheduled_start_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'scheduled_end_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'reason' => 'Pembinaan selesai dan peserta siap ujian ulang.',
        ])->assertRedirect();
        $this->assertSame(Status::ExamScheduled, $application->fresh()->status);
        $this->assertDatabaseHas('application_status_histories', [
            'permit_application_id' => $application->id,
            'action' => 'schedule_exam',
            'notes' => 'Pembinaan selesai dan peserta siap ujian ulang.',
        ]);

        $tokenResponse = $this->actingAs($this->hse)->post(route('dashboard.exam-sessions.generate-token', $session));
        $retryToken = $tokenResponse->getSession()->get('issued_exam_token');
        auth()->logout();
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-RETRY', 'token' => $retryToken]);
        $this->post(route('exam.begin'));
        $secondAttempt = ExamAttempt::where('permit_application_id', $application->id)->where('attempt_number', 2)->firstOrFail();
        $lifecycle = app(ExamAttemptLifecycleService::class);
        foreach ($secondAttempt->questions as $attemptQuestion) {
            $lifecycle->saveAnswer($secondAttempt, $attemptQuestion->order_no, $this->correctOptionId($attemptQuestion));
        }
        $lifecycle->finalize($secondAttempt);

        $this->assertSame(Status::ExamPassed, $application->fresh()->status);
        $this->assertSame(2, ExamAttempt::where('permit_application_id', $application->id)->count());
        $this->assertFalse($firstAttempt->fresh()->is_passed);
        $this->assertTrue($secondAttempt->fresh()->is_passed);

        $this->actingAs($this->hse)->post(route('dashboard.permit-applications.submit-ktt', $application), [
            'notes' => 'Hasil attempt kedua telah divalidasi.',
        ])->assertRedirect();
        $this->assertSame(Status::KttReview, $application->fresh()->status);
    }

    public function test_retry_is_rejected_when_two_attempts_already_exist(): void
    {
        [$application, $session] = $this->examContext('NIK-LIMIT');
        $application->update(['status' => Status::ExamRetryRequired]);
        foreach ([1, 2] as $number) {
            ExamAttempt::create([
                'permit_application_id' => $application->id,
                'exam_session_id' => $session->id,
                'attempt_number' => $number,
                'status' => 'completed',
                'is_passed' => false,
                'passing_score_snapshot' => 80,
                'duration_minutes_snapshot' => 60,
            ]);
        }

        $this->actingAs($this->hse)->post(route('dashboard.exam-sessions.retry', $session), [
            'scheduled_start_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'scheduled_end_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'reason' => 'Percobaan tambahan tanpa override.',
        ])->assertSessionHasErrors('retry');
        $this->assertSame(Status::ExamRetryRequired, $application->fresh()->status);
    }

    public function test_hse_question_form_only_lists_categories_owned_by_its_owner(): void
    {
        $globalCategory = QuestionCategory::create([
            'owner_id' => null,
            'name' => 'Kategori Global',
            'description' => 'Hanya dapat dikelola developer',
            'measured' => 'Kompetensi',
            'measurable' => 'Nilai',
        ]);
        $otherOwner = Partner::create([
            'organization_kind' => Partner::KIND_OWNER,
            'legal_name' => 'Owner Lain',
            'short_name' => 'OL',
            'email' => 'owner-lain@example.test',
            'status' => 'active',
            'level' => 'owner',
        ]);
        $otherCategory = QuestionCategory::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Kategori Owner Lain',
            'description' => 'Kategori owner lain',
            'measured' => 'Kompetensi',
            'measurable' => 'Nilai',
        ]);

        $this->actingAs($this->hse)
            ->get(route('dashboard.questions.create'))
            ->assertOk()
            ->assertViewHas('categories', function ($categories) use ($globalCategory, $otherCategory) {
                return $categories->modelKeys() === [$this->questionCategory->id]
                    && ! $categories->contains($globalCategory)
                    && ! $categories->contains($otherCategory);
            });
    }

    public function test_hse_cannot_store_question_in_global_category(): void
    {
        $globalCategory = QuestionCategory::create([
            'owner_id' => null,
            'name' => 'Kategori Global',
            'description' => 'Hanya dapat dikelola developer',
            'measured' => 'Kompetensi',
            'measurable' => 'Nilai',
        ]);

        $this->actingAs($this->hse)
            ->postJson(route('dashboard.questions.store'), [
                'category_id' => $globalCategory->id,
                'question' => 'Tidak boleh masuk kategori global',
                'type' => 'multiple_choice',
                'score' => 10,
                'options' => [
                    ['label' => 'A', 'answer' => 'Benar'],
                    ['label' => 'B', 'answer' => 'Salah'],
                ],
                'correct_option' => 'A',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        $this->assertDatabaseMissing('questions', [
            'question' => 'Tidak boleh masuk kategori global',
        ]);
    }

    public function test_developer_question_form_can_list_global_and_owner_categories(): void
    {
        $globalCategory = QuestionCategory::create([
            'owner_id' => null,
            'name' => 'Kategori Global',
            'description' => 'Kategori developer',
            'measured' => 'Kompetensi',
            'measurable' => 'Nilai',
        ]);
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)
            ->get(route('dashboard.questions.create'))
            ->assertOk()
            ->assertViewHas('categories', function ($categories) use ($globalCategory) {
                return $categories->contains($globalCategory)
                    && $categories->contains($this->questionCategory);
            });
    }

    public function test_mvp_rejects_essay_and_invalid_multiple_choice_bank_questions(): void
    {
        $essay = [
            'category_id' => $this->questionCategory->id,
            'question' => 'Essay tidak diizinkan', 'type' => 'essay_auto', 'score' => 10,
            'keywords' => [['keyword' => 'aman', 'score' => 10]],
        ];
        $this->actingAs($this->hse)->post(route('dashboard.questions.store'), $essay)
            ->assertSessionHasErrors('type');

        $invalidChoice = [
            'category_id' => $this->questionCategory->id,
            'question' => 'Opsi tidak lengkap', 'type' => 'multiple_choice', 'score' => 10,
            'options' => [['label' => 'A', 'answer' => 'Satu']], 'correct_option' => 'A',
        ];
        $this->actingAs($this->hse)->post(route('dashboard.questions.store'), $invalidChoice)
            ->assertSessionHasErrors('options');
        $this->assertDatabaseMissing('questions', ['question' => 'Essay tidak diizinkan']);
        $this->assertDatabaseMissing('questions', ['question' => 'Opsi tidak lengkap']);
    }

    public function test_answer_key_is_hidden_from_safety_audience_but_visible_to_hse(): void
    {
        [$application, , $plainToken] = $this->examContext('NIK-AUDIENCE');
        $this->post(route('exam.authenticate'), ['nik' => 'NIK-AUDIENCE', 'token' => $plainToken]);
        $this->post(route('exam.begin'));
        $attempt = ExamAttempt::where('permit_application_id', $application->id)->firstOrFail();
        app(ExamAttemptLifecycleService::class)->finalize($attempt);
        $safety = User::factory()->create(['partner_id' => $this->partner->id]);
        $safety->assignRole('safety_mitra');

        $this->actingAs($safety)->get(route('dashboard.exam-results.show', $attempt))
            ->assertOk()->assertDontSee('jawaban benar');
        $this->actingAs($this->hse)->get(route('dashboard.exam-results.show', $attempt))
            ->assertOk()->assertSee('jawaban benar');
    }

    private function correctOptionId(\App\Models\ExamAttemptQuestion $attemptQuestion): int
    {
        return (int) collect($attemptQuestion->options_snapshot)->firstWhere('is_correct', true)['id'];
    }

    private function wrongOptionId(\App\Models\ExamAttemptQuestion $attemptQuestion): int
    {
        return (int) collect($attemptQuestion->options_snapshot)->firstWhere('is_correct', false)['id'];
    }

    /** @return array{0: PermitApplication, 1: ExamSession, 2: string} */
    private function examContext(string $nik): array
    {
        $manpower = Manpower::create([
            'partner_id' => $this->partner->id, 'owner_id' => $this->owner->id,
            'name' => 'Peserta '.$nik, 'nik' => $nik, 'contact_number' => '0812', 'blood_type' => 'O+',
        ]);
        $application = PermitApplication::create([
            'application_number' => 'SEC-'.uniqid(), 'type' => Type::MinePermitSimper,
            'status' => Status::ExamScheduled, 'owner_id' => $this->owner->id,
            'partner_id' => $this->partner->id, 'manpower_id' => $manpower->id,
            'created_by' => $this->hse->id, 'submission_version' => 1,
            'submitted_snapshot' => ['name' => $manpower->name, 'nik' => $nik, 'partner_name' => $this->partner->legal_name],
        ]);
        $session = ExamSession::create([
            'permit_application_id' => $application->id, 'owner_id' => $this->owner->id,
            'created_by' => $this->hse->id, 'name' => 'Sesi '.$nik,
            'scheduled_start_at' => now()->subMinute(), 'scheduled_end_at' => now()->addHours(2),
            'duration' => 60, 'passing_score' => 80, 'max_attempts' => 2,
            'status' => 'active', 'is_active' => true, 'activated_at' => now(),
        ]);
        $session->categories()->attach($this->questionCategory, ['question_count' => 2]);
        $plainToken = 'TOKEN-'.str_replace('-', '', $nik);
        ExamToken::create([
            'permit_application_id' => $application->id, 'exam_session_id' => $session->id,
            'created_by' => $this->hse->id, 'token' => ExamToken::digest($plainToken),
            'expired_at' => now()->addHour(),
        ]);

        return [$application, $session, $plainToken];
    }

    /** @return array{0: Question, 1: AnswerOption} */
    private function question(string $text, int $score): array
    {
        $question = Question::create([
            'category_id' => $this->questionCategory->id, 'question' => $text,
            'type' => 'multiple_choice', 'score' => $score,
        ]);
        $correct = AnswerOption::create([
            'question_id' => $question->id, 'label' => 'A', 'answer' => 'Benar', 'is_correct' => true,
        ]);
        AnswerOption::create([
            'question_id' => $question->id, 'label' => 'B', 'answer' => 'Salah', 'is_correct' => false,
        ]);

        return [$question, $correct];
    }
}
