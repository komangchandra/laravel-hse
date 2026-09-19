<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamToken extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'token',
        'display_token',
    ];

    protected $casts = [
        'display_token' => 'encrypted',
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function simper()
    {
        return $this->belongsTo(
            Simper::class
        );
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public static function digest(string $plainToken): string
    {
        return hash('sha256', strtoupper(trim($plainToken)));
    }
}
