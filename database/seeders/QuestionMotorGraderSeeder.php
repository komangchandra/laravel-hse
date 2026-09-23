<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionMotorGraderSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];
    private const COMPONENT_DIAGRAM_PATH = 'questions/motor-grader/mg-component-diagram.jpg';
    private const IMAGE_SOURCES = [
        self::COMPONENT_DIAGRAM_PATH => 'seeders/assets/mg-component-diagram.jpg',
        'questions/motor-grader/mg-instrumen-panel-1.png' => 'seeders/assets/mg-instrumen-panel-1.png',
        'questions/motor-grader/mg-instrumen-panel-2.png' => 'seeders/assets/mg-instrumen-panel-2.png',
        'questions/motor-grader/mg-instrumen-panel-3.png' => 'seeders/assets/mg-instrumen-panel-3.png',
        'questions/motor-grader/mg-instrumen-panel-4.png' => 'seeders/assets/mg-instrumen-panel-4.png',
        'questions/motor-grader/mg-instrumen-panel-5.png' => 'seeders/assets/mg-instrumen-panel-5.png',
        'questions/motor-grader/mg-instrumen-panel-6.png' => 'seeders/assets/mg-instrumen-panel-6.png',
        'questions/motor-grader/mg-instrumen-panel-7.png' => 'seeders/assets/mg-instrumen-panel-7.png',
        'questions/motor-grader/mg-instrumen-panel-8.png' => 'seeders/assets/mg-instrumen-panel-8.png',
        'questions/motor-grader/mg-instrumen-panel-9.png' => 'seeders/assets/mg-instrumen-panel-9.png',
        'questions/motor-grader/mg-instrumen-panel-10.png' => 'seeders/assets/mg-instrumen-panel-10.png',
    ];

    public function run(): void
    {
        $this->copyQuestionImages();
        DB::transaction(function () {
            $questions = array_merge($this->safetyQuestions(), $this->componentQuestions(), $this->panelQuestions(), $this->operationQuestions(), $this->fieldSituationQuestions());
            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()->where('owner_id', $ownerId)->where('name', 'Soal Teori Motorgrader')->firstOrFail();
                foreach ($questions as $data) {
                    $question = Question::updateOrCreate(
                        ['category_id' => $category->id, 'question' => $data['question'], 'photo_path' => $data['photo_path']],
                        ['type' => 'multiple_choice', 'score' => 1.6],
                    );
                    foreach ($data['options'] as $label => $option) {
                        AnswerOption::updateOrCreate(['question_id' => $question->id, 'label' => $label], $option);
                    }
                    $question->options()->whereNotIn('label', array_keys($data['options']))->delete();
                }
            }
        });
    }

    private function safetyQuestions(): array
    {
        return [
            $this->q('Pada waktu akan mengoperasikan mesin, perlengkapan apa yang harus dikenakan?', 'B', ['Baju kerja dan kacamata hitam', 'Helm, sabuk pengaman, sepatu kerja, dan sarung tangan', 'Kacamata hitam dan sarung tangan']),
            $this->q('Pada waktu mengisi bahan bakar, bagaimana kondisi engine yang benar?', 'B', ['Boleh hidup asalkan transmisi netral dan operator tidak merokok', 'Harus dimatikan, tidak merokok, dan safety lock diaktifkan', 'Boleh hidup asalkan rem parkir diaktifkan dan operator tidak merokok']),
            $this->q('Menurut aturan keselamatan, berapa kali klakson harus dibunyikan saat start, maju, dan mundur secara berurutan?', 'C', ['2 kali, lalu 3 kali', '3 kali, lalu 2 kali', '1 kali, 2 kali, lalu 3 kali']),
            $this->q('Saat mengoperasikan mesin, tindakan apa yang tepat untuk menghindari bahaya?', 'C', ['Membawa penumpang untuk membantu operator', 'Berdiri agar lebih mudah melihat keadaan sekeliling', 'Tidak membawa penumpang serta memperhatikan rambu kerja dan lingkungan']),
            $this->q('Apa yang dilakukan jika terjadi gangguan pada mesin yang sedang dioperasikan?', 'C', ['Langsung memeriksa dan memperbaikinya sendiri', 'Meminta teman memperbaikinya', 'Menghentikan engine, memarkir unit di tempat aman, dan melapor kepada atasan']),
            $this->q('Bagaimana prosedur turun dari mesin yang baik dan benar?', 'B', ['Turun sambil membelakangi tangga', 'Turun sambil menghadap tangga dengan mempertahankan tiga titik kontak', 'Melompat dari tangga']),
            $this->q('Apa yang dianjurkan untuk memeriksa air baterai pada malam hari atau dalam kondisi kurang terang?', 'A', ['Lampu senter', 'Korek api', 'Jari tangan']),
            $this->q('Cara kerja yang tidak mematuhi prosedur dan membahayakan disebut apa?', 'B', ['Kondisi tidak aman', 'Tindakan tidak aman', 'Kecerobohan manusia']),
            $this->q('Sikap pemarah, emosional, dan tidak peduli terhadap K3 termasuk apa?', 'B', ['Sifat buruk seseorang', 'Tindakan tidak aman', 'Keadaan sosial seseorang']),
            $this->q('Siapa yang bertanggung jawab terhadap keselamatan kerja di lingkungannya?', 'C', ['Perusahaan atau pimpinan', 'Pekerja', 'Semua pihak secara bersama-sama']),
            $this->q('Berapa batas kecepatan maksimal di jalan hauling dan di jalan tambang/pit yang diizinkan di area kerja tambang PT Ansaf Inti Resources?', 'A', ['40 km/jam di jalan hauling dan 30 km/jam di jalan tambang/pit', '40 km/jam di jalan hauling dan 40 km/jam di jalan tambang/pit', '40 km/jam di jalan hauling dan 50 km/jam di jalan tambang/pit']),
            $this->q('Berikut merupakan tindakan ketika mengalami rem blong di jalan tambang, kecuali:', 'B', ['Mengurangi kecepatan dan mengaktifkan rem parkir secara perlahan', 'Tetap mengendalikan kemudi dan menurunkan gigi ke posisi yang lebih rendah', 'Mengarahkan unit ke sisi tanggul atau tebing secara perlahan']),
            $this->q('Area mana yang diizinkan untuk parkir?', 'B', ['Rest area', 'Daerah yang datar, luas, mudah dijangkau, serta jauh dari area longsor dan operasi unit', 'Area yang berjarak 10 meter dari unit yang sedang beroperasi']),
            $this->q('Apa yang akan terjadi jika pengemudi menekan pedal kopling ketika menuruni jalan curam?', 'B', ['Kendaraan menjadi lebih lambat', 'Penggerak empat roda dapat menjadi aktif', 'Putaran mesin menjadi berlebih dan dapat menyebabkan kerusakan']),
            $this->q('Tidak tersedianya alat pelindung diri, kurangnya koordinasi, dan reaksi lamban merupakan penyebab langsung kecelakaan yang digolongkan sebagai apa?', 'A', ['Tindakan tidak aman', 'Kondisi tidak aman', 'Faktor fisik dan keadaan sosial']),
        ];
    }

    private function componentQuestions(): array
    {
        $rows = [
            [1, 'A', ['Leaning cylinder', 'Front tire', 'Drawbar']], [2, 'B', ['Rear tire', 'Front tire', 'Chassis']],
            [3, 'C', ['Blade', 'Articulation', 'Drawbar']], [4, 'B', ['Blade lift cylinder', 'Blade', 'Drawbar']],
            [5, 'A', ['Articulation', 'Chassis', 'Leaning cylinder']], [6, 'B', ['Front tire', 'Rear tire', 'Engine']],
            [7, 'C', ['Air cleaner', 'Exhaust pipe', 'Engine']], [8, 'A', ['Exhaust pipe', 'Precleaner', 'Air cleaner']],
            [9, 'B', ['Chassis', 'Blade lift cylinder', 'Leaning cylinder']], [10, 'B', ['Drawbar', 'Chassis', 'Articulation']],
        ];
        return array_map(fn ($row) => $this->q("Pada diagram Motor Grader, apakah nama komponen yang ditunjukkan oleh nomor {$row[0]}?", $row[1], $row[2], self::COMPONENT_DIAGRAM_PATH), $rows);
    }

    private function panelQuestions(): array
    {
        $rows = [
            [1, 'Apakah nama simbol berikut?', 'B', ['Lampu peringatan temperatur oli transmisi', 'Lampu peringatan temperatur air pendingin engine', 'Lampu peringatan temperatur oli retarder'], 'Apakah fungsi simbol pada gambar tersebut?', 'A', ['Menyala jika temperatur air pendingin engine terlalu panas', 'Menyala jika temperatur oli engine terlalu panas', 'Menunjukkan temperatur rem retarder terlalu panas']],
            [2, 'Apakah nama simbol berikut?', 'C', ['Lampu peringatan tekanan oli retarder', 'Lampu peringatan tekanan oli transmisi', 'Lampu peringatan tekanan oli engine'], 'Apakah fungsi simbol pada gambar tersebut?', 'B', ['Menyala jika tekanan oli retarder di bawah standar', 'Menyala jika tekanan oli engine di bawah standar', 'Menyala jika tekanan oli transmisi di bawah standar']],
            [3, 'Apakah nama instrumen berikut?', 'B', ['Gauge tekanan udara di dalam sistem', 'Gauge tekanan udara di dalam tangki', 'Gauge tekanan udara sistem rem'], 'Apakah fungsi instrumen pada gambar tersebut?', 'A', ['Mengetahui apakah tekanan udara di dalam tangki sudah cukup', 'Mengetahui apakah tekanan udara di dalam sistem sudah cukup', 'Mengetahui apakah fungsi rem sedang aktif']],
            [4, 'Apakah nama simbol berikut?', 'B', ['Air cleaner clogging', 'Battery charge level', 'Fuel level'], 'Apakah fungsi simbol pada gambar tersebut?', 'B', ['Menunjukkan bahan bakar di dalam tangki kurang dari batas minimum', 'Menunjukkan ketidaknormalan pada sistem kelistrikan', 'Menunjukkan elemen air cleaner tersumbat atau kotor']],
            [5, 'Apakah nama instrumen berikut?', 'B', ['Speedometer', 'Gauge RPM engine', 'Gauge tachometer'], 'Apakah fungsi instrumen pada gambar tersebut?', 'B', ['Mengetahui kecepatan kendaraan', 'Mengetahui putaran engine', 'Menentukan perpindahan transmisi yang tepat']],
            [6, 'Apakah nama simbol berikut?', 'A', ['Engine oil level', 'Radiator water level', 'Hydraulic oil level'], 'Apakah fungsi simbol pada gambar tersebut?', 'B', ['Mengetahui jumlah air radiator', 'Mengetahui jumlah oli engine', 'Mengetahui jumlah oli hidrolik']],
            [7, 'Apakah nama simbol berikut?', 'C', ['Engine oil level', 'Radiator water level', 'Hydraulic oil level'], 'Apakah fungsi simbol pada gambar tersebut?', 'C', ['Mengetahui jumlah air radiator', 'Mengetahui jumlah oli engine', 'Mengetahui jumlah oli hidrolik']],
            [8, 'Apakah nama simbol berikut?', 'C', ['Lampu peringatan penggunaan transmisi', 'Lampu peringatan penggunaan retarder brake', 'Lampu peringatan penggunaan brake/ABS'], 'Apakah fungsi simbol pada gambar tersebut?', 'C', ['Menunjukkan retarder sedang aktif', 'Menunjukkan transmission low splitter sedang aktif', 'Menunjukkan sistem rem antilock sedang aktif']],
            [9, 'Apakah nama simbol berikut?', 'C', ['Engine water temperature', 'Hydraulic temperature', 'Engine oil pressure'], 'Apakah fungsi simbol pada gambar tersebut?', 'A', ['Menunjukkan tekanan oli engine berada di bawah standar', 'Menunjukkan temperatur oli hidrolik terlalu panas', 'Menunjukkan temperatur air engine terlalu panas']],
            [10, 'Apakah nama simbol berikut?', 'C', ['Air cleaner clogging', 'Battery charge level', 'Fuel level'], 'Apakah fungsi simbol pada gambar tersebut?', 'A', ['Menunjukkan jumlah bahan bakar di dalam tangki kurang dari batas minimum', 'Menunjukkan ketidaknormalan pada sistem kelistrikan', 'Menunjukkan elemen air cleaner tersumbat atau kotor']],
        ];
        $questions = [];
        foreach ($rows as [$number, $nameQuestion, $nameKey, $names, $functionQuestion, $functionKey, $functions]) {
            $photo = "questions/motor-grader/mg-instrumen-panel-{$number}.png";
            $questions[] = $this->q($nameQuestion, $nameKey, $names, $photo);
            $questions[] = $this->q($functionQuestion, $functionKey, $functions, $photo);
        }
        return $questions;
    }

    private function operationQuestions(): array
    {
        return [
            $this->q('Kapan pemeriksaan dan perawatan harian dilakukan?', 'B', ['Setiap hari ketika akan memulai pekerjaan', 'Dua kali sehari, yaitu pada awal dan akhir shift', 'Hanya jika diperintahkan oleh pengawas lapangan']),
            $this->q('Apa yang dilakukan jika menemukan kelainan saat pemeriksaan?', 'C', ['Mengabaikan kerusakan dan tetap menghidupkan mesin', 'Tetap menghidupkan unit sambil menunggu mekanik', 'Melaporkan kerusakan kepada atasan dan mencatatnya pada formulir kerja']),
            $this->q('Apakah fungsi exhaust brake?', 'B', ['Menghentikan kendaraan seketika', 'Mengurangi kecepatan', 'Menahan kendaraan saat bermuatan']),
            $this->q('Apakah fungsi parking brake?', 'B', ['Mengurangi kecepatan', 'Menahan kendaraan ketika sedang parkir', 'Menghentikan kendaraan saat berjalan']),
            $this->q('Berapakah jumlah tingkat kecepatan transmisi tersebut?', 'A', ['6 kecepatan maju dan 6 kecepatan mundur', '8 kecepatan maju dan 6 kecepatan mundur', '5 kecepatan maju dan 6 kecepatan mundur']),
            $this->q('Apa yang harus dilakukan jika lampu tekanan oli mesin menyala?', 'B', ['Tetap mengoperasikan kendaraan sampai mekanik datang', 'Mematikan mesin, memasang rem parkir, dan melapor kepada pengawas atau mekanik', 'Membiarkan mesin idle selama lima menit sebelum dimatikan']),
            $this->q('Drive tandem roda belakang digerakkan langsung oleh apa?', 'A', ['Drive chain', 'Bevel gear', 'Sistem hidrolik']),
            $this->q('Struktur ripper dan blade dinaikkan serta diturunkan dengan tenaga apa?', 'C', ['Listrik', 'Hidrolik', 'Mekanis']),
            $this->q('Saat memarkir kendaraan, berapa jarak yang harus dipertahankan dari kendaraan lain?', 'A', ['Paling sedikit satu kali lebar kendaraan', 'Sedekat mungkin tanpa membentur kendaraan lain', 'Jarak berapa pun asalkan sesuai dengan medan kerja']),
            $this->q('Saat mematikan mesin secara normal, berapa lama mesin harus dibiarkan tetap hidup?', 'C', ['10 menit', '2 menit', '5 menit']),
        ];
    }

    private function fieldSituationQuestions(): array
    {
        return [
            $this->q('Apa yang dilakukan jika melihat kecelakaan di area tambang?', 'A', ['Segera melaporkan kejadian kepada pengawas', 'Meninggalkan lokasi tanpa memberi tahu siapa pun', 'Memindahkan semua barang di lokasi sebelum melapor']),
            $this->q('Apa yang dilakukan jika unit mengalami kerusakan di jalan hauling?', 'B', ['Tetap menjalankan unit sampai tujuan', 'Memarkir unit di tempat aman dan melapor kepada atasan', 'Meninggalkan unit di jalur aktif tanpa pengamanan']),
            $this->q('Bagaimana prosedur menanjak yang tepat, baik saat bermuatan maupun kosong?', 'C', ['Menggunakan gigi tinggi sejak dari bawah tanjakan', 'Memindahkan transmisi berulang kali saat berada di tengah tanjakan', 'Memilih gigi transmisi rendah sejak dari bawah tanjakan dan menghindari perpindahan gigi di tanjakan']),
            $this->q('Apa yang dilakukan ketika hujan turun secara tiba-tiba dan kondisi operasi menjadi tidak aman?', 'B', ['Menambah kecepatan agar pekerjaan cepat selesai', 'Segera memarkir unit di tempat yang aman', 'Tetap bekerja tanpa menilai kondisi jalan']),
            $this->q('Apa tindakan utama ketika mengalami keadaan darurat rem blong?', 'A', ['Tetap tenang, mengendalikan unit, dan berusaha menurunkan transmisi ke gigi rendah', 'Mematikan engine seketika lalu meninggalkan kemudi', 'Menginjak pedal gas agar unit tetap stabil']),
        ];
    }

    private function q(string $question, string $correct, array $answers, ?string $photo = null): array
    {
        $options = [];
        foreach (array_combine(['A', 'B', 'C'], $answers) as $label => $answer) {
            $options[$label] = ['answer' => $answer, 'is_correct' => $label === $correct];
        }
        return ['question' => $question, 'photo_path' => $photo, 'options' => $options];
    }

    private function copyQuestionImages(): void
    {
        foreach (self::IMAGE_SOURCES as $storagePath => $sourcePath) {
            $absoluteSource = database_path($sourcePath);
            $contents = is_file($absoluteSource) ? file_get_contents($absoluteSource) : false;
            if ($contents === false || ! Storage::disk('local')->put($storagePath, $contents)) {
                throw new RuntimeException("Gagal menyalin aset soal Motor Grader: {$sourcePath}.");
            }
        }
    }
}
