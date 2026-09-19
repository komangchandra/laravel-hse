@extends('layouts.admin')

@section('title', 'Sesi Ujian')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="fw-bold mb-0">Sesi Ujian</h4>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">
                        Dashboard
                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Sesi Ujian
                </li>

            </ol>
        </nav>
    </div>

    @can('create', App\Models\ExamSession::class)
    <a href="{{ route('dashboard.exam-sessions.create') }}"
        class="btn btn-primary shadow-sm">

        <i class="bi bi-plus-lg me-1"></i>
        Tambah Sesi

    </a>
    @endcan

</div>

@if (session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif

<div class="card border-0 shadow-sm rounded-3">

    {{-- HEADER --}}
    <div class="card-header bg-white py-3 border-bottom border-light">

        <div class="row align-items-center g-2">

            <div class="col">
                <h6 class="mb-0 fw-bold">
                    Daftar Sesi Ujian
                </h6>
            </div>

            <div class="col-auto">

                <form action="{{ route('dashboard.exam-sessions.index') }}"
                    method="GET">

                    <div class="input-group input-group-sm">

                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Cari nama sesi..."
                            value="{{ request('search') }}">

                        <button class="btn btn-outline-secondary"
                            type="submit">

                            <i class="bi bi-search"></i>

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- BODY --}}
    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th class="ps-4">
                            Nama Sesi
                        </th>

                        <th>
                            Durasi
                        </th>

                        <th>
                            Passing Score
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Dibuat
                        </th>

                        <th class="text-end pe-4">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($examSessions as $session)

                    <tr>

                        {{-- NAMA --}}
                        <td class="ps-4">

                            <div class="fw-semibold">
                                {{ $session->name }}
                            </div>

                            @if($session->description)

                                <small class="text-muted">
                                    {{ Str::limit($session->description, 80) }}
                                </small>

                            @endif

                        </td>

                        {{-- DURASI --}}
                        <td>
                            {{ $session->duration }} Menit
                        </td>

                        {{-- PASSING SCORE --}}
                        <td>

                            <span class="badge bg-info">
                                {{ $session->passing_score }}%
                            </span>

                        </td>

                        {{-- STATUS --}}
                        <td>

                            @if($session->is_active)

                                <span class="badge bg-success">
                                    Aktif
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Nonaktif
                                </span>

                            @endif

                        </td>

                        {{-- CREATED --}}
                        <td>

                            <span class="text-muted">
                                {{ $session->created_at->format('d M Y') }}
                            </span>

                        </td>

                        {{-- ACTION --}}
                        <td class="text-end pe-4">

                            <div class="btn-group shadow-sm">

                                {{-- DETAIL --}}
                                <a href="{{ route('dashboard.exam-sessions.show', $session) }}"
                                    class="btn btn-sm btn-white border border-end-0">
                                    <i class="bi bi-eye text-info"></i>
                                </a>

                                @can('update', $session)
                                {{-- KATEGORI --}}
                                <a href="{{ route('dashboard.exam-sessions.categories.create', $session) }}"
                                    class="btn btn-sm btn-white border border-end-0"
                                    title="Atur Kategori">

                                    <i class="bi bi-journal-check text-warning"></i>

                                </a>

                                {{-- EDIT --}}
                                <a href="{{ route('dashboard.exam-sessions.edit', $session) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-pencil-square text-primary"></i>

                                </a>
                                @endcan

                                @can('delete', $session)
                                {{-- DELETE --}}
                                <form method="POST"
                                    action="{{ route('dashboard.exam-sessions.destroy', $session) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus sesi ujian ini?');">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="btn btn-sm btn-white border">

                                        <i class="bi bi-trash text-danger"></i>

                                    </button>

                                </form>
                                @endcan

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="6"
                            class="text-center py-5">

                            <p class="text-muted mb-0">
                                Belum ada sesi ujian.
                            </p>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- PAGINATION --}}
    @if($examSessions->hasPages())

        <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">

            {{ $examSessions->links() }}

        </div>

    @endif

</div>

@endsection
