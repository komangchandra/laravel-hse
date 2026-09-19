<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccessArea extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function owner()
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    public function applications()
    {
        return $this->belongsToMany(PermitApplication::class, 'application_access_areas')->withTimestamps();
    }

    public function scopeForOwner(Builder $query, int $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }
}
