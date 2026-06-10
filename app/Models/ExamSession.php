<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $guarded = [];

    public function categories()
    {
        return $this->belongsToMany(QuestionCategory::class, 'exam_session_categories');
    }
}
