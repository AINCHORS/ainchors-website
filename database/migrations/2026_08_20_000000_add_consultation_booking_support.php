<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultation_requests')) {
            Schema::create('consultation_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->unsignedBigInteger('workflow_audit_id')->nullable()->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['requested', 'booked', 'completed', 'cancelled', 'no_show'])->default('requested')->index();
                $table->dateTime('requested_at');
                $table->dateTime('scheduled_at')->nullable()->index();
                $table->string('source_page', 120)->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('consultation_requests', 'source_page')) {
            Schema::table('consultation_requests', function (Blueprint $table): void {
                $table->string('source_page', 120)->nullable()->index()->after('scheduled_at');
            });
        }

        // MariaDB's existing schema uses an enum for lead source. SQLite test
        // databases represent enums as strings, so no engine-specific change is
        // required there.
        if (Schema::hasTable('leads') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `leads` MODIFY `source` ENUM('contact', 'workflow_audit', 'course', 'ai_assistant', 'consulting_booking', 'other') NOT NULL DEFAULT 'other'");
        }
    }

    public function down(): void
    {
        // Booking requests and lead-source values are business records. This
        // migration intentionally does not delete or narrow stored data.
    }
};
