<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionKeyword extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'keyword',
        'score',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
