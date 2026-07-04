<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamToken extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function simper()
    {
        return $this->belongsTo(
            Simper::class
        );
    }
}