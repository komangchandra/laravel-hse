@extends('layouts.admin')

@section('title', 'Buat Manpower')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Buat Manpower</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.manpowers.index') }}" class="text-decoration-none">Manpower</a></li>
                <li class="breadcrumb-item active">Buat Baru</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dashboard.manpowers.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.manpowers.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row mb-4">
                {{-- NIK --}}
                <div class="col-md-6">

                    <label for="nik" class="form-label fw-bold">
                        NIK
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-person-vcard"></i>
                        </span>

                        <input type="text"
                            name="nik"
                            id="nik"
                            class="form-control border-start-0 @error('nik') is-invalid @enderror"
                            placeholder="e.g. 1234567890"
                            value="{{ old('nik') }}">

                    </div>

                    @error('nik')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- NAMA --}}
                <div class="col-md-6">

                    <label for="name" class="form-label fw-bold">
                        Nama Manpower <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-person"></i>
                        </span>

                        <input type="text"
                            name="name"
                            id="name"
                            class="form-control border-start-0 @error('name') is-invalid @enderror"
                            placeholder="e.g. John Doe"
                            value="{{ old('name') }}"
                            required>

                    </div>

                    @error('name')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            <div class="row mb-4">

                {{-- CONTACT --}}
                <div class="col-md-6">

                    <label for="contact_number" class="form-label fw-bold">
                        Nomor Kontak <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-telephone"></i>
                        </span>

                        <input type="text"
                            name="contact_number"
                            id="contact_number"
                            class="form-control border-start-0 @error('contact_number') is-invalid @enderror"
                            placeholder="e.g. 08123456789"
                            value="{{ old('contact_number') }}"
                            required>

                    </div>

                    @error('contact_number')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- BLOOD TYPE --}}
                <div class="col-md-6">

                    <label for="blood_type" class="form-label fw-bold">
                        Golongan Darah <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-droplet"></i>
                        </span>

                        <select name="blood_type"
                            id="blood_type"
                            class="form-control border-start-0 @error('blood_type') is-invalid @enderror"
                            required>

                            <option value="" disabled selected>
                                Pilih golongan darah...
                            </option>

                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $blood)

                                <option value="{{ $blood }}"
                                    {{ old('blood_type') == $blood ? 'selected' : '' }}>

                                    {{ $blood }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    @error('blood_type')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            <div class="row mb-4">

                {{-- FOTO --}}
                <div class="col-md-6">

                    <label for="photo_path" class="form-label fw-bold">
                        Foto
                    </label>

                    <small class="text-muted d-block mb-2">
                        JPG / JPEG / PNG maksimal 2MB
                    </small>

                    <div class="input-group">

                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-camera"></i>
                        </span>

                        <input type="file"
                            name="photo_path"
                            id="photo_path"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            class="form-control @error('photo_path') is-invalid @enderror">

                    </div>

                    @error('photo_path')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- DOCUMENT --}}
                <div class="col-md-6">

                    <label for="document_path" class="form-label fw-bold">
                        Dokumen PDF
                    </label>

                    <small class="text-muted d-block mb-2">
                        PDF maksimal 5MB
                    </small>

                    <div class="input-group">

                        <span class="input-group-text bg-light text-muted">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </span>

                        <input type="file"
                            name="document_path"
                            id="document_path"
                            accept=".pdf,application/pdf"
                            class="form-control @error('document_path') is-invalid @enderror">

                    </div>

                    @error('document_path')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

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