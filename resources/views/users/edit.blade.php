@extends('layouts.admin')

@section('title', 'Ubah Akun')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Ubah Akun</h4>
        <p class="text-muted mb-0">{{ $user->name }} · {{ $user->email }}</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users._form')
            <div class="d-flex gap-2 pt-4 mt-4 border-top">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan perubahan</button>
                <a href="{{ route('users.index') }}" class="btn btn-light border">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
