@extends('layouts.admin')

@section('title', 'Ubah Organisasi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Ubah Organisasi</h4>
        <span class="text-muted">{{ $partner->legal_name }}</span>
    </div>
    <a href="{{ route('dashboard.partners.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.partners.update', $partner) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('partners._form')
            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                <a href="{{ route('dashboard.partners.index') }}" class="btn btn-light border">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
