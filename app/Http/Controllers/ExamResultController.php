<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamResultController extends Controller
{
    public function index()
    {
        $attempts = ExamAttempt::with([
                'simper.manpower',
                'examSession'
            ])
            ->latest()
            ->paginate(10);

        return view('dashboard.exam-results.index', compact('attempts'));
    }

    public function show(ExamAttempt $attempt)
    {
        $attempt->load([
            'simper.manpower',
            'answers.question.options'
        ]);

        return view('dashboard.exam-results.show', compact('attempt'));
    }

    public function pdf(ExamAttempt $attempt)
    {
        $attempt->load([
            'simper.manpower',
            'answers.question.options',
            'answers.option'
        ]);

        $pdf = Pdf::loadView('dashboard.exam-results.pdf', compact('attempt'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('hasil-ujian-'.$attempt->id.'.pdf');
    }
}
