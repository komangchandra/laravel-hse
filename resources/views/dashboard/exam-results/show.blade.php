@extends('layouts.admin')
@section('title', 'Detail Hasil Ujian')
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div><h4 class="fw-bold mb-1">Detail Hasil Ujian</h4><div class="text-muted">{{ $attempt->referenceNumber() }}</div></div>
    <div class="d-flex gap-2"><a href="{{ route('dashboard.exam-results.index') }}" class="btn btn-outline-secondary">Kembali</a><a href="{{ route('dashboard.exam-results.pdf', $attempt) }}" target="_blank" class="btn btn-danger">Print PDF</a></div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3">
    <div class="col-md-4"><div class="p-3 border rounded bg-light"><small class="text-muted">Peserta</small><div class="fw-bold">{{ $attempt->participantValue('name') ?? '-' }}</div></div></div>
    <div class="col-md-4"><div class="p-3 border rounded bg-light"><small class="text-muted">NIK</small><div class="fw-bold">{{ $attempt->participantValue('nik') ?? '-' }}</div></div></div>
    <div class="col-md-4"><div class="p-3 border rounded bg-light"><small class="text-muted">Pengajuan</small><div class="fw-bold">{{ $attempt->referenceNumber() ?? '-' }}</div></div></div>
    <div class="col-md-3"><div class="p-3 border rounded text-center"><small class="text-muted">Score</small><div class="fs-4 fw-bold text-primary">{{ number_format($attempt->score, 2) }}</div></div></div>
    <div class="col-md-3"><div class="p-3 border rounded text-center"><small class="text-muted">Benar</small><div class="fs-4 fw-bold text-success">{{ $attempt->correct_answers }}</div></div></div>
    <div class="col-md-3"><div class="p-3 border rounded text-center"><small class="text-muted">Status</small><div><span class="badge {{ $attempt->is_passed ? 'bg-success' : 'bg-danger' }} px-3 py-2">{{ $attempt->is_passed ? 'LULUS' : 'TIDAK LULUS' }}</span></div></div></div>
    <div class="col-md-3"><div class="p-3 border rounded text-center"><small class="text-muted">Finalisasi</small><div class="small">{{ $attempt->finished_at }}<br>{{ $attempt->completion_reason }}</div></div></div>
    <div class="col-md-4"><div class="p-3 border rounded text-center"><small class="text-muted">Salah / Kosong</small><div class="fw-bold">{{ $attempt->wrong_answers }} / {{ $attempt->blank_answers }}</div></div></div>
    <div class="col-md-4"><div class="p-3 border rounded text-center"><small class="text-muted">Ambang Lulus</small><div class="fw-bold">{{ number_format($attempt->passing_score_snapshot, 2) }}%</div></div></div>
    <div class="col-md-4"><div class="p-3 border rounded text-center"><small class="text-muted">Versi Aturan</small><div class="fw-bold">{{ $attempt->scoring_rule_version ?? '-' }}</div></div></div>
</div></div></div>

<div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="mb-0 fw-bold">Review Jawaban dari Snapshot</h6></div><div class="card-body">
@forelse($attempt->questions->sortBy('order_no') as $attemptQuestion)
    @php($question = $attemptQuestion->question_snapshot)
    @php($answer = $attemptQuestion->answer)
    <div class="border rounded p-3 mb-3">
        <div class="mb-2"><strong>{{ $attemptQuestion->order_no }}. {!! nl2br(e($question['text'] ?? 'Snapshot soal lama tidak tersedia')) !!}</strong></div>
        @foreach(collect($attemptQuestion->options_snapshot) as $option)
            <div class="{{ (int) $option['id'] === (int) $answer?->answer_option_id ? 'fw-bold text-primary' : (($canViewAnswerKey && $option['is_correct']) ? 'text-success' : 'text-muted') }}">
                {{ $option['label'] }}. {{ $option['answer'] }}
                @if((int) $option['id'] === (int) $answer?->answer_option_id) — jawaban peserta @endif
                @if($canViewAnswerKey && $option['is_correct']) — jawaban benar @endif
            </div>
        @endforeach
        <div class="mt-2"><span class="badge {{ $answer?->is_correct ? 'bg-success' : 'bg-danger' }}">{{ $answer?->is_correct ? 'BENAR' : ($answer ? 'SALAH' : 'KOSONG') }}</span><span class="ms-2">Skor: {{ $answer?->score ?? 0 }}</span></div>
    </div>
@empty
    <p class="text-muted mb-0">Snapshot soal belum tersedia untuk attempt lama ini.</p>
@endforelse
</div></div>
@endsection
