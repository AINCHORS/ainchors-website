<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('value', 255);
                $table->timestamps();
            });
        }

        // The existing MariaDB schema already contains leads. This guarded
        // definition keeps a fresh Laravel installation able to store genuine
        // contact submissions without replacing or altering existing CRM data.
        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('visitor_id')->nullable()->index();
                $table->unsignedBigInteger('workflow_audit_id')->nullable()->index();
                $table->enum('source', ['contact', 'workflow_audit', 'course', 'ai_assistant', 'other'])->default('other')->index();
                $table->string('full_name');
                $table->string('email')->index();
                $table->string('phone', 50)->nullable();
                $table->string('company_name')->nullable();
                $table->enum('status', ['new', 'contacted', 'qualified', 'consultation_requested', 'consultation_booked', 'proposal', 'won', 'lost'])->default('new')->index();
                $table->unsignedBigInteger('assigned_admin_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: CRM leads may be business records.
    }
};
