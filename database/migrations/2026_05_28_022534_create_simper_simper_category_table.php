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
        Schema::create('simper_simper_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simper_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('simper_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('level', [
                'full',
                'partial',
                'test',
                'latihan',
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simper_simper_category');
    }
};
