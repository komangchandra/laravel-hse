@php
    $editing = isset($application);
    $selectedAreaIds = old('access_area_ids', $editing ? $application->accessAreas->modelKeys() : []);
    $selectedCategoryData = $editing ? $application->categories->mapWithKeys(fn($category) => [$category->id => [
        'selected' => 1, 'level' => $category->pivot->level, 'restrictions' => $category->pivot->restrictions,
        'supervisor_name' => $category->pivot->supervisor_name, 'activity_start_date' => $category->pivot->activity_start_date,
        'activity_end_date' => $category->pivot->activity_end_date,
    ]])->all() : [];
    $categoryInput = old('categories', $selectedCategoryData);
    $currentType = old('type', $editing ? $application->type->value : 'mine_permit_only');
@endphp
@if($editing)<input type="hidden" name="version" value="{{ old('version', $application->version) }}">@endif

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h5 class="fw-bold mb-3">1. Tenaga kerja dan jenis pengajuan</h5>
    <div class="row g-3">
        <div class="col-md-7"><label for="manpower-search" class="form-label fw-semibold">Tenaga kerja</label>
            @unless($editing)<input id="manpower-search" type="search" class="form-control mb-2" placeholder="Cari nama, NIK, atau perusahaan..." autocomplete="off" aria-controls="manpower-select">@endunless
            <select id="manpower-select" name="manpower_id" class="form-select @error('manpower_id') is-invalid @enderror" {{ $editing ? 'disabled' : '' }} required>
                <option value="">Pilih tenaga kerja</option>
                @foreach($manpowers as $manpower)<option value="{{ $manpower->id }}" data-owner-id="{{ $manpower->owner_id }}" data-position="{{ $manpower->position }}" data-department="{{ $manpower->department }}" @selected(old('manpower_id', $application->manpower_id ?? '') == $manpower->id)>{{ $manpower->name }} · {{ $manpower->nik }} · {{ $manpower->partner?->short_name }} ({{ $manpower->missingRequiredDocumentTypes() ? 'dokumen belum lengkap' : 'dokumen minimum lengkap' }})</option>@endforeach
            </select>
            @if($editing)<input type="hidden" name="manpower_id" value="{{ $application->manpower_id }}">@endif
            @error('manpower_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5"><label class="form-label fw-semibold">Jenis pengajuan</label>
            <select id="application-type" name="type" class="form-select @error('type') is-invalid @enderror" {{ $editing ? 'disabled' : '' }} required>
                @foreach($types as $type)<option value="{{ $type->value }}" @selected($currentType === $type->value)>{{ $type->value === 'mine_permit_only' ? 'Mine Permit only' : 'Mine Permit + SIMPER' }}</option>@endforeach
            </select>
            @if($editing)<input type="hidden" name="type" value="{{ $application->type->value }}">@endif
        </div>
    </div>
</div></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h5 class="fw-bold mb-3">2. Data penugasan dan area akses</h5>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Site/lokasi kerja</label><input name="site_name" class="form-control" value="{{ old('site_name', $application->site_name ?? auth()->user()->partner?->site_name) }}"></div>
        <div class="col-md-3"><label class="form-label">Jabatan penugasan</label><input id="assignment-position" class="form-control" value="{{ $application->manpower->position ?? '' }}" readonly><div class="form-text">Mengikuti data manpower.</div></div>
        <div class="col-md-3"><label class="form-label">Departemen</label><input id="assignment-department" class="form-control" value="{{ $application->manpower->department ?? '' }}" readonly><div class="form-text">Mengikuti data manpower.</div></div>
        <div class="col-md-3"><label class="form-label">Rencana mulai</label><input type="date" name="planned_start_date" class="form-control" value="{{ old('planned_start_date', isset($application) ? $application->planned_start_date?->toDateString() : '') }}"></div>
        <div class="col-md-3 d-flex align-items-end"><div class="alert alert-info py-2 mb-0 w-100">Masa berlaku otomatis 1 tahun sejak pengajuan dikirim.</div></div>
        <div class="col-md-6"><label class="form-label">Catatan pengaju</label><textarea name="applicant_notes" class="form-control" rows="2">{{ old('applicant_notes', $application->applicant_notes ?? '') }}</textarea></div>
        <div class="col-12"><label class="form-label fw-semibold">Area akses</label><div class="small text-muted mb-2">Pilihan mengikuti owner dari tenaga kerja yang dipilih.</div><div class="d-flex flex-wrap gap-3">
            @forelse($accessAreas as $area)<div class="form-check access-area-option" data-owner-id="{{ $area->owner_id }}"><input class="form-check-input" type="checkbox" name="access_area_ids[]" value="{{ $area->id }}" id="area-{{ $area->id }}" @checked(in_array($area->id, array_map('intval', $selectedAreaIds)))><label class="form-check-label" for="area-{{ $area->id }}">{{ $area->name }}</label></div>@empty<span class="text-danger">Belum ada master area aktif untuk owner ini.</span>@endforelse
        </div></div>
    </div>
</div></div>

<div id="simper-fields" class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h5 class="fw-bold mb-2">3. Kategori dan level SIMPER</h5><p class="text-muted">F: beroperasi penuh. P: beroperasi dengan batasan. T: hanya kegiatan tes. L: hanya latihan di bawah pengawasan.</p>
    @forelse($categories as $category)@php($value = $categoryInput[$category->id] ?? [])
        <div class="border rounded p-3 mb-3 simper-category-option" data-owner-id="{{ $category->owner_id ?? '' }}"><div class="row g-2 align-items-end">
            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="categories[{{ $category->id }}][selected]" value="1" id="category-{{ $category->id }}" @checked(!empty($value['selected']))><label class="form-check-label fw-semibold" for="category-{{ $category->id }}">{{ $category->name }}</label></div></div>
            <div class="col-md-2"><label class="form-label small">Level</label><select name="categories[{{ $category->id }}][level]" class="form-select simper-level"><option value="">Pilih</option>@foreach(['F','P','T','L'] as $level)<option value="{{ $level }}" @selected(($value['level'] ?? '') === $level)>{{ $level }}</option>@endforeach</select></div>
            <div class="col-md-7 level-restrictions"><label class="form-label small">Batasan operasi untuk level P</label><input name="categories[{{ $category->id }}][restrictions]" class="form-control" value="{{ $value['restrictions'] ?? '' }}" placeholder="Contoh: hanya LV di area hauling road, maksimal shift siang"></div>
            <div class="col-md-4 offset-md-3 level-supervision"><label class="form-label small">Nama supervisor untuk level T/L</label><input name="categories[{{ $category->id }}][supervisor_name]" class="form-control" value="{{ $value['supervisor_name'] ?? '' }}"></div>
            <div class="col-md-2 level-supervision"><label class="form-label small">Mulai tes/latihan</label><input type="date" name="categories[{{ $category->id }}][activity_start_date]" class="form-control" value="{{ $value['activity_start_date'] ?? '' }}"></div>
            <div class="col-md-2 level-supervision"><label class="form-label small">Selesai tes/latihan</label><input type="date" name="categories[{{ $category->id }}][activity_end_date]" class="form-control" value="{{ $value['activity_end_date'] ?? '' }}"></div>
        </div></div>
    @empty<div class="alert alert-warning">Belum ada kategori SIMPER untuk owner ini.</div>@endforelse
</div></div>

@if($editing)
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h5 class="fw-bold">Checklist dokumen</h5>
    @php($requiredDocs = $application->type->requiresExam() ? App\Models\Manpower::requiredDocumentTypesForCategories($application->categories) : App\Models\Manpower::REQUIRED_MINE_PERMIT_DOCUMENTS)
    @php($missingDocs = $application->manpower->missingRequiredDocumentTypes($requiredDocs))
    @if($missingDocs)<div class="alert alert-warning">Belum lengkap: {{ collect($missingDocs)->map(fn($type) => App\Models\ManpowerDocument::TYPES[$type] ?? $type)->join(', ') }}.</div>@else<div class="alert alert-success">Seluruh dokumen pengajuan tersedia.</div>@endif
    <p class="small text-muted">Dokumen tidak perlu disetujui HSE sebelum pengajuan dikirim. HSE akan memvalidasi data dan snapshot berkas pada tahap review pengajuan.</p>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Dokumen</th><th>Status</th><th>Tanggal terbit</th></tr></thead><tbody>@forelse($application->manpower->documents->whereIn('type', $requiredDocs)->sortBy('type') as $document)<tr><td>{{ App\Models\ManpowerDocument::TYPES[$document->type] ?? $document->type }}</td><td>@if($document->verification_status === 'verified')<span class="badge bg-success">Divalidasi HSE</span>@elseif($document->verification_status === 'rejected')<span class="badge bg-danger">Perlu diganti</span>@else<span class="badge bg-warning text-dark">Siap diajukan</span>@endif</td><td>{{ $document->issued_at?->format('d-m-Y') ?? '-' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Belum ada dokumen.</td></tr>@endforelse</tbody></table></div>
</div></div>
@endif

<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <div class="form-check mb-2"><input type="hidden" name="truth_declaration" value="0"><input class="form-check-input" type="checkbox" name="truth_declaration" value="1" id="truth" @checked(old('truth_declaration', isset($application) && $application->truth_declared_at))><label class="form-check-label" for="truth">Saya menyatakan seluruh data yang diajukan benar.</label></div>
    <div class="form-check mb-4"><input type="hidden" name="processing_consent" value="0"><input class="form-check-input" type="checkbox" name="processing_consent" value="1" id="consent" @checked(old('processing_consent', isset($application) && $application->processing_consented_at))><label class="form-check-label" for="consent">Saya menyetujui pemrosesan data untuk penerbitan permit.</label></div>
    <div class="d-flex gap-2"><button name="action" value="draft" class="btn btn-outline-primary">Simpan Draft</button><button name="action" value="submit" class="btn btn-primary">Kirim Pengajuan</button></div>
</div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('application-type');
    const box = document.getElementById('simper-fields');
    const select = document.getElementById('manpower-select');
    const position = document.getElementById('assignment-position');
    const department = document.getElementById('assignment-department');
    const syncType = () => box.style.display = type.value === 'mine_permit_simper' ? '' : 'none';
    type.addEventListener('change', syncType);
    syncType();

    const syncOwner = () => {
        const selected = select.options[select.selectedIndex];
        const owner = selected?.dataset.ownerId || '';
        position.value = selected?.dataset.position || '';
        department.value = selected?.dataset.department || '';
        document.querySelectorAll('.access-area-option').forEach(item => {
            const visible = !!owner && item.dataset.ownerId === owner;
            item.classList.toggle('d-none', !visible);
            if (!visible) item.querySelector('input').checked = false;
        });
        document.querySelectorAll('.simper-category-option').forEach(item => {
            const visible = !!owner && (!item.dataset.ownerId || item.dataset.ownerId === owner);
            item.classList.toggle('d-none', !visible);
            if (!visible) item.querySelector('input[type=checkbox]').checked = false;
        });
    };
    select.addEventListener('change', syncOwner);
    syncOwner();

    document.querySelectorAll('.simper-category-option').forEach(row => {
        const level = row.querySelector('.simper-level');
        const syncLevel = () => {
            row.querySelectorAll('.level-restrictions').forEach(el => el.classList.toggle('d-none', level.value !== 'P'));
            row.querySelectorAll('.level-supervision').forEach(el => el.classList.toggle('d-none', !['T', 'L'].includes(level.value)));
        };
        level.addEventListener('change', syncLevel);
        syncLevel();
    });

    const search = document.getElementById('manpower-search');
    if (search && select) {
        const options = [...select.options].slice(1);
        search.addEventListener('input', () => {
            const term = search.value.toLocaleLowerCase('id');
            options.forEach(option => option.hidden = !option.text.toLocaleLowerCase('id').includes(term));
            const visible = options.filter(option => !option.hidden);
            if (visible.length === 1) {
                select.value = visible[0].value;
                select.dispatchEvent(new Event('change'));
            }
        });
    }
});
</script>
@endpush
