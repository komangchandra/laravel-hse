@extends('layouts.admin')

@section('title', 'Manajemen Kategori Simper')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Daftar Kategori Simper</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active">Daftar Kategori Simper</li>
            </ol>
        </nav>
    </div>

    <a href="{{ route('dashboard.simper-categories.create') }}" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
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
                <h6 class="mb-0 fw-bold text-dark">Daftar Kategori Simper</h6>
            </div>
            <div class="col-auto">
                <form action="{{ route('dashboard.simper-categories.index') }}" method="GET" class="d-flex">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control border-end-0" 
                               placeholder="Cari kategori..." value="{{ request('search') }}">
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
                        <th class="ps-4">Kategori Simper</th>
                        <th class="ps-4">Deskripsi</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($simperCategories as $simperCategory)
                    <tr>
                        {{-- CATEGORY --}}
                        <td class="ps-4">
                            <span class="fw-bold text-dark">
                                {{ $simperCategory->name }}
                            </span>
                        </td>

                        {{-- DESCRIPTION --}}
                        <td class="ps-4">
                            <span class="text-muted">
                                {{ $simperCategory->description ?? 'Tidak ada deskripsi' }}
                            </span>
                        </td>

                        {{-- ACTION --}}
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm">
                                <a href="{{ route('dashboard.simper-categories.edit', $simperCategory) }}"
                                    class="btn btn-sm btn-white border border-end-0">

                                    <i class="bi bi-pencil-square text-primary me-1"></i>
                                    Edit

                                </a>

                                <form method="POST"
                                    action="{{ route('dashboard.simper-categories.destroy', $simperCategory) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">

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
                        <td colspan="3" class="text-center py-5">
                            <p class="text-muted mb-0">
                                Tidak ada kategori yang ditemukan.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($simperCategories->hasPages())
    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">
        {!! $simperCategories->links() !!}
    </div>
    @endif
</div>
@endsection