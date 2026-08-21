<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the append-only audit store only when it is not already present.
     * This keeps the migration additive for the established MariaDB database.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_audit_logs')) {
            return;
        }

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('entity_type', 191)->index();
            $table->string('entity_id', 191)->nullable()->index();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Audit history is business and security evidence, so rollback must not
     * erase it from an existing database.
     */
    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
