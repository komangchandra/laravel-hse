<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimperCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function simpers()
    {
        return $this->belongsToMany(Simper::class, 'simper_simper_category')
            ->withPivot('level')
            ->withTimestamps();
    }
}
