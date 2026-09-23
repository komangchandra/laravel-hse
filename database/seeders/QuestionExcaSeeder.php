<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionExcaSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];

    private const IMAGE_SOURCES = [
        'questions/exca/exca-component-diagram.jpg' => 'seeders/assets/exca-component-diagram.jpg',
        'questions/exca/exca-instrumen-panel-1.png' => 'seeders/assets/exca-instrumen-panel-1.png',
        'questions/exca/exca-instrumen-panel-2.png' => 'seeders/assets/exca-instrumen-panel-2.png',
        'questions/exca/exca-instrumen-panel-3.png' => 'seeders/assets/exca-instrumen-panel-3.png',
        'questions/exca/exca-instrumen-panel-4.png' => 'seeders/assets/exca-instrumen-panel-4.png',
        'questions/exca/exca-instrumen-panel-5.png' => 'seeders/assets/exca-instrumen-panel-5.png',
        'questions/exca/exca-instrumen-panel-6.png' => 'seeders/assets/exca-instrumen-panel-6.png',
        'questions/exca/exca-instrumen-panel-7.png' => 'seeders/assets/exca-instrumen-panel-7.png',
        'questions/exca/exca-instrumen-panel-8.png' => 'seeders/assets/exca-instrumen-panel-8.png',
        'questions/exca/exca-instrumen-panel-9.png' => 'seeders/assets/exca-instrumen-panel-9.png',
        'questions/exca/exca-instrumen-panel-10.png' => 'seeders/assets/exca-instrumen-panel-10.png',
    ];

    public function run(): void
    {
        $this->copyQuestionImages();

        DB::transaction(function () {
            $questions = [
                [
                    'question' => 'Pada waktu akan mengoperasikan mesin, perlengkapan apa yang Anda kenakan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Baju kerja dan kacamata hitam.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Helm, ikat pinggang pengaman, sepatu kerja, dan sarung tangan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Kacamata hitam dan sarung tangan.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Pada waktu mengisi bahan bakar, engine harus dalam kondisi bagaimana?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Boleh hidup asalkan gigi transmisi netral dan tidak merokok.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Harus dimatikan, tidak merokok, dan safety lock diaktifkan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Boleh hidup asalkan rem tangan dimasukkan dan tidak merokok.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Menurut aturan safety, klakson harus dibunyikan berapa kali saat start, maju, dan mundur? Urutan yang benar adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '2 - 3 - 1',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => '3 - 2 - 1',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '1 - 2 - 3',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Saat mengoperasikan mesin, tindakan yang tepat untuk menghindari bahaya adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Membawa penumpang lain untuk membantu operator.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Operator berdiri agar mudah melihat sekeliling.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Tidak membawa penumpang serta memperhatikan rambu kerja dan lingkungan.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Jika terjadi gangguan pada mesin yang Anda operasikan, tindakan yang tepat adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Langsung memeriksa dan berusaha memperbaikinya sendiri.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Meminta bantuan teman untuk memperbaikinya.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menghentikan engine, memarkir unit di tempat aman, dan melapor kepada atasan.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Prosedur turun dari mesin yang baik dan benar adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Turun sambil membelakangi tangga yang tersedia.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Turun sambil menghadap tangga yang tersedia dengan tiga titik tumpu (three-point contact).',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Turun dengan melompat dari tangga yang tersedia.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Saat memeriksa air baterai pada malam hari atau dalam kondisi kurang terang, alat yang dianjurkan adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Lampu senter.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Korek api.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Jari tangan.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Cara kerja yang tidak mematuhi prosedur dengan baik dan membahayakan disebut ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kondisi tidak aman.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tindakan tidak aman.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Kecerobohan manusia.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Pemarah, emosional, dan tidak peduli terhadap K3 termasuk ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Sifat buruk seseorang.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tindakan tidak aman.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Keadaan sosial seseorang.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Siapa yang bertanggung jawab atas keselamatan kerja di lingkungannya?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Perusahaan atau pimpinan.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Pekerja.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Bersama-sama.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa batas kecepatan maksimum di jalan hauling dan jalan tambang/pit yang diizinkan di area kerja tambang PT MSM?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '40 km/jam dan 30 km/jam.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => '40 km/jam dan 40 km/jam.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '40 km/jam dan 50 km/jam.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berikut ini merupakan tindakan yang harus dilakukan jika mengalami rem blong di jalan tambang, kecuali ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mengurangi kecepatan dan mengaktifkan hand brake secara perlahan.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tetap mengendalikan steering dan menurunkan gear ke posisi yang lebih rendah.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Mengarahkan unit ke sisi tanggul atau tebing secara perlahan.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Area manakah yang diizinkan untuk parkir?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Rest area.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Daerah datar, luas, mudah dijangkau, serta jauh dari area longsor dan operasi unit.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Sepuluh meter dari unit yang sedang beroperasi.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang terjadi jika pengemudi menekan pedal kopling ketika menuruni jalan curam?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kendaraan menjadi lebih lambat.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Unit melaju karena gaya gravitasi dan tidak dapat dikendalikan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Putaran mesin menjadi berlebih dan dapat menyebabkan kerusakan.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Alat proteksi diri tidak tersedia, koordinasi kurang, dan reaksi lambat merupakan penyebab langsung kecelakaan yang digolongkan sebagai ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tindakan tidak aman.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Kondisi tidak aman.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Faktor fisik dan keadaan sosial.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 1?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Bucket',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Counterweight',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Arm',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 2?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Bucket link',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Teeth bucket',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Bucket cylinder',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 3?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Arm cylinder',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Boom cylinder',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Bucket cylinder',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 4?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Boom',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Arm',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Bucket',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 5?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Arm cylinder',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Bucket cylinder',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Boom cylinder',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 6?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Arm',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Boom',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Counterweight',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 7?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Boom cylinder',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Arm cylinder',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Bucket cylinder',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 8?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Idler',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Track shoe',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Final drive',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 9?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Final drive',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Idler',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Track shoe',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah nama komponen yang ditunjukkan oleh nomor 10?',
                    'photo_path' => 'questions/exca/exca-component-diagram.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Track shoe',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Idler',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Final drive',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-1.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Engine Oil Level',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Radiator Water Level',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Hydraulic Oil Level',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-1.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Mengetahui jumlah air radiator.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Mengetahui jumlah oli engine.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Mengetahui jumlah oli hydraulic.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-2.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Engine Oil Level',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Radiator Water Level',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Hydraulic Oil Level',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-2.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Mengetahui jumlah air radiator.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mengetahui jumlah oli engine.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Mengetahui jumlah oli hydraulic.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-3.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Engine Oil Level',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Radiator Water Level',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Hydraulic Oil Level',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-3.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Mengetahui jumlah air radiator.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mengetahui jumlah oli engine.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Mengetahui jumlah oli hydraulic.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-4.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Engine Water Temperature',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Hydraulic Oil Temperature',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Engine Oil Pressure',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-4.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, tekanan oli engine berada di bawah standar.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, temperatur oli hydraulic terlalu tinggi.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, temperatur air engine terlalu tinggi.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-5.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Engine Water Temperature',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Hydraulic Oil Temperature',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Engine Oil Pressure',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-5.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, tekanan oli engine berada di bawah standar.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, temperatur oli hydraulic terlalu tinggi.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, temperatur air engine terlalu tinggi.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-6.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Engine Water Temperature',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Hydraulic Oil Temperature',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Engine Oil Pressure',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-6.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, tekanan oli engine berada di bawah standar.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, temperatur oli hydraulic terlalu tinggi.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Jika monitor menyala dan alarm berbunyi, temperatur air engine terlalu tinggi.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-7.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Air Cleaner Clogging',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Battery Charge Level',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Fuel Level',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-7.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Menunjukkan jumlah bahan bakar di dalam tangki berada di bawah standar minimum.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menunjukkan ketidaknormalan pada sistem kelistrikan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Menunjukkan elemen air cleaner mengalami kebuntuan/kotor.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-8.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Air Cleaner Clogging',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Battery Charge Level',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Fuel Level',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-8.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Menunjukkan jumlah bahan bakar di dalam tangki berada di bawah standar minimum.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Menunjukkan ketidaknormalan pada sistem kelistrikan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menunjukkan elemen air cleaner mengalami kebuntuan/kotor.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-9.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Air Cleaner Clogging',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Battery Charge Level',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Fuel Level',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-9.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Menunjukkan jumlah bahan bakar di dalam tangki berada di bawah standar minimum.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menunjukkan ketidaknormalan pada sistem kelistrikan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menunjukkan elemen air cleaner mengalami kebuntuan/kotor.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa nama simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-10.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Pemanasan awal',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Electrical',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Fuel Level',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol instrumen pada gambar tersebut?',
                    'photo_path' => 'questions/exca/exca-instrumen-panel-10.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Menunjukkan sistem electrical tidak normal.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menunjukkan pemanasan pada ruang pembakaran sedang berfungsi.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menunjukkan pemanasan pada engine sedang berfungsi.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa kapasitas bucket KOBELCO SK 480?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '3,2 m³',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => '6,5 m³',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '7,5 m³',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa maksimum RPM engine?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '2.000 RPM',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => '2.100 RPM',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '1.800 RPM',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa arti SP pada excavator?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Super Power',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Super Penggalian',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Super Productivity',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa jumlah carrier roller pada KOBELCO 480?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '4 buah',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => '3 buah',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '2 buah',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa kekenduran track yang diizinkan oleh pabrik?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '20',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => '10',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '5',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa akibat sering mengoperasikan perlengkapan kerja (bucket) hingga langkah akhir sehingga terjadi benturan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kerusakan langsung pada cylinder dan sistem hydraulic.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Bucket selalu bersih dari tanah atau lumpur yang melekat.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Kebocoran dan kekurangan oli hydraulic.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa akibat sering menggunakan swing untuk memutar unit di tempat?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mempercepat perpindahan posisi unit.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Meningkatkan produksi dan efisiensi waktu.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Attachment kerja dapat bengkok atau mengalami puntiran.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Heavy Lift Switch berfungsi sebagai ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Penambahan tenaga hydraulic untuk menggerakkan boom turun (menaikkan chassis ketika unit terjebak di tanah lembek/lumpur).',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Penambahan tenaga hydraulic saat boom digerakkan selama operasi pengangkatan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Kombinasi gerakan attachment agar lebih lincah dan produktif.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Machine Push Up Switch berfungsi sebagai ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Penambahan tenaga hydraulic untuk menggerakkan boom turun (menaikkan chassis ketika unit terjebak di tanah lembek/lumpur).',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Penambahan tenaga hydraulic saat boom digerakkan selama operasi pengangkatan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Kombinasi gerakan attachment agar lebih lincah dan produktif.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Shockless Boom Control berfungsi untuk ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Menambah tenaga hydraulic untuk menggerakkan boom turun.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menambah tenaga hydraulic saat boom digerakkan selama operasi pengangkatan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Mengurangi kejutan attachment terhadap chassis ketika pengoperasian boom dihentikan/netral.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Sebelum engine dihidupkan, apa yang Anda lakukan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Pemeriksaan keliling dan kondisi unit.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Pemeriksaan keliling untuk memeriksa kondisi unit dan sekitarnya.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Pemeriksaan keliling dan diskusi dengan operator bulldozer.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa tujuan instrument panel/gauge pada monitor panel di dalam kabin operator?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Memberikan informasi kepada operator jika terjadi kondisi tidak normal.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Memberikan petunjuk kepada operator tentang kondisi unit jika terjadi ketidaknormalan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Memberikan peringatan dan petunjuk kepada operator mengenai tindakan yang harus dilakukan.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Setelah engine hidup, apa yang dilakukan operator?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Langsung bekerja karena dump truck sudah menunggu/mengantre.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Melakukan pemanasan serta memeriksa kembali kebocoran oli maupun air.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Menunggu perintah foreman atau supervisor.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Di manakah posisi front idler saat melakukan digging?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Belakang.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Depan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Di belakang atau depan tidak menjadi masalah.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Setiap berapa jam excavator harus beristirahat saat melakukan travel?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Setiap 1 jam, istirahat 30 menit.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Setiap 1 jam, istirahat 15 menit.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Setiap 2 jam, istirahat 30 menit.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan jika lampu monitor engine oil pressure tiba-tiba menyala disertai alarm?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Unit tetap dioperasikan perlahan-lahan dan melapor kepada atasan.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Hentikan unit, tunggu sekitar 5 menit, kemudian matikan engine dan lapor kepada atasan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Hentikan unit, segera matikan engine, dan lapor kepada atasan.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan jika lampu monitor temperatur air engine tiba-tiba menyala disertai alarm?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Hentikan operasi, kecilkan gas, matikan engine, lalu periksa air radiator.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Hentikan operasi, kecilkan gas pada putaran medium, lalu periksa air radiator.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Hentikan operasi, kecilkan gas pada putaran medium, matikan engine, lalu periksa air radiator.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Jika jumlah oli engine saat diperiksa berada pada posisi “L”, apa yang Anda lakukan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Langsung menghidupkan engine karena masih berada pada batas minimum.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menghidupkan engine terlebih dahulu, kemudian menambahkan oli.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menambahkan oli terlebih dahulu, kemudian menghidupkan engine.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Gas buang berwarna hitam disebabkan oleh ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Perbandingan tidak sempurna karena terlalu banyak air.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Perbandingan tidak sempurna karena terlalu banyak udara.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Perbandingan tidak sempurna karena terlalu banyak bahan bakar.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa kekenduran track yang diizinkan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '20–30 cm',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => '10–15 cm',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => '30–40 mm',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Kapan drain pada tangki bahan bakar dilakukan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Setiap selesai mengisi bahan bakar.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Setiap akan memulai operasi.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Setiap akan mengisi bahan bakar.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa ketinggian bucket dari permukaan tanah ketika melakukan traveling?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '20–30 cm',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => '20–30 mm',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '30–40 mm',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapa batas ketinggian air yang diizinkan ketika beroperasi di daerah berair?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Sampai batas revolving frame.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Sampai batas atas track shoe.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Sampai batas pusat carrier roller.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Pada air cleaner element terdapat angka 1, 2, 3, 4, 5, dan 6. Apa maksudnya?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Hanya sebagai hiasan agar lebih menarik.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menunjukkan berapa kali air cleaner telah dibersihkan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Untuk membersihkan air cleaner.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Kapan pemeriksaan oli engine dilakukan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Saat engine baru dihidupkan (idle).',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Saat engine masih dalam keadaan mati.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Tidak menjadi masalah apakah engine mati atau hidup.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika melihat kecelakaan di area tambang?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mengabaikannya dan melanjutkan pekerjaan.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mengamankan diri dan segera melaporkan kejadian kepada atasan.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Langsung memindahkan korban tanpa menilai kondisi sekitar.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika unit mengalami kerusakan di jalan hauling?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tetap mengoperasikan unit sampai tujuan.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Meninggalkan unit tanpa memberi tanda atau laporan.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Memarkir unit di tempat aman dan melaporkannya kepada atasan.',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apakah kepanjangan P2H dan kapan kegiatan tersebut dilakukan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Pemeriksaan dan Perawatan Harian; dilakukan pada awal dan akhir shift.',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Pengawasan dan Pengendalian Harian; dilakukan hanya ketika unit rusak.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Persiapan dan Pengoperasian Harian; dilakukan satu kali setiap minggu.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan ketika hujan tiba-tiba turun?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tetap bekerja tanpa memperhatikan kondisi lapangan.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Memarkir unit di tempat yang aman.',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Meningkatkan kecepatan agar pekerjaan cepat selesai.',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika unit amblas?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Memaksakan unit keluar dengan gerakan attachment berulang-ulang.',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Meninggalkan unit tanpa memberi informasi kepada siapa pun.',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Melapor kepada pengawas untuk meminta bantuan evakuasi unit.',
                            'is_correct' => true,
                        ],
                    ],
                ],
            ];

            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()
                    ->where('owner_id', $ownerId)
                    ->where('name', 'Soal Teori Excavator')
                    ->firstOrFail();

                foreach ($questions as $questionData) {
                    $question = Question::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'question' => $questionData['question'],
                            'photo_path' => $questionData['photo_path'],
                        ],
                        [
                            'type' => 'multiple_choice',
                            'score' => 1.3,
                        ],
                    );

                    foreach ($questionData['options'] as $label => $option) {
                        AnswerOption::updateOrCreate(
                            ['question_id' => $question->id, 'label' => $label],
                            $option,
                        );
                    }

                    $question->options()
                        ->whereNotIn('label', array_keys($questionData['options']))
                        ->delete();
                }
            }
        });
    }

    private function copyQuestionImages(): void
    {
        foreach (self::IMAGE_SOURCES as $storagePath => $sourcePath) {
            $absoluteSource = database_path($sourcePath);
            $contents = is_file($absoluteSource) ? file_get_contents($absoluteSource) : false;

            if ($contents === false || ! Storage::disk('local')->put($storagePath, $contents)) {
                throw new RuntimeException("Gagal menyalin aset soal Excavator: {$sourcePath}.");
            }
        }
    }
}
