<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedInteger('passing_score')->default(80)->change();
        });
        DB::table('exam_sessions')->where('passing_score', 70)->update(['passing_score' => 80]);
    }

    public function down(): void
    {
        DB::table('exam_sessions')->where('passing_score', 80)->update(['passing_score' => 70]);
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedInteger('passing_score')->default(70)->change();
        });
    }
};
