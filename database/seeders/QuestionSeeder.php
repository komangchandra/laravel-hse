<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    private const OWNER_IDS = [1, 2];

    public function run(): void
    {
        DB::transaction(function () {
            $options = [
                'A' => ['answer' => 'Melakukan pemeriksaan awal kendaraan (pre-start check)', 'is_correct' => true],
                'B' => ['answer' => 'Langsung menyalakan mesin dan berangkat', 'is_correct' => false],
                'C' => ['answer' => 'Membunyikan klakson sepanjang perjalanan', 'is_correct' => false],
            ];

            foreach (self::OWNER_IDS as $ownerId) {
                $category = QuestionCategory::query()
                    ->where('owner_id', $ownerId)
                    ->where('name', 'Soal Teori Light Vehicle')
                    ->firstOrFail();

                $question = Question::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'question' => 'Apa tindakan pertama yang harus dilakukan sebelum mengoperasikan kendaraan ringan?',
                    ],
                    [
                        'type' => 'multiple_choice',
                        'score' => 10,
                        'photo_path' => null,
                    ],
                );

                foreach ($options as $label => $option) {
                    AnswerOption::updateOrCreate(
                        ['question_id' => $question->id, 'label' => $label],
                        $option,
                    );
                }

                $question->options()->whereNotIn('label', array_keys($options))->delete();
            }
        });
    }
}
