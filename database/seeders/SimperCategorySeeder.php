<?php

namespace Database\Seeders;

use App\Models\SimperCategory;
use Illuminate\Database\Seeder;

class SimperCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        [
            'name' => 'Simper Light Vehicle',
            'description' => 'Kendaraan ringan untuk angkutan orang atau barang di area operasional.',
        ],
        [
            'name' => 'Simper Dump Truck',
            'description' => 'Kendaraan angkut material tambang dengan bak jungkit.',
        ],
        [
            'name' => 'Simper Excavator',
            'description' => 'Alat berat untuk penggalian, pemuatan, dan pekerjaan material handling.',
        ],
        [
            'name' => 'Simper Bulldozer',
            'description' => 'Alat berat untuk mendorong, meratakan, dan ripping material.',
        ],
        [
            'name' => 'Simper Motorgrader',
            'description' => 'Alat berat untuk pembentukan dan pemeliharaan permukaan jalan.',
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            SimperCategory::updateOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }
    }
}
