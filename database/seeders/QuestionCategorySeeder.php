<?php

namespace Database\Seeders;

use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;

class QuestionCategorySeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];

    private const CATEGORIES = [
        [
            'name' => 'Soal Teori Light Vehicle',
            'description' => 'Kategori soal yang berfokus pada aspek keselamatan kendaraan ringan.',
            'measured' => 'Peraturan lalu lintas, pemeriksaan kendaraan, penggunaan sabuk pengaman, rambu, dan identifikasi bahaya kendaraan ringan.',
            'measurable' => 'Diukur melalui ujian teori keselamatan dan operasional kendaraan ringan.',
        ],
        [
            'name' => 'Soal Teori Rambu-rambu',
            'description' => 'Kategori soal yang berfokus pada rambu peringatan, larangan, perintah, dan petunjuk di area operasional.',
            'measured' => 'Kemampuan mengenali arti, fungsi, dan tindakan yang harus dilakukan berdasarkan rambu.',
            'measurable' => 'Diukur melalui ujian teori pengenalan dan penerapan rambu.',
        ],
        [
            'name' => 'Soal Teori Dump Truck',
            'description' => 'Kategori soal yang berfokus pada keselamatan dan operasional dump truck.',
            'measured' => 'Pemeriksaan unit, prosedur operasi, komunikasi, blind spot, dumping, dan pengendalian bahaya dump truck.',
            'measurable' => 'Diukur melalui ujian teori keselamatan dan operasional dump truck.',
        ],
        [
            'name' => 'Soal Teori Excavator',
            'description' => 'Kategori soal yang berfokus pada keselamatan dan operasional excavator.',
            'measured' => 'Pemeriksaan unit, prosedur operasi, swing radius, lifting, penggalian, dan pengendalian bahaya excavator.',
            'measurable' => 'Diukur melalui ujian teori keselamatan dan operasional excavator.',
        ],
        [
            'name' => 'Soal Teori Bulldozer',
            'description' => 'Kategori soal yang berfokus pada keselamatan dan operasional bulldozer.',
            'measured' => 'Pemeriksaan unit, prosedur operasi, kestabilan medan, dozing, ripping, dan pengendalian bahaya bulldozer.',
            'measurable' => 'Diukur melalui ujian teori keselamatan dan operasional bulldozer.',
        ],
        [
            'name' => 'Soal Teori Motorgrader',
            'description' => 'Kategori soal yang berfokus pada keselamatan dan operasional motor grader.',
            'measured' => 'Pemeriksaan unit, prosedur operasi, pengaturan blade, grading, kondisi jalan, dan pengendalian bahaya motor grader.',
            'measurable' => 'Diukur melalui ujian teori keselamatan dan operasional motor grader.',
        ],
    ];

    public function run(): void
    {
        foreach (self::OWNER_IDS as $ownerId) {
            foreach (self::CATEGORIES as $category) {
                QuestionCategory::updateOrCreate(
                    [
                        'owner_id' => $ownerId,
                        'name' => $category['name'],
                    ],
                    $category,
                );
            }
        }
    }
}
