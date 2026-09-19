<?php

namespace App\Models;

use App\Enums\PermitApplicationStatus;
use Illuminate\Database\Eloquent\Model;

class ApplicationStatusHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_status' => PermitApplicationStatus::class,
            'to_status' => PermitApplicationStatus::class,
            'metadata' => 'array',
            'application_version' => 'integer',
            'transitioned_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
