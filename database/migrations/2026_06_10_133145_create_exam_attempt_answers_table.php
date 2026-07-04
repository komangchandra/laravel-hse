<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_attempt_answers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('exam_attempt_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('answer_option_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->longText('essay_answer')
                ->nullable();

            $table->integer('score')
                ->default(0);

            $table->boolean('is_correct')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
