@extends('layouts.admin')

@section('title', 'Tambah Organisasi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Organisasi</h4>
    <a href="{{ route('dashboard.partners.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.partners.store') }}" enctype="multipart/form-data">
            @csrf
            @include('partners._form')
            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-success">Simpan organisasi</button>
                <a href="{{ route('dashboard.partners.index') }}" class="btn btn-light border">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
