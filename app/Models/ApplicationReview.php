<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationReview extends Model
{
    protected $guarded = [];

    protected $hidden = ['approval_claim_token'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submission_version' => 'integer',
            'reviewer_snapshot' => 'array',
        ];
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
