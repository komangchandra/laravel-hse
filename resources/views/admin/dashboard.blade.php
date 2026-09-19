@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="d-flex justify-content-between flex-wrap align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Dashboard {{ strtoupper(auth()->user()->roles->pluck('name')->join(' / ')) }}</h4><p class="text-muted mb-0">Ringkasan pekerjaan dan status operasional sesuai kewenangan Anda.</p></div>
    @if(auth()->user()->isDeveloper())<form class="d-flex gap-2"><select name="owner_id" class="form-select"><option value="">Semua owner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($selectedOwnerId === $owner->id)>{{ $owner->legal_name }}</option>@endforeach</select><button class="btn btn-primary">Filter</button></form>@endif
</div>

<div class="row g-3 mb-4">@foreach($metrics as $metric)<div class="col-sm-6 col-xl-3"><a href="{{ $metric['url'] }}" class="text-decoration-none"><div class="card border-0 shadow-sm h-100 border-start border-4 border-{{ $metric['color'] }}"><div class="card-body"><div class="text-muted small text-uppercase fw-semibold">{{ $metric['label'] }}</div><div class="display-6 fw-bold text-{{ $metric['color'] }}">{{ number_format($metric['count']) }}</div><div class="small text-muted">Lihat daftar sumber →</div></div></div></a></div>@endforeach</div>

<div class="row g-4">
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0 fw-bold">Notifikasi Terbaru</h5><a href="{{ route('dashboard.notifications.index') }}" class="btn btn-sm btn-outline-primary">Lihat semua</a></div><div class="list-group list-group-flush">@forelse($recentNotifications as $notification)<form method="POST" action="{{ route('dashboard.notifications.read', $notification) }}">@csrf<button class="list-group-item list-group-item-action text-start w-100 {{ $notification->read_at ? '' : 'bg-primary bg-opacity-10' }}"><strong>{{ $notification->title }}</strong><div class="small text-muted">{{ $notification->message }}</div><small>{{ $notification->created_at->diffForHumans() }}</small></button></form>@empty<div class="p-4 text-center text-muted">Belum ada notifikasi.</div>@endforelse</div></div></div>
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white"><h5 class="mb-0 fw-bold">Audit Operasional Terbaru</h5></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th class="ps-3">Waktu</th><th>Aktor</th><th>Aksi</th><th>Status</th></tr></thead><tbody>@forelse($recentAudit as $audit)<tr><td class="ps-3 small">{{ $audit->occurred_at->format('d-m H:i') }}</td><td>{{ $audit->actor_name ?? 'Sistem' }}</td><td><code>{{ $audit->action }}</code></td><td class="small">{{ $audit->from_status ?? '-' }} → {{ $audit->to_status ?? '-' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted p-4">Belum ada audit operasional.</td></tr>@endforelse</tbody></table></div></div></div>
</div>

@if($recentUserActivity->isNotEmpty())
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><h5 class="mb-0 fw-bold">Aktivitas Akun Terbaru</h5></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th class="ps-3">Pengguna</th><th>Aksi</th><th>Perangkat</th><th>Waktu</th></tr></thead><tbody>@foreach($recentUserActivity as $log)<tr><td class="ps-3">{{ $log->name ?? 'Sistem' }}</td><td>{{ $log->action }}</td><td class="small">{{ data_get($log->meta, 'user_agent', 'Unknown') }}</td><td class="small">{{ $log->created_at->format('d-m-Y H:i') }}</td></tr>@endforeach</tbody></table></div></div>
@endif
@endsection
