<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function options()
    {
        return $this->hasMany(AnswerOption::class);
    }

    public function keywords()
    {
        return $this->hasMany(QuestionKeyword::class);
    }

    public function attemptQuestions()
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

        return $query->whereHas('category', fn (Builder $query) => $query->visibleTo($user));
    }

    public function scopeValidForExam(Builder $query): Builder
    {
        return $query->where('type', 'multiple_choice')
            ->where('score', '>', 0)
            ->whereHas('options', null, '>=', 2)
            ->whereHas('options', fn (Builder $query) => $query->where('is_correct', true), '=', 1);
    }
}
