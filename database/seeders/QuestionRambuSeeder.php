<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionRambuSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];

    public function run(): void
    {
        $this->copyQuestionImages();

        DB::transaction(function () {
            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()
                    ->where('owner_id', $ownerId)
                    ->where('name', 'Soal Teori Rambu-rambu')
                    ->firstOrFail();

                foreach ($this->questions() as $number => $data) {
                    $photoPath = sprintf('questions/rambu/rambu_%02d.png', $number + 1);
                    $question = Question::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'question' => 'Apa arti rambu berikut?',
                            'photo_path' => $photoPath,
                        ],
                        [
                            'type' => 'multiple_choice',
                            'score' => 3.3,
                        ],
                    );

                    foreach (array_combine(['A', 'B', 'C'], $data['answers']) as $label => $answer) {
                        AnswerOption::updateOrCreate(
                            ['question_id' => $question->id, 'label' => $label],
                            ['answer' => $answer, 'is_correct' => $label === $data['correct']],
                        );
                    }

                    $question->options()->whereNotIn('label', ['A', 'B', 'C'])->delete();
                }
            }
        });
    }

    private function questions(): array
    {
        return [
            ['correct' => 'A', 'answers' => ['Berhenti 8 detik', 'Parkir dilarang', 'Jalan satu arah']],
            ['correct' => 'B', 'answers' => ['Berhenti total', 'Beri kesempatan kendaraan lain melintas', 'Dilarang masuk']],
            ['correct' => 'C', 'answers' => ['Dilarang belok kiri', 'Dilarang putar balik', 'Dilarang mendahului']],
            ['correct' => 'A', 'answers' => ['Batas akhir larangan mendahului', 'Batas akhir kecepatan 40 km/jam', 'Batas akhir larangan parkir']],
            ['correct' => 'B', 'answers' => ['Kecepatan minimum 40 km/jam', 'Kecepatan maksimum 40 km/jam', 'Batas akhir kecepatan 40 km/jam']],
            ['correct' => 'C', 'answers' => ['Kecepatan maksimum 40 km/jam', 'Kecepatan minimum 40 km/jam', 'Batas akhir pembatasan kecepatan']],
            ['correct' => 'A', 'answers' => ['Wajib mengikuti arah panah', 'Dilarang belok kiri', 'Arah jalan satu arah']],
            ['correct' => 'B', 'answers' => ['Dilarang berhenti', 'Dilarang masuk', 'Wajib berhenti']],
            ['correct' => 'C', 'answers' => ['Belok kanan wajib', 'Simpang tiga di kanan', 'Tikungan ke kanan']],
            ['correct' => 'A', 'answers' => ['Dilarang belok kanan', 'Wajib belok kanan', 'Dilarang putar balik']],
            ['correct' => 'B', 'answers' => ['Jalan berkelok ke kiri', 'Tikungan ganda', 'Tikungan tajam ke kanan']],
            ['correct' => 'C', 'answers' => ['Persimpangan tiga arah', 'Jalan lurus', 'Simpang empat']],
            ['correct' => 'A', 'answers' => ['Simpang tiga dari sisi kiri', 'Simpang tiga sisi kanan', 'Jalan menyempit sisi kiri']],
            ['correct' => 'B', 'answers' => ['Wajib berhenti', 'Hati-hati', 'Jalan licin']],
            ['correct' => 'C', 'answers' => ['Kendaraan dari depan wajib berhenti', 'Jalur dua arah bebas hambatan', 'Berhenti dan dahulukan kendaraan dari depan']],
            ['correct' => 'A', 'answers' => ['Kendaraan roda dua dilarang masuk', 'Kendaraan roda dua wajib masuk', 'Dilarang kendaraan roda empat']],
            ['correct' => 'B', 'answers' => ['Jalan menyempit di kanan', 'Jembatan sempit', 'Jalan bergelombang']],
            ['correct' => 'C', 'answers' => ['Dilarang putar balik', 'Dilarang parkir', 'Dilarang berhenti (stop)']],
            ['correct' => 'A', 'answers' => ['Tempat parkir', 'Dilarang parkir', 'Jalan satu arah']],
            ['correct' => 'B', 'answers' => ['Turunan', 'Tanjakan', 'Jalan datar']],
            ['correct' => 'C', 'answers' => ['Turunan terjal', 'Jalan licin', 'Tanjakan terjal']],
            ['correct' => 'A', 'answers' => ['Jalan bergelombang', 'Jalan berlubang', 'Jalan licin']],
            ['correct' => 'B', 'answers' => ['Tikungan tajam ke kanan', 'Arah tikungan tajam ke kiri', 'Dilarang belok kiri']],
            ['correct' => 'C', 'answers' => ['Penyempitan di sisi kanan', 'Jalan melebar', 'Penyempitan jalan']],
            ['correct' => 'A', 'answers' => ['Jalan cembung', 'Jalan bergelombang', 'Jalan licin']],
            ['correct' => 'B', 'answers' => ['Kecepatan minimum 20 km/jam', 'Kecepatan maksimum 20 km/jam', 'Batas akhir kecepatan 20 km/jam']],
            ['correct' => 'C', 'answers' => ['Kecepatan maksimum 20 km/jam', 'Kecepatan minimum 20 km/jam', 'Batas akhir kecepatan maksimum 20 km/jam']],
            ['correct' => 'A', 'answers' => ['Wajib mengikuti arah putaran', 'Dilarang putar balik', 'Wajib belok kanan']],
            ['correct' => 'B', 'answers' => ['Jalan licin', 'Waspada jatuhan batu', 'Waspada banjir']],
            ['correct' => 'C', 'answers' => ['Dilarang membunyikan klakson', 'Klakson tidak berfungsi', 'Wajib membunyikan klakson']],
        ];
    }

    private function copyQuestionImages(): void
    {
        foreach (range(1, 30) as $number) {
            $filename = sprintf('rambu_%02d.png', $number);
            $sourcePath = "seeders/assets/{$filename}";
            $absoluteSource = database_path($sourcePath);
            $contents = is_file($absoluteSource) ? file_get_contents($absoluteSource) : false;

            if ($contents === false || ! Storage::disk('local')->put("questions/rambu/{$filename}", $contents)) {
                throw new RuntimeException("Gagal menyalin aset soal Rambu: {$sourcePath}.");
            }
        }
    }
}
