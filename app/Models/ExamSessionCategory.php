<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSessionCategory extends Model
{
    protected $guarded = [];

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }
}
