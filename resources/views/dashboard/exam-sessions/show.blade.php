@extends('layouts.admin')

@section('title', 'Detail Sesi Ujian')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-0">
            <i class="bi bi-eye me-2 text-info"></i>
            Detail Sesi Ujian
        </h4>

        <nav aria-label="breadcrumb">

            <ol class="breadcrumb mb-0">

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.exam-sessions.index') }}">
                        Sesi Ujian
                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Detail
                </li>

            </ol>

        </nav>

    </div>

    <div>

        @can('update', $examSession)
        <a href="{{ route('dashboard.exam-sessions.categories.create', $examSession) }}"
            class="btn btn-warning shadow-sm">

            <i class="bi bi-journal-check me-1"></i>
            Atur Kategori

        </a>

        <a href="{{ route('dashboard.exam-sessions.edit', $examSession) }}"
            class="btn btn-primary shadow-sm">

            <i class="bi bi-pencil-square me-1"></i>
            Edit

        </a>
        @endcan

    </div>

</div>

<div class="row">

    {{-- INFORMASI SESSION --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">

                <h6 class="mb-0 fw-bold">

                    <i class="bi bi-info-circle me-2"></i>
                    Informasi Session

                </h6>

            </div>

            <div class="card-body">

                <table class="table table-borderless mb-0">

                    <tr>
                        <td>Nama</td>
                        <td class="fw-semibold">
                            {{ $examSession->name }}
                        </td>
                    </tr>

                    <tr>
                        <td>Durasi</td>
                        <td>
                            {{ $examSession->duration }} Menit
                        </td>
                    </tr>

                    <tr>
                        <td>Passing Score</td>
                        <td>
                            <span class="badge bg-info">
                                {{ $examSession->passing_score }}%
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>Status</td>
                        <td>

                            @if($examSession->is_active)

                                <span class="badge bg-success">
                                    Aktif
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Nonaktif
                                </span>

                            @endif

                        </td>
                    </tr>

                    <tr>
                        <td>Dibuat</td>
                        <td>
                            {{ $examSession->created_at->format('d M Y H:i') }}
                        </td>
                    </tr>

                </table>

            </div>

        </div>

    </div>

    {{-- KATEGORI --}}
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">

                <h6 class="mb-0 fw-bold">

                    <i class="bi bi-list-check me-2"></i>
                    Kategori Soal

                </h6>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>Kategori</th>
                                <th width="180">Jumlah Soal</th>

                            </tr>

                        </thead>

                        <tbody>

                            @php
                                $totalQuestions = 0;
                            @endphp

                            @forelse($examSession->categories as $category)

                                @php
                                    $totalQuestions += $category->pivot->question_count;
                                @endphp

                                <tr>

                                    <td>

                                        <i class="bi bi-folder-fill text-warning me-2"></i>

                                        {{ $category->name }}

                                    </td>

                                    <td>

                                        <span class="badge bg-primary">

                                            {{ $category->pivot->question_count }}
                                            Soal

                                        </span>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="2"
                                        class="text-center py-4">

                                        <span class="text-muted">

                                            Belum ada kategori yang dipilih.

                                        </span>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                        @if($examSession->categories->count())

                        <tfoot class="table-light">

                            <tr>

                                <th>Total</th>

                                <th>

                                    <span class="badge bg-success">

                                        {{ $totalQuestions }}
                                        Soal

                                    </span>

                                </th>

                            </tr>

                        </tfoot>

                        @endif

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
