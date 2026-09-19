<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('partners')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('type', 32);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['owner_id', 'year', 'type'], 'application_number_sequence_unique');
        });

        Schema::create('permit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 64)->unique();
            $table->string('type', 32)->index();
            $table->foreignId('owner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('manpower_id')->constrained('manpowers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->unsignedBigInteger('version')->default(1);
            $table->unsignedInteger('submission_version')->default(0);
            $table->json('submitted_snapshot')->nullable();
            $table->json('issued_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
            $table->index(['partner_id', 'status']);
        });

        Schema::create('application_simper_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_application_id')->constrained()->restrictOnDelete();
            $table->foreignId('simper_category_id')->constrained()->restrictOnDelete();
            $table->string('level', 50);
            $table->timestamps();
            $table->unique(['permit_application_id', 'simper_category_id'], 'app_simper_category_unique');
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_application_id')->constrained()->restrictOnDelete();
            $table->foreignId('manpower_document_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('submission_version');
            $table->string('type', 64);
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('file_path');
            $table->char('checksum', 64);
            $table->unsignedInteger('source_version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['permit_application_id', 'submission_version', 'manpower_document_id'], 'app_document_submission_unique');
        });

        Schema::create('application_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_application_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('submission_version');
            $table->string('stage', 20)->index();
            $table->string('decision', 20);
            $table->text('notes')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_application_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 40);
            $table->string('to_status', 40);
            $table->string('action', 50);
            $table->string('actor_type', 20)->default('user');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('application_version');
            $table->timestamp('transitioned_at');
            $table->timestamps();
            $table->index(['permit_application_id', 'application_version'], 'app_history_version_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('application_reviews');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('application_simper_categories');
        Schema::dropIfExists('permit_applications');
        Schema::dropIfExists('application_number_sequences');
    }
};
