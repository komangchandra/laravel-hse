<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttemptQuestion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'question_snapshot' => 'array',
        'options_snapshot' => 'array',
        'score_snapshot' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(
            ExamAttempt::class,
            'exam_attempt_id'
        );
    }

    public function question()
    {
        return $this->belongsTo(
            Question::class
        );
    }

    public function answer()
    {
        return $this->hasOne(ExamAttemptAnswer::class);
    }
}
