<?php

namespace Database\Seeders;

use App\Models\PartnerType;
use Illuminate\Database\Seeder;

class PartnerTypeSeeder extends Seeder
{
    private const TYPES = [
        ['code' => 'rental', 'name' => 'Rental'],
        ['code' => 'heavy_equipment_contractor', 'name' => 'Kontraktor Alat Berat'],
        ['code' => 'mining_contractor', 'name' => 'Kontraktor Tambang'],
        ['code' => 'kso', 'name' => 'Kerja Sama Operasi (KSO)'],
        ['code' => 'transport_contractor', 'name' => 'Kontraktor Transportasi'],
        ['code' => 'service_vendor', 'name' => 'Penyedia Jasa'],
        ['code' => 'supplier', 'name' => 'Pemasok'],
        ['code' => 'other', 'name' => 'Lainnya'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            PartnerType::updateOrCreate(['code' => $type['code']], $type + ['is_active' => true]);
        }
    }
}
