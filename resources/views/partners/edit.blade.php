@extends('layouts.admin')

@section('title', 'Ubah Mitra Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Mitra Kerja: {{ $partner->legal_name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.partners.index') }}" class="text-decoration-none">Mitra Kerja</a></li>
                <li class="breadcrumb-item active">{{ $partner->legal_name }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dashboard.partners.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.partners.update', $partner->id) }}">
            @csrf
            @method('PUT')

            <div class="row mb-4">
                {{-- Nama Mitra --}}
                <div class="col-md-6">
                    <label for="legal_name" class="form-label fw-bold">Nama Mitra Kerja</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <input
                            type="text"
                            name="legal_name"
                            id="legal_name"
                            class="form-control @error('legal_name') is-invalid @enderror"
                            placeholder="e.g. PT Gorby Putra Utama"
                            value="{{ old('legal_name', $partner->legal_name) }}"
                            required
                        >
                    </div>
                    @error('legal_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Akronim --}}
                <div class="col-md-6">
                    <label for="short_name" class="form-label fw-bold">Akronim</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <input
                            type="text"
                            name="short_name"
                            id="short_name"
                            class="form-control @error('short_name') is-invalid @enderror"
                            placeholder="e.g. GPU"
                            value="{{ old('short_name', $partner->short_name) }}"
                            required
                        >
                    </div>
                    @error('short_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                {{-- Email --}}
                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="e.g. info@gorbyputrautama.com"
                            value="{{ old('email', $partner->email) }}"
                            required
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="col-md-6">
                    <label for="status" class="form-label fw-bold">Status</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-info-circle"></i>
                        </span>
                        <select
                            name="status"
                            id="status"
                            class="form-select @error('status') is-invalid @enderror"
                            required
                        >
                            <option value="" disabled>Pilih status...</option>
                            @foreach (['Active', 'Inactive', 'Slowdown', 'Suspend'] as $status)
                                <option
                                    value="{{ $status }}"
                                    {{ old('status', $partner->status) == $status ? 'selected' : '' }}
                                >
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('status')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="level" class="form-label fw-bold">Jenis Mitra</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-info-circle"></i>
                        </span>
                        <select
                            name="level"
                            id="level"
                            class="form-select @error('level') is-invalid @enderror"
                            required
                        >
                            <option value="" disabled>Pilih jenis mitra...</option>

                            @foreach ($roles as $role)
                                <option
                                    value="{{ $role->name }}"
                                    {{ old('level', $partner->level) == $role->name ? 'selected' : '' }}
                                >
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('level')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Mitra Kerja
                </button>

                <a href="{{ route('dashboard.partners.index') }}"
                class="btn btn-light border px-4">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection