<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->decimal('raw_score', 10, 2)->default(0)->after('score');
            $table->unsignedInteger('wrong_answers')->default(0)->after('correct_answers');
            $table->unsignedInteger('blank_answers')->default(0)->after('wrong_answers');
            $table->string('scoring_rule_version', 32)->nullable()->after('passing_score_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['raw_score', 'wrong_answers', 'blank_answers', 'scoring_rule_version']);
        });
    }
};
