<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_positions')) {
            Schema::create('job_positions', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 120)->unique();
                $table->string('slug', 140)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('job_position_id')->constrained()->restrictOnDelete();
                $table->string('full_name');
                $table->string('email')->index();
                $table->string('phone', 50)->index();
                $table->date('interview_available_on')->nullable()->index();
                $table->text('interview_availability_notes')->nullable();
                $table->string('resume_disk', 80)->default('job-applications');
                $table->string('resume_path', 500);
                $table->string('resume_original_name', 255);
                $table->string('resume_mime', 120);
                $table->unsignedBigInteger('resume_size');
                $table->text('short_note')->nullable();
                $table->timestamp('recruitment_consent_at');
                $table->enum('status', ['new', 'reviewing', 'shortlisted', 'rejected'])->default('new')->index();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('status_changed_at')->nullable();
                $table->text('internal_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_application_status_histories')) {
            Schema::create('job_application_status_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
                $table->enum('status', ['new', 'reviewing', 'shortlisted', 'rejected']);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: recruitment records and resumes are
        // business records. Removal must be an explicit retention decision.
    }
};
