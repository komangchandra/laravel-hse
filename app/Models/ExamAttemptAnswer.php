<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttemptAnswer extends Model
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
        return $this->belongsTo(Question::class);
    }

    public function option()
    {
        return $this->belongsTo(
            AnswerOption::class,
            'answer_option_id'
        );
    }
}
