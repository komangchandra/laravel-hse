<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simper extends Model
{
    protected $fillable = [
        'partner_id',
        'manpower_id',
        'code',
        'driving_license_number',
        'license_class',
        'valid_from',
        'valid_until',
        'status',
        'violations',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function manpower()
    {
        return $this->belongsTo(Manpower::class);
    }

    public function categories()
    {
        return $this->belongsToMany(SimperCategory::class, 'simper_simper_category')
            ->withPivot('level')
            ->withTimestamps();
    }
}
