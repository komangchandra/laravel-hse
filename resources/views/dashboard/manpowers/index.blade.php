@extends('layouts.admin')

@section('title', 'Manajemen Manpower')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Manpower</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active">Manpower</li>
            </ol>
        </nav>
    </div>

    <a href="{{ route('dashboard.manpowers.create') }}" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Manpower
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
                <h6 class="mb-0 fw-bold text-dark">Daftar Manpower</h6>
            </div>
            <div class="col-auto">
                <form action="{{ route('dashboard.manpowers.index') }}" method="GET" class="d-flex">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control border-end-0" 
                               placeholder="Cari manpower..." value="{{ request('search') }}">
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
                        <th class="ps-4">Foto</th>
                        <th class="ps-4">NIK</th>
                        <th class="ps-4">Nama</th>
                        <th class="ps-4">Mitra</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($manpowers as $manpower)
                    <tr>

                        {{-- FOTO --}}
                        <td class="ps-4">

                            @if($manpower->photo_path)

                                <img src="{{ asset('storage/' . $manpower->photo_path) }}"
                                    alt="{{ $manpower->name }}"
                                    class="rounded-circle border object-fit-cover"
                                    width="50"
                                    height="50">

                            @else

                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border"
                                    style="width:50px; height:50px;">

                                    <i class="bi bi-person text-muted"></i>

                                </div>

                            @endif

                        </td>

                        {{-- NIK --}}
                        <td class="ps-4 align-middle">

                            <span class="fw-semibold text-dark">
                                {{ $manpower->nik ?? '-' }}
                            </span>

                        </td>

                        {{-- NAMA --}}
                        <td class="ps-4 align-middle">

                            <div class="d-flex flex-column">

                                <span class="fw-bold text-dark">
                                    {{ $manpower->name }}
                                </span>

                                <small class="text-muted">
                                    {{ $manpower->contact_number }}
                                </small>

                            </div>

                        </td>

                        {{-- MITRA --}}
                        <td class="ps-4 align-middle">

                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">

                                <i class="bi bi-buildings me-1"></i>

                                {{ $manpower->partner->short_name ?? '-' }}

                            </span>

                        </td>

                        {{-- ACTION --}}
                        <td class="text-end pe-4 align-middle">

                            <div class="btn-group shadow-sm">

                                @role(['developer','super-admin','owner'])

                                <a href="{{ route('dashboard.manpowers.edit', $manpower) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-pencil-square text-primary me-1"></i>
                                    Edit

                                </a>

                                @endrole

                                @role('developer')

                                <form method="POST"
                                    action="{{ route('dashboard.manpowers.destroy', $manpower) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus manpower ini?');">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="btn btn-sm btn-white border">

                                        <i class="bi bi-trash text-danger"></i>

                                    </button>

                                </form>

                                @endrole

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="5" class="text-center py-5">

                            <div class="d-flex flex-column align-items-center">

                                <i class="bi bi-people text-muted"
                                    style="font-size: 3rem;"></i>

                                <p class="text-muted mt-3 mb-0">
                                    Tidak ada manpower ditemukan.
                                </p>

                            </div>

                        </td>
                    </tr>

                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($manpowers->hasPages())
    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">
        {!! $manpowers->links() !!}
    </div>
    @endif
</div>
@endsection