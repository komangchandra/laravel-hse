@extends('layouts.admin')
@section('title', 'Kartu Permit')
@section('content')
@php($snapshot = $issuance->print_snapshot)
<div class="d-flex justify-content-between align-items-start mb-4">
    <div><h4 class="fw-bold mb-1">{{ $issuance->mine_permit_number }}</h4><span class="badge {{ $issuance->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($issuance->status) }}</span>@if($issuance->simpol_number)<span class="ms-2 text-muted">{{ $issuance->simpol_number }}</span>@endif</div>
    <a href="{{ route('dashboard.permit-applications.show', $issuance->application) }}" class="btn btn-outline-secondary">Kembali ke Pengajuan</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-4">
    <div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="card-body p-4">
        <h5 class="fw-bold">Snapshot kartu</h5>
        <dl class="row mb-0">
            <dt class="col-sm-4">Nama</dt><dd class="col-sm-8">{{ data_get($snapshot, 'manpower.name') }}</dd>
            <dt class="col-sm-4">Perusahaan</dt><dd class="col-sm-8">{{ data_get($snapshot, 'manpower.partner_name') }}</dd>
            <dt class="col-sm-4">Jabatan / departemen</dt><dd class="col-sm-8">{{ data_get($snapshot, 'manpower.position') }} / {{ data_get($snapshot, 'manpower.department') }}</dd>
            <dt class="col-sm-4">Area akses</dt><dd class="col-sm-8">{{ collect($snapshot['access_areas'] ?? [])->join(', ') }}</dd>
            <dt class="col-sm-4">Masa berlaku</dt><dd class="col-sm-8">{{ $issuance->valid_from->format('d-m-Y') }} s.d. {{ $issuance->expires_at->format('d-m-Y') }}</dd>
            <dt class="col-sm-4">Validasi publik</dt><dd class="col-sm-8"><a target="_blank" href="{{ $snapshot['verification_url'] }}">{{ $snapshot['verification_url'] }}</a></dd>
        </dl>
        @if($issuance->simpol_number)<hr><h6 class="fw-bold">Kategori SIMPER</h6>@foreach($snapshot['categories'] ?? [] as $category)<span class="badge bg-primary me-1">{{ $category['name'] }} — {{ $category['level'] }}</span>@endforeach @endif
    </div></div></div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <h5 class="fw-bold">Cetak 15 × 10 cm</h5>
            @if($issuance->isPrintable())
                @can('download', $issuance)<a target="_blank" href="{{ route('dashboard.permit-cards.pdf', $issuance) }}" class="btn btn-primary w-100">Buka PDF Kartu</a>@endcan
                <p class="small text-muted mt-2 mb-0">Setiap pembukaan PDF dicatat. Pembukaan kedua dan seterusnya dicatat sebagai reprint.</p>
            @else<div class="alert alert-warning mb-0">Kartu tidak dapat dicetak karena tidak aktif atau kedaluwarsa.</div>@endif
        </div></div>
        @can('revoke', $issuance)<div class="card border-danger shadow-sm"><div class="card-body p-4"><h5 class="fw-bold text-danger">Cabut Permit</h5><form method="POST" action="{{ route('dashboard.permit-cards.revoke', $issuance) }}">@csrf<textarea required name="reason" class="form-control mb-2" rows="3" placeholder="Alasan pencabutan"></textarea><button class="btn btn-danger w-100">Cabut Permit</button></form></div></div>@endcan
    </div>
</div>

<div class="card border-0 shadow-sm mt-4"><div class="card-body p-4"><h5 class="fw-bold">Riwayat cetak</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Pengguna</th></tr></thead><tbody>@forelse($issuance->printLogs->sortByDesc('printed_at') as $log)<tr><td>{{ $log->printed_at->format('d-m-Y H:i') }}</td><td>{{ $log->action === 'initial_print' ? 'Cetak pertama' : 'Reprint' }}</td><td>{{ $log->printer?->name ?? 'Pengguna dihapus' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Belum pernah dicetak.</td></tr>@endforelse</tbody></table></div></div></div>
@endsection
