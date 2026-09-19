@extends('layouts.admin')

@section('title', 'Tambah Tenaga Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Tambah Tenaga Kerja</h4><p class="text-muted mb-0">Lengkapi profil sebelum mengunggah dokumen.</p></div>
    <a href="{{ route('dashboard.manpowers.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="{{ route('dashboard.manpowers.store') }}" enctype="multipart/form-data">
        @csrf
        @include('dashboard.manpowers._form')
        <div class="border-top mt-4 pt-4"><button class="btn btn-primary">Simpan dan lanjutkan ke dokumen</button></div>
    </form>
</div></div>
@endsection
