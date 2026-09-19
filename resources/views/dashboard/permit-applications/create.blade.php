@extends('layouts.admin')

@section('title', 'Buat Pengajuan Permit')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Buat Pengajuan</h4><p class="text-muted mb-0">Draft boleh disimpan sebelum seluruh persyaratan lengkap.</p></div>
    <a href="{{ route('dashboard.permit-applications.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>
@if($errors->any())<div class="alert alert-danger"><strong>Pengajuan belum dapat diproses.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('dashboard.permit-applications.store') }}">@csrf
    @include('dashboard.permit-applications._form')
</form>
@endsection
