<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'metadata' => 'array',
            'submission_version' => 'integer',
            'source_version' => 'integer',
        ];
    }

    public function application()
    {
        return $this->belongsTo(PermitApplication::class, 'permit_application_id');
    }

    public function sourceDocument()
    {
        return $this->belongsTo(ManpowerDocument::class, 'manpower_document_id')->withTrashed();
    }
}
