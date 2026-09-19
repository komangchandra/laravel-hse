@extends('layouts.admin')

@section('title', 'Detail Soal')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-0">
            Detail Soal
        </h4>

        <nav aria-label="breadcrumb">

            <ol class="breadcrumb mb-0">

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}"
                        class="text-decoration-none">

                        Dashboard

                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.questions.index') }}"
                        class="text-decoration-none">

                        Soal

                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Detail
                </li>

            </ol>

        </nav>

    </div>

    <a href="{{ route('dashboard.questions.index') }}"
        class="btn btn-sm btn-outline-secondary shadow-sm">

        <i class="bi bi-arrow-left me-1"></i>
        Kembali

    </a>

</div>

<div class="card border-0 shadow-sm rounded-3">

    <div class="card-body p-4">

        {{-- CATEGORY --}}
        <div class="mb-4">

            <label class="form-label fw-bold text-muted">
                Kategori Soal
            </label>

            <div class="fs-6">
                {{ $question->category->name }}
            </div>

        </div>

        {{-- QUESTION --}}
        <div class="mb-4">

            <label class="form-label fw-bold text-muted">
                Pertanyaan
            </label>

            <div class="fs-5 fw-semibold">
                {{ $question->question }}
            </div>

        </div>

        {{-- PHOTO --}}
        @if($question->photo_path)

            <div class="mb-4">

                <label class="form-label fw-bold text-muted">
                    Gambar Soal
                </label>

                <div>
                    <img src="{{ route('dashboard.questions.photo', $question) }}"
                        class="img-fluid rounded shadow-sm border"
                        style="max-height: 350px;">
                </div>

            </div>

        @endif

        <div class="row mb-4">

            {{-- TYPE --}}
            <div class="col-md-6">

                <label class="form-label fw-bold text-muted">
                    Tipe Soal
                </label>

                <div>

                    @if($question->type == 'multiple_choice')

                        <span class="badge bg-primary">
                            Pilihan Ganda
                        </span>

                    @elseif($question->type == 'essay_auto')

                        <span class="badge bg-success">
                            Essay Auto
                        </span>

                    @endif

                </div>

            </div>

            {{-- SCORE --}}
            <div class="col-md-6">

                <label class="form-label fw-bold text-muted">
                    Skor
                </label>

                <div class="fw-semibold">
                    {{ $question->score }}
                </div>

            </div>

        </div>

        {{-- MULTIPLE CHOICE --}}
        @if($question->type === 'multiple_choice')

            <hr>

            <h5 class="fw-bold mb-3 text-primary">
                Pilihan Jawaban
            </h5>

            <div class="list-group">

                @foreach($question->options as $option)

                    <div class="list-group-item d-flex justify-content-between align-items-center">

                        <div>
                            <strong>{{ $option->label }}.</strong>
                            {{ $option->answer }}
                        </div>

                        @if($option->is_correct)

                            <span class="badge bg-success">
                                Jawaban Benar
                            </span>

                        @endif

                    </div>

                @endforeach

            </div>

        @endif

        {{-- ESSAY --}}
        @if($question->type === 'essay_auto')

            <hr>

            <h5 class="fw-bold mb-3 text-success">
                Keyword Jawaban
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="70%">
                                Keyword
                            </th>

                            <th width="30%">
                                Skor
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($question->keywords as $keyword)

                            <tr>

                                <td>
                                    {{ $keyword->keyword }}
                                </td>

                                <td>
                                    {{ $keyword->score }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="2"
                                    class="text-center text-muted">

                                    Tidak ada keyword jawaban

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        @endif

    </div>

</div>

@endsection
