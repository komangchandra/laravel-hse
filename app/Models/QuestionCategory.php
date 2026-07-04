<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    protected $guarded = [];

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
