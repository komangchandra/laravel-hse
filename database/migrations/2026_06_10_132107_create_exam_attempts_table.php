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
        Schema::create('exam_attempts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('simper_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('exam_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('total_questions')->default(0);

            $table->integer('correct_answers')->default(0);

            $table->decimal('score', 5, 2)->nullable();

            $table->boolean('is_passed')->nullable();

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
            ])->default('pending');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
