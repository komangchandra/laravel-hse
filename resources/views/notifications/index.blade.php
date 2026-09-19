@extends('layouts.admin')
@section('title', 'Notifikasi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-1">Notifikasi</h4><p class="text-muted mb-0">Pembaruan pengajuan, ujian, keputusan, dan permit.</p></div><form method="POST" action="{{ route('dashboard.notifications.read-all') }}">@csrf<button class="btn btn-outline-primary">Tandai Semua Dibaca</button></form></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="list-group list-group-flush">
@forelse($notifications as $notification)
<form method="POST" action="{{ route('dashboard.notifications.read', $notification) }}">@csrf<button class="list-group-item list-group-item-action text-start w-100 {{ $notification->read_at ? '' : 'bg-primary bg-opacity-10' }}"><div class="d-flex justify-content-between"><strong>{{ $notification->title }}</strong><small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small></div><div class="text-muted mt-1">{{ $notification->message }}</div></button></form>
@empty<div class="p-5 text-center text-muted">Belum ada notifikasi.</div>@endforelse
</div>@if($notifications->hasPages())<div class="card-footer bg-white">{{ $notifications->links() }}</div>@endif</div>
@endsection
