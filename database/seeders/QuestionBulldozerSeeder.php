<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionBulldozerSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];
    private const COMPONENT_DIAGRAM_PATH = 'questions/dozer/bulldozer-component-diagram.png';
    private const IMAGE_SOURCES = [
        self::COMPONENT_DIAGRAM_PATH => 'seeders/assets/bulldozer-component-diagram.png',
        'questions/dozer/bulldozer-instrumen-panel-1.png' => 'seeders/assets/bulldozer-instrumen-panel-1.png',
        'questions/dozer/bulldozer-instrumen-panel-2.png' => 'seeders/assets/bulldozer-instrumen-panel-2.png',
        'questions/dozer/bulldozer-instrumen-panel-3.png' => 'seeders/assets/bulldozer-instrumen-panel-3.png',
        'questions/dozer/bulldozer-instrumen-panel-4.png' => 'seeders/assets/bulldozer-instrumen-panel-4.png',
        'questions/dozer/bulldozer-instrumen-panel-5.png' => 'seeders/assets/bulldozer-instrumen-panel-5.png',
        'questions/dozer/bulldozer-instrumen-panel-6.png' => 'seeders/assets/bulldozer-instrumen-panel-6.png',
        'questions/dozer/bulldozer-instrumen-panel-7.png' => 'seeders/assets/bulldozer-instrumen-panel-7.png',
        'questions/dozer/bulldozer-instrumen-panel-8.png' => 'seeders/assets/bulldozer-instrumen-panel-8.png',
        'questions/dozer/bulldozer-instrumen-panel-9.png' => 'seeders/assets/bulldozer-instrumen-panel-9.png',
        'questions/dozer/bulldozer-instrumen-panel-10.png' => 'seeders/assets/bulldozer-instrumen-panel-10.png',
    ];

    public function run(): void
    {
        $this->copyQuestionImages();
        DB::transaction(function () {
            $questions = array_merge($this->safetyQuestions(), $this->componentQuestions(), $this->panelQuestions(), $this->maintenanceQuestions(), $this->operationalQuestions(), $this->workSituationQuestions());
            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()->where('owner_id', $ownerId)->where('name', 'Soal Teori Bulldozer')->firstOrFail();
                foreach ($questions as $data) {
                    $question = Question::updateOrCreate(
                        ['category_id' => $category->id, 'question' => $data['question'], 'photo_path' => $data['photo_path']],
                        ['type' => 'multiple_choice', 'score' => 1.25],
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
            $this->q('Pada waktu akan mengoperasikan mesin, perlengkapan apa yang harus Anda kenakan?', 'B', ['Baju kerja dan kacamata hitam.', 'Helm, sabuk pengaman, sepatu kerja, dan sarung tangan.', 'Kacamata hitam dan sarung tangan.']),
            $this->q('Pada waktu mengisi bahan bakar, bagaimana kondisi engine yang benar?', 'B', ['Boleh hidup asalkan transmisi netral dan operator tidak merokok.', 'Harus dimatikan, dilarang merokok, dan safety lock diaktifkan.', 'Boleh hidup asalkan rem parkir diaktifkan dan operator tidak merokok.']),
            $this->q('Menurut aturan keselamatan, berapa kali klakson dibunyikan secara berurutan saat akan menghidupkan engine, bergerak maju, dan bergerak mundur?', 'C', ['2 - 3 - 1.', '3 - 2 - 1.', '1 - 2 - 3.']),
            $this->q('Untuk menghindari bahaya selama mengoperasikan mesin, operator sebaiknya melakukan apa?', 'C', ['Membawa seorang penumpang untuk membantu.', 'Berdiri agar mudah melihat keadaan sekeliling.', 'Tidak membawa penumpang serta memperhatikan rambu kerja dan lingkungan.']),
            $this->q('Jika terjadi gangguan pada mesin yang Anda operasikan, apa yang harus dilakukan?', 'C', ['Langsung memeriksa dan berusaha memperbaikinya sendiri.', 'Meminta bantuan teman untuk memperbaikinya.', 'Menghentikan engine, memarkir unit di tempat aman, dan melapor kepada atasan.']),
            $this->q('Bagaimana prosedur turun dari mesin yang benar?', 'B', ['Turun sambil membelakangi tangga.', 'Turun menghadap tangga dengan mempertahankan tiga titik kontak.', 'Melompat turun dari tangga.']),
            $this->q('Saat memeriksa air baterai pada malam hari atau dalam kondisi kurang terang, alat penerangan apa yang dianjurkan?', 'A', ['Lampu senter.', 'Korek api.', 'Jari tangan.']),
            $this->q('Cara kerja yang tidak mematuhi prosedur dan membahayakan disebut apa?', 'B', ['Kondisi tidak aman.', 'Tindakan tidak aman.', 'Keadaan sosial.']),
            $this->q('Sikap pemarah, emosional, dan tidak peduli terhadap K3 termasuk apa?', 'A', ['Sifat buruk seseorang.', 'Kondisi tidak aman.', 'Keadaan lingkungan kerja.']),
            $this->q('Siapa yang bertanggung jawab atas keselamatan di lingkungan kerja?', 'C', ['Perusahaan atau pimpinan saja.', 'Pekerja saja.', 'Perusahaan, pimpinan, dan seluruh pekerja secara bersama-sama.']),
            $this->q('Berapa batas kecepatan maksimum di jalan hauling dan di jalan tambang/pit yang diizinkan di area kerja IUP PT. Gorby Putra Utama/IUP PT. Gorby Energy?', 'A', ['40 km/jam dan 30 km/jam.', '40 km/jam dan 40 km/jam.', '40 km/jam dan 50 km/jam.']),
            $this->q('Di bawah ini merupakan tindakan yang harus dilakukan ketika mengalami keadaan darurat di jalan tambang, kecuali?', 'A', ['Mengaktifkan rem parkir saat unit masih melaju.', 'Tetap mengendalikan steering dan menurunkan transmisi ke tingkat yang lebih rendah.', 'Mengarahkan unit secara perlahan ke sisi tanggul atau tebing pengaman bila diperlukan.']),
            $this->q('Area manakah yang memenuhi syarat untuk memarkir unit?', 'B', ['Rest area tanpa mempertimbangkan keadaan permukaannya.', 'Daerah datar, luas, mudah dijangkau, serta jauh dari area longsor dan operasi unit.', 'Area yang berjarak 10 meter dari unit yang sedang beroperasi.']),
            $this->q('Apa yang terjadi saat pengemudi menekan pedal rem dengan benar ketika menuruni jalan curam?', 'A', ['Kecepatan kendaraan berkurang.', 'Unit langsung melaju tidak terkendali akibat gravitasi.', 'Putaran engine selalu menjadi berlebih dan merusak engine.']),
            $this->q('Tidak tersedianya alat pelindung diri merupakan penyebab langsung kecelakaan yang digolongkan sebagai apa?', 'B', ['Tindakan tidak aman.', 'Kondisi tidak aman.', 'Faktor sosial.']),
        ];
    }

    private function componentQuestions(): array
    {
        $rows = [
            [1, 'A', ['Blade.', 'Ripper.', 'Radiator.']], [2, 'B', ['Track shoe.', 'Frame blade.', 'Idler.']],
            [3, 'C', ['Blade lift cylinder.', 'Arm.', 'Tilt cylinder blade.']], [4, 'A', ['Track shoe.', 'Carrier roller.', 'Sprocket.']],
            [5, 'B', ['Idler.', 'Sprocket.', 'Track shoe.']], [6, 'C', ['Radiator.', 'Exhaust pipe.', 'Ripper.']],
            [7, 'A', ['Carrier roller.', 'Idler.', 'Frame blade.']], [8, 'B', ['Tilt cylinder blade.', 'Blade lift cylinder.', 'Arm.']],
            [9, 'A', ['Exhaust pipe.', 'Radiator.', 'Ripper.']], [10, 'B', ['Blade.', 'Arm.', 'Frame blade.']],
        ];
        return array_map(fn ($row) => $this->q("Pada diagram Bulldozer, komponen yang ditunjukkan oleh nomor {$row[0]} adalah apa?", $row[1], $row[2], self::COMPONENT_DIAGRAM_PATH), $rows);
    }

    private function panelQuestions(): array
    {
        $levels = ['Engine Oil Level.', 'Radiator Water Level.', 'Hydraulic Oil Level.'];
        $levelFunctions = ['Menunjukkan jumlah air radiator.', 'Menunjukkan jumlah oli engine.', 'Menunjukkan jumlah oli hidraulik.'];
        $warnings = ['Engine Water Temperature.', 'Hydraulic Oil Temperature.', 'Engine Oil Pressure.'];
        $warningFunctions = ['Tekanan oli engine berada di bawah standar.', 'Temperatur oli hidraulik terlalu tinggi.', 'Temperatur air pendingin engine terlalu tinggi.'];
        $indicators = ['Air Cleaner Clogging.', 'Battery Charge Level.', 'Fuel Level.'];
        $indicatorFunctions = ['Bahan bakar di dalam tangki berada di bawah batas minimum.', 'Terdapat ketidaknormalan pada sistem kelistrikan atau pengisian baterai.', 'Elemen air cleaner mengalami penyumbatan atau kotor.'];
        $rows = [
            [1, 'B', 'A', $levels, $levelFunctions, 'Apa fungsi simbol tersebut?'], [2, 'A', 'B', $levels, $levelFunctions, 'Apa fungsi simbol tersebut?'], [3, 'C', 'C', $levels, $levelFunctions, 'Apa fungsi simbol tersebut?'],
            [4, 'C', 'A', $warnings, $warningFunctions, 'Apa arti simbol tersebut ketika monitor menyala dan alarm berbunyi?'], [5, 'B', 'B', $warnings, $warningFunctions, 'Apa arti simbol tersebut ketika monitor menyala dan alarm berbunyi?'], [6, 'A', 'C', $warnings, $warningFunctions, 'Apa arti simbol tersebut ketika monitor menyala dan alarm berbunyi?'],
            [7, 'B', 'B', $indicators, $indicatorFunctions, 'Apa arti simbol tersebut ketika monitor menyala dan alarm berbunyi?'], [8, 'C', 'A', $indicators, $indicatorFunctions, 'Apa arti simbol tersebut ketika monitor menyala dan alarm berbunyi?'], [9, 'A', 'C', $indicators, $indicatorFunctions, 'Apa arti simbol tersebut ketika monitor menyala dan alarm berbunyi?'],
            [10, 'A', 'B', ['Pemanasan awal (preheating).', 'Sistem kelistrikan.', 'Fuel Level.'], ['Sistem kelistrikan tidak normal.', 'Pemanasan awal pada ruang pembakaran sedang berfungsi.', 'Engine mengalami panas berlebih.'], 'Apa arti simbol tersebut ketika lampu monitor menyala?'],
        ];
        $questions = [];
        foreach ($rows as [$number, $nameKey, $functionKey, $names, $functions, $functionQuestion]) {
            $photo = "questions/dozer/bulldozer-instrumen-panel-{$number}.png";
            $questions[] = $this->q('Apa nama simbol panel instrumen tersebut?', $nameKey, $names, $photo);
            $questions[] = $this->q($functionQuestion, $functionKey, $functions, $photo);
        }
        return $questions;
    }

    private function maintenanceQuestions(): array
    {
        return [
            $this->q('Pada berapa jam operasi (HM) oli engine seharusnya diganti?', 'B', ['100 HM.', '250 HM.', '300 HM.']),
            $this->q('Bagaimana cara yang benar untuk memeriksa oli torque converter/transmisi?', 'B', ['Saat engine mati.', 'Saat engine hidup pada putaran idle.', 'Saat engine mati setelah sebelumnya hidup beberapa saat.']),
            $this->q('Kekurangan oli pada engine dapat mengakibatkan apa?', 'A', ['Engine mengalami panas berlebih.', 'Seluruh unit langsung mengalami panas berlebih.', 'Engine selalu mengalami low power tanpa gejala lain.']),
            $this->q('Bagaimana syarat pemeriksaan oli hidraulik pada bulldozer?', 'A', ['Parkir di tempat rata, turunkan seluruh attachment, matikan engine, lalu periksa sight gauge pada tangki hidraulik.', 'Parkir di tempat rata, biarkan engine hidup, lalu periksa sight gauge.', 'Parkir di tempat rata dengan blade dan ripper tetap terangkat.']),
            $this->q('Air yang digunakan untuk menambah air radiator seharusnya berupa apa?', 'B', ['Air apa saja asalkan tidak mengandung lumpur.', 'Air bersih yang layak diminum atau coolant sesuai spesifikasi.', 'Air sungai yang disaring dengan kain.']),
            $this->q('Jenis air apa yang digunakan untuk menambah air baterai?', 'C', ['Air mineral.', 'Accu zuur.', 'Air suling.']),
            $this->q('Berapa kekenduran track maksimum yang diizinkan oleh pabrik?', 'A', ['20–30 mm.', '30–40 mm.', '40–50 mm.']),
            $this->q('Apa fungsi turbocharger?', 'A', ['Membantu memasukkan lebih banyak udara ke ruang bakar.', 'Menumpahkan bahan bakar ke ruang bakar.', 'Hanya mendorong gas buang keluar dari ruang bakar.']),
            $this->q('Apa fungsi water separator pada fuel system?', 'B', ['Mencampur bahan bakar dengan air.', 'Memisahkan bahan bakar dari air dan kotoran.', 'Hanya menunjukkan kondisi bahan bakar.']),
            $this->q('Warna gas buang yang menunjukkan pembakaran paling baik adalah ...', 'C', ['Hitam.', 'Biru.', 'Kelabu tipis atau transparan.']),
            $this->q('Apa akibat umum dari saringan udara (air cleaner) yang tersumbat?', 'B', ['Tenaga engine menjadi lebih besar.', 'Tenaga engine menurun (low power).', 'Tidak terjadi perubahan pada tenaga engine.']),
            $this->q('Apa yang harus dilakukan jika terdapat indikasi filter oli tersumbat?', 'B', ['Mencuci filter oli dan memasangnya kembali.', 'Mengganti filter oli dengan yang baru.', 'Hanya mengganti oli engine agar filter menjadi bersih.']),
            $this->q('Bagaimana warna gas buang ketika saringan udara tersumbat atau kotor?', 'C', ['Putih dan tenaga bertambah.', 'Biru dan tenaga tetap normal.', 'Hitam dan tenaga engine berkurang.']),
            $this->q('Apa akibat mengabaikan perawatan mesin?', 'C', ['Jam operasi menjadi lebih panjang tanpa risiko.', 'Kondisi unit tetap terkontrol selama operator berhati-hati.', 'Kondisi unit tidak terkontrol dan komponen lebih cepat rusak.']),
            $this->q('Kegiatan apa yang dilakukan agar mesin tetap siap dipakai?', 'C', ['Perawatan harian saja.', 'Perawatan berkala saja.', 'Perawatan harian dan berkala.']),
            $this->q('Perawatan adalah tindakan yang dilakukan terhadap mesin agar ...', 'A', ['Mesin selalu dalam kondisi siap pakai.', 'Kemampuan mesin dipaksa melebihi standar.', 'Mesin hanya terlihat lebih bersih.']),
            $this->q('Apa tujuan utama pelaksanaan perawatan?', 'C', ['Hanya meminimalkan biaya pengoperasian.', 'Hanya mencegah kecelakaan.', 'Memaksimalkan waktu operasi dan meminimalkan biaya perbaikan.']),
            $this->q('Apa arti gas buang berwarna hitam?', 'C', ['Bahan bakar yang terbakar mengandung oli.', 'Campuran mengandung lebih banyak udara daripada bahan bakar.', 'Campuran mengandung terlalu banyak bahan bakar dibandingkan udara.']),
            $this->q('Bagaimana posisi dipstick yang benar saat membaca ketinggian oli engine?', 'C', ['Menghadap ke atas.', 'Menghadap ke bawah.', 'Mendatar.']),
            $this->q('Bagaimana prosedur mematikan engine yang benar?', 'C', ['Menaikkan putaran engine agar baterai penuh, lalu langsung mematikannya.', 'Menginjak pedal gas agar baterai terisi, lalu mematikan engine.', 'Menurunkan putaran ke low idle selama kurang lebih lima menit, lalu mematikan engine.']),
        ];
    }

    private function operationalQuestions(): array
    {
        return [
            $this->q('Apa yang harus dilakukan sebelum mengoperasikan unit?', 'B', ['Langsung menghidupkan engine.', 'Melakukan pemeriksaan terhadap kondisi unit.', 'Menunggu perintah tanpa memeriksa unit.']),
            $this->q('Berapa jarak dorong yang paling efisien untuk bulldozer?', 'B', ['20–30 meter.', '30–50 meter.', '40–80 meter.']),
            $this->q('Bagaimana langkah yang benar saat memindahkan arah transmisi dari maju ke mundur atau sebaliknya?', 'C', ['Langsung memindahkan transmission lever ke arah sebaliknya.', 'Hanya menurunkan RPM, lalu langsung memindahkan lever.', 'Menghentikan unit terlebih dahulu, kemudian memindahkan transmission lever.']),
            $this->q('Saat melakukan travel atau berpindah lokasi, berapa ketinggian blade dari permukaan tanah?', 'B', ['30–40 cm.', '10–30 cm.', 'Setinggi apa pun asalkan operator merasa aman.']),
            $this->q('Saat bulldozer bergerak mundur di medan datar, bagian track mana yang kencang?', 'B', ['Bagian bawah.', 'Bagian atas.', 'Bagian atas dan bawah sama-sama kencang.']),
            $this->q('Berapa tingkat kecepatan transmisi bulldozer D85?', 'B', ['1 sampai 2.', '1 sampai 3.', '1 sampai 4.']),
            $this->q('Apa kegunaan steering lever?', 'B', ['Menghentikan unit.', 'Membelokkan unit.', 'Menggerakkan attachment.']),
            $this->q('Mengapa mendorong di daerah menurun lebih menguntungkan?', 'C', ['Hanya karena beban engine lebih ringan.', 'Hanya karena kecepatan unit lebih tinggi.', 'Tenaga dorong dibantu oleh berat unit dan gaya gravitasi.']),
            $this->q('Apa tindakan pertama saat membuat jalan di lokasi yang masih baru?', 'C', ['Langsung menimbun lokasi agar cepat selesai.', 'Langsung membentuk badan jalan tanpa persiapan.', 'Mengupas dan membuang lapisan tanah atas, kemudian membentuk jalan.']),
            $this->q('Bagaimana teknik yang benar untuk mendorong pohon besar dengan bulldozer?', 'B', ['Mendorong batangnya langsung dengan kekuatan penuh.', 'Menggali area di sekitar pohon hingga akar terputus, kemudian mendorong pohon.', 'Mengelupas batang pohon sedikit demi sedikit sebelum mendorongnya.']),
        ];
    }

    private function workSituationQuestions(): array
    {
        return [
            $this->q('Apa tindakan pertama yang tepat jika melihat kecelakaan di area tambang?', 'A', ['Segera melapor kepada atasan atau petugas tanggap darurat sesuai prosedur.', 'Meninggalkan lokasi tanpa memberi tahu siapa pun.', 'Memindahkan korban sendiri tanpa menilai bahaya di sekitar lokasi.']),
            $this->q('Apa yang harus dilakukan jika unit mengalami kerusakan di jalan hauling?', 'B', ['Tetap menjalankan unit sampai ke workshop.', 'Memarkir atau mengamankan unit di tempat aman dan melapor kepada atasan.', 'Meninggalkan unit di jalur aktif tanpa memasang pengaman.']),
            $this->q('Apa kepanjangan P2H dan kapan kegiatan tersebut dilakukan?', 'A', ['Pemeriksaan dan Perawatan Harian; dilakukan pada awal dan akhir shift.', 'Pengawasan Peralatan Hauling; dilakukan hanya ketika unit rusak.', 'Pemeriksaan Proses Hauling; dilakukan sebulan sekali.']),
            $this->q('Apa yang harus dilakukan ketika hujan tiba-tiba turun dan kondisi kerja menjadi tidak aman?', 'B', ['Menambah kecepatan agar pekerjaan cepat selesai.', 'Menghentikan pekerjaan dan memarkir unit di tempat aman sesuai arahan pengawas.', 'Tetap bekerja seperti biasa tanpa menilai kondisi lapangan.']),
            $this->q('Apa yang harus dilakukan jika unit amblas?', 'C', ['Terus menggerakkan track dengan putaran tinggi sampai unit keluar.', 'Keluar dari unit dan berjalan mencari bantuan tanpa melapor.', 'Mengamankan unit dan melapor kepada pengawas untuk meminta bantuan evakuasi.']),
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
                throw new RuntimeException("Gagal menyalin aset soal Bulldozer: {$sourcePath}.");
            }
        }
    }
}
