@extends('layouts.admin')

@section('title', 'Manajemen Simper')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="fw-bold mb-0">Daftar Simper</h4>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}"
                        class="text-decoration-none">

                        Dashboard

                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Daftar Simper
                </li>

            </ol>
        </nav>
    </div>

    <a href="{{ route('dashboard.simpers.create') }}"
        class="btn btn-primary shadow-sm">

        <i class="bi bi-plus-lg me-1"></i>
        Tambah Simper

    </a>

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
                <h6 class="mb-0 fw-bold text-dark">
                    Daftar Simper
                </h6>
            </div>

            <div class="col-auto">

                <form action="{{ route('dashboard.simpers.index') }}"
                    method="GET"
                    class="d-flex">

                    <div class="input-group input-group-sm">

                        <input type="text"
                            name="search"
                            class="form-control border-end-0"
                            placeholder="Cari manpower / code..."
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

    {{-- BODY --}}
    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>

                        <th class="ps-4">
                            Code
                        </th>

                        <th>
                            Manpower
                        </th>

                        <th>
                            Partner
                        </th>

                        <th>
                            Kategori
                        </th>

                        <th>
                            Dibuat
                        </th>

                        <th class="text-end pe-4">
                            Actions
                        </th>

                    </tr>
                </thead>

                <tbody>

                    @forelse($simpers as $simper)

                    <tr>

                        {{-- CODE --}}
                        <td class="ps-4">

                            <span class="fw-bold text-dark">
                                {{ $simper->code }}
                            </span>

                        </td>

                        {{-- MANPOWER --}}
                        <td>

                            <div class="fw-semibold">
                                {{ $simper->manpower->name ?? '-' }}
                            </div>

                        </td>

                        {{-- PARTNER --}}
                        <td>

                            <span class="text-muted">
                                {{ $simper->partner->short_name ?? '-' }}
                            </span>

                        </td>

                        {{-- CATEGORY --}}
                        <td>

                            @forelse($simper->categories as $category)

                                <div class="mb-1">

                                    <span class="badge bg-primary">

                                        {{ $category->name }}

                                    </span>

                                    <span class="badge bg-light text-dark border">

                                        {{ strtoupper($category->pivot->level) }}

                                    </span>

                                </div>

                            @empty

                                <span class="text-muted">
                                    Tidak ada kategori
                                </span>

                            @endforelse

                        </td>

                        {{-- CREATED --}}
                        <td>

                            <span class="text-muted">
                                {{ $simper->created_at->format('d M Y') }}
                            </span>

                        </td>

                        {{-- ACTION --}}
                        <td class="text-end pe-4">

                            <div class="btn-group shadow-sm">

                                {{-- DETAIL --}}
                                <a href="{{ route('dashboard.simpers.show', $simper) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-eye text-info"></i>

                                </a>

                                {{-- EDIT --}}
                                <a href="{{ route('dashboard.simpers.edit', $simper) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-pencil-square text-primary"></i>

                                </a>

                                {{-- DELETE --}}
                                <form method="POST"
                                    action="{{ route('dashboard.simpers.destroy', $simper) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus simper ini?');">

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

                        <td colspan="6"
                            class="text-center py-5">

                            <p class="text-muted mb-0">
                                Tidak ada data simper ditemukan.
                            </p>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- PAGINATION --}}
    @if($simpers->hasPages())

    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">

        {!! $simpers->links() !!}

    </div>

    @endif

</div>

@endsection
