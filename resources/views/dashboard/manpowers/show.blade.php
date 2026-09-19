@extends('layouts.admin')

@section('title', 'Profil Tenaga Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Profil Tenaga Kerja</h4><p class="text-muted mb-0">Data sumber untuk pengajuan dan kartu.</p></div>
    <div class="d-flex gap-2">
        @can('update', $manpower) @unless($manpower->trashed())<a href="{{ route('dashboard.manpowers.edit', $manpower) }}" class="btn btn-primary">Ubah</a>@endunless @endcan
        <a href="{{ route('dashboard.manpowers.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($manpower->trashed())<div class="alert alert-secondary">Data ini sudah diarsipkan. Profil, dokumen, dan histori tetap dapat dibaca.</div>@endif

<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <div class="row g-4">
        <div class="col-md-3 text-center">
            @if($manpower->photo_path)<img src="{{ route('dashboard.manpowers.photo', $manpower) }}" class="img-thumbnail object-fit-cover" style="width:180px;height:240px" alt="Foto {{ $manpower->name }}">@endif
        </div>
        <div class="col-md-9">
            <div class="row g-3">
                @foreach([
                    'NIK/nomor pekerja' => $manpower->nik,
                    'Nama' => $manpower->name,
                    'Perusahaan' => $manpower->partner?->legal_name,
                    'Owner' => $manpower->owner?->legal_name,
                    'Jabatan' => $manpower->position,
                    'Departemen' => $manpower->department,
                    'Tempat/tanggal lahir' => $manpower->birth_place.', '.$manpower->birth_date?->format('d-m-Y'),
                    'Golongan darah' => $manpower->blood_type,
                    'Telepon' => $manpower->contact_number,
                    'Kontak darurat' => $manpower->emergency_contact_name.' · '.$manpower->emergency_contact_number,
                ] as $label => $value)
                    <div class="col-md-6"><div class="small text-muted">{{ $label }}</div><div class="fw-semibold">{{ $value ?: '-' }}</div></div>
                @endforeach
                <div class="col-md-6"><div class="small text-muted">Status</div><span class="badge {{ $manpower->is_active && !$manpower->trashed() ? 'bg-success' : 'bg-secondary' }}">{{ $manpower->is_active && !$manpower->trashed() ? 'Aktif' : 'Tidak aktif' }}</span></div>
            </div>
        </div>
    </div>
</div></div>

@include('dashboard.manpowers._documents')
@endsection
