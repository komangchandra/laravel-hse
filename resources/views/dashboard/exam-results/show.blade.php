@extends('layouts.admin')

@section('title', 'Detail Hasil Ujian')

@section('content')

{{-- HEADER --}}
<div class="mb-4">

    <div class="d-flex justify-content-between align-items-start">

        <div>

            <h4 class="fw-bold mb-0">Detail Hasil Ujian</h4>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 mt-1">

                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none">
                            Dashboard
                        </a>
                    </li>

                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard.exam-results.index') }}" class="text-decoration-none">
                            Hasil Ujian
                        </a>
                    </li>

                    <li class="breadcrumb-item active">
                        Detail
                    </li>

                </ol>
            </nav>

        </div>

        {{-- ACTION BUTTON --}}
        <div class="d-flex gap-2">

            <a href="{{ route('dashboard.exam-results.index') }}"
                class="btn btn-outline-secondary">

                <i class="bi bi-arrow-left me-1"></i>
                Kembali

            </a>

            <a href="{{ route('dashboard.exam-results.pdf', $attempt) }}"
                target="_blank"
                class="btn btn-danger shadow-sm">

                <i class="bi bi-file-earmark-pdf me-1"></i>
                Print PDF

            </a>

        </div>

    </div>

</div>

{{-- ================= SUMMARY ================= --}}
<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <div class="row g-3">

            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <small class="text-muted">Peserta</small>
                    <div class="fw-bold">
                        {{ $attempt->simper->manpower->name ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <small class="text-muted">NIK</small>
                    <div class="fw-bold">
                        {{ $attempt->simper->manpower->nik ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <small class="text-muted">SIMPER</small>
                    <div class="fw-bold">
                        {{ $attempt->simper->code ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 border rounded text-center">
                    <small class="text-muted">Score</small>
                    <div class="fs-4 fw-bold text-primary">
                        {{ number_format($attempt->score, 2) }}
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 border rounded text-center">
                    <small class="text-muted">Benar</small>
                    <div class="fs-4 fw-bold text-success">
                        {{ $attempt->correct_answers }}
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 border rounded text-center">
                    <small class="text-muted">Status</small>
                    <div>
                        @if($attempt->is_passed)
                            <span class="badge bg-success px-3 py-2">LULUS</span>
                        @else
                            <span class="badge bg-danger px-3 py-2">TIDAK LULUS</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 border rounded text-center">
                    <small class="text-muted">Waktu</small>
                    <div class="small text-muted">
                        {{ $attempt->finished_at }}
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

{{-- ================= DETAIL JAWABAN ================= --}}
<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">Review Jawaban</h6>
    </div>

    <div class="card-body">

        @foreach($attempt->answers as $index => $answer)

            @php
                $question = $answer->question;
            @endphp

            <div class="border rounded p-3 mb-3">

                <div class="mb-2">
                    <strong>
                        {{ $index + 1 }}.
                        {!! nl2br(e($question->question)) !!}
                    </strong>
                </div>

                @if($question->type === 'multiple_choice')

                    @foreach($question->options as $opt)

                        <div class="form-check">

                            <label class="form-check-label">

                                @if($opt->id == $answer->answer_option_id)

                                    <span class="fw-bold text-primary">
                                        ➤ {{ $opt->label }}. {{ $opt->answer }}
                                    </span>

                                @elseif($opt->is_correct)

                                    <span class="text-success">
                                        ✔ {{ $opt->label }}. {{ $opt->answer }}
                                    </span>

                                @else

                                    <span class="text-muted">
                                        {{ $opt->label }}. {{ $opt->answer }}
                                    </span>

                                @endif

                            </label>

                        </div>

                    @endforeach

                    <div class="mt-2">

                        @if($answer->is_correct)
                            <span class="badge bg-success">BENAR</span>
                        @else
                            <span class="badge bg-danger">SALAH</span>
                        @endif

                    </div>

                @endif

                @if($question->type === 'essay_auto')

                    <div class="mt-2">

                        <div class="p-2 bg-light rounded">
                            <strong>Jawaban:</strong><br>
                            {{ $answer->essay_answer }}
                        </div>

                        <div class="mt-2">
                            <span class="badge bg-info">
                                Score: {{ $answer->score }}
                            </span>
                        </div>

                    </div>

                @endif

            </div>

        @endforeach

    </div>

</div>

@endsection