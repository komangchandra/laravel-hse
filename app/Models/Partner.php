<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    public const KIND_OWNER = 'owner';

    public const KIND_PARTNER = 'partner';

    protected $guarded = [];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'owner_id');
    }

    public function childPartners(): HasMany
    {
        return $this->hasMany(self::class, 'owner_id');
    }

    public function partnerType(): BelongsTo
    {
        return $this->belongsTo(PartnerType::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function manpowers(): HasMany
    {
        return $this->hasMany(Manpower::class);
    }

    public function simpers(): HasMany
    {
        return $this->hasMany(Simper::class);
    }

    public function permitApplications(): HasMany
    {
        return $this->hasMany(PermitApplication::class, 'partner_id');
    }

    public function ownedPermitApplications(): HasMany
    {
        return $this->hasMany(PermitApplication::class, 'owner_id');
    }

    public function accessAreas(): HasMany
    {
        return $this->hasMany(AccessArea::class, 'owner_id');
    }

    public function permitIssuances(): HasMany
    {
        return $this->hasMany(PermitIssuance::class, 'owner_id');
    }

    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('organization_kind', self::KIND_OWNER);
    }

    public function scopePartners(Builder $query): Builder
    {
        return $query->where('organization_kind', self::KIND_PARTNER);
    }

    public function scopeOwnedBy(Builder $query, self|int $owner): Builder
    {
        return $query->where('owner_id', $owner instanceof self ? $owner->getKey() : $owner);
    }

    public function scopeForOwner(Builder $query, self|int $owner): Builder
    {
        $ownerId = $owner instanceof self ? $owner->getKey() : $owner;

        return $query->where(function (Builder $query) use ($ownerId) {
            $query->whereKey($ownerId)->orWhere('owner_id', $ownerId);
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->whereIn($this->qualifyColumn('id'), $user->accessibleOrganizationIds());
    }

    public function isOwner(): bool
    {
        return $this->organization_kind === self::KIND_OWNER;
    }

    public function isPartner(): bool
    {
        return $this->organization_kind === self::KIND_PARTNER;
    }

    public function hasOperationalData(): bool
    {
        return $this->users()->exists() || $this->manpowers()->exists() || $this->simpers()->exists()
            || $this->permitApplications()->exists() || $this->ownedPermitApplications()->exists();
    }
}
