<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerOption extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
