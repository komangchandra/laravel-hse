<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('partners')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('kind', 32);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['owner_id', 'year', 'kind'], 'permit_number_sequence_unique');
        });

        Schema::create('permit_issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('manpower_id')->constrained('manpowers')->restrictOnDelete();
            $table->foreignId('approval_review_id')->constrained('application_reviews')->restrictOnDelete();
            $table->foreignId('replaces_issuance_id')->nullable()->constrained('permit_issuances')->restrictOnDelete();
            $table->string('type', 32);
            $table->string('mine_permit_number', 80)->unique();
            $table->string('simpol_number', 80)->nullable()->unique();
            $table->uuid('public_id')->unique();
            $table->string('status', 24)->default('active')->index();
            $table->string('violation_indicator', 16)->default('green');
            $table->json('print_snapshot');
            $table->timestamp('issued_at');
            $table->date('valid_from');
            $table->date('expires_at')->index();
            $table->date('simper_expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
            $table->index(['manpower_id', 'status']);
        });

        Schema::create('permit_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_issuance_id')->constrained()->restrictOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 24);
            $table->json('metadata')->nullable();
            $table->timestamp('printed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_print_logs');
        Schema::dropIfExists('permit_issuances');
        Schema::dropIfExists('permit_number_sequences');
    }
};
