<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
            'duration' => 'integer',
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function blueprints()
    {
        return $this->hasMany(ExamSessionBlueprint::class);
    }

    public function tokens()
    {
        return $this->hasMany(ExamToken::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query;
        }

        return $query->where('owner_id', $user->ownerOrganizationId());
    }

    public function categories()
    {
        return $this->belongsToMany(
            QuestionCategory::class,
            'exam_session_categories',
            'exam_session_id',
            'category_id'
        )->withPivot('question_count');
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
