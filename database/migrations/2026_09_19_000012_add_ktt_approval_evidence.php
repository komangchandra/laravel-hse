<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_applications', function (Blueprint $table) {
            $table->timestamp('issuance_requested_at')->nullable()->after('approved_at');
        });

        Schema::table('application_reviews', function (Blueprint $table) {
            $table->json('reviewer_snapshot')->nullable()->after('reviewer_id');
            $table->text('approval_claim_token')->nullable()->after('reviewer_snapshot');
            $table->unique(
                ['permit_application_id', 'submission_version', 'stage'],
                'application_review_stage_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('application_reviews', function (Blueprint $table) {
            $table->dropUnique('application_review_stage_version_unique');
            $table->dropColumn(['reviewer_snapshot', 'approval_claim_token']);
        });

        Schema::table('permit_applications', function (Blueprint $table) {
            $table->dropColumn('issuance_requested_at');
        });
    }
};
