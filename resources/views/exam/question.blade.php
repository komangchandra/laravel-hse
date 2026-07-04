@extends('layouts.exam')

@section('title', 'Ujian SIMPER')

@section('content')

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-10">

            {{-- HEADER --}}
            <div class="card border-0 shadow-sm mb-3">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h5 class="mb-1">
                                Soal {{ $number }}
                                dari
                                {{ $attempt->total_questions }}
                            </h5>

                            <small class="text-muted">
                                Status: {{ ucfirst($attempt->status) }}
                            </small>

                        </div>

                        <div>

                            <span class="badge bg-danger fs-6">
                                <i class="bi bi-clock me-1"></i>
                                Timer
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            {{-- SOAL --}}
            <div class="card border-0 shadow-sm">

                @php
                    $question = $attemptQuestion->question;
                @endphp

                <form method="POST"
                    action="{{ route('exam.save-answer', [
                        'attempt' => $attempt->id,
                        'number' => $number
                    ]) }}">

                    @csrf

                    <div class="card-body">

                        {{-- FOTO --}}
                        @if($question->photo_path)

                            <div class="mb-4 text-center">

                                <img src="{{ asset('storage/' . $question->photo_path) }}"
                                    class="img-fluid rounded border shadow-sm"
                                    style="max-height: 350px;">

                            </div>

                        @endif

                        {{-- PERTANYAAN --}}
                        <div class="mb-4">

                            <h5 class="fw-bold">
                                {!! nl2br(e($question->question)) !!}
                            </h5>

                        </div>

                        {{-- PILIHAN GANDA --}}
                        @if($question->type == 'multiple_choice')

                            @foreach($question->options as $option)

                                <div class="form-check border rounded p-3 mb-2">

                                    <input class="form-check-input"
                                        type="radio"
                                        name="answer_option_id"
                                        id="option{{ $option->id }}"
                                        value="{{ $option->id }}"
                                        required>

                                    <label class="form-check-label w-100"
                                        for="option{{ $option->id }}">

                                        <strong>
                                            {{ $option->label }}.
                                        </strong>

                                        {{ $option->answer }}

                                    </label>

                                </div>

                            @endforeach

                        @endif

                        {{-- ESSAY --}}
                        @if($question->type == 'essay_auto')

                            <div class="mb-3">

                                <textarea class="form-control"
                                    rows="6"
                                    name="essay_answer"
                                    placeholder="Tulis jawaban Anda..."></textarea>

                            </div>

                        @endif

                    </div>

                    {{-- FOOTER --}}
                    <div class="card-footer bg-white">

                        <div class="d-flex justify-content-between">

                            <button type="button"
                                class="btn btn-outline-secondary"
                                disabled>

                                <i class="bi bi-arrow-left"></i>
                                Sebelumnya

                            </button>

                            <button type="submit"
                                class="btn btn-primary">

                                Berikutnya
                                <i class="bi bi-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection