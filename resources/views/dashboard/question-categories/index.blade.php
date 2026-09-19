@extends('layouts.admin')

@section('title', 'Manajemen Kategori Soal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Kategori Soal</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active">Kategori Soal</li>
            </ol>
        </nav>
    </div>

    @can('create', App\Models\QuestionCategory::class)
    <a href="{{ route('dashboard.question-categories.create') }}" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori Soal
    </a>
    @endcan

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
                <h6 class="mb-0 fw-bold text-dark">Daftar Kategori Soal</h6>
            </div>
            <div class="col-auto">
                <form action="{{ route('dashboard.question-categories.index') }}" method="GET" class="d-flex">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control border-end-0" 
                               placeholder="Cari kategori soal..." value="{{ request('search') }}">
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
                        <th class="ps-4">Nama Kategori</th>
                        <th class="ps-4">Deskripsi</th>
                        <th class="ps-4">Aspek Diukur</th>
                        <th class="ps-4">Aspek Terukur</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questionCategories as $questionCategory)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark">{{ $questionCategory->name }}</span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark">{{ $questionCategory->description }}</span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="text-dark">{{ $questionCategory->measured }}</span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="text-dark">{{ $questionCategory->measurable }}</span>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm">
                                @can('update', $questionCategory)
                                <a href="{{ route('dashboard.question-categories.edit', $questionCategory) }}" class="btn btn-sm btn-white border border-end-0">
                                    <i class="bi bi-pencil-square text-primary me-1"></i> Edit
                                </a>
                                @endcan
                                  
                                @can('delete', $questionCategory)
                                <form method="POST" action="{{ route('dashboard.question-categories.destroy', $questionCategory) }}" 
                                      class="d-inline" 
                                      onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-white border">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <p class="text-muted mb-0">Tidak ada kategori pertanyaan yang ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($questionCategories->hasPages())
    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">
        {!! $questionCategories->links() !!}
    </div>
    @endif
</div>
@endsection
