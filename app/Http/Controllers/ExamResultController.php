<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExamResultController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ExamAttempt::class);
        $attempts = ExamAttempt::visibleTo($request->user())->with([
            'simper.manpower',
            'application.manpower',
            'examSession',
        ])
            ->latest()
            ->paginate(10);

        return view('dashboard.exam-results.index', compact('attempts'));
    }

    public function show(ExamAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        $attempt->load([
            'simper.manpower',
            'application.manpower',
            'questions.answer',
        ]);

        $canViewAnswerKey = request()->user()->isDeveloper() || request()->user()->hasAnyRole(['hse_owner', 'ktt']);

        return view('dashboard.exam-results.show', compact('attempt', 'canViewAnswerKey'));
    }

    public function pdf(ExamAttempt $attempt)
    {
        $this->authorize('download', $attempt);
        $attempt->load([
            'simper.manpower',
            'application.manpower',
            'questions.answer',
        ]);

        $canViewAnswerKey = request()->user()->isDeveloper() || request()->user()->hasAnyRole(['hse_owner', 'ktt']);
        $pdf = Pdf::loadView('dashboard.exam-results.pdf', compact('attempt', 'canViewAnswerKey'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('hasil-ujian-'.$attempt->id.'.pdf');
    }
}
