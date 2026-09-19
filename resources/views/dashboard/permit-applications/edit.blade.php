@extends('layouts.admin')

@section('title', 'Perbaiki Pengajuan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Perbaiki Pengajuan</h4><p class="text-muted mb-0">{{ $application->application_number }} · versi {{ $application->version }}</p></div>
    <a href="{{ route('dashboard.permit-applications.show', $application) }}" class="btn btn-outline-secondary">Kembali</a>
</div>
@if($errors->any())<div class="alert alert-danger"><strong>Pengajuan belum dapat diproses.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('dashboard.permit-applications.update', $application) }}">@csrf @method('PUT')
    @include('dashboard.permit-applications._form')
</form>
@endsection
