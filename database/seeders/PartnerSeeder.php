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

        Partner::create([
            'legal_name' => 'PT. Gorby Energy',
            'short_name' => 'GE',
            'email' => 'ge@gorbyputrautama.com',
            'status' => 'active',
            'level' => 'owner'
        ]);

        Partner::create([
            'legal_name' => 'PT. Bara Permata Mining',
            'short_name' => 'BPM',
            'email' => 'hsebarapermatamining@gmail.com',
            'status' => 'active',
            'level' => 'contractor'
        ]);

        Partner::create([
            'legal_name' => 'PT. Gericon Prima',
            'short_name' => 'GP',
            'email' => 'gericon.sitegpu@gmail.com',
            'status' => 'active',
            'level' => 'contractor'
        ]);

        Partner::create([
            'legal_name' => 'PT. Karunia Mitrabara Bestari',
            'short_name' => 'KMB',
            'email' => 'dedhyjefriadhy@gmail.com',
            'status' => 'active',
            'level' => 'contractor'
        ]);

        Partner::create([
            'legal_name' => 'PT. Multi Service Mining',
            'short_name' => 'MSM',
            'email' => 'safetymsmbisa@gmail.com',
            'status' => 'active',
            'level' => 'contractor'
        ]);

        Partner::create([
            'legal_name' => 'PT. Berkat Primat Lancar',
            'short_name' => 'BPL',
            'email' => 'bplsitege@gmail.com',
            'status' => 'active',
            'level' => 'contractor'
        ]);

        Partner::create([
            'legal_name' => 'PT. Karimata Multi Prima',
            'short_name' => 'KMP',
            'email' => 'basarudin.mahmud@karimatamultiprima.com',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Grand Indo Perkasa',
            'short_name' => 'GIP',
            'email' => 'hsegip24@gmail.com',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Huthama Buana Perkasa',
            'short_name' => 'HBP',
            'email' => 'hsehbp@gmail.com',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Maju Jay Rental Service',
            'short_name' => 'MJRS',
            'email' => 'ndraperkasa@gmail.com',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Dadide Sukses Rental',
            'short_name' => 'DSR',
            'email' => 'dsbsumsel@gmail.com',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Bach Multi Global',
            'short_name' => 'BMG',
            'email' => 'prayogi.sukmana@bachmultiglobal.co.id',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Aradda Daya Abadi',
            'short_name' => 'ADA',
            'email' => 'araddadayaabadi@gmail.com',
            'status' => 'active',
            'level' => 'rental'
        ]);

        Partner::create([
            'legal_name' => 'PT. Trans Jaya Pertama',
            'short_name' => 'TJP',
            'email' => 'irvanssilalahi@gmail.com',
            'status' => 'active',
            'level' => 'rental'
        ]);
    }
}
