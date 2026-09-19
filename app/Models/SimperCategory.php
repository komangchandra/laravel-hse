<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SimperCategory extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'description',
    ];

    public function owner()
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNull('owner_id')
            ->orWhere('owner_id', $user->ownerOrganizationId()));
    }

    public function simpers()
    {
        return $this->belongsToMany(Simper::class, 'simper_simper_category')
            ->withPivot('level')
            ->withTimestamps();
    }
}
