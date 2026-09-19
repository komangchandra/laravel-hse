@extends('layouts.admin')

@section('title', 'Organisasi Owner dan Mitra')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Organisasi Owner dan Mitra</h4>
        <span class="text-muted">Struktur perusahaan penerbit dan perusahaan mitra</span>
    </div>
    @can('create', App\Models\Partner::class)
        <a href="{{ route('dashboard.partners.create') }}" class="btn btn-primary">Tambah organisasi</a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <form action="{{ route('dashboard.partners.index') }}" method="GET" class="d-flex ms-auto" style="max-width: 360px">
            <input type="search" name="search" class="form-control" placeholder="Cari nama, singkatan, atau email" value="{{ $search }}">
            <button class="btn btn-outline-secondary ms-2" type="submit">Cari</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Organisasi</th>
                    <th>Jenis</th>
                    <th>Owner</th>
                    <th>Tipe mitra</th>
                    <th>Status</th>
                    @can('create', App\Models\Partner::class) <th class="text-end pe-4">Aksi</th> @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $partner)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $partner->legal_name }}</div>
                            <small class="text-muted">{{ $partner->short_name }} · {{ $partner->email }}</small>
                        </td>
                        <td><span class="badge {{ $partner->isOwner() ? 'text-bg-primary' : 'text-bg-info' }}">{{ $partner->isOwner() ? 'Owner' : 'Mitra' }}</span></td>
                        <td>
                            @if($partner->isOwner())
                                <span class="text-muted">—</span>
                            @elseif($partner->owner)
                                {{ $partner->owner->short_name }}
                            @else
                                <span class="badge text-bg-warning">Belum dipetakan</span>
                            @endif
                        </td>
                        <td>{{ $partner->partnerType?->name ?? '—' }}</td>
                        <td><span class="text-capitalize">{{ $partner->status }}</span></td>
                        @can('update', $partner)
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('dashboard.partners.edit', $partner) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                <form method="POST" action="{{ route('dashboard.partners.destroy', $partner) }}" class="d-inline" onsubmit="return confirm('Hapus organisasi ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada organisasi yang ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($partners->hasPages())
        <div class="card-footer bg-white">{{ $partners->links() }}</div>
    @endif
</div>
@endsection
