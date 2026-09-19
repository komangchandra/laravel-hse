@php
    $selectedKind = old('organization_kind', $partner->organization_kind ?? 'partner');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="legal_name" class="form-label fw-bold">Nama legal</label>
        <input type="text" name="legal_name" id="legal_name" class="form-control @error('legal_name') is-invalid @enderror"
            value="{{ old('legal_name', $partner->legal_name ?? '') }}" required>
        @error('legal_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="short_name" class="form-label fw-bold">Nama singkat</label>
        <input type="text" name="short_name" id="short_name" class="form-control text-uppercase @error('short_name') is-invalid @enderror"
            value="{{ old('short_name', $partner->short_name ?? '') }}" required>
        @error('short_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="organization_kind" class="form-label fw-bold">Jenis organisasi</label>
        <select name="organization_kind" id="organization_kind" class="form-select @error('organization_kind') is-invalid @enderror" required>
            <option value="owner" @selected($selectedKind === 'owner')>Owner</option>
            <option value="partner" @selected($selectedKind === 'partner')>Mitra</option>
        </select>
        @error('organization_kind') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 partner-field">
        <label for="owner_id" class="form-label fw-bold">Owner</label>
        <select name="owner_id" id="owner_id" class="form-select @error('owner_id') is-invalid @enderror">
            <option value="">Pilih owner</option>
            @foreach($owners as $owner)
                <option value="{{ $owner->id }}" @selected((string) old('owner_id', $partner->owner_id ?? '') === (string) $owner->id)>
                    {{ $owner->legal_name }} ({{ $owner->short_name }})
                </option>
            @endforeach
        </select>
        @error('owner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 partner-field">
        <label for="partner_type_id" class="form-label fw-bold">Tipe mitra</label>
        <select name="partner_type_id" id="partner_type_id" class="form-select @error('partner_type_id') is-invalid @enderror">
            <option value="">Pilih tipe mitra</option>
            @foreach($partnerTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('partner_type_id', $partner->partner_type_id ?? '') === (string) $type->id)>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        @error('partner_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label fw-bold">Email organisasi</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $partner->email ?? '') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="phone" class="form-label fw-bold">Telepon</label>
        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $partner->phone ?? '') }}">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="status" class="form-label fw-bold">Status</label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach(['active' => 'Aktif', 'inactive' => 'Tidak aktif', 'slowdown' => 'Slowdown', 'suspended' => 'Ditangguhkan'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', strtolower($partner->status ?? 'active')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="site_name" class="form-label fw-bold">Nama site</label>
        <input type="text" name="site_name" id="site_name" class="form-control @error('site_name') is-invalid @enderror"
            value="{{ old('site_name', $partner->site_name ?? '') }}">
        @error('site_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="emergency_phone" class="form-label fw-bold">Nomor darurat</label>
        <input type="text" name="emergency_phone" id="emergency_phone" class="form-control @error('emergency_phone') is-invalid @enderror"
            value="{{ old('emergency_phone', $partner->emergency_phone ?? '') }}">
        @error('emergency_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="permit_prefix" class="form-label fw-bold">Prefix kartu/izin</label>
        <input type="text" name="permit_prefix" id="permit_prefix" class="form-control text-uppercase @error('permit_prefix') is-invalid @enderror"
            value="{{ old('permit_prefix', $partner->permit_prefix ?? '') }}">
        @error('permit_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-8">
        <label for="address" class="form-label fw-bold">Alamat</label>
        <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $partner->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="logo" class="form-label fw-bold">Logo</label>
        <input type="file" name="logo" id="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
        <div class="form-text">Maksimal 2 MB.</div>
        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const kind = document.getElementById('organization_kind');
        const partnerFields = document.querySelectorAll('.partner-field');
        const owner = document.getElementById('owner_id');
        const type = document.getElementById('partner_type_id');
        const syncKind = () => {
            const isPartner = kind.value === 'partner';
            partnerFields.forEach(field => field.classList.toggle('d-none', !isPartner));
            owner.required = isPartner;
            type.required = isPartner;
            owner.disabled = !isPartner;
            type.disabled = !isPartner;
        };
        kind.addEventListener('change', syncKind);
        syncKind();
    })();
</script>
@endpush
