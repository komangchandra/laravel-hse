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
        Schema::create('simpers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->onDelete('cascade');
            $table->foreignId('manpower_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique(); //nomor HSE

            $table->string('driving_license_number')->nullable(); //Nomor SIM
            $table->string('license_class')->nullable(); //Golongan SIM 
            $table->date('valid_from')->nullable(); 
            $table->date('valid_until')->nullable(); 
            $table->string('status')->nullable();  //Pengajuan, Peninjauan, Aktif, Tidak Aktif, Blokir
            $table->string('violations')->nullable(); //Pelanggaran Teguran Lisan, Teguran Tertulis, SP1, SP2, SP3
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpers');
    }
};
