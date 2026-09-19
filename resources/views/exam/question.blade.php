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
                                <span id="exam-timer">--:--</span>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            {{-- SOAL --}}
            <div class="card border-0 shadow-sm">

                @php
                    $question = $attemptQuestion->question_snapshot;
                    $options = collect($attemptQuestion->options_snapshot);
                    $selectedOptionId = $attemptQuestion->answer?->answer_option_id;
                @endphp

                <form method="POST"
                    action="{{ route('exam.save-answer', [
                        'attempt' => $attempt->id,
                        'number' => $number
                    ]) }}">

                    @csrf

                    <div class="card-body">

                        {{-- FOTO --}}
                        @if(!empty($question['photo_path']))

                            <div class="mb-4 text-center">

                                <img src="{{ route('exam.question-photo', $attemptQuestion) }}"
                                    class="img-fluid rounded border shadow-sm"
                                    style="max-height: 350px;">

                            </div>

                        @endif

                        {{-- PERTANYAAN --}}
                        <div class="mb-4">

                            <h5 class="fw-bold">
                                {!! nl2br(e($question['text'])) !!}
                            </h5>

                        </div>

                        {{-- PILIHAN GANDA --}}
                        @if($question['type'] === 'multiple_choice')

                            @foreach($options as $option)

                                <div class="form-check border rounded p-3 mb-2">

                                    <input class="form-check-input"
                                        type="radio"
                                        name="answer_option_id"
                                        id="option{{ $option['id'] }}"
                                        value="{{ $option['id'] }}"
                                        @checked((int) $selectedOptionId === (int) $option['id'])
                                        required>

                                    <label class="form-check-label w-100"
                                        for="option{{ $option['id'] }}">

                                        <strong>
                                            {{ $option['label'] }}.
                                        </strong>

                                        {{ $option['answer'] }}

                                    </label>

                                </div>

                            @endforeach

                        @endif

                        {{-- ESSAY --}}
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

                                {{ $number >= $attempt->total_questions ? 'Selesaikan Ujian' : 'Berikutnya' }}
                                <i class="bi bi-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                </form>

                <form id="timeout-finish" method="POST" action="{{ route('exam.finish', $attempt) }}" class="d-none">
                    @csrf
                </form>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deadline = new Date(@json($attempt->deadline_at?->toIso8601String())).getTime();
    const timer = document.getElementById('exam-timer');
    let submitted = false;
    const tick = function () {
        const remaining = Math.max(0, deadline - Date.now());
        const totalSeconds = Math.floor(remaining / 1000);
        timer.textContent = String(Math.floor(totalSeconds / 60)).padStart(2, '0') + ':' + String(totalSeconds % 60).padStart(2, '0');
        if (remaining <= 0 && !submitted) {
            submitted = true;
            document.getElementById('timeout-finish').submit();
        }
    };
    tick();
    window.setInterval(tick, 1000);
});
</script>
@endpush
