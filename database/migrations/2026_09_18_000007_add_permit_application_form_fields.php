<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('partners')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['owner_id', 'code']);
        });

        Schema::create('application_access_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_application_id')->constrained()->restrictOnDelete();
            $table->foreignId('access_area_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['permit_application_id', 'access_area_id'], 'app_access_area_unique');
        });

        Schema::table('permit_applications', function (Blueprint $table) {
            $table->string('site_name')->nullable()->after('manpower_id');
            $table->string('assignment_position')->nullable()->after('site_name');
            $table->string('assignment_department')->nullable()->after('assignment_position');
            $table->date('planned_start_date')->nullable()->after('assignment_department');
            $table->date('requested_valid_until')->nullable()->after('planned_start_date');
            $table->text('applicant_notes')->nullable()->after('requested_valid_until');
            $table->timestamp('truth_declared_at')->nullable()->after('applicant_notes');
            $table->timestamp('processing_consented_at')->nullable()->after('truth_declared_at');
        });

        Schema::table('application_simper_categories', function (Blueprint $table) {
            $table->text('restrictions')->nullable()->after('level');
            $table->string('supervisor_name')->nullable()->after('restrictions');
            $table->date('activity_start_date')->nullable()->after('supervisor_name');
            $table->date('activity_end_date')->nullable()->after('activity_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('application_simper_categories', function (Blueprint $table) {
            $table->dropColumn(['restrictions', 'supervisor_name', 'activity_start_date', 'activity_end_date']);
        });
        Schema::table('permit_applications', function (Blueprint $table) {
            $table->dropColumn([
                'site_name', 'assignment_position', 'assignment_department', 'planned_start_date',
                'requested_valid_until', 'applicant_notes', 'truth_declared_at', 'processing_consented_at',
            ]);
        });
        Schema::dropIfExists('application_access_areas');
        Schema::dropIfExists('access_areas');
    }
};
