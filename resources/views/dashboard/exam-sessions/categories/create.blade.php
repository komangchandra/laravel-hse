@extends('layouts.admin')

@section('title', 'Kategori Sesi Ujian')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-0">
            <i class="bi bi-journal-check me-2 text-warning"></i>
            Atur Kategori Soal
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

                    <a href="{{ route('dashboard.exam-sessions.index') }}"
                        class="text-decoration-none">

                        Sesi Ujian

                    </a>

                </li>

                <li class="breadcrumb-item active">

                    Kategori Soal

                </li>

            </ol>

        </nav>

    </div>

    <a href="{{ route('dashboard.exam-sessions.index') }}"
        class="btn btn-light shadow-sm">

        <i class="bi bi-arrow-left me-1"></i>
        Kembali

    </a>

</div>

<div class="alert alert-info border-0 shadow-sm">

    <i class="bi bi-info-circle-fill me-2"></i>

    Mengatur kategori soal untuk sesi:

    <strong>{{ $examSession->name }}</strong>

</div>

<form action="{{ route('dashboard.exam-sessions.categories.store', $examSession) }}"
    method="POST">

    @csrf

    <div class="card border-0 shadow-sm rounded-3">

        <div class="card-header bg-white border-bottom">

            <div class="d-flex justify-content-between align-items-center">

                <h6 class="mb-0 fw-bold">

                    <i class="bi bi-list-check me-2 text-primary"></i>
                    Pilih Kategori Soal

                </h6>

                <span class="badge bg-secondary">

                    {{ $categories->count() }} Kategori

                </span>

            </div>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th width="80" class="text-center">
                                Pilih
                            </th>

                            <th>
                                Kategori
                            </th>

                            <th width="220">
                                Jumlah Soal
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($categories as $category)

                            @php
                                $questionCount = $selectedCategories[$category->id] ?? null;
                            @endphp

                            <tr>

                                <td class="text-center">

                                    <input type="checkbox"
                                        class="form-check-input"
                                        name="categories[{{ $category->id }}][selected]"
                                        value="1"
                                        {{ $questionCount !== null ? 'checked' : '' }}>

                                </td>

                                <td>

                                    <div class="fw-semibold">

                                        <i class="bi bi-folder-fill text-warning me-2"></i>
                                        {{ $category->name }}

                                    </div>

                                </td>

                                <td>

                                    <input type="number"
                                        min="1"
                                        class="form-control"
                                        placeholder="Jumlah soal"
                                        name="categories[{{ $category->id }}][question_count]"
                                        value="{{ $questionCount }}">

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer bg-white text-end">

            <button type="submit"
                class="btn btn-primary shadow-sm">

                <i class="bi bi-save me-1"></i>
                Simpan Kategori

            </button>

        </div>

    </div>

</form>

@endsection