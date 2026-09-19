<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempt_number')->nullable()->after('exam_session_id');
            $table->timestamp('authenticated_at')->nullable()->after('status');
            $table->timestamp('deadline_at')->nullable()->after('started_at');
            $table->timestamp('finalized_at')->nullable()->after('finished_at');
            $table->unsignedInteger('duration_minutes_snapshot')->nullable()->after('deadline_at');
            $table->unsignedInteger('passing_score_snapshot')->nullable()->after('duration_minutes_snapshot');
            $table->unsignedInteger('max_score_snapshot')->default(0)->after('passing_score_snapshot');
            $table->string('completion_reason', 32)->nullable()->after('finalized_at');
            $table->unique(['permit_application_id', 'attempt_number'], 'exam_attempt_application_number_unique');
        });

        Schema::table('exam_tokens', function (Blueprint $table) {
            $table->foreignId('exam_attempt_id')->nullable()->after('exam_session_id')
                ->constrained('exam_attempts')->restrictOnDelete();
        });

        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
            $table->json('question_snapshot')->nullable()->after('question_id');
            $table->json('options_snapshot')->nullable()->after('question_snapshot');
            $table->unsignedInteger('score_snapshot')->default(0)->after('options_snapshot');
            $table->unique(['exam_attempt_id', 'order_no'], 'exam_attempt_question_order_unique');
        });

        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
            $table->foreignId('exam_attempt_question_id')->nullable()->after('exam_attempt_id')
                ->constrained('exam_attempt_questions')->restrictOnDelete();
            $table->json('selected_option_snapshot')->nullable()->after('answer_option_id');
            $table->unique('exam_attempt_question_id', 'exam_attempt_answer_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropUnique('exam_attempt_answer_question_unique');
            $table->dropConstrainedForeignId('exam_attempt_question_id');
            $table->dropColumn('selected_option_snapshot');
            $table->dropForeign(['question_id']);
        });
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->dropUnique('exam_attempt_question_order_unique');
            $table->dropColumn(['question_snapshot', 'options_snapshot', 'score_snapshot']);
            $table->dropForeign(['question_id']);
        });
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });
        Schema::table('exam_tokens', fn (Blueprint $table) => $table->dropConstrainedForeignId('exam_attempt_id'));
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropUnique('exam_attempt_application_number_unique');
            $table->dropColumn([
                'attempt_number', 'authenticated_at', 'deadline_at', 'finalized_at',
                'duration_minutes_snapshot', 'passing_score_snapshot', 'max_score_snapshot', 'completion_reason',
            ]);
        });
    }
};
