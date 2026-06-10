<?php

namespace Database\Seeders;

use App\Models\SimperCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SimperCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SimperCategory::create([
            'name' => 'Simper Light Vehicle',
            'description' => 'Kategori simper yang berfokus pada aspek-aspek keselamatan terkait kendaraan ringan, seperti mobil penumpang, kendaraan kecil, atau kendaraan komersial ringan.',
        ]);

        SimperCategory::create([
            'name' => 'Simper Excavator',
            'description' => 'Kategori simper yang berfokus pada aspek-aspek keselamatan terkait kendaraan berat, seperti excavator',
        ]);

        SimperCategory::create([
            'name' => 'Simper Dump Truck',
            'description' => 'Kategori simper yang berfokus pada aspek-aspek keselamatan terkait kendaraan berat, seperti dump truck',
        ]);

        SimperCategory::create([
            'name' => 'Simper Bulldozer',
            'description' => 'Kategori simper yang berfokus pada aspek-aspek keselamatan terkait kendaraan berat, seperti bulldozer',
        ]);

        SimperCategory::create([
            'name' => 'Simper Motorgrader',
            'description' => 'Kategori simper yang berfokus pada aspek-aspek keselamatan terkait kendaraan berat, seperti motorgrader',
        ]);
    }
}
