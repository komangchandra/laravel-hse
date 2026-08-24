<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Simper;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Question;
use App\Models\ExamAttemptAnswer;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function login()
    {
        return view('exam.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'nik' => ['required'],
            'token' => ['required'],
        ]);

        $examToken = ExamToken::with([
                'simper.manpower'
            ])
            ->where('token', strtoupper($request->token))
            ->first();

        if (!$examToken) {

            return back()->withErrors([
                'token' => 'Token tidak ditemukan.'
            ]);
        }

        if ($examToken->expired_at < now()) {

            return back()->withErrors([
                'token' => 'Token sudah kadaluarsa.'
            ]);
        }

        if ($examToken->used_at) {

            return back()->withErrors([
                'token' => 'Token sudah digunakan.'
            ]);
        }

        if (
            $examToken->simper->manpower->nik != $request->nik
        ) {

            return back()->withErrors([
                'nik' => 'NIK tidak sesuai.'
            ]);
        }

        session([
            'exam_token_id' => $examToken->id,
            'simper_id' => $examToken->simper_id,
        ]);

        return redirect()
            ->route('exam.start');
    }

    public function start()
    {
        $simper = Simper::with([
            'manpower',
            'partner',
            'categories',
        ])->findOrFail(
            session('simper_id')
        );

        $examSession = ExamSession::where(
            'is_active',
            true
        )->first();

        return view(
            'exam.start',
            compact(
                'simper',
                'examSession'
            )
        );
    }

    public function begin()
    {
        $simperId = session('simper_id');

        if (!$simperId) {

            return redirect()
                ->route('exam.login');
        }

        $examSession = ExamSession::with('categories')
            ->where('is_active', true)
            ->firstOrFail();

        $attempt = ExamAttempt::create([
            'simper_id' => $simperId,
            'exam_session_id' => $examSession->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $questions = collect();

        foreach ($examSession->categories as $category) {

            $count = $category->pivot->question_count;

            $categoryQuestions = Question::where(
                    'category_id',
                    $category->id
                )
                ->inRandomOrder()
                ->limit($count)
                ->get();

            $questions = $questions->merge(
                $categoryQuestions
            );
        }

        $questions = $questions->shuffle()->values();

        foreach ($questions as $index => $question) {

            ExamAttemptQuestion::create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'order_no' => $index + 1,
            ]);
        }

        $attempt->update([
            'total_questions' => $questions->count(),
        ]);

        return redirect()->route(
            'exam.question',
            [
                'attempt' => $attempt->id,
                'number' => 1,
            ]
        );
    }

    public function question(ExamAttempt $attempt, int $number)
    {
        $attemptQuestion = $attempt
            ->questions()
            ->with([
                'question.options',
                'question.keywords'
            ])
            ->where(
                'order_no',
                $number
            )
            ->firstOrFail();

            // dd($attemptQuestion);
        return view(
            'exam.question',
            compact(
                'attempt',
                'attemptQuestion',
                'number'
            )
        );
    }

    public function saveAnswer(
        Request $request,
        ExamAttempt $attempt,
        int $number
    )
    {
        $attemptQuestion = $attempt
            ->questions()
            ->with([
                'question.options',
                'question.keywords'
            ])
            ->where('order_no', $number)
            ->firstOrFail();

        $question = $attemptQuestion->question;

        $data = [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ];

        if ($question->type === 'multiple_choice') {

            $selectedOption = $question->options()
                ->find($request->answer_option_id);

            $data['answer_option_id'] =
                $selectedOption?->id;

            $data['is_correct'] =
                $selectedOption?->is_correct ?? false;

            $data['score'] =
                $selectedOption?->is_correct
                    ? $question->score
                    : 0;
        }

        if ($question->type === 'essay_auto') {

            $essay = strtolower(
                $request->essay_answer
            );

            $score = 0;

            foreach ($question->keywords as $keyword) {

                if (
                    str_contains(
                        $essay,
                        strtolower($keyword->keyword)
                    )
                ) {
                    $score += $keyword->score;
                }
            }

            $data['essay_answer'] =
                $request->essay_answer;

            $data['score'] = $score;

            $data['is_correct'] = null;
        }

        ExamAttemptAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            $data
        );

        $nextNumber = $number + 1;

        if ($nextNumber <= $attempt->total_questions) {

            return redirect()->route(
                'exam.question',
                [
                    'attempt' => $attempt->id,
                    'number' => $nextNumber,
                ]
            );
        }

        return redirect()->route(
            'exam.finish',
            $attempt
        );
    }

    public function finish(ExamAttempt $attempt)
    {
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('exam.login');
        }

        $answers = ExamAttemptAnswer::where('exam_attempt_id', $attempt->id)->get();

        $score = $answers->sum('score');
        $correctAnswers = $answers->where('is_correct', true)->count();
        $wrongAnswers = $answers->where('is_correct', false)->count();

        $isPassed = $score >= $attempt->examSession->passing_score;

        $attempt->update([
            'status' => 'completed',
            'finished_at' => now(),
            'score' => $score,
            'correct_answers' => $correctAnswers,
            'is_passed' => $isPassed,
        ]);

        /**
         * UPDATE SIMPER STATUS
         */
        $simper = Simper::find(session('simper_id'));

        if ($simper) {
            $simper->update([
                'status' => $isPassed
                    ? 'lulus_ujian'
                    : 'tidak_lulus_ujian',
            ]);
        }

        ExamToken::where('id', session('exam_token_id'))
            ->update(['used_at' => now()]);

        session()->forget([
            'exam_token_id',
            'simper_id'
        ]);

        return view('exam.finish', compact(
            'attempt',
            'score',
            'correctAnswers',
            'wrongAnswers'
        ));
    }
}
