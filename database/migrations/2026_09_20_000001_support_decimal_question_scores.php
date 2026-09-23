<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('score', 10, 2)->default(0)->change();
        });
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->decimal('score_snapshot', 10, 2)->default(0)->change();
        });
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->decimal('score', 10, 2)->default(0)->change();
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->decimal('max_score_snapshot', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->integer('score')->default(0)->change();
        });
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->unsignedInteger('score_snapshot')->default(0)->change();
        });
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->integer('score')->default(0)->change();
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedInteger('max_score_snapshot')->default(0)->change();
        });
    }
};
