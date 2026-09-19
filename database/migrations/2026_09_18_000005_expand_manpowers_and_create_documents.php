<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->foreignId('owner_id')->nullable()->after('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('position')->nullable()->after('name');
            $table->string('department')->nullable()->after('position');
            $table->string('birth_place')->nullable()->after('department');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('emergency_contact_name')->nullable()->after('contact_number');
            $table->string('emergency_contact_number', 20)->nullable()->after('emergency_contact_name');
            $table->boolean('is_active')->default(true)->after('photo_path')->index();
            $table->softDeletes();
            $table->unique(['owner_id', 'nik']);
        });

        DB::table('manpowers')->orderBy('id')->each(function ($manpower) {
            $ownerId = DB::table('partners')->where('id', $manpower->partner_id)->value('owner_id');
            DB::table('manpowers')->where('id', $manpower->id)->update(['owner_id' => $ownerId]);
        });

        Schema::create('manpower_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manpower_id')->constrained()->restrictOnDelete();
            $table->string('type', 64)->index();
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->char('checksum', 64);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_status', 20)->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['manpower_id', 'type', 'version']);
        });

        DB::table('manpowers')->whereNotNull('document_path')->orderBy('id')->each(function ($manpower) {
            DB::table('manpower_documents')->insert([
                'manpower_id' => $manpower->id,
                'type' => 'other',
                'file_path' => $manpower->document_path,
                'original_name' => 'dokumen-lama-'.$manpower->id.'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 0,
                'checksum' => hash('sha256', 'legacy:'.$manpower->id.':'.$manpower->document_path),
                'version' => 1,
                'verification_status' => 'pending',
                'created_at' => $manpower->created_at,
                'updated_at' => $manpower->updated_at,
            ]);
        });

        Schema::table('simpers', function (Blueprint $table) {
            $table->json('manpower_snapshot')->nullable()->after('manpower_id');
        });

        DB::table('simpers')->orderBy('id')->each(function ($simper) {
            $manpower = DB::table('manpowers')->where('id', $simper->manpower_id)->first();
            if (! $manpower) {
                return;
            }

            $partner = DB::table('partners')->where('id', $manpower->partner_id)->first();
            $owner = $manpower->owner_id ? DB::table('partners')->where('id', $manpower->owner_id)->first() : null;
            DB::table('simpers')->where('id', $simper->id)->update(['manpower_snapshot' => json_encode([
                'manpower_id' => $manpower->id,
                'nik' => $manpower->nik,
                'name' => $manpower->name,
                'partner_id' => $manpower->partner_id,
                'partner_name' => $partner?->legal_name,
                'owner_id' => $manpower->owner_id,
                'owner_name' => $owner?->legal_name,
                'position' => $manpower->position,
                'department' => $manpower->department,
                'birth_place' => $manpower->birth_place,
                'birth_date' => $manpower->birth_date,
                'blood_type' => $manpower->blood_type,
                'contact_number' => $manpower->contact_number,
                'emergency_contact_name' => $manpower->emergency_contact_name,
                'emergency_contact_number' => $manpower->emergency_contact_number,
                'photo_path' => $manpower->photo_path,
                'captured_at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR)]);
        });
    }

    public function down(): void
    {
        Schema::table('simpers', function (Blueprint $table) {
            $table->dropColumn('manpower_snapshot');
        });
        Schema::dropIfExists('manpower_documents');
        Schema::table('manpowers', function (Blueprint $table) {
            $table->dropUnique(['owner_id', 'nik']);
            $table->dropForeign(['owner_id']);
            $table->dropColumn([
                'owner_id', 'position', 'department', 'birth_place', 'birth_date',
                'emergency_contact_name', 'emergency_contact_number', 'is_active', 'deleted_at',
            ]);
            $table->unique('nik');
        });
    }
};
