<?php

namespace App\Services;

use App\Enums\PermitApplicationStatus as ApplicationStatus;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamToken;
use App\Models\PermitApplication;
use App\Models\Question;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptLifecycleService
{
    public function __construct(private readonly PermitApplicationWorkflow $workflow) {}

    /** @return array{token: ExamToken, attempt: ExamAttempt} */
    public function authenticate(string $plainToken, string $nik): array
    {
        return DB::transaction(function () use ($plainToken, $nik) {
            $token = ExamToken::query()
                ->where(fn ($query) => $query
                    ->where('token', ExamToken::digest($plainToken))
                    ->orWhere('token', strtoupper(trim($plainToken))))
                ->lockForUpdate()->first();
            if (! $token) {
                throw ValidationException::withMessages(['token' => 'Token tidak ditemukan.']);
            }

            $token->load(['application.manpower', 'application.partner', 'examSession']);
            $application = $token->application
                ? PermitApplication::query()->lockForUpdate()->findOrFail($token->permit_application_id)
                : null;
            $session = $token->examSession;

            if (! $application || ! $session || $token->revoked_at || $token->expired_at->isPast()) {
                throw ValidationException::withMessages(['token' => 'Token tidak aktif atau sudah kedaluwarsa.']);
            }
            if ($session->permit_application_id !== $application->id
                || $session->owner_id !== $application->owner_id
                || $token->exam_session_id !== $session->id) {
                throw ValidationException::withMessages(['token' => 'Token tidak sesuai dengan pengajuan atau owner ujian.']);
            }
            if (! $session->is_active || $session->status !== 'active') {
                throw ValidationException::withMessages(['token' => 'Sesi ujian belum aktif.']);
            }
            // A generated token is an explicit signal that HSE has opened access.
            // Use its issue time for tokens made before the session start, including
            // tokens created with the former one-hour-ahead scheduling behaviour.
            $availableFrom = $token->created_at && $token->created_at->lt($session->scheduled_start_at)
                ? $token->created_at
                : $session->scheduled_start_at;
            if (now()->lt($availableFrom)) {
                throw ValidationException::withMessages([
                    'token' => 'Ujian belum dapat dimulai. Jadwal mulai '.$availableFrom->format('d-m-Y H:i').'.',
                ]);
            }
            if (now()->gt($session->scheduled_end_at)) {
                throw ValidationException::withMessages(['token' => 'Jadwal ujian telah berakhir.']);
            }

            $expectedNik = (string) data_get($application->submitted_snapshot, 'nik', $application->manpower?->nik);
            if (! hash_equals($expectedNik, trim($nik))) {
                throw ValidationException::withMessages(['nik' => 'NIK tidak sesuai.']);
            }

            $attempt = $token->exam_attempt_id
                ? ExamAttempt::query()->lockForUpdate()->find($token->exam_attempt_id)
                : null;
            if ($attempt) {
                if ($attempt->permit_application_id !== $application->id
                    || $attempt->exam_session_id !== $session->id
                    || ! in_array($attempt->status, ['pending', 'in_progress'], true)) {
                    throw ValidationException::withMessages(['token' => 'Token sudah digunakan dan tidak dapat dipakai kembali.']);
                }
                $expectedStatus = $attempt->status === 'pending'
                    ? ApplicationStatus::ExamScheduled
                    : ApplicationStatus::ExamInProgress;
                if ($application->status !== $expectedStatus) {
                    throw ValidationException::withMessages(['token' => 'Status pengajuan tidak sesuai attempt token.']);
                }
            } else {
                if ($token->used_at || $application->status !== ApplicationStatus::ExamScheduled) {
                    throw ValidationException::withMessages(['token' => 'Token sudah digunakan atau pengajuan tidak siap ujian.']);
                }
                $attempt = ExamAttempt::query()
                    ->where('permit_application_id', $application->id)
                    ->where('exam_session_id', $session->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->lockForUpdate()->first();
                if (! $attempt) {
                    $attemptCount = ExamAttempt::query()->where('permit_application_id', $application->id)->count();
                    if ($attemptCount >= $session->max_attempts) {
                        throw ValidationException::withMessages(['token' => 'Batas attempt pengajuan telah tercapai.']);
                    }
                    $attempt = ExamAttempt::create([
                        'permit_application_id' => $application->id,
                        'exam_session_id' => $session->id,
                        'attempt_number' => $attemptCount + 1,
                        'status' => 'pending',
                        'authenticated_at' => now(),
                        'duration_minutes_snapshot' => $session->duration,
                        'passing_score_snapshot' => $session->passing_score,
                        'scoring_rule_version' => 'percentage-v1',
                    ]);
                }
                $token->update(['used_at' => now(), 'exam_attempt_id' => $attempt->id]);
            }

            return ['token' => $token->fresh(), 'attempt' => $attempt->fresh()];
        }, 3);
    }

    public function begin(ExamAttempt $attempt, ExamToken $token): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $token) {
            $current = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $currentToken = ExamToken::query()->lockForUpdate()->findOrFail($token->id);
            $this->assertTokenOwnsAttempt($currentToken, $current);

            if ($current->status === 'in_progress') {
                if ($current->deadline_at?->isPast()) {
                    $this->finalizeLocked($current, 'timeout');
                }

                return $current->fresh();
            }
            if ($current->status !== 'pending') {
                throw ValidationException::withMessages(['attempt' => 'Attempt tidak dapat dimulai kembali.']);
            }

            $session = $current->examSession()->with('categories')->firstOrFail();
            $application = PermitApplication::query()->lockForUpdate()->findOrFail($current->permit_application_id);
            if ($application->status !== ApplicationStatus::ExamScheduled || ! $session->is_active
                || now()->lt($session->scheduled_start_at) || now()->gt($session->scheduled_end_at)) {
                throw ValidationException::withMessages(['attempt' => 'Pengajuan atau jadwal tidak lagi siap untuk memulai ujian.']);
            }

            $selectedQuestions = collect();
            foreach ($session->categories as $category) {
                $required = (int) $category->pivot->question_count;
                $questions = Question::query()->with('options')
                    ->where('category_id', $category->id)->validForExam()
                    ->inRandomOrder()->limit($required)->get();
                if ($questions->count() !== $required) {
                    throw ValidationException::withMessages(['questions' => "Stok soal kategori {$category->name} berubah dan tidak lagi mencukupi."]);
                }
                $selectedQuestions = $selectedQuestions->merge($questions);
            }
            $selectedQuestions = $selectedQuestions->shuffle()->values();
            $maxScore = 0;
            foreach ($selectedQuestions as $index => $question) {
                $score = max(0, (int) $question->score);
                $maxScore += $score;
                ExamAttemptQuestion::create([
                    'exam_attempt_id' => $current->id,
                    'question_id' => $question->id,
                    'order_no' => $index + 1,
                    'score_snapshot' => $score,
                    'question_snapshot' => [
                        'source_id' => $question->id,
                        'text' => $question->question,
                        'type' => $question->type,
                        'photo_path' => $question->photo_path,
                    ],
                    'options_snapshot' => $question->options->map(fn ($option) => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'answer' => $option->answer,
                        'is_correct' => (bool) $option->is_correct,
                    ])->values()->all(),
                ]);
            }

            $startedAt = now();
            $current->update([
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'deadline_at' => $startedAt->copy()->addMinutes($current->duration_minutes_snapshot),
                'total_questions' => $selectedQuestions->count(),
                'max_score_snapshot' => $maxScore,
            ]);
            $this->workflow->startExam($application, [
                'attempt_id' => $current->id,
                'exam_session_id' => $session->id,
                'deadline_at' => $current->deadline_at?->toIso8601String(),
            ]);

            return $current->fresh(['questions']);
        }, 3);
    }

    public function expireIfNeeded(ExamAttempt $attempt): bool
    {
        if ($attempt->status !== 'in_progress' || ! $attempt->deadline_at?->isPast()) {
            return false;
        }

        $this->finalize($attempt, 'timeout');

        return true;
    }

    public function saveAnswer(ExamAttempt $attempt, int $number, int $optionId): bool
    {
        return DB::transaction(function () use ($attempt, $number, $optionId) {
            $current = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($current->status !== 'in_progress') {
                throw ValidationException::withMessages(['attempt' => 'Attempt sudah selesai dan tidak menerima jawaban.']);
            }
            if ($current->deadline_at?->isPast()) {
                $this->finalizeLocked($current, 'timeout');

                return false;
            }

            $attemptQuestion = ExamAttemptQuestion::query()
                ->where('exam_attempt_id', $current->id)->where('order_no', $number)
                ->lockForUpdate()->firstOrFail();
            $selected = collect($attemptQuestion->options_snapshot)
                ->first(fn ($option) => (int) $option['id'] === $optionId);
            if (! $selected) {
                throw ValidationException::withMessages([
                    'answer_option_id' => 'Pilihan jawaban bukan bagian dari snapshot soal ini.',
                ]);
            }

            ExamAttemptAnswer::updateOrCreate(
                ['exam_attempt_question_id' => $attemptQuestion->id],
                [
                    'exam_attempt_id' => $current->id,
                    'question_id' => $attemptQuestion->question_id,
                    'answer_option_id' => $selected['id'],
                    'selected_option_snapshot' => $selected,
                    'is_correct' => (bool) $selected['is_correct'],
                    'score' => $selected['is_correct'] ? $attemptQuestion->score_snapshot : 0,
                ]
            );

            return true;
        }, 3);
    }

    /** @return array{attempt: ExamAttempt, score: float|int, correctAnswers: int, wrongAnswers: int, blankAnswers: int} */
    public function finalize(ExamAttempt $attempt, string $reason = 'submitted'): array
    {
        return DB::transaction(function () use ($attempt, $reason) {
            $current = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            return $this->finalizeLocked($current, $reason);
        }, 3);
    }

    public function assertSessionBinding(ExamAttempt $attempt, int $tokenId, int $applicationId, int $sessionId): ExamToken
    {
        if ($attempt->id < 1 || $attempt->permit_application_id !== $applicationId
            || $attempt->exam_session_id !== $sessionId) {
            throw new AuthorizationException('Attempt tidak sesuai session peserta.');
        }
        $token = ExamToken::query()->whereKey($tokenId)->where('exam_attempt_id', $attempt->id)
            ->where('permit_application_id', $applicationId)->where('exam_session_id', $sessionId)
            ->whereNull('revoked_at')->first();
        if (! $token) {
            throw new AuthorizationException('Token tidak sesuai attempt peserta.');
        }

        return $token;
    }

    /** @return array{attempt: ExamAttempt, score: float|int, correctAnswers: int, wrongAnswers: int, blankAnswers: int} */
    private function finalizeLocked(ExamAttempt $attempt, string $reason): array
    {
        if ($attempt->status === 'completed') {
            return $this->summary($attempt);
        }
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Attempt belum dimulai atau tidak dapat difinalisasi.']);
        }
        if ($reason !== 'timeout' && $attempt->deadline_at?->isPast()) {
            $reason = 'timeout';
        }

        $answers = ExamAttemptAnswer::query()->where('exam_attempt_id', $attempt->id)->get();
        $rawScore = (float) $answers->sum('score');
        $maxScore = (float) $attempt->max_score_snapshot;
        $score = $maxScore > 0 ? round(min(100, max(0, ($rawScore / $maxScore) * 100)), 2) : 0.0;
        $correct = $answers->where('is_correct', true)->count();
        $wrong = $answers->where('is_correct', false)->count();
        $blank = max(0, $attempt->total_questions - $answers->count());
        $passed = $score >= $attempt->passing_score_snapshot;
        $attempt->update([
            'status' => 'completed',
            'finished_at' => now(),
            'finalized_at' => now(),
            'completion_reason' => $reason,
            'score' => $score,
            'raw_score' => $rawScore,
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'blank_answers' => $blank,
            'is_passed' => $passed,
        ]);

        $attemptsRemain = ExamAttempt::query()->where('permit_application_id', $attempt->permit_application_id)->count()
            < $attempt->examSession->max_attempts;
        $this->workflow->recordExamResult(
            $attempt->application,
            $passed,
            $attemptsRemain,
            ['attempt_id' => $attempt->id, 'exam_session_id' => $attempt->exam_session_id, 'reason' => $reason]
        );

        return $this->summary($attempt->fresh());
    }

    /** @return array{attempt: ExamAttempt, score: float|int, correctAnswers: int, wrongAnswers: int, blankAnswers: int} */
    private function summary(ExamAttempt $attempt): array
    {
        return [
            'attempt' => $attempt,
            'score' => $attempt->score ?? 0,
            'correctAnswers' => (int) $attempt->correct_answers,
            'wrongAnswers' => (int) $attempt->wrong_answers,
            'blankAnswers' => (int) $attempt->blank_answers,
        ];
    }

    private function assertTokenOwnsAttempt(ExamToken $token, ExamAttempt $attempt): void
    {
        if ($token->exam_attempt_id !== $attempt->id
            || $token->permit_application_id !== $attempt->permit_application_id
            || $token->exam_session_id !== $attempt->exam_session_id
            || $token->revoked_at) {
            throw new AuthorizationException('Token tidak sesuai attempt peserta.');
        }
    }
}
