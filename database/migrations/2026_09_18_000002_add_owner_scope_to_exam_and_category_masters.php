<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('partners')->restrictOnDelete();
        });
        Schema::table('question_categories', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('partners')->restrictOnDelete();
        });
        Schema::table('simper_categories', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('partners')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', fn (Blueprint $table) => $table->dropConstrainedForeignId('owner_id'));
        Schema::table('question_categories', fn (Blueprint $table) => $table->dropConstrainedForeignId('owner_id'));
        Schema::table('simper_categories', fn (Blueprint $table) => $table->dropConstrainedForeignId('owner_id'));
    }
};
