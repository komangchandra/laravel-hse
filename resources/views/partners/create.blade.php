@extends('layouts.admin')

@section('title', 'Buat Mitra Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Buat Mitra Kerja</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.partners.index') }}" class="text-decoration-none">Mitra Kerja</a></li>
                <li class="breadcrumb-item active">Buat Baru</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dashboard.partners.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.partners.store') }}">
            @csrf

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="legal_name" class="form-label fw-bold">Nama Mitra Kerja</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <input type="text" name="legal_name" id="legal_name" 
                               class="form-control border-start-0 @error('legal_name') is-invalid @enderror" 
                               placeholder="e.g. PT Gorby Putra Utama"
                               value="{{ old('legal_name') }}" required>
                        @error('legal_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="short_name" class="form-label fw-bold">Akronim</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <input type="text" name="short_name" id="short_name" 
                               class="form-control border-start-0 @error('short_name') is-invalid @enderror" 
                               placeholder="e.g. GPU"
                               value="{{ old('short_name') }}" required>
                        @error('short_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" 
                               class="form-control border-start-0 @error('email') is-invalid @enderror" 
                               placeholder="e.g. info@gorbyputrautama.com"
                               value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label fw-bold">Status</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-info-circle"></i>
                        </span>
                        <select name="status" id="status" class="form-control border-start-0 @error('status') is-invalid @enderror" required>
                            <option value="" disabled selected>Pilih status...</option>
                            <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="Slowdown" {{ old('status') == 'Slowdown' ? 'selected' : '' }}>Slowdown</option>
                            <option value="Suspend" {{ old('status') == 'Suspend' ? 'selected' : '' }}>Suspend</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="level" class="form-label fw-bold">Jenis Mitra</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-info-circle"></i>
                        </span>
                        <select name="level" id="level" class="form-control border-start-0 @error('level') is-invalid @enderror" required>
                            <option value="" disabled selected>Pilih jenis mitra...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('level') == $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-success px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
                <a href="{{ route('dashboard.partners.index') }}" class="btn btn-light border px-4">Batal</a>
            </div>
        </form>
    </div>
</div>

@endsection