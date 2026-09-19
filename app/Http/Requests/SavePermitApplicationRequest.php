<?php

namespace App\Http\Requests;

use App\Enums\PermitApplicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePermitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'version' => ['nullable', 'integer', 'min:1'],
            'manpower_id' => ['required', 'integer', 'exists:manpowers,id'],
            'type' => ['required', Rule::enum(PermitApplicationType::class)],
            'site_name' => ['nullable', 'string', 'max:255'],
            'planned_start_date' => ['nullable', 'date'],
            'applicant_notes' => ['nullable', 'string', 'max:2000'],
            'truth_declaration' => ['nullable', 'boolean'],
            'processing_consent' => ['nullable', 'boolean'],
            'access_area_ids' => ['nullable', 'array'],
            'access_area_ids.*' => ['integer', 'distinct', 'exists:access_areas,id'],
            'categories' => ['nullable', 'array'],
            'categories.*.selected' => ['nullable', 'boolean'],
            'categories.*.level' => ['nullable', Rule::in(['F', 'P', 'T', 'L'])],
            'categories.*.restrictions' => ['nullable', 'string', 'max:1000'],
            'categories.*.supervisor_name' => ['nullable', 'string', 'max:255'],
            'categories.*.activity_start_date' => ['nullable', 'date'],
            'categories.*.activity_end_date' => ['nullable', 'date', 'after_or_equal:categories.*.activity_start_date'],
        ];
    }

    public function draftData(): array
    {
        return $this->only([
            'site_name', 'planned_start_date', 'applicant_notes', 'truth_declaration', 'processing_consent',
        ]);
    }

    public function selectedCategories(): array
    {
        return collect($this->input('categories', []))
            ->filter(fn (array $category) => filter_var($category['selected'] ?? false, FILTER_VALIDATE_BOOL))
            ->map(fn (array $category, $id) => [
                'category_id' => (int) $id,
                'level' => $category['level'] ?? '',
                'restrictions' => $category['restrictions'] ?? null,
                'supervisor_name' => $category['supervisor_name'] ?? null,
                'activity_start_date' => $category['activity_start_date'] ?? null,
                'activity_end_date' => $category['activity_end_date'] ?? null,
            ])->values()->all();
    }
}
