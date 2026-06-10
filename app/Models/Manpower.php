<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manpower extends Model
{
    protected $guarded = [];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
