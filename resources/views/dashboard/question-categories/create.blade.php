@extends('layouts.admin')

@section('title', 'Buat Kategori Soal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Buat Kategori Soal</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.question-categories.index') }}" class="text-decoration-none">Kategori Soal</a></li>
                <li class="breadcrumb-item active">Buat Baru</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dashboard.question-categories.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.question-categories.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">Nama Kategori</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <input type="text" name="name" id="name" 
                               class="form-control border-start-0 @error('name') is-invalid @enderror" 
                               placeholder="e.g. Kategori Soal 1"
                               value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="description" class="form-label fw-bold">Deskripsi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <textarea name="description" id="description" 
                               class="form-control border-start-0 @error('description') is-invalid @enderror" 
                               placeholder="e.g. Deskripsi kategori soal"
                               required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="measured" class="form-label fw-bold">Aspek yang Diukur</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <textarea name="measured" id="measured" 
                               class="form-control border-start-0 @error('measured') is-invalid @enderror" 
                               placeholder="e.g. Aspek yang diukur"
                               required>{{ old('measured') }}</textarea>
                        @error('measured')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="measurable" class="form-label fw-bold">Aspek Terukur</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <textarea name="measurable" id="measurable" 
                               class="form-control border-start-0 @error('measurable') is-invalid @enderror" 
                               placeholder="e.g. Aspek yang dapat diukur"
                               required>{{ old('measurable') }}</textarea>
                        @error('measurable')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-success px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
                <a href="{{ route('dashboard.manpowers.index') }}" class="btn btn-light border px-4">Batal</a>
            </div>
        </form>
    </div>
</div>

@endsection