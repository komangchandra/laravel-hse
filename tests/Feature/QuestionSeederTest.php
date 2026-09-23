<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionCategory;
use Database\Seeders\PartnerSeeder;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Database\Seeders\QuestionBulldozerSeeder;
use Database\Seeders\QuestionDtSeeder;
use Database\Seeders\QuestionExcaSeeder;
use Database\Seeders\QuestionLvSeeder;
use Database\Seeders\QuestionMotorGraderSeeder;
use Database\Seeders\QuestionRambuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_categories_are_seeded_for_both_owners(): void
    {
        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
        ]);

        $this->assertSame(6, QuestionCategory::where('owner_id', 1)->count());
        $this->assertSame(6, QuestionCategory::where('owner_id', 2)->count());
        $this->assertSame(12, QuestionCategory::count());
    }

    public function test_question_lv_seeder_creates_valid_questions_and_component_diagram_for_each_owner(): void
    {
        Storage::fake('local');
        $this->assertFileExists(database_path('seeders/assets/diagram-komponen-lv.png'));

        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
            QuestionLvSeeder::class,
        ]);

        $questions = Question::with(['category', 'options'])->get();

        $this->assertCount(100, $questions);
        $this->assertSame(50, $questions->filter(fn (Question $question) => $question->category->owner_id === 1)->count());
        $this->assertSame(50, $questions->filter(fn (Question $question) => $question->category->owner_id === 2)->count());
        $this->assertSame(40, $questions->whereNotNull('photo_path')->count());
        $this->assertSame(20, $questions->where('photo_path', 'questions/diagram-komponen-lv.png')->count());
        Storage::disk('local')->assertExists('questions/diagram-komponen-lv.png');
        Storage::disk('local')->assertExists([
            'questions/panel-01-temperature-coolant.png',
            'questions/panel-02-engine-oil-pressure.png',
            'questions/panel-03-fuel-level.png',
            'questions/panel-04-battery-charge.png',
            'questions/panel-05-engine-rpm.png',
        ]);

        foreach ($questions as $question) {
            $this->assertSame('multiple_choice', $question->type);
            $this->assertCount(3, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertTrue(Question::validForExam()->whereKey($question)->exists());
        }
    }

    public function test_question_dt_seeder_creates_all_questions_and_assets_for_each_owner(): void
    {
        Storage::fake('local');

        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
            QuestionDtSeeder::class,
        ]);

        $questions = Question::with(['category', 'options'])->get();

        $this->assertCount(122, $questions);
        $this->assertSame(61, $questions->filter(fn (Question $question) => $question->category->owner_id === 1)->count());
        $this->assertSame(61, $questions->filter(fn (Question $question) => $question->category->owner_id === 2)->count());
        $this->assertSame(52, $questions->whereNotNull('photo_path')->count());
        Storage::disk('local')->assertExists([
            'questions/dt/dt-component-unit.jpg',
            'questions/dt/dt-instrumen-panel-1.png',
            'questions/dt/dt-instrumen-panel-2.png',
            'questions/dt/dt-instrumen-panel-3.png',
            'questions/dt/dt-instrumen-panel-4.png',
            'questions/dt/dt-instrumen-panel-5.png',
            'questions/dt/dt-instrumen-panel-6.png',
            'questions/dt/dt-instrumen-panel-7.png',
            'questions/dt/dt-instrumen-panel-8.png',
        ]);

        foreach ($questions as $question) {
            $this->assertSame('Soal Teori Dump Truck', $question->category->name);
            $this->assertSame('multiple_choice', $question->type);
            $this->assertSame('1.60', $question->score);
            $this->assertCount(3, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertTrue(Question::validForExam()->whereKey($question)->exists());
        }
    }

    public function test_question_exca_seeder_creates_all_questions_and_assets_for_each_owner(): void
    {
        Storage::fake('local');

        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
            QuestionExcaSeeder::class,
        ]);

        $questions = Question::with(['category', 'options'])->get();

        $this->assertCount(150, $questions);
        $this->assertSame(75, $questions->filter(fn (Question $question) => $question->category->owner_id === 1)->count());
        $this->assertSame(75, $questions->filter(fn (Question $question) => $question->category->owner_id === 2)->count());
        $this->assertSame(60, $questions->whereNotNull('photo_path')->count());
        Storage::disk('local')->assertExists([
            'questions/exca/exca-component-diagram.jpg',
            'questions/exca/exca-instrumen-panel-1.png',
            'questions/exca/exca-instrumen-panel-2.png',
            'questions/exca/exca-instrumen-panel-3.png',
            'questions/exca/exca-instrumen-panel-4.png',
            'questions/exca/exca-instrumen-panel-5.png',
            'questions/exca/exca-instrumen-panel-6.png',
            'questions/exca/exca-instrumen-panel-7.png',
            'questions/exca/exca-instrumen-panel-8.png',
            'questions/exca/exca-instrumen-panel-9.png',
            'questions/exca/exca-instrumen-panel-10.png',
        ]);

        foreach ($questions as $question) {
            $this->assertSame('Soal Teori Excavator', $question->category->name);
            $this->assertSame('multiple_choice', $question->type);
            $this->assertSame('1.30', $question->score);
            $this->assertCount(3, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertTrue(Question::validForExam()->whereKey($question)->exists());
        }
    }

    public function test_question_bulldozer_seeder_creates_all_questions_and_assets_for_each_owner(): void
    {
        Storage::fake('local');

        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
            QuestionBulldozerSeeder::class,
        ]);

        $questions = Question::with(['category', 'options'])->get();

        $this->assertCount(160, $questions);
        $this->assertSame(80, $questions->filter(fn (Question $question) => $question->category->owner_id === 1)->count());
        $this->assertSame(80, $questions->filter(fn (Question $question) => $question->category->owner_id === 2)->count());
        $this->assertSame(60, $questions->whereNotNull('photo_path')->count());
        Storage::disk('local')->assertExists([
            'questions/dozer/bulldozer-component-diagram.png',
            'questions/dozer/bulldozer-instrumen-panel-1.png',
            'questions/dozer/bulldozer-instrumen-panel-2.png',
            'questions/dozer/bulldozer-instrumen-panel-3.png',
            'questions/dozer/bulldozer-instrumen-panel-4.png',
            'questions/dozer/bulldozer-instrumen-panel-5.png',
            'questions/dozer/bulldozer-instrumen-panel-6.png',
            'questions/dozer/bulldozer-instrumen-panel-7.png',
            'questions/dozer/bulldozer-instrumen-panel-8.png',
            'questions/dozer/bulldozer-instrumen-panel-9.png',
            'questions/dozer/bulldozer-instrumen-panel-10.png',
        ]);

        foreach ($questions as $question) {
            $this->assertSame('Soal Teori Bulldozer', $question->category->name);
            $this->assertSame('multiple_choice', $question->type);
            $this->assertSame('1.25', $question->score);
            $this->assertCount(3, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertTrue(Question::validForExam()->whereKey($question)->exists());
        }
    }

    public function test_question_motor_grader_seeder_creates_all_questions_and_assets_for_each_owner(): void
    {
        Storage::fake('local');

        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
            QuestionMotorGraderSeeder::class,
        ]);

        $questions = Question::with(['category', 'options'])->get();

        $this->assertCount(120, $questions);
        $this->assertSame(60, $questions->filter(fn (Question $question) => $question->category->owner_id === 1)->count());
        $this->assertSame(60, $questions->filter(fn (Question $question) => $question->category->owner_id === 2)->count());
        $this->assertSame(60, $questions->whereNotNull('photo_path')->count());
        Storage::disk('local')->assertExists([
            'questions/motor-grader/mg-component-diagram.jpg',
            'questions/motor-grader/mg-instrumen-panel-1.png',
            'questions/motor-grader/mg-instrumen-panel-2.png',
            'questions/motor-grader/mg-instrumen-panel-3.png',
            'questions/motor-grader/mg-instrumen-panel-4.png',
            'questions/motor-grader/mg-instrumen-panel-5.png',
            'questions/motor-grader/mg-instrumen-panel-6.png',
            'questions/motor-grader/mg-instrumen-panel-7.png',
            'questions/motor-grader/mg-instrumen-panel-8.png',
            'questions/motor-grader/mg-instrumen-panel-9.png',
            'questions/motor-grader/mg-instrumen-panel-10.png',
        ]);

        foreach ($questions as $question) {
            $this->assertSame('Soal Teori Motorgrader', $question->category->name);
            $this->assertSame('multiple_choice', $question->type);
            $this->assertSame('1.60', $question->score);
            $this->assertCount(3, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertTrue(Question::validForExam()->whereKey($question)->exists());
        }
    }

    public function test_question_rambu_seeder_creates_all_questions_and_assets_for_each_owner(): void
    {
        Storage::fake('local');

        $this->seed([
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            QuestionCategorySeeder::class,
            QuestionRambuSeeder::class,
        ]);

        $questions = Question::with(['category', 'options'])->get();

        $this->assertCount(60, $questions);
        $this->assertSame(30, $questions->filter(fn (Question $question) => $question->category->owner_id === 1)->count());
        $this->assertSame(30, $questions->filter(fn (Question $question) => $question->category->owner_id === 2)->count());
        $this->assertSame(60, $questions->whereNotNull('photo_path')->count());
        Storage::disk('local')->assertExists(array_map(
            fn (int $number) => sprintf('questions/rambu/rambu_%02d.png', $number),
            range(1, 30),
        ));

        foreach ($questions as $question) {
            $this->assertSame('Soal Teori Rambu-rambu', $question->category->name);
            $this->assertSame('multiple_choice', $question->type);
            $this->assertSame('3.30', $question->score);
            $this->assertCount(3, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertTrue(Question::validForExam()->whereKey($question)->exists());
        }
    }
}
