<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Services\ExamAttemptLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(private readonly ExamAttemptLifecycleService $lifecycle) {}

    public function login(): View
    {
        return view('exam.login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:100'],
            'token' => ['required', 'string', 'max:100'],
        ]);
        $context = $this->lifecycle->authenticate($validated['token'], $validated['nik']);
        $request->session()->regenerate();
        $request->session()->put([
            'exam_token_id' => $context['token']->id,
            'attempt_id' => $context['attempt']->id,
            'application_id' => $context['attempt']->permit_application_id,
            'exam_session_id' => $context['attempt']->exam_session_id,
        ]);

        return redirect()->route($context['attempt']->status === 'in_progress' ? 'exam.question' : 'exam.start',
            $context['attempt']->status === 'in_progress' ? ['attempt' => $context['attempt'], 'number' => 1] : []);
    }

    public function start(Request $request): View
    {
        [$attempt] = $this->participantContext($request);
        if ($attempt->status === 'in_progress' && $this->lifecycle->expireIfNeeded($attempt)) {
            return $this->finishedView($request, $attempt);
        }
        abort_unless(in_array($attempt->status, ['pending', 'in_progress'], true), 403);
        $attempt->load(['application.manpower', 'application.partner', 'examSession']);
        $participant = [
            'name' => data_get($attempt->application->submitted_snapshot, 'name', $attempt->application->manpower?->name),
            'nik' => data_get($attempt->application->submitted_snapshot, 'nik', $attempt->application->manpower?->nik),
            'organization' => data_get($attempt->application->submitted_snapshot, 'partner_name', $attempt->application->partner?->legal_name),
        ];

        return view('exam.start', ['participant' => $participant, 'examSession' => $attempt->examSession]);
    }

    public function begin(Request $request)
    {
        [$attempt, $token] = $this->participantContext($request);
        $attempt = $this->lifecycle->begin($attempt, $token);
        if ($attempt->status === 'completed') {
            return $this->finishedView($request, $attempt);
        }

        return redirect()->route('exam.question', ['attempt' => $attempt, 'number' => 1]);
    }

    public function question(Request $request, ExamAttempt $attempt, int $number)
    {
        [$attempt] = $this->participantContext($request, $attempt);
        if ($this->lifecycle->expireIfNeeded($attempt)) {
            return $this->finishedView($request, $attempt);
        }
        abort_unless($attempt->status === 'in_progress', 403);
        $attemptQuestion = $attempt->questions()->where('order_no', $number)->firstOrFail();

        return view('exam.question', compact('attempt', 'attemptQuestion', 'number'));
    }

    public function saveAnswer(Request $request, ExamAttempt $attempt, int $number)
    {
        $validated = $request->validate(['answer_option_id' => ['required', 'integer']]);
        [$attempt] = $this->participantContext($request, $attempt);
        $saved = $this->lifecycle->saveAnswer($attempt, $number, (int) $validated['answer_option_id']);
        if (! $saved) {
            return $this->finishedView($request, $attempt);
        }
        $attempt->refresh();
        if ($number >= $attempt->total_questions) {
            $summary = $this->lifecycle->finalize($attempt);

            return $this->finishedView($request, $summary['attempt'], $summary);
        }

        return redirect()->route('exam.question', ['attempt' => $attempt, 'number' => $number + 1]);
    }

    public function finish(Request $request, ExamAttempt $attempt): View
    {
        [$attempt] = $this->participantContext($request, $attempt);
        $summary = $this->lifecycle->finalize($attempt);

        return $this->finishedView($request, $summary['attempt'], $summary);
    }

    public function questionPhoto(Request $request, ExamAttemptQuestion $attemptQuestion): StreamedResponse
    {
        $attempt = $attemptQuestion->attempt;
        [$attempt] = $this->participantContext($request, $attempt);
        abort_if($this->lifecycle->expireIfNeeded($attempt), 410, 'Waktu ujian telah habis.');
        abort_unless($attempt->status === 'in_progress' && $attemptQuestion->exam_attempt_id === $attempt->id, 403);
        $path = data_get($attemptQuestion->question_snapshot, 'photo_path');
        abort_unless($path, 404);
        $disk = Storage::disk('local')->exists($path) ? Storage::disk('local') : Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, 'soal-'.$attemptQuestion->id, ['Content-Disposition' => 'inline']);
    }

    /** @return array{0: ExamAttempt, 1: \App\Models\ExamToken} */
    private function participantContext(Request $request, ?ExamAttempt $routeAttempt = null): array
    {
        abort_unless($request->session()->has(['exam_token_id', 'attempt_id', 'application_id', 'exam_session_id']), 403);
        $attemptId = (int) $request->session()->get('attempt_id');
        if ($routeAttempt && $routeAttempt->id !== $attemptId) {
            abort(403, 'Attempt tidak sesuai session peserta.');
        }
        $attempt = $routeAttempt ?? ExamAttempt::findOrFail($attemptId);
        $token = $this->lifecycle->assertSessionBinding(
            $attempt,
            (int) $request->session()->get('exam_token_id'),
            (int) $request->session()->get('application_id'),
            (int) $request->session()->get('exam_session_id'),
        );

        return [$attempt, $token];
    }

    /** @param array{attempt: ExamAttempt, score: float|int, correctAnswers: int, wrongAnswers: int, blankAnswers: int}|null $summary */
    private function finishedView(Request $request, ExamAttempt $attempt, ?array $summary = null): View
    {
        $summary ??= $this->lifecycle->finalize($attempt, $attempt->deadline_at?->isPast() ? 'timeout' : 'submitted');
        $request->session()->forget(['exam_token_id', 'attempt_id', 'application_id', 'exam_session_id']);

        return view('exam.finish', $summary);
    }
}
