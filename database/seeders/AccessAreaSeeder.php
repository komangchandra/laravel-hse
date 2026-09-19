<?php

namespace Database\Seeders;

use App\Models\AccessArea;
use App\Models\Partner;
use Illuminate\Database\Seeder;

class AccessAreaSeeder extends Seeder
{
    private const AREAS = [
        'pit' => 'PIT',
        'ccp-hauling-road' => 'Area CCP & Hauling Road',
        'workshop' => 'Workshop',
        'office-mess' => 'Kantor & Mess',
    ];

    public function run(): void
    {
        Partner::owners()->each(function (Partner $owner): void {
            AccessArea::where('owner_id', $owner->id)
                ->whereNotIn('code', array_keys(self::AREAS))
                ->update(['is_active' => false]);

            foreach (self::AREAS as $code => $name) {
                AccessArea::updateOrCreate(
                    ['owner_id' => $owner->id, 'code' => $code],
                    ['name' => $name, 'is_active' => true],
                );
            }
        });
    }
}
