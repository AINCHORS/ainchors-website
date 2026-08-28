<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollments')) {
            return;
        }

        // A previous "completed" access status is not a learning-progress record.
        // Retain access if it has no passed expiry, otherwise mark it expired.
        DB::table('enrollments')
            ->where('status', 'completed')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);

        DB::table('enrollments')
            ->where('status', 'completed')
            ->update(['status' => 'active', 'updated_at' => now()]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `enrollments` MODIFY `status` ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        // The old completed status represented progress tracking, which is retired.
    }
};
