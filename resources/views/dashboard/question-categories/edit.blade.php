@extends('layouts.admin')

@section('title', 'Ubah Kategori Soal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Kategori Soal: {{ $questionCategory->name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.question-categories.index') }}" class="text-decoration-none">Kategori Soal</a></li>
                <li class="breadcrumb-item active">{{ $questionCategory->name }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dashboard.question-categories.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.question-categories.update', $questionCategory->id) }}">
            @csrf
            @method('PUT')

            <div class="row mb-4">
                {{-- Nama Kategori --}}
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">Nama Kategori</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="e.g. PT Gorby Putra Utama"
                            value="{{ old('name', $questionCategory->name) }}"
                            required
                        >
                    </div>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Deskripsi --}}
                <div class="col-md-6">
                    <label for="description" class="form-label fw-bold">Deskripsi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <textarea
                            type="text"
                            name="description"
                            id="description"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="e.g. Deskripsi kategori soal"
                            required
                        >{{ old('description', $questionCategory->description) }}</textarea>
                    </div>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                {{-- measured --}}
                <div class="col-md-6">
                    <label for="measured" class="form-label fw-bold">Aspek yang Diukur</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-graph-up"></i>
                        </span>
                        <textarea
                            type="text"
                            name="measured"
                            id="measured"
                            class="form-control @error('measured') is-invalid @enderror"
                            placeholder="e.g. Aspek yang diukur"
                            required
                        >{{ old('measured', $questionCategory->measured) }}</textarea>
                    </div>
                    @error('measured')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="col-md-6">
                    <label for="measurable" class="form-label fw-bold">Aspek Terukur</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-check-circle"></i>
                        </span>
                        <textarea
                            type="text"
                            name="measurable"
                            id="measurable"
                            class="form-control @error('measurable') is-invalid @enderror"
                            placeholder="e.g. Aspek yang terukur"
                            required
                        >{{ old('measurable', $questionCategory->measurable) }}</textarea>
                    </div>
                    @error('measurable')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Kategori Soal
                </button>

                <a href="{{ route('dashboard.question-categories.index') }}"
                class="btn btn-light border px-4">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection