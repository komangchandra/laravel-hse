@extends('layouts.admin')

@section('title', 'Tenaga Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Tenaga Kerja</h4><p class="text-muted mb-0">Profil dan kelengkapan dokumen pekerja.</p></div>
    @can('create', App\Models\Manpower::class)<a href="{{ route('dashboard.manpowers.create') }}" class="btn btn-primary">Tambah tenaga kerja</a>@endcan
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->has('manpower'))<div class="alert alert-danger">{{ $errors->first('manpower') }}</div>@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col"><input name="search" class="form-control" value="{{ $search }}" placeholder="Cari NIK, nama, jabatan, atau departemen"></div>
            <div class="col-auto"><div class="form-check"><input id="archived" class="form-check-input" type="checkbox" name="archived" value="1" @checked(request()->boolean('archived'))><label for="archived" class="form-check-label">Tampilkan arsip</label></div></div>
            <div class="col-auto"><button class="btn btn-outline-primary">Cari</button></div>
        </form>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th class="ps-4">Tenaga kerja</th><th>Perusahaan</th><th>Jabatan/departemen</th><th>Dokumen</th><th>Status</th><th class="text-end pe-4">Tindakan</th></tr></thead>
        <tbody>
        @forelse($manpowers as $manpower)
            <tr>
                <td class="ps-4"><div class="d-flex align-items-center gap-3">
                    @if($manpower->photo_path)<img src="{{ route('dashboard.manpowers.photo', $manpower) }}" class="rounded object-fit-cover" width="45" height="60" alt="">@endif
                    <div><strong>{{ $manpower->name }}</strong><div class="small text-muted">{{ $manpower->nik }}</div></div>
                </div></td>
                <td>{{ $manpower->partner?->short_name ?? '-' }}</td>
                <td>{{ $manpower->position ?? '-' }}<div class="small text-muted">{{ $manpower->department ?? '-' }}</div></td>
                <td><span class="badge bg-light text-dark border">{{ $manpower->documents->count() }} file</span></td>
                <td><span class="badge {{ $manpower->trashed() || !$manpower->is_active ? 'bg-secondary' : 'bg-success' }}">{{ $manpower->trashed() ? 'Diarsipkan' : ($manpower->is_active ? 'Aktif' : 'Tidak aktif') }}</span></td>
                <td class="text-end pe-4">
                    <a href="{{ route('dashboard.manpowers.show', $manpower) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    @if($manpower->trashed())
                        @can('update', $manpower)<form method="POST" action="{{ route('dashboard.manpowers.restore', $manpower) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Pulihkan</button></form>@endcan
                    @else
                        @can('update', $manpower)<a href="{{ route('dashboard.manpowers.edit', $manpower) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>@endcan
                        @can('delete', $manpower)<form method="POST" action="{{ route('dashboard.manpowers.destroy', $manpower) }}" class="d-inline" onsubmit="return confirm('Arsipkan tenaga kerja ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Arsipkan</button></form>@endcan
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada data tenaga kerja.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($manpowers->hasPages())<div class="card-footer bg-white">{{ $manpowers->links() }}</div>@endif
</div>
@endsection
