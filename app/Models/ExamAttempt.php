<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $guarded = [];

    public function simper()
    {
        return $this->belongsTo(Simper::class);
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
}