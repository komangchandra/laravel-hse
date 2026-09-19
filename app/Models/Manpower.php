<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manpower extends Model
{
    use SoftDeletes;

    public const REQUIRED_MINE_PERMIT_DOCUMENTS = [
        'identity',
        'assignment_letter',
        'medical_checkup',
        'safety_induction',
        'hse_compliance',
    ];

    protected $guarded = [];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    public function simpers(): HasMany
    {
        return $this->hasMany(Simper::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ManpowerDocument::class);
    }

    public function permitApplications(): HasMany
    {
        return $this->hasMany(PermitApplication::class);
    }

    public function permitIssuances(): HasMany
    {
        return $this->hasMany(PermitIssuance::class);
    }

    public function allDocuments(): HasMany
    {
        return $this->documents()->withTrashed();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->whereIn('partner_id', $user->accessibleOrganizationIds());
    }

    /** @return list<string> */
    public function missingRequiredDocumentTypes(?array $requiredTypes = null): array
    {
        $validTypes = $this->documents
            ->sortByDesc('version')
            ->unique('type')
            ->filter(fn (ManpowerDocument $document) => $document->isValid())
            ->pluck('type')->unique();

        return collect($requiredTypes ?? self::REQUIRED_MINE_PERMIT_DOCUMENTS)->diff($validTypes)->values()->all();
    }

    /**
     * @param  iterable<SimperCategory>  $categories
     * @return list<string>
     */
    public static function requiredDocumentTypesForCategories(iterable $categories): array
    {
        $categoryNames = collect($categories)->pluck('name')->map(fn (string $name) => mb_strtolower($name));
        $requiresOperatorCertificate = $categoryNames->contains(fn (string $name) => str_contains($name, 'a2b')
            || str_contains($name, 'excavator')
            || str_contains($name, 'bulldozer')
            || str_contains($name, 'motorgrader'));

        return array_values(array_merge(
            self::REQUIRED_MINE_PERMIT_DOCUMENTS,
            ['driver_license'],
            $requiresOperatorCertificate ? ['operator_certificate'] : [],
            ['initial_assessment'],
        ));
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $this->loadMissing(['partner', 'owner', 'documents']);

        return [
            'manpower_id' => $this->id,
            'nik' => $this->nik,
            'name' => $this->name,
            'partner_id' => $this->partner_id,
            'partner_name' => $this->partner?->legal_name,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->owner?->legal_name,
            'position' => $this->position,
            'department' => $this->department,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->toDateString(),
            'blood_type' => $this->blood_type,
            'contact_number' => $this->contact_number,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_number' => $this->emergency_contact_number,
            'photo_path' => $this->photo_path,
            'documents' => $this->documents->map(fn (ManpowerDocument $document) => [
                'id' => $document->id,
                'type' => $document->type,
                'document_number' => $document->document_number,
                'issued_at' => $document->issued_at?->toDateString(),
                'expires_at' => $document->expires_at?->toDateString(),
                'checksum' => $document->checksum,
                'version' => $document->version,
                'verification_status' => $document->verification_status,
            ])->values()->all(),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->withTrashed()->where($field ?? $this->getRouteKeyName(), $value);
    }
}
