<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreignId('permit_application_id')->nullable()->after('owner_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->after('permit_application_id')->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_start_at')->nullable()->after('description');
            $table->timestamp('scheduled_end_at')->nullable()->after('scheduled_start_at');
            $table->unsignedTinyInteger('max_attempts')->default(2)->after('passing_score');
            $table->string('status', 24)->default('draft')->after('max_attempts');
            $table->timestamp('activated_at')->nullable()->after('is_active');
            $table->unique('permit_application_id', 'exam_session_application_unique');
            $table->index(['owner_id', 'status'], 'exam_session_owner_status_index');
        });

        Schema::create('exam_session_blueprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simper_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_category_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('question_count');
            $table->timestamps();
            $table->unique(
                ['exam_session_id', 'simper_category_id', 'question_category_id'],
                'exam_blueprint_mapping_unique'
            );
        });

        Schema::table('exam_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('simper_id')->nullable()->change();
            $table->foreignId('permit_application_id')->nullable()->after('simper_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_session_id')->nullable()->after('permit_application_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->after('exam_session_id')->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('used_at');
            $table->index(['exam_session_id', 'used_at', 'revoked_at'], 'exam_token_session_state_index');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedBigInteger('simper_id')->nullable()->change();
            $table->foreignId('permit_application_id')->nullable()->after('simper_id')->constrained()->restrictOnDelete();
            $table->index(['permit_application_id', 'status'], 'exam_attempt_application_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('exam_attempt_application_status_index');
            $table->dropConstrainedForeignId('permit_application_id');
            $table->unsignedBigInteger('simper_id')->nullable(false)->change();
        });
        Schema::table('exam_tokens', function (Blueprint $table) {
            $table->dropIndex('exam_token_session_state_index');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('exam_session_id');
            $table->dropConstrainedForeignId('permit_application_id');
            $table->dropColumn('revoked_at');
            $table->unsignedBigInteger('simper_id')->nullable(false)->change();
        });
        Schema::dropIfExists('exam_session_blueprints');
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex('exam_session_owner_status_index');
            $table->dropUnique('exam_session_application_unique');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('permit_application_id');
            $table->dropColumn(['scheduled_start_at', 'scheduled_end_at', 'max_attempts', 'status', 'activated_at']);
        });
    }
};
