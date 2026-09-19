<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PermitIssuance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'print_snapshot' => 'array',
            'issued_at' => 'datetime',
            'valid_from' => 'date',
            'expires_at' => 'date',
            'simper_expires_at' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function owner()
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function manpower()
    {
        return $this->belongsTo(Manpower::class)->withTrashed();
    }

    public function approvalReview()
    {
        return $this->belongsTo(ApplicationReview::class, 'approval_review_id');
    }

    public function replacesIssuance()
    {
        return $this->belongsTo(self::class, 'replaces_issuance_id');
    }

    public function replacement()
    {
        return $this->hasOne(self::class, 'replaces_issuance_id');
    }

    public function printLogs()
    {
        return $this->hasMany(PermitPrintLog::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->whereIn('partner_id', $user->accessibleOrganizationIds());
    }

    public function isPrintable(): bool
    {
        return $this->status === 'active' && ! $this->expires_at->isPast();
    }
}
