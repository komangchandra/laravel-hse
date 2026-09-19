<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    protected $guarded = [];

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

    public function questions()
    {
        return $this->hasMany(Question::class, 'category_id');
    }

    public function examSessions()
    {
        return $this->belongsToMany(
            ExamSession::class,
            'exam_session_categories',
            'category_id',
            'exam_session_id'
        )->withPivot('question_count');
    }
}
