@php($missing = $manpower->missingRequiredDocumentTypes())

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Dokumen Tenaga Kerja</h5></div>
    <div class="card-body">
        @if($missing)
            <div class="alert alert-warning">
                Dokumen dasar Mine Permit yang belum tersedia:
                <strong>{{ collect($missing)->map(fn($type) => App\Models\ManpowerDocument::TYPES[$type])->join(', ') }}</strong>.
            </div>
        @else
            <div class="alert alert-success">Dokumen dasar Mine Permit sudah tersedia.</div>
        @endif

        @can('create', [App\Models\ManpowerDocument::class, $manpower])
            <form method="POST" action="{{ route('dashboard.manpowers.documents.store', $manpower) }}" enctype="multipart/form-data" class="border rounded p-3 mb-4">
                @csrf
                <h6 class="fw-bold">Tambah versi dokumen</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Pilih tipe</option>
                            @foreach(App\Models\ManpowerDocument::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nomor dokumen</label>
                        <input name="document_number" class="form-control @error('document_number') is-invalid @enderror" value="{{ old('document_number') }}" required>
                        @error('document_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal terbit</label>
                        <input type="date" name="issued_at" class="form-control @error('issued_at') is-invalid @enderror" value="{{ old('issued_at') }}" required>
                        @error('issued_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">File</label>
                        <input type="file" name="file" accept=".pdf,application/pdf" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Hanya PDF, maksimal 2 MB. File lama tidak akan ditimpa.</div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Unggah dokumen</button></div>
                </div>
            </form>
        @endcan

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Tipe/versi</th><th>Nomor</th><th>Tanggal terbit</th><th>Status</th><th class="text-end">Tindakan</th></tr></thead>
                <tbody>
                @forelse($manpower->allDocuments->sortByDesc('created_at') as $document)
                    <tr class="{{ $document->trashed() ? 'text-muted' : '' }}">
                        <td><strong>{{ App\Models\ManpowerDocument::TYPES[$document->type] ?? $document->type }}</strong><br><small>Versi {{ $document->version }} · {{ $document->original_name }}</small></td>
                        <td>{{ $document->document_number ?? '-' }}</td>
                        <td>{{ $document->issued_at?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            @if($document->trashed())
                                <span class="badge bg-secondary">Diarsipkan</span>
                            @elseif($document->verification_status === 'verified')
                                <span class="badge bg-success">Divalidasi HSE</span>
                            @elseif($document->verification_status === 'rejected')
                                <span class="badge bg-danger">Perlu diganti</span>
                            @else
                                <span class="badge bg-warning text-dark">Siap diajukan</span>
                            @endif
                            @if($document->verification_note)<div class="small mt-1">{{ $document->verification_note }}</div>@endif
                        </td>
                        <td class="text-end">
                            @unless($document->trashed())
                                <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('dashboard.manpowers.documents.download', [$manpower, $document]) }}">Lihat</a>
                                @can('delete', $document)
                                    <form class="d-inline" method="POST" action="{{ route('dashboard.manpowers.documents.destroy', [$manpower, $document]) }}" onsubmit="return confirm('Arsipkan versi dokumen ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Arsipkan</button>
                                    </form>
                                @endcan
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada dokumen.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
