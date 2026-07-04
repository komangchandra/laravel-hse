@extends('layouts.exam')

@section('title', 'Hasil Ujian')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center p-5">

                    {{-- STATUS --}}
                    <div class="mb-3">

                        @if($attempt->is_passed)
                            <span class="badge bg-success fs-6 px-3 py-2">
                                LULUS
                            </span>
                        @else
                            <span class="badge bg-danger fs-6 px-3 py-2">
                                TIDAK LULUS
                            </span>
                        @endif

                    </div>

                    <h3 class="mb-2">Ujian Selesai</h3>

                    <p class="text-muted mb-4">
                        Terima kasih sudah menyelesaikan ujian SIMPER
                    </p>

                    <hr>

                    {{-- RINGKASAN HASIL --}}
                    <div class="row text-center mb-4">

                        <div class="col-md-4">
                            <h6 class="text-muted">Total Soal</h6>
                            <h4>{{ $attempt->total_questions }}</h4>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted">Jawaban Benar</h6>
                            <h4 class="text-success">{{ $correctAnswers }}</h4>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted">Jawaban Salah</h6>
                            <h4 class="text-danger">{{ $wrongAnswers }}</h4>
                        </div>

                    </div>

                    <div class="mb-4">

                        <h6 class="text-muted">Skor Akhir</h6>

                        <h1 class="text-primary fw-bold">
                            {{ number_format($score, 2) }}
                        </h1>

                    </div>

                    <hr>

                    {{-- INFO TAMBAHAN --}}
                    <div class="text-muted small mb-4">

                        <div>
                            Mulai: {{ $attempt->started_at }}
                        </div>

                        <div>
                            Selesai: {{ $attempt->finished_at }}
                        </div>

                    </div>

                    {{-- BUTTON --}}
                    <a href="{{ route('exam.login') }}"
                        class="btn btn-primary px-4">

                        Selesai
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection