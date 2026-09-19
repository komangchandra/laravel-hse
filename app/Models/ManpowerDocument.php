<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManpowerDocument extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'assignment_letter' => 'Surat Pengantar Perusahaan',
        'identity' => 'KTP',
        'safety_induction' => 'Bukti Induksi',
        'medical_checkup' => 'Surat Pernyataan, Medical Checkup & Tes NAPZA',
        'hse_compliance' => 'Bukti Sosialisasi SOP',
        'driver_license' => 'SIM A / SIM B2 Umum (sesuai kategori)',
        'operator_certificate' => 'Sertifikat Pengoperasian Unit (khusus A2B)',
        'initial_assessment' => 'Form Hasil Tes Praktik',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
            'version' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isValid(): bool
    {
        return $this->verification_status !== 'rejected';
    }
}
