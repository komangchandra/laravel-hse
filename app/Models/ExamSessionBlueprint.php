<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSessionBlueprint extends Model
{
    protected $guarded = [];

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function simperCategory()
    {
        return $this->belongsTo(SimperCategory::class);
    }

    public function questionCategory()
    {
        return $this->belongsTo(QuestionCategory::class);
    }
}
