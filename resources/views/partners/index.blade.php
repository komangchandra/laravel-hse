@extends('layouts.admin')

@section('title', 'Manajemen Mitra Kerja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Mitra Kerja</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active">Mitra Kerja</li>
            </ol>
        </nav>
    </div>
    @can('partner.create')
    <a href="{{ route('dashboard.partners.create') }}" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Mitra Kerja
    </a>
    @endcan
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session("success") }}
    </div>
@endif

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom border-light">
        <div class="row align-items-center g-2">
            <div class="col">
                <h6 class="mb-0 fw-bold text-dark">Daftar Mitra Kerja</h6>
            </div>
            <div class="col-auto">
                <form action="{{ route('dashboard.partners.index') }}" method="GET" class="d-flex">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control border-end-0" 
                               placeholder="Cari mitra kerja..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Mitra</th>
                        <th class="ps-4">Akronim</th>
                        <th class="ps-4">Email</th>
                        <th class="ps-4">Jenis Mitra</th>
                        <th class="ps-4">Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark">{{ $partner->legal_name }}</span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark">{{ $partner->short_name }}</span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="text-dark">{{ $partner->email }}</span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">
                                    {{ $partner->level }}
                                </span>
                            </div>
                        </td>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                @php $status = strtolower($partner->status); @endphp

                                @if($status === 'active')
                                <div class="bg-success bg-opacity-10 p-2 rounded-2 me-3" title="Aktif">
                                    <i class="bi bi-check-circle text-success"></i>
                                </div>
                                @elseif($status === 'inactive' || $status === 'disabled')
                                <div class="bg-danger bg-opacity-10 p-2 rounded-2 me-3" title="Tidak aktif">
                                    <i class="bi bi-x-circle text-danger"></i>
                                </div>
                                @elseif($status === 'slowdown' || $status === 'slow')
                                <div class="bg-warning bg-opacity-10 p-2 rounded-2 me-3" title="Slowdown">
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                </div>
                                @else
                                <div class="bg-secondary bg-opacity-10 p-2 rounded-2 me-3" title="Unknown">
                                    <i class="bi bi-question-circle text-secondary"></i>
                                </div>
                                @endif

                                <span class="fw-bold text-dark text-capitalize">{{ $partner->status }}</span>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm">
                                @role('developer')
                                <a href="{{ route('dashboard.partners.edit', $partner) }}" class="btn btn-sm btn-white border border-end-0">
                                    <i class="bi bi-pencil-square text-primary me-1"></i> Edit
                                </a>
                                @endrole
                                  
                                @role('developer')
                                <form method="POST" action="{{ route('dashboard.partners.destroy', $partner) }}" 
                                      class="d-inline" 
                                      onsubmit="return confirm('Yakin ingin menghapus partner ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-white border">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </form>
                                @endrole
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <p class="text-muted mb-0">Tidak ada mitra kerja yang ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($partners->hasPages())
    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-center">
        {!! $partners->links() !!}
    </div>
    @endif
</div>
@endsection