@extends('layouts.admin')

@section('title', 'Ubah Tenaga Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Ubah Tenaga Kerja</h4><p class="text-muted mb-0">{{ $manpower->name }} · {{ $manpower->nik }}</p></div>
    <div class="d-flex gap-2"><a href="{{ route('dashboard.manpowers.show', $manpower) }}" class="btn btn-outline-primary">Lihat profil</a><a href="{{ route('dashboard.manpowers.index') }}" class="btn btn-outline-secondary">Kembali</a></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="{{ route('dashboard.manpowers.update', $manpower) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('dashboard.manpowers._form')
        <div class="border-top mt-4 pt-4"><button class="btn btn-primary">Simpan perubahan</button></div>
    </form>
</div></div>

@include('dashboard.manpowers._documents')
@endsection
