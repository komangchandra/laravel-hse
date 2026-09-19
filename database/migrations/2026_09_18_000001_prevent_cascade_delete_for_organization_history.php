<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();
        });

        Schema::table('simpers', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });

        Schema::table('simpers', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });
    }
};
