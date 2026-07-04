@extends('layouts.admin')

@section('title', 'Hasil Ujian SIMPER')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="fw-bold mb-0">Hasil Ujian SIMPER</h4>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Hasil Ujian</li>
            </ol>
        </nav>
    </div>

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
                <h6 class="mb-0 fw-bold text-dark">Daftar Hasil Ujian</h6>
            </div>

            <div class="col-auto">

                <form action="{{ route('dashboard.exam-results.index') }}" method="GET" class="d-flex">

                    <div class="input-group input-group-sm">

                        <input type="text"
                            name="search"
                            class="form-control border-end-0"
                            placeholder="Cari nama / NIK / simper..."
                            value="{{ request('search') }}">

                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted"
                            type="submit">

                            <i class="bi bi-search"></i>

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- TABLE --}}
    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>
                        <th class="ps-4">Peserta</th>
                        <th>SIMPER</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Waktu</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($attempts as $attempt)

                    <tr>

                        {{-- PESERTA --}}
                        <td class="ps-4">

                            <div class="d-flex flex-column">

                                <span class="fw-bold text-dark">
                                    {{ $attempt->simper->manpower->name ?? '-' }}
                                </span>

                                <small class="text-muted">
                                    NIK: {{ $attempt->simper->manpower->nik ?? '-' }}
                                </small>

                            </div>

                        </td>

                        {{-- SIMPER --}}
                        <td>

                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">

                                <i class="bi bi-person-badge me-1"></i>

                                {{ $attempt->simper->code ?? '-' }}

                            </span>

                        </td>

                        {{-- SCORE --}}
                        <td class="text-center">

                            <span class="fw-bold text-dark">
                                {{ number_format($attempt->score, 2) }}
                            </span>

                        </td>

                        {{-- STATUS --}}
                        <td class="text-center">

                            @if($attempt->is_passed)

                                <span class="badge bg-success px-3 py-2">
                                    LULUS
                                </span>

                            @else

                                <span class="badge bg-danger px-3 py-2">
                                    TIDAK LULUS
                                </span>

                            @endif

                        </td>

                        {{-- WAKTU --}}
                        <td class="text-center">

                            <small class="text-muted d-block">
                                {{ $attempt->finished_at ? \Carbon\Carbon::parse($attempt->finished_at)->format('d M Y H:i') : '-' }}
                            </small>

                        </td>

                        {{-- ACTION --}}
                        <td class="text-end pe-4">

                            <a href="{{ route('dashboard.exam-results.show', $attempt) }}"
                                class="btn btn-sm btn-outline-primary">

                                <i class="bi bi-eye me-1"></i>
                                Detail

                            </a>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="6" class="text-center py-5">

                            <div class="d-flex flex-column align-items-center">

                                <i class="bi bi-clipboard-x text-muted"
                                    style="font-size: 3rem;"></i>

                                <p class="text-muted mt-3 mb-0">
                                    Belum ada hasil ujian.
                                </p>

                            </div>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- PAGINATION --}}
    @if($attempts->hasPages())

    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">

        {!! $attempts->links() !!}

    </div>

    @endif

</div>

@endsection