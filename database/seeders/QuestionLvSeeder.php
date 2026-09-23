<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionLvSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];

    private const COMPONENT_DIAGRAM_PATH = 'questions/diagram-komponen-lv.png';

    private const IMAGE_SOURCES = [
        self::COMPONENT_DIAGRAM_PATH => 'seeders/assets/diagram-komponen-lv.png',
        'questions/panel-01-temperature-coolant.png' => 'seeders/assets/panel-01-temperature-coolant.png',
        'questions/panel-02-engine-oil-pressure.png' => 'seeders/assets/panel-02-engine-oil-pressure.png',
        'questions/panel-03-fuel-level.png' => 'seeders/assets/panel-03-fuel-level.png',
        'questions/panel-04-battery-charge.png' => 'seeders/assets/panel-04-battery-charge.png',
        'questions/panel-05-engine-rpm.png' => 'seeders/assets/panel-05-engine-rpm.png',
    ];

    public function run(): void
    {
        $this->copyQuestionImages();

        DB::transaction(function () {
            $questions = [
                [
                    'question' => 'Pada waktu akan mengoperasikan mesin, perlengkapan apa yang harus dikenakan?',
                    'options' => [
                        'A' => ['answer' => 'Baju kerja dan kacamata hitam.', 'is_correct' => false],
                        'B' => ['answer' => 'Helm, sabuk pengaman, sepatu kerja, dan sarung tangan.', 'is_correct' => true],
                        'C' => ['answer' => 'Kacamata hitam dan sarung tangan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa yang harus dilakukan terhadap engine pada waktu mengisi bahan bakar?',
                    'options' => [
                        'A' => ['answer' => 'Engine boleh hidup asalkan transmisi netral dan tidak merokok.', 'is_correct' => false],
                        'B' => ['answer' => 'Engine harus dimatikan, tidak merokok, dan safety lock diaktifkan.', 'is_correct' => true],
                        'C' => ['answer' => 'Engine boleh hidup asalkan rem tangan dipasang dan tidak merokok.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Menurut aturan safety, berapa kali klakson dibunyikan secara berurutan saat start, maju, dan mundur?',
                    'options' => [
                        'A' => ['answer' => '2 - 3.', 'is_correct' => false],
                        'B' => ['answer' => '3 - 2.', 'is_correct' => false],
                        'C' => ['answer' => '1 - 2 - 3.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Saat mengoperasikan mesin, tindakan apa yang sebaiknya dilakukan untuk menghindari bahaya?',
                    'options' => [
                        'A' => ['answer' => 'Membawa penumpang lain untuk membantu operator.', 'is_correct' => false],
                        'B' => ['answer' => 'Operator berdiri agar mudah melihat sekeliling.', 'is_correct' => false],
                        'C' => ['answer' => 'Tidak membawa penumpang serta memperhatikan rambu kerja dan lingkungan.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan jika terjadi gangguan pada mesin yang sedang dioperasikan?',
                    'options' => [
                        'A' => ['answer' => 'Langsung memeriksa dan mencoba memperbaikinya sendiri.', 'is_correct' => false],
                        'B' => ['answer' => 'Meminta bantuan teman untuk memperbaikinya.', 'is_correct' => false],
                        'C' => ['answer' => 'Menghentikan engine, parkir di tempat aman, dan melapor kepada atasan.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Bagaimana prosedur turun dari mesin yang baik dan benar?',
                    'options' => [
                        'A' => ['answer' => 'Turun sambil membelakangi tangga.', 'is_correct' => false],
                        'B' => ['answer' => 'Turun menghadap tangga dengan mempertahankan tiga titik kontak.', 'is_correct' => true],
                        'C' => ['answer' => 'Turun dengan melompat dari tangga.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Alat penerangan apa yang dianjurkan saat memeriksa air baterai pada malam hari atau dalam kondisi kurang terang?',
                    'options' => [
                        'A' => ['answer' => 'Lampu senter.', 'is_correct' => true],
                        'B' => ['answer' => 'Korek api.', 'is_correct' => false],
                        'C' => ['answer' => 'Jari tangan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Perbuatan yang tidak mematuhi prosedur kerja dan membahayakan digolongkan sebagai apa?',
                    'options' => [
                        'A' => ['answer' => 'Kondisi tidak aman.', 'is_correct' => false],
                        'B' => ['answer' => 'Tindakan tidak aman.', 'is_correct' => true],
                        'C' => ['answer' => 'Kecerobohan manusia.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Sikap pemarah, emosional, dan tidak peduli terhadap K3 termasuk apa?',
                    'options' => [
                        'A' => ['answer' => 'Sifat buruk seseorang.', 'is_correct' => false],
                        'B' => ['answer' => 'Tindakan tidak aman.', 'is_correct' => true],
                        'C' => ['answer' => 'Keadaan sosial seseorang.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Siapa yang bertanggung jawab atas keselamatan kerja di lingkungannya?',
                    'options' => [
                        'A' => ['answer' => 'Perusahaan atau pimpinan.', 'is_correct' => false],
                        'B' => ['answer' => 'Pekerja.', 'is_correct' => false],
                        'C' => ['answer' => 'Semua pihak secara bersama-sama.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Berapa batas kecepatan maksimum di jalan hauling dan di jalan tambang/pit yang diizinkan di area kerja tambang PT Gorby Putra Utama?',
                    'options' => [
                        'A' => ['answer' => '40 km/jam di jalan hauling dan 30 km/jam di jalan tambang/pit.', 'is_correct' => true],
                        'B' => ['answer' => '40 km/jam di jalan hauling dan 40 km/jam di jalan tambang/pit.', 'is_correct' => false],
                        'C' => ['answer' => '40 km/jam di jalan hauling dan 50 km/jam di jalan tambang/pit.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Tindakan berikut harus dilakukan saat mengalami rem blong di jalan tambang, kecuali?',
                    'options' => [
                        'A' => ['answer' => 'Mengurangi kecepatan dan mengaktifkan hand brake secara perlahan.', 'is_correct' => false],
                        'B' => ['answer' => 'Tetap mengendalikan steering dan menurunkan gear ke posisi lebih rendah.', 'is_correct' => true],
                        'C' => ['answer' => 'Mengarahkan unit ke sisi tanggul atau tebing secara perlahan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Area manakah yang diizinkan untuk parkir?',
                    'options' => [
                        'A' => ['answer' => 'Rest area.', 'is_correct' => false],
                        'B' => ['answer' => 'Daerah datar, luas, mudah dijangkau, serta jauh dari area longsor dan operasi unit.', 'is_correct' => true],
                        'C' => ['answer' => 'Sepuluh meter dari unit operasi.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa yang akan terjadi jika pengemudi menekan pedal kopling ketika menuruni jalan yang curam?',
                    'options' => [
                        'A' => ['answer' => 'Kendaraan menjadi lebih lambat.', 'is_correct' => false],
                        'B' => ['answer' => 'Penggerak empat roda dapat menjadi aktif.', 'is_correct' => true],
                        'C' => ['answer' => 'Putaran mesin menjadi berlebih dan dapat menyebabkan kerusakan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Tidak adanya alat pelindung diri, kurangnya koordinasi, dan reaksi yang lamban merupakan penyebab langsung kecelakaan yang digolongkan sebagai apa?',
                    'options' => [
                        'A' => ['answer' => 'Tindakan tidak aman.', 'is_correct' => true],
                        'B' => ['answer' => 'Kondisi tidak aman.', 'is_correct' => false],
                        'C' => ['answer' => 'Faktor fisik dan keadaan sosial.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 1 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Lampu belakang.', 'is_correct' => false],
                        'B' => ['answer' => 'Kaca spion.', 'is_correct' => true],
                        'C' => ['answer' => 'Wiper.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 2 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Kaca samping kiri.', 'is_correct' => true],
                        'B' => ['answer' => 'Kaca spion.', 'is_correct' => false],
                        'C' => ['answer' => 'Cabin.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 3 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Gardan.', 'is_correct' => false],
                        'B' => ['answer' => 'Per/spring.', 'is_correct' => false],
                        'C' => ['answer' => 'Roda belakang.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 4 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Lampu kerja.', 'is_correct' => false],
                        'B' => ['answer' => 'Lampu belakang.', 'is_correct' => true],
                        'C' => ['answer' => 'Kaca spion.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 5 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Gardan.', 'is_correct' => true],
                        'B' => ['answer' => 'Roda belakang.', 'is_correct' => false],
                        'C' => ['answer' => 'Per/spring.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 6 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Lampu belakang.', 'is_correct' => false],
                        'B' => ['answer' => 'Kaca spion.', 'is_correct' => false],
                        'C' => ['answer' => 'Lampu kerja.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 7 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Per/spring.', 'is_correct' => true],
                        'B' => ['answer' => 'Gardan.', 'is_correct' => false],
                        'C' => ['answer' => 'Roda belakang.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 8 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Wiper.', 'is_correct' => false],
                        'B' => ['answer' => 'Frea cliner.', 'is_correct' => true],
                        'C' => ['answer' => 'Kaca samping kiri.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 9 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Kaca spion.', 'is_correct' => false],
                        'B' => ['answer' => 'Cabin.', 'is_correct' => false],
                        'C' => ['answer' => 'Wiper.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Pada diagram Light Vehicle, komponen yang ditunjukkan oleh nomor 10 adalah apa?',
                    'photo_path' => self::COMPONENT_DIAGRAM_PATH,
                    'options' => [
                        'A' => ['answer' => 'Cabin.', 'is_correct' => true],
                        'B' => ['answer' => 'Kaca samping kiri.', 'is_correct' => false],
                        'C' => ['answer' => 'Kaca spion.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa nama gambar/simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-01-temperature-coolant.png',
                    'options' => [
                        'A' => ['answer' => 'Monitor warning lamp temperatur oli transmisi.', 'is_correct' => false],
                        'B' => ['answer' => 'Monitor warning lamp temperatur air pendingin engine.', 'is_correct' => true],
                        'C' => ['answer' => 'Monitor warning lamp temperatur oli retarder.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-01-temperature-coolant.png',
                    'options' => [
                        'A' => ['answer' => 'Menyala saat temperatur air pendingin engine mengalami overheat.', 'is_correct' => true],
                        'B' => ['answer' => 'Menyala saat temperatur oli engine mengalami overheat.', 'is_correct' => false],
                        'C' => ['answer' => 'Menunjukkan temperatur rem retarder mengalami overheat.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa nama gambar/simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-02-engine-oil-pressure.png',
                    'options' => [
                        'A' => ['answer' => 'Monitor warning lamp tekanan oli retarder.', 'is_correct' => false],
                        'B' => ['answer' => 'Monitor warning lamp tekanan oli transmisi.', 'is_correct' => false],
                        'C' => ['answer' => 'Monitor warning lamp tekanan oli engine.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-02-engine-oil-pressure.png',
                    'options' => [
                        'A' => ['answer' => 'Menyala saat tekanan oli retarder berada di bawah standar.', 'is_correct' => false],
                        'B' => ['answer' => 'Menyala saat tekanan oli engine berada di bawah standar.', 'is_correct' => true],
                        'C' => ['answer' => 'Menyala saat tekanan oli transmisi berada di bawah standar.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa nama gambar/simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-03-fuel-level.png',
                    'options' => [
                        'A' => ['answer' => 'Air cleaner clogging.', 'is_correct' => false],
                        'B' => ['answer' => 'Battery charge level.', 'is_correct' => false],
                        'C' => ['answer' => 'Fuel level.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-03-fuel-level.png',
                    'options' => [
                        'A' => ['answer' => 'Menyala dan membunyikan alarm saat bahan bakar di dalam tangki kurang dari batas minimum.', 'is_correct' => true],
                        'B' => ['answer' => 'Menyala dan membunyikan alarm saat terjadi ketidaknormalan pada sistem kelistrikan.', 'is_correct' => false],
                        'C' => ['answer' => 'Menyala dan membunyikan alarm saat elemen air cleaner tersumbat atau kotor.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa nama gambar/simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-04-battery-charge.png',
                    'options' => [
                        'A' => ['answer' => 'Air cleaner clogging.', 'is_correct' => false],
                        'B' => ['answer' => 'Battery charge level.', 'is_correct' => true],
                        'C' => ['answer' => 'Fuel level.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-04-battery-charge.png',
                    'options' => [
                        'A' => ['answer' => 'Menyala dan membunyikan alarm saat bahan bakar di dalam tangki kurang dari batas minimum.', 'is_correct' => false],
                        'B' => ['answer' => 'Menyala dan membunyikan alarm saat terjadi ketidaknormalan pada sistem kelistrikan.', 'is_correct' => true],
                        'C' => ['answer' => 'Menyala dan membunyikan alarm saat elemen air cleaner tersumbat atau kotor.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa nama gambar/gauge panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-05-engine-rpm.png',
                    'options' => [
                        'A' => ['answer' => 'Gauge speedometer.', 'is_correct' => false],
                        'B' => ['answer' => 'Gauge RPM engine.', 'is_correct' => true],
                        'C' => ['answer' => 'Gauge tachometer.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa fungsi simbol panel instrumen tersebut?',
                    'photo_path' => 'questions/panel-05-engine-rpm.png',
                    'options' => [
                        'A' => ['answer' => 'Mengetahui kecepatan kendaraan.', 'is_correct' => false],
                        'B' => ['answer' => 'Mengetahui putaran engine.', 'is_correct' => true],
                        'C' => ['answer' => 'Mengetahui waktu perpindahan transmisi yang tepat.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Kapan pemeriksaan dan perawatan harian dilakukan?',
                    'options' => [
                        'A' => ['answer' => 'Setiap hari ketika akan memulai pekerjaan.', 'is_correct' => false],
                        'B' => ['answer' => 'Dua kali sehari, yaitu pada awal shift dan akhir shift.', 'is_correct' => false],
                        'C' => ['answer' => 'Hanya jika diperintahkan oleh pengawas lapangan.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Saat melakukan pemeriksaan dan menemukan kelainan, tindakan apa yang dilakukan?',
                    'options' => [
                        'A' => ['answer' => 'Mengabaikan kerusakan dan tetap menghidupkan mesin.', 'is_correct' => true],
                        'B' => ['answer' => 'Tetap menghidupkan unit sambil menunggu mekanik.', 'is_correct' => false],
                        'C' => ['answer' => 'Melaporkan kerusakan kepada atasan dan mencatatnya pada formulir kerja.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa fungsi brake?',
                    'options' => [
                        'A' => ['answer' => 'Menghentikan kendaraan seketika.', 'is_correct' => false],
                        'B' => ['answer' => 'Mengurangi kecepatan.', 'is_correct' => false],
                        'C' => ['answer' => 'Menahan kendaraan pada saat bermuatan.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Apa fungsi parking brake?',
                    'options' => [
                        'A' => ['answer' => 'Mengurangi kecepatan.', 'is_correct' => false],
                        'B' => ['answer' => 'Digunakan ketika kendaraan sedang parkir.', 'is_correct' => true],
                        'C' => ['answer' => 'Menghentikan kendaraan saat berjalan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Kapan brake digunakan?',
                    'options' => [
                        'A' => ['answer' => 'Kapan saja sesuai kebutuhan.', 'is_correct' => false],
                        'B' => ['answer' => 'Saat mengurangi kecepatan dan berhenti.', 'is_correct' => true],
                        'C' => ['answer' => 'Saat kendaraan akan berjalan lambat.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Tindakan apa yang dilakukan jika lampu tekanan oli mesin menyala?',
                    'options' => [
                        'A' => ['answer' => 'Terus mengoperasikan kendaraan sampai mekanik datang.', 'is_correct' => false],
                        'B' => ['answer' => 'Mematikan mesin, memasang rem parkir, dan melapor kepada pengawas atau mekanik.', 'is_correct' => true],
                        'C' => ['answer' => 'Membiarkan mesin idle selama lima menit sebelum dimatikan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Prosedur mana yang diikuti saat Light Vehicle selesai beroperasi?',
                    'options' => [
                        'A' => ['answer' => 'Berhenti di tanah rata, memasang rem parkir, langsung mematikan engine, lalu membersihkan unit.', 'is_correct' => false],
                        'B' => ['answer' => 'Berhenti di tanah rata, memasang rem parkir, membiarkan engine idle selama lima menit, lalu mematikan engine.', 'is_correct' => true],
                        'C' => ['answer' => 'Berhenti di tanah rata, memasang rem parkir, dan membiarkan engine tetap hidup.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Saat memarkir kendaraan, berapa jarak dari kendaraan lain?',
                    'options' => [
                        'A' => ['answer' => 'Paling sedikit satu kali lebar kendaraan.', 'is_correct' => true],
                        'B' => ['answer' => 'Sedekat mungkin tanpa membentur kendaraan lain.', 'is_correct' => false],
                        'C' => ['answer' => 'Jarak berapa pun asalkan sesuai dengan medan kerja.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Saat mengemudikan Light Vehicle dan tiba-tiba turun hujan, tindakan apa yang dilakukan?',
                    'options' => [
                        'A' => ['answer' => 'Tetap berjalan sampai tempat parkir yang telah ditentukan.', 'is_correct' => false],
                        'B' => ['answer' => 'Langsung berhenti dan memasang rem parkir.', 'is_correct' => false],
                        'C' => ['answer' => 'Tetap mengoperasikan kendaraan dengan berjalan perlahan.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Apa tindakan yang dilakukan jika Light Vehicle amblas?',
                    'options' => [
                        'A' => ['answer' => 'Mendorong kendaraan menggunakan loader atau bulldozer.', 'is_correct' => false],
                        'B' => ['answer' => 'Melapor kepada atasan agar kendaraan ditarik menggunakan bulldozer atau loader.', 'is_correct' => false],
                        'C' => ['answer' => 'Mengaktifkan double berat.', 'is_correct' => true],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan jika melihat kecelakaan di area tambang?',
                    'options' => [
                        'A' => ['answer' => 'Meninggalkan lokasi tanpa memberi tahu siapa pun.', 'is_correct' => false],
                        'B' => ['answer' => 'Segera melaporkan kejadian kepada pengawas.', 'is_correct' => true],
                        'C' => ['answer' => 'Melanjutkan pekerjaan dan menunggu orang lain melapor.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan jika unit mengalami kerusakan di jalan hauling?',
                    'options' => [
                        'A' => ['answer' => 'Parkir di tempat yang aman dan melapor kepada atasan.', 'is_correct' => true],
                        'B' => ['answer' => 'Tetap mengoperasikan unit sampai tujuan.', 'is_correct' => false],
                        'C' => ['answer' => 'Memperbaiki unit di tengah jalan tanpa pengamanan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Bagaimana prosedur menanjak, baik dalam kondisi bermuatan maupun kosong?',
                    'options' => [
                        'A' => ['answer' => 'Menggunakan gigi rendah sejak dari bawah tanjakan dan tidak memindahkan gigi saat menanjak.', 'is_correct' => true],
                        'B' => ['answer' => 'Menggunakan gigi tinggi dan memindahkan gigi berulang kali di tengah tanjakan.', 'is_correct' => false],
                        'C' => ['answer' => 'Memindahkan transmisi ke netral agar kendaraan melaju lebih ringan.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan ketika hujan turun secara tiba-tiba?',
                    'options' => [
                        'A' => ['answer' => 'Segera memarkir unit di tempat yang aman.', 'is_correct' => true],
                        'B' => ['answer' => 'Menambah kecepatan agar cepat keluar dari area hujan.', 'is_correct' => false],
                        'C' => ['answer' => 'Berhenti di tengah jalan tanpa memasang rem parkir.', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Apa yang dilakukan jika mengalami keadaan darurat rem blong?',
                    'options' => [
                        'A' => ['answer' => 'Tetap tenang dan berusaha memasukkan transmisi ke gigi rendah.', 'is_correct' => true],
                        'B' => ['answer' => 'Segera mematikan engine tanpa mengendalikan arah kendaraan.', 'is_correct' => false],
                        'C' => ['answer' => 'Melompat keluar dari kendaraan saat kendaraan masih bergerak.', 'is_correct' => false],
                    ],
                ],
            ];

            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()
                    ->where('owner_id', $ownerId)
                    ->where('name', 'Soal Teori Light Vehicle')
                    ->firstOrFail();

                foreach ($questions as $questionData) {
                    $photoPath = $questionData['photo_path'] ?? null;
                    $question = Question::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'question' => $questionData['question'],
                            'photo_path' => $photoPath,
                        ],
                        [
                            'type' => 'multiple_choice',
                            'score' => 2,
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
                throw new RuntimeException("Gagal menyalin aset soal Light Vehicle: {$sourcePath}.");
            }
        }
    }
}
