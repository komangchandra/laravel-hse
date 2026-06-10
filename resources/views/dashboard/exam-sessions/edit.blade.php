@extends('layouts.admin')

@section('title', 'Edit Sesi Ujian')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-0">
            Edit Sesi Ujian
        </h4>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}"
                        class="text-decoration-none">
                        Dashboard
                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.exam-sessions.index') }}"
                        class="text-decoration-none">
                        Sesi Ujian
                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Edit Sesi Ujian
                </li>

            </ol>
        </nav>

    </div>

    <a href="{{ route('dashboard.exam-sessions.index') }}"
        class="btn btn-sm btn-outline-secondary shadow-sm">

        <i class="bi bi-arrow-left me-1"></i>
        Kembali

    </a>

</div>

<div class="card border-0 shadow-sm rounded-3">

    <div class="card-body p-4">

        <form method="POST"
            action="{{ route('dashboard.exam-sessions.update', $examSession) }}">

            @csrf
            @method('PUT')

            {{-- NAMA SESI --}}
            <div class="mb-4">

                <label for="name"
                    class="form-label fw-bold">

                    Nama Sesi
                    <span class="text-danger">*</span>

                </label>

                <div class="input-group">

                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-journal-text"></i>
                    </span>

                    <input type="text"
                        name="name"
                        id="name"
                        class="form-control border-start-0 @error('name') is-invalid @enderror"
                        value="{{ old('name', $examSession->name) }}"
                        required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            {{-- DESKRIPSI --}}
            <div class="mb-4">

                <label for="description"
                    class="form-label fw-bold">

                    Deskripsi

                </label>

                <textarea name="description"
                    id="description"
                    rows="4"
                    class="form-control @error('description') is-invalid @enderror"
                    placeholder="Deskripsi sesi ujian (opsional)">{{ old('description', $examSession->description) }}</textarea>

                @error('description')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- DURASI + PASSING SCORE --}}
            <div class="row mb-4">

                <div class="col-md-6">

                    <label for="duration"
                        class="form-label fw-bold">

                        Durasi Ujian (Menit)
                        <span class="text-danger">*</span>

                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-clock"></i>
                        </span>

                        <input type="number"
                            name="duration"
                            id="duration"
                            min="1"
                            class="form-control border-start-0 @error('duration') is-invalid @enderror"
                            value="{{ old('duration', $examSession->duration) }}"
                            required>

                        @error('duration')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

                <div class="col-md-6">

                    <label for="passing_score"
                        class="form-label fw-bold">

                        Passing Score (%)
                        <span class="text-danger">*</span>

                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-award"></i>
                        </span>

                        <input type="number"
                            name="passing_score"
                            id="passing_score"
                            min="0"
                            max="100"
                            class="form-control border-start-0 @error('passing_score') is-invalid @enderror"
                            value="{{ old('passing_score', $examSession->passing_score) }}"
                            required>

                        @error('passing_score')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>

            {{-- STATUS --}}
            <div class="mb-4">

                <label class="form-label fw-bold">
                    Status
                </label>

                <div class="form-check form-switch">

                    <input class="form-check-input"
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        {{ old('is_active', $examSession->is_active) ? 'checked' : '' }}>

                    <label class="form-check-label"
                        for="is_active">

                        Aktifkan sesi ujian

                    </label>

                </div>

            </div>

            {{-- INFO --}}
            <div class="alert alert-light border">

                <div class="row">

                    <div class="col-md-6">
                        <small class="text-muted">
                            Dibuat:
                            <strong>
                                {{ $examSession->created_at->format('d M Y H:i') }}
                            </strong>
                        </small>
                    </div>

                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            Terakhir diubah:
                            <strong>
                                {{ $examSession->updated_at->format('d M Y H:i') }}
                            </strong>
                        </small>
                    </div>

                </div>

            </div>

            {{-- ACTION --}}
            <div class="mt-4 pt-3 border-top d-flex gap-2">

                <button type="submit"
                    class="btn btn-primary px-4 shadow-sm">

                    <i class="bi bi-save me-1"></i>
                    Update

                </button>

                <a href="{{ route('dashboard.exam-sessions.index') }}"
                    class="btn btn-light border px-4">

                    Batal

                </a>

            </div>

        </form>

    </div>

</div>

@endsection