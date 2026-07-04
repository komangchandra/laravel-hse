@extends('layouts.admin')

@section('title', 'Detail SIMPER')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-0">
            Detail SIMPER
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
                    <a href="{{ route('dashboard.simpers.index') }}"
                        class="text-decoration-none">

                        SIMPER

                    </a>
                </li>

                <li class="breadcrumb-item active">
                    Detail
                </li>

            </ol>

        </nav>

    </div>

    <a href="{{ route('dashboard.simpers.index') }}"
        class="btn btn-outline-secondary">

        <i class="bi bi-arrow-left me-1"></i>
        Kembali

    </a>

</div>


@if(session('success'))

    <div class="alert alert-success">

        {{ session('success') }}

    </div>

@endif

<div class="row g-4">

    {{-- INFORMASI SIMPER --}}
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">

                <h6 class="mb-0 fw-bold">

                    <i class="bi bi-person-badge me-2"></i>
                    Informasi SIMPER

                </h6>

            </div>

            <div class="card-body">

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Kode
                    </div>

                    <div class="col-md-8 fw-semibold">
                        {{ $simper->code }}
                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Manpower
                    </div>

                    <div class="col-md-8 fw-semibold">
                        {{ $simper->manpower->name ?? '-' }}
                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        NIK
                    </div>

                    <div class="col-md-8">
                        {{ $simper->manpower->nik ?? '-' }}
                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Mitra Kerja
                    </div>

                    <div class="col-md-8">
                        {{ $simper->partner->name ?? '-' }}
                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Status
                    </div>

                    <div class="col-md-8">

                        @switch($simper->status)

                            @case('pengajuan')
                                <span class="badge bg-warning">
                                    Pengajuan
                                </span>
                            @break

                            @case('aktif')
                                <span class="badge bg-success">
                                    Aktif
                                </span>
                            @break

                            @default
                                <span class="badge bg-secondary">
                                    {{ ucfirst($simper->status) }}
                                </span>

                        @endswitch

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4 text-muted">
                        Kategori SIMPER
                    </div>

                    <div class="col-md-8">

                        @forelse($simper->categories as $category)

                            <span class="badge bg-primary me-1">
                                {{ $category->name }}
                            </span>

                        @empty

                            <span class="text-muted">
                                Belum ada kategori
                            </span>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- PANEL UJIAN --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">

                <h6 class="mb-0 fw-bold">

                    <i class="bi bi-journal-check me-2"></i>
                    Ujian SIMPER

                </h6>

            </div>

            <div class="card-body">

                @php
                    $latestToken = $simper->examTokens->sortByDesc('id')->first();
                @endphp

                <div class="mb-3">

                    <small class="text-muted d-block">
                        Token Aktif
                    </small>

                    <div class="fw-bold fs-5">

                        {{ $latestToken->token ?? '-' }}

                    </div>

                </div>

                @if($latestToken)

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Expired
                        </small>

                        <div>
                            {{ $latestToken->expired_at->format('d M Y H:i') }}
                        </div>

                    </div>

                @endif

                <form method="POST"
                    action="{{ route('dashboard.simpers.generate-token', $simper) }}">

                    @csrf

                    <button type="submit"
                        class="btn btn-primary w-100">

                        <i class="bi bi-key me-1"></i>
                        Generate Token

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection