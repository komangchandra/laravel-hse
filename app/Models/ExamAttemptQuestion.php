<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttemptQuestion extends Model
{
    protected $guarded = [];

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
}