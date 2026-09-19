@extends('layouts.admin')
@section('title', 'Pengajuan Permit')
@section('content')
@php
    $queue = request('queue');
    $queueTitle = $queue === 'review' ? 'Antrean Review HSE' : ($queue === 'ktt' ? 'Antrean Persetujuan KTT' : 'Pengajuan Permit');
    $queueDescription = $queue === 'review'
        ? 'Validasi pengajuan dan dokumen berdasarkan snapshot saat submit.'
        : ($queue === 'ktt' ? 'Keputusan akhir untuk pengajuan milik owner Anda.' : 'Mine Permit dan Mine Permit + SIMPER.');
    $isReviewQueue = in_array($queue, ['review', 'ktt'], true);
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $queueTitle }}</h4>
        <p class="text-muted mb-0">{{ $queueDescription }}</p>
    </div>
    @can('create', App\Models\PermitApplication::class)<a href="{{ route('dashboard.permit-applications.create') }}" class="btn btn-primary">Buat Pengajuan</a>@endcan
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if(auth()->user()->hasRole('hse_owner'))
<div class="btn-group mb-3">
    <a href="{{ route('dashboard.permit-applications.index') }}" class="btn btn-outline-primary {{ !request('queue') ? 'active' : '' }}">Semua</a>
    <a href="{{ route('dashboard.permit-applications.index', ['queue' => 'internal']) }}" class="btn btn-outline-primary {{ request('queue') === 'internal' ? 'active' : '' }}">Pengajuan Internal Saya</a>
    <a href="{{ route('dashboard.permit-applications.index', ['queue' => 'review']) }}" class="btn btn-outline-primary {{ request('queue') === 'review' ? 'active' : '' }}">Perlu Review HSE <span class="badge text-bg-primary ms-1">{{ $reviewCount }}</span></a>
</div>
@endif
@if(auth()->user()->hasRole('ktt'))
<div class="btn-group mb-3">
    <a href="{{ route('dashboard.permit-applications.index') }}" class="btn btn-outline-primary {{ !$queue ? 'active' : '' }}">Semua</a>
    <a href="{{ route('dashboard.permit-applications.index', ['queue' => 'ktt']) }}" class="btn btn-outline-primary {{ $queue === 'ktt' ? 'active' : '' }}">Perlu Persetujuan KTT <span class="badge text-bg-primary ms-1">{{ $kttReviewCount }}</span></a>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <form class="row g-2">
            <input type="hidden" name="queue" value="{{ request('queue') }}">
            <div class="col-lg-3"><input name="search" value="{{ $search }}" class="form-control" placeholder="Nomor, nama, atau NIK"></div>
            <div class="col-lg-2"><select name="organization_id" class="form-select"><option value="">Semua organisasi</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((int) request('organization_id') === $organization->id)>{{ $organization->short_name }}</option>@endforeach</select></div>
            <div class="col-lg-2"><select name="type" class="form-select"><option value="">Semua jenis</option>@foreach($types as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->value === 'mine_permit_only' ? 'Mine Permit only' : 'Mine Permit + SIMPER' }}</option>@endforeach</select></div>
            @if($isReviewQueue)
                <div class="col-lg-2"><select name="age_days" class="form-select"><option value="">Semua umur antrean</option>@foreach([1, 3, 7, 14] as $days)<option value="{{ $days }}" @selected((int) request('age_days') === $days)>Menunggu minimal {{ $days }} hari</option>@endforeach</select></div>
            @else
                <div class="col-lg-2"><select name="status" class="form-select"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucwords(str_replace('_', ' ', $status->value)) }}</option>@endforeach</select></div>
            @endif
            <div class="col-lg-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" title="Dari tanggal"></div>
            <div class="col-lg-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" title="Sampai tanggal"></div>
            <div class="col-lg-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
        </form>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th class="ps-4">Nomor</th><th>Tenaga kerja</th><th>Pengaju</th><th>Jenis</th><th>Status</th><th>{{ $isReviewQueue ? 'Waktu tunggu' : 'Tanggal' }}</th><th class="text-end pe-4">Aksi</th></tr></thead>
        <tbody>
        @forelse($applications as $application)
            <tr>
                <td class="ps-4 fw-semibold">{{ $application->application_number }}</td>
                <td>{{ $application->manpower?->name }}<div class="small text-muted">{{ $application->manpower?->nik }}</div></td>
                <td>{{ $application->partner?->short_name }}<div class="small text-muted">{{ $application->creator?->name }}</div></td>
                <td>{{ $application->type->value === 'mine_permit_only' ? 'Mine Permit' : 'Mine Permit + SIMPER' }}</td>
                <td><span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $application->status->value)) }}</span></td>
                <td>@if($isReviewQueue && $application->submitted_at)<strong>{{ (int) floor($application->submitted_at->diffInDays(now())) }} hari</strong><div class="small text-muted">sejak {{ $application->submitted_at->format('d-m-Y H:i') }}</div>@else{{ $application->created_at->format('d-m-Y') }}@endif</td>
                <td class="text-end pe-4"><a href="{{ route('dashboard.permit-applications.show', $application) }}" class="btn btn-sm btn-outline-primary">{{ $isReviewQueue ? 'Periksa' : 'Detail' }}</a> @can('update', $application)<a href="{{ route('dashboard.permit-applications.edit', $application) }}" class="btn btn-sm btn-outline-secondary">Perbaiki</a>@endcan</td>
            </tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-5">{{ $queue === 'review' ? 'Tidak ada pengajuan yang menunggu review HSE.' : ($queue === 'ktt' ? 'Tidak ada pengajuan yang menunggu persetujuan KTT.' : 'Belum ada pengajuan.') }}</td></tr>@endforelse
        </tbody>
    </table></div>
    @if($applications->hasPages())<div class="card-footer bg-white">{{ $applications->links() }}</div>@endif
</div>
@endsection
