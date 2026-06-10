<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Partner::create([
            'legal_name' => 'PT. Gorby Putra Utama',
            'short_name' => 'GPU',
            'email' => 'gpu@gorbyputrautama.com',
            'status' => 'active',
            'level' => 'owner'
        ]);
    }
}
