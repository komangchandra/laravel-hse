@php
    $editing = isset($user);
    $selectedRoles = collect(old('roles', $editing ? $user->getRoleNames()->all() : []));
    $selectedPartner = old('partner_id', $editing ? $user->partner_id : '');
    $active = old('is_active', $editing ? (int) $user->is_active : 1);
    $roleLabels = ['developer' => 'Developer', 'ktt' => 'KTT', 'hse_owner' => 'HSE Owner', 'safety_mitra' => 'Safety Mitra'];
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Nama lengkap</label>
        <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold">Email</label>
        <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="mobile" class="form-label fw-semibold">Nomor telepon</label>
        <input id="mobile" type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $user->mobile ?? '') }}">
        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="partner_id" class="form-label fw-semibold">Organisasi</label>
        <select id="partner_id" name="partner_id" class="form-select @error('partner_id') is-invalid @enderror">
            <option value="">Tanpa organisasi (khusus Developer)</option>
            @foreach ($partners as $partner)
                <option value="{{ $partner->id }}" data-kind="{{ $partner->organization_kind }}" @selected((string) $selectedPartner === (string) $partner->id)>
                    {{ $partner->short_name }} — {{ $partner->isOwner() ? 'Owner' : 'Mitra' }}
                </option>
            @endforeach
        </select>
        @error('partner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div id="organization-help" class="form-text">Pilih role agar organisasi yang sesuai dapat ditentukan.</div>
    </div>
</div>

<hr class="my-4">

<fieldset class="mb-4">
    <legend class="form-label fw-semibold">Role akun</legend>
    <div class="row g-2">
        @foreach ($roles as $role)
            <div class="col-sm-6 col-xl-3">
                <div class="form-check border rounded p-3 h-100">
                    <input id="role_{{ $role->name }}" class="form-check-input role-option" type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($selectedRoles->contains($role->name))>
                    <label class="form-check-label fw-medium" for="role_{{ $role->name }}">{{ $roleLabels[$role->name] ?? $role->name }}</label>
                </div>
            </div>
        @endforeach
    </div>
    @error('roles') <div class="small text-danger mt-2">{{ $message }}</div> @enderror
    @error('roles.*') <div class="small text-danger mt-2">{{ $message }}</div> @enderror
</fieldset>

<div class="row g-3">
    <div class="col-md-6">
        <label for="password" class="form-label fw-semibold">{{ $editing ? 'Password baru' : 'Password awal' }}</label>
        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $editing ? '' : 'required' }} autocomplete="new-password">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Minimal 8 karakter, berisi huruf besar, huruf kecil, angka, dan simbol. {{ $editing ? 'Kosongkan jika tidak diubah.' : '' }}</div>
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" {{ $editing ? '' : 'required' }} autocomplete="new-password">
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input id="is_active" class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool) $active)>
            <label class="form-check-label fw-semibold" for="is_active">Akun aktif</label>
            <div class="form-text">Akun aktif dapat langsung login. Menonaktifkan akun akan mengakhiri seluruh sesi login miliknya.</div>
        </div>
        @error('is_active') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const roles = [...document.querySelectorAll('.role-option')];
    const organization = document.getElementById('partner_id');
    const help = document.getElementById('organization-help');

    function updateRoleOptions(changedRole = null) {
        const selected = roles.filter((input) => input.checked).map((input) => input.value);
        if (changedRole === 'developer' && selected.includes('developer')) {
            roles.forEach((input) => { if (input.value !== 'developer') input.checked = false; });
        } else if (changedRole && changedRole !== 'developer') {
            roles.find((input) => input.value === 'developer').checked = false;
        }

        const current = roles.filter((input) => input.checked).map((input) => input.value);
        if (current.includes('safety_mitra') && changedRole === 'safety_mitra') {
            roles.forEach((input) => { if (input.value !== 'safety_mitra') input.checked = false; });
        } else if (current.includes('safety_mitra') && current.some((role) => ['ktt', 'hse_owner'].includes(role))) {
            roles.find((input) => input.value === 'safety_mitra').checked = false;
        }

        const finalRoles = roles.filter((input) => input.checked).map((input) => input.value);
        const developer = finalRoles.includes('developer');
        const requiredKind = finalRoles.includes('safety_mitra') ? 'partner' : finalRoles.some((role) => ['ktt', 'hse_owner'].includes(role)) ? 'owner' : null;
        organization.disabled = developer;
        if (developer) organization.value = '';
        [...organization.options].forEach((option) => {
            if (!option.value) return;
            option.hidden = requiredKind !== null && option.dataset.kind !== requiredKind;
            option.disabled = option.hidden;
        });
        if (organization.selectedOptions[0]?.disabled) organization.value = '';
        help.textContent = developer ? 'Developer bersifat global dan tidak terikat organisasi.' : requiredKind === 'partner' ? 'Safety Mitra wajib ditempatkan pada organisasi mitra.' : requiredKind === 'owner' ? 'KTT dan HSE Owner wajib ditempatkan pada organisasi owner.' : 'Pilih role agar organisasi yang sesuai dapat ditentukan.';
    }

    roles.forEach((input) => input.addEventListener('change', () => updateRoleOptions(input.value)));
    updateRoleOptions();
});
</script>
@endpush
