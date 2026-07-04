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
        Schema::create('exam_tokens', function (Blueprint $table) {

            $table->id();

            $table->foreignId('simper_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token')->unique();

            $table->timestamp('expired_at');

            $table->timestamp('used_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_tokens');
    }
};
