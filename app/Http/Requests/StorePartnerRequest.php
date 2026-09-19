<?php

namespace App\Http\Requests;

use App\Models\Partner;
use App\Models\PartnerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('developer') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'legal_name' => trim((string) $this->input('legal_name')),
            'short_name' => strtoupper(trim((string) $this->input('short_name'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'status' => strtolower((string) $this->input('status')),
            'owner_id' => $this->input('owner_id') ?: null,
            'partner_type_id' => $this->input('partner_type_id') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50', 'unique:partners,short_name'],
            'email' => ['required', 'email', 'max:255', 'unique:partners,email'],
            'organization_kind' => ['required', Rule::in([Partner::KIND_OWNER, Partner::KIND_PARTNER])],
            'owner_id' => ['nullable', 'required_if:organization_kind,partner', 'prohibited_if:organization_kind,owner', 'integer', 'exists:partners,id'],
            'partner_type_id' => ['nullable', 'required_if:organization_kind,partner', 'prohibited_if:organization_kind,owner', 'integer', 'exists:partner_types,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'slowdown', 'suspended'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'permit_prefix' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('organization_kind') !== Partner::KIND_PARTNER) {
                return;
            }

            $owner = Partner::find($this->input('owner_id'));
            if ($owner && (! $owner->isOwner() || $owner->owner_id !== null)) {
                $validator->errors()->add('owner_id', 'Organisasi induk harus berupa owner tingkat utama.');
            }

            $type = PartnerType::find($this->input('partner_type_id'));
            if ($type && ! $type->is_active) {
                $validator->errors()->add('partner_type_id', 'Tipe mitra sudah tidak aktif.');
            }
        }];
    }
}
