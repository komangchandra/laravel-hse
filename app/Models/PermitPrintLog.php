<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitPrintLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'printed_at' => 'datetime'];
    }

    public function issuance()
    {
        return $this->belongsTo(PermitIssuance::class, 'permit_issuance_id');
    }

    public function printer()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
