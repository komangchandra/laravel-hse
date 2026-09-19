@php($editing = isset($manpower))

@if(auth()->user()->isDeveloper())
    <div class="mb-4">
        <label for="partner_id" class="form-label fw-semibold">Organisasi tenaga kerja</label>
        <select name="partner_id" id="partner_id" class="form-select @error('partner_id') is-invalid @enderror" required>
            <option value="">Pilih organisasi</option>
            @foreach($partners as $partner)
                <option value="{{ $partner->id }}" @selected(old('partner_id', $manpower->partner_id ?? '') == $partner->id)>{{ $partner->legal_name }}</option>
            @endforeach
        </select>
        @error('partner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@else
    <div class="alert alert-light border"><strong>Perusahaan:</strong> {{ auth()->user()->partner->legal_name }}</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label for="nik" class="form-label fw-semibold">NIK/nomor pekerja</label>
        <input id="nik" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $manpower->nik ?? '') }}" required>
        @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">NIK harus unik dalam satu owner.</div>
    </div>
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Nama lengkap</label>
        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $manpower->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="position" class="form-label fw-semibold">Jabatan</label>
        <input id="position" name="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $manpower->position ?? '') }}" required>
        @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="department" class="form-label fw-semibold">Departemen</label>
        <input id="department" name="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department', $manpower->department ?? '') }}" required>
        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="birth_place" class="form-label fw-semibold">Tempat lahir</label>
        <input id="birth_place" name="birth_place" class="form-control @error('birth_place') is-invalid @enderror" value="{{ old('birth_place', $manpower->birth_place ?? '') }}" required>
        @error('birth_place') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="birth_date" class="form-label fw-semibold">Tanggal lahir</label>
        <input id="birth_date" type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', isset($manpower) ? $manpower->birth_date?->toDateString() : '') }}" required>
        @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="contact_number" class="form-label fw-semibold">Nomor telepon</label>
        <input id="contact_number" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $manpower->contact_number ?? '') }}" required>
        @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="blood_type" class="form-label fw-semibold">Golongan darah</label>
        <select id="blood_type" name="blood_type" class="form-select @error('blood_type') is-invalid @enderror" required>
            <option value="">Pilih golongan darah</option>
            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $blood)
                <option value="{{ $blood }}" @selected(old('blood_type', $manpower->blood_type ?? '') === $blood)>{{ $blood }}</option>
            @endforeach
        </select>
        @error('blood_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="emergency_contact_name" class="form-label fw-semibold">Nama kontak darurat</label>
        <input id="emergency_contact_name" name="emergency_contact_name" class="form-control @error('emergency_contact_name') is-invalid @enderror" value="{{ old('emergency_contact_name', $manpower->emergency_contact_name ?? '') }}" required>
        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="emergency_contact_number" class="form-label fw-semibold">Nomor kontak darurat</label>
        <input id="emergency_contact_number" name="emergency_contact_number" class="form-control @error('emergency_contact_number') is-invalid @enderror" value="{{ old('emergency_contact_number', $manpower->emergency_contact_number ?? '') }}" required>
        @error('emergency_contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label for="photo_path" class="form-label fw-semibold">Foto formal</label>
        <input id="photo_path" type="file" name="photo_path" accept="image/jpeg,image/png" class="form-control @error('photo_path') is-invalid @enderror" {{ $editing && $manpower->photo_path ? '' : 'required' }}>
        @error('photo_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">JPG/PNG, rasio 3:4, minimal 300×400 px, maksimal 5 MB.</div>
    </div>
    <div class="col-md-4">
        <label for="is_active" class="form-label fw-semibold">Status tenaga kerja</label>
        <input type="hidden" name="is_active" value="0">
        <div class="form-control d-flex align-items-center">
            <div class="form-check form-switch mb-0">
                <input id="is_active" class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $manpower->is_active ?? true))>
                <label for="is_active" class="form-check-label fw-semibold">Tenaga kerja aktif</label>
            </div>
        </div>
        <div class="form-text">Nonaktifkan jika tenaga kerja tidak lagi dapat diajukan.</div>
    </div>
</div>
