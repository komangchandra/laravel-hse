<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'authenticated_at' => 'datetime',
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'finished_at' => 'datetime',
            'finalized_at' => 'datetime',
            'is_passed' => 'boolean',
            'attempt_number' => 'integer',
            'duration_minutes_snapshot' => 'integer',
            'passing_score_snapshot' => 'integer',
            'max_score_snapshot' => 'integer',
            'raw_score' => 'decimal:2',
            'score' => 'decimal:2',
            'wrong_answers' => 'integer',
            'blank_answers' => 'integer',
        ];
    }

    public function simper()
    {
        return $this->belongsTo(Simper::class);
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    public function questions()
    {
        return $this->hasMany(
            ExamAttemptQuestion::class
        );
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->whereHas('application', fn (Builder $query) => $query->visibleTo($user))
                ->orWhereHas('simper', fn (Builder $query) => $query->visibleTo($user));
        });
    }

    public function participantValue(string $field): mixed
    {
        return $this->application
            ? data_get($this->application->submitted_snapshot, $field, $this->application->manpower?->{$field})
            : $this->simper?->manpowerValue($field);
    }

    public function referenceNumber(): ?string
    {
        return $this->application?->application_number ?? $this->simper?->code;
    }
}
