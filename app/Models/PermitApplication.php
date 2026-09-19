<?php

namespace App\Models;

use App\Enums\PermitApplicationStatus;
use App\Enums\PermitApplicationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitApplication extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'status' => PermitApplicationStatus::Draft->value,
        'version' => 1,
        'submission_version' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => PermitApplicationType::class,
            'status' => PermitApplicationStatus::class,
            'version' => 'integer',
            'submission_version' => 'integer',
            'submitted_snapshot' => 'array',
            'issued_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'issuance_requested_at' => 'datetime',
            'issued_at' => 'datetime',
            'planned_start_date' => 'date',
            'requested_valid_until' => 'date',
            'truth_declared_at' => 'datetime',
            'processing_consented_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(SimperCategory::class, 'application_simper_categories')
            ->withPivot(['level', 'restrictions', 'supervisor_name', 'activity_start_date', 'activity_end_date'])
            ->withTimestamps();
    }

    public function accessAreas(): BelongsToMany
    {
        return $this->belongsToMany(AccessArea::class, 'application_access_areas')->withTimestamps();
    }

    public function documentSnapshots(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ApplicationReview::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderBy('application_version');
    }

    public function examSession()
    {
        return $this->hasOne(ExamSession::class);
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function examTokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function issuance()
    {
        return $this->hasOne(PermitIssuance::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->whereIn('partner_id', $user->accessibleOrganizationIds());
    }
}
