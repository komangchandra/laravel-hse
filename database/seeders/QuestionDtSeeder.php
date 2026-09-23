<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionDtSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];

    private const IMAGE_SOURCES = [
        'questions/dt/dt-component-unit.jpg' => 'seeders/assets/dt-component-unit.jpg',
        'questions/dt/dt-instrumen-panel-1.png' => 'seeders/assets/dt-instrumen-panel-1.png',
        'questions/dt/dt-instrumen-panel-2.png' => 'seeders/assets/dt-instrumen-panel-2.png',
        'questions/dt/dt-instrumen-panel-3.png' => 'seeders/assets/dt-instrumen-panel-3.png',
        'questions/dt/dt-instrumen-panel-4.png' => 'seeders/assets/dt-instrumen-panel-4.png',
        'questions/dt/dt-instrumen-panel-5.png' => 'seeders/assets/dt-instrumen-panel-5.png',
        'questions/dt/dt-instrumen-panel-6.png' => 'seeders/assets/dt-instrumen-panel-6.png',
        'questions/dt/dt-instrumen-panel-7.png' => 'seeders/assets/dt-instrumen-panel-7.png',
        'questions/dt/dt-instrumen-panel-8.png' => 'seeders/assets/dt-instrumen-panel-8.png',
    ];

    public function run(): void
    {
        $this->copyQuestionImages();

        DB::transaction(function () {
            $questions = [
                [
                    'question' => 'Pada saat akan mengoperasikan unit, perlengkapan keselamatan apa yang harus dikenakan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Baju kerja dan kacamata hitam',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Helm, sabuk pengaman, sepatu kerja, dan sarung tangan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Kacamata hitam dan sarung tangan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan terhadap engine ketika mengisi bahan bakar?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Engine boleh hidup asalkan gigi transmisi netral dan tidak merokok',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Engine harus dimatikan, tidak merokok, dan safety lock diaktifkan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Engine boleh hidup asalkan rem tangan diaktifkan dan tidak merokok',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Menurut aturan keselamatan, urutan jumlah bunyi klakson ketika start, maju, dan mundur yang benar adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '2-3-1',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => '3-2-1',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '1-2-3',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Tindakan yang tepat untuk menghindari bahaya saat mengoperasikan unit adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Membawa seorang penumpang untuk membantu operator',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Berdiri agar lebih mudah melihat keadaan di sekitar unit',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Tidak membawa penumpang serta memperhatikan rambu dan lingkungan kerja',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika terjadi gangguan pada unit yang sedang dioperasikan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Langsung memeriksa dan memperbaikinya sendiri',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Meminta teman untuk memperbaikinya',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menghentikan engine, memarkir unit di tempat aman, dan melapor kepada atasan',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Prosedur turun dari unit yang baik dan benar adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Turun sambil membelakangi tangga',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Turun menghadap tangga dengan mempertahankan tiga titik kontak',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Melompat dari tangga unit',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Alat penerangan yang dianjurkan saat memeriksa air baterai pada malam hari atau dalam kondisi kurang terang adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Lampu senter',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Korek api',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Cahaya dari telepon genggam sambil menyentuh baterai',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Cara kerja yang tidak mematuhi prosedur dan dapat menimbulkan bahaya disebut ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kondisi tidak aman',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tindakan tidak aman',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Keadaan sosial',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Sikap pemarah, emosional, dan tidak peduli terhadap K3 termasuk ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kondisi lingkungan kerja',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tindakan tidak aman',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Keadaan sosial seseorang',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Siapa yang bertanggung jawab terhadap keselamatan di lingkungan kerja?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Perusahaan atau pimpinan saja',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Pekerja saja',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Perusahaan, pimpinan, dan pekerja secara bersama-sama',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapakah batas kecepatan maksimal di jalan hauling dan jalan tambang/pit di area kerja PT GPU?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '40 km/jam di jalan hauling dan 30 km/jam di jalan tambang/pit',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => '40 km/jam di jalan hauling dan 40 km/jam di jalan tambang/pit',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => '40 km/jam di jalan hauling dan 50 km/jam di jalan tambang/pit',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berikut ini merupakan tindakan dalam keadaan darurat rem blong di jalan tambang, kecuali ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mengurangi kecepatan dan mengaktifkan hand brake secara perlahan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tetap mengendalikan kemudi dan menurunkan gigi ke posisi yang lebih rendah',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Mengarahkan unit ke sisi tanggul atau tebing secara perlahan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Area yang diizinkan untuk memarkir unit adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Rest area tanpa memperhatikan kondisi permukaannya',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Daerah datar, luas, mudah dijangkau, serta jauh dari area longsor dan operasi unit',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Area yang berjarak 10 meter dari unit yang sedang beroperasi',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang akan terjadi jika pengemudi menekan pedal kopling ketika menuruni jalan yang curam?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kendaraan menjadi lebih lambat',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Penggerak empat roda dapat menjadi aktif',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Putaran mesin menjadi berlebih dan dapat menyebabkan kerusakan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Tidak tersedianya alat pelindung diri, kurangnya koordinasi, dan reaksi yang lamban merupakan penyebab langsung kecelakaan yang digolongkan sebagai ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tindakan tidak aman',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Kondisi tidak aman',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Faktor fisik dan keadaan sosial',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 1 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Chassis',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Vessel',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Tail gate',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 2 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Shaft transfer',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Spring',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Trunnion',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 3 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Trunnion',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Air wiper',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Shaft transfer',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 4 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Tangki bahan bakar',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tangki hydraulic',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Vessel',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 5 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Lampu jauh',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Lampu sein',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Lampu kerja',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 6 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Lampu jauh',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Lampu kerja',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Lampu rem',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 7 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Tail gate',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Air cleaner',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Air wiper',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 8 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Lampu kerja',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Kaca spion',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Air wiper',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 9 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Chassis',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Vessel',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Tail gate',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Komponen yang ditunjukkan oleh nomor 10 pada gambar adalah ...',
                    'photo_path' => 'questions/dt/dt-component-unit.jpg',
                    'options' => [
                        'A' => [
                            'answer' => 'Trunnion',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Spring',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Tail gate',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-1.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Monitor warning lamp temperatur oli transmisi',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Monitor warning lamp temperatur air pendingin engine',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Monitor warning lamp temperatur oli retarder',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Fungsi simbol pada gambar soal nomor 26 adalah untuk menunjukkan bahwa ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-1.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Temperatur air pendingin engine mengalami overheat',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Temperatur oli engine mengalami overheat',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Temperatur rem retarder mengalami overheat',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-2.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Monitor warning lamp tekanan oli retarder',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Monitor warning lamp tekanan oli transmisi',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Monitor warning lamp tekanan oli engine',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Fungsi simbol pada gambar soal nomor 28 adalah untuk menunjukkan bahwa ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-2.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Tekanan oli retarder berada di bawah standar',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Tekanan oli engine berada di bawah standar',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Tekanan oli transmisi berada di bawah standar',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-3.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Gauge tekanan udara di dalam sistem',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Gauge tekanan udara di dalam tangki',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Gauge tekanan udara sistem rem',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Fungsi alat ukur pada gambar soal nomor 30 adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-3.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Mengetahui apakah tekanan udara di dalam tangki sudah cukup',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Mengetahui apakah tekanan seluruh sistem sudah cukup',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Mengetahui bahwa fungsi rem sedang aktif',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-4.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Air cleaner clogging',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Battery charge level',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Fuel level',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Jika simbol pada gambar soal nomor 32 menyala dan alarm berbunyi, hal tersebut menunjukkan ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-4.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Jumlah bahan bakar di dalam tangki berada di bawah batas minimum',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Terjadi ketidaknormalan pada sistem kelistrikan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Elemen air cleaner mengalami kebuntuan atau kotor',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama alat ukur tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-5.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Speedometer',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Gauge RPM engine',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Odometer',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Fungsi alat ukur pada gambar soal nomor 34 adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-5.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Mengetahui kecepatan kendaraan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mengetahui putaran engine',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Mengetahui jarak tempuh kendaraan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-6.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Monitor warning lamp penggunaan service brake',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Monitor warning lamp penggunaan differential lock',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Monitor warning lamp penggunaan retarder',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Jika simbol pada gambar soal nomor 36 menyala, artinya ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-6.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Service brake sedang aktif',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Retarder sedang aktif',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Differential lock sedang aktif',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-7.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Warning monitor lamp PTO',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Warning monitor lamp transmisi',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Warning monitor lamp retarder',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Jika simbol pada gambar soal nomor 38 menyala, artinya ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-7.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Retarder sedang aktif',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'PTO sedang aktif',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Exhaust brake sedang aktif',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Perhatikan gambar instrumen panel berikut. Nama gambar atau simbol tersebut adalah ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-8.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Monitor warning lamp penggunaan transmisi',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Monitor warning lamp penggunaan retarder brake',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Monitor warning lamp penggunaan service brake',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Jika simbol pada gambar soal nomor 40 menyala, artinya ...',
                    'photo_path' => 'questions/dt/dt-instrumen-panel-8.png',
                    'options' => [
                        'A' => [
                            'answer' => 'Retarder sedang aktif',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Transmission low splitter sedang aktif',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Sistem rem anti-lock sedang aktif',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Kapan pemeriksaan dan perawatan harian dilakukan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Setiap hari hanya ketika akan memulai pekerjaan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Dua kali sehari, yaitu pada awal dan akhir shift',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Hanya jika diperintahkan oleh pengawas lapangan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika ditemukan kelainan saat pemeriksaan unit?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mengabaikan kerusakan dan tetap menghidupkan unit',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menghidupkan unit sambil menunggu mekanik',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Melaporkan kerusakan kepada atasan dan mencatatnya pada formulir kerja',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi exhaust brake?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Menghentikan kendaraan seketika',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mengurangi kecepatan kendaraan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Menahan kendaraan saat bermuatan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa fungsi parking brake?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mengurangi kecepatan kendaraan ketika berjalan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menahan kendaraan ketika sedang diparkir',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Menghentikan kendaraan dalam keadaan darurat',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Kapan exhaust brake digunakan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Kapan saja tanpa memperhatikan kondisi kendaraan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Pada saat mengurangi kecepatan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Hanya ketika kendaraan akan berhenti total',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika lampu tekanan oli engine menyala?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Terus mengoperasikan kendaraan sampai mekanik datang',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mematikan engine, mengaktifkan rem parkir, dan melapor kepada pengawas atau mekanik',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Membiarkan engine idle selama lima menit sebelum dimatikan tanpa melapor',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Pada unit truck terdapat istilah differential lock. Apa arti inter-axle?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Menggabungkan axle depan dan axle belakang',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menyamakan putaran roda kanan dan kiri',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Menggabungkan differential dengan axle',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Prosedur yang benar setelah dump truck selesai beroperasi adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Berhenti di tanah rata, mengaktifkan rem parkir, langsung mematikan engine, lalu membersihkan unit',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Berhenti di tanah rata, mengaktifkan rem parkir, membiarkan engine idle selama lima menit, lalu mematikan engine',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Berhenti di tanah rata, mengaktifkan rem parkir, dan membiarkan engine tetap hidup',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Ketika memarkir kendaraan, jarak minimum dari kendaraan lain adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Satu kali lebar kendaraan',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Sedekat mungkin selama tidak membentur kendaraan lain',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Bebas selama sesuai dengan kondisi medan kerja',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Kapan inter-wheel sebaiknya digunakan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Saat jalan licin dengan kecepatan maksimum 20 km/jam',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Saat unit amblas dengan kecepatan maksimum 20 km/jam',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Saat unit mengalami slip dengan kecepatan maksimum 20 km/jam',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Kapan transmisi C (crawler) digunakan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Ketika akan melakukan dumping',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Ketika akan melakukan loading',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Ketika selesai loading dan menghadapi jalur menanjak',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Ketika sedang mengemudikan dump truck lalu hujan turun tiba-tiba, tindakan yang harus dilakukan adalah ...',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tetap berjalan sampai tempat parkir yang telah ditentukan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Segera menghentikan dump truck dan mengaktifkan rem parkir',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Tetap mengoperasikan dump truck dengan kecepatan rendah',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa tindakan yang tepat jika truck mengalami amblas?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Mendorong unit menggunakan loader atau bulldozer',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Melapor kepada atasan agar unit ditarik menggunakan bulldozer atau loader',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Mengaktifkan inter-axle dan inter-wheel tanpa melapor',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan ketika mengoperasikan unit di tikungan?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tidak perlu mengurangi kecepatan karena kondisi jalan sudah diketahui',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Mengurangi kecepatan menggunakan exhaust brake dan menyesuaikan kecepatan dengan kondisi jalan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Mengurangi kecepatan hanya menggunakan service brake',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Berapakah tekanan udara normal di dalam tangki?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => '5-6 bar',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => '8-9 bar',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => '9-10 bar',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika melihat kecelakaan di area tambang?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Segera melaporkan kejadian kepada pengawas',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Meninggalkan lokasi tanpa melapor',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Memindahkan semua barang di lokasi sebelum menghubungi siapa pun',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan jika unit mengalami kerusakan di jalan hauling?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tetap menjalankan unit hingga bengkel',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Memarkir unit di tempat aman dan melaporkannya kepada atasan',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Meninggalkan unit di jalur aktif tanpa tanda peringatan',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Bagaimana prosedur menanjak yang benar, baik dalam kondisi bermuatan maupun kosong?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Memindahkan gigi berulang kali ketika berada di tengah tanjakan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Menggunakan gigi tinggi agar unit cepat mencapai puncak',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Memilih gigi rendah sejak dari bawah dan tidak memindahkan gigi di tengah tanjakan',
                            'is_correct' => true,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan ketika hujan turun tiba-tiba saat unit beroperasi?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Menambah kecepatan agar segera keluar dari area hujan',
                            'is_correct' => false,
                        ],
                        'B' => [
                            'answer' => 'Segera memarkir unit di tempat yang aman',
                            'is_correct' => true,
                        ],
                        'C' => [
                            'answer' => 'Tetap bekerja tanpa mengubah cara pengoperasian',
                            'is_correct' => false,
                        ],
                    ],
                ],
                [
                    'question' => 'Apa tindakan awal yang tepat jika mengalami keadaan darurat rem blong?',
                    'photo_path' => null,
                    'options' => [
                        'A' => [
                            'answer' => 'Tetap tenang dan berusaha memindahkan transmisi ke gigi rendah',
                            'is_correct' => true,
                        ],
                        'B' => [
                            'answer' => 'Mematikan engine dan meninggalkan kemudi',
                            'is_correct' => false,
                        ],
                        'C' => [
                            'answer' => 'Menginjak pedal gas agar unit tetap stabil',
                            'is_correct' => false,
                        ],
                    ],
                ],
            ];

            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()
                    ->where('owner_id', $ownerId)
                    ->where('name', 'Soal Teori Dump Truck')
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
                            'score' => 1.6,
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
                throw new RuntimeException("Gagal menyalin aset soal Dump Truck: {$sourcePath}.");
            }
        }
    }
}
