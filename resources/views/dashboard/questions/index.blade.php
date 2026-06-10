@extends('layouts.admin')

@section('title', 'Manajemen Soal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Daftar Soal</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active">Daftar Soal</li>
            </ol>
        </nav>
    </div>

    <a href="{{ route('dashboard.questions.create') }}" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Soal
    </a>

</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session("success") }}
    </div>
@endif

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom border-light">
        <div class="row align-items-center g-2">
            <div class="col">
                <h6 class="mb-0 fw-bold text-dark">Daftar Soal</h6>
            </div>
            <div class="col-auto">
                <form action="{{ route('dashboard.questions.index') }}" method="GET" class="d-flex">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control border-end-0" 
                               placeholder="Cari soal..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Kategori Soal</th>
                        <th class="ps-4">Pertanyaan</th>
                        <th class="ps-4">Tipe</th>
                        <th class="ps-4">Kunci Jawaban</th>
                        <th class="ps-4">Skor</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($questions as $question)
                    <tr>
                        {{-- CATEGORY --}}
                        <td class="ps-4">
                            <span class="fw-bold text-dark">
                                {{ $question->category->name }}
                            </span>
                        </td>

                        {{-- PERTANYAAN --}}
                        <td class="ps-4" style="min-width:250px">
                            <div class="d-flex flex-column">

                                <span class="fw-bold text-dark">
                                    {{ $question->question }}
                                </span>

                                @if($question->photo_path)
                                    <img src="{{ asset('storage/' . $question->photo_path) }}"
                                        class="img-thumbnail mt-2"
                                        style="max-height:120px; width:auto;">
                                @endif

                            </div>
                        </td>

                        {{-- TYPE --}}
                        <td class="ps-4">
                            <span class="badge bg-info text-dark">
                                {{ $question->type }}
                            </span>
                        </td>

                        {{-- KUNCI JAWABAN --}}
                        <td class="ps-4" style="min-width:250px">

                            {{-- MULTIPLE CHOICE --}}
                            @if($question->type === 'multiple_choice')

                                @php
                                    $correct = $question->options
                                        ->where('is_correct', true)
                                        ->first();
                                @endphp

                                @if($correct)
                                    <div class="small">
                                        <span class="badge bg-success">
                                            {{ $correct->label }}
                                        </span>

                                        {{ $correct->answer }}
                                    </div>
                                @else
                                    <span class="text-muted">
                                        Tidak ada jawaban benar
                                    </span>
                                @endif

                            @endif

                            {{-- ESSAY AUTO --}}
                            @if($question->type === 'essay_auto')

                                @if($question->keywords->count())

                                    <div class="d-flex flex-column gap-1">

                                        @foreach($question->keywords as $keyword)

                                            <div>
                                                <span class="badge bg-primary">
                                                    {{ $keyword->score }}
                                                </span>

                                                {{ $keyword->keyword }}
                                            </div>

                                        @endforeach

                                    </div>

                                @else

                                    <span class="text-muted">
                                        Tidak ada keyword
                                    </span>

                                @endif

                            @endif

                        </td>

                        {{-- SCORE --}}
                        <td class="ps-4">
                            <span class="text-dark">
                                {{ $question->score }}
                            </span>
                        </td>

                        {{-- ACTION --}}
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm">

                            {{-- SHOW --}}
                                <a href="{{ route('dashboard.questions.show', $question) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-eye text-info me-1"></i>
                                    Lihat

                                </a>

                                <a href="{{ route('dashboard.questions.edit', $question) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-pencil-square text-primary me-1"></i>
                                    Edit

                                </a>

                                <form method="POST"
                                    action="{{ route('dashboard.questions.destroy', $question) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus pertanyaan ini?');">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="btn btn-sm btn-white border">

                                        <i class="bi bi-trash text-danger"></i>

                                    </button>

                                </form>

                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <p class="text-muted mb-0">
                                Tidak ada soal yang ditemukan.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($questions->hasPages())
    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">
        {!! $questions->links() !!}
    </div>
    @endif
</div>
@endsection