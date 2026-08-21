<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_singleton')) {
            Schema::table('users', function (Blueprint $table): void {
                // MariaDB permits multiple NULL values under a unique index.
                // The sole administrator is always stored as 1.
                $table->unsignedTinyInteger('admin_singleton')->nullable()->unique();
            });
        }

        DB::table('users')->where('role', 'admin')->update(['admin_singleton' => 1]);
        DB::table('users')->where('role', '!=', 'admin')->update(['admin_singleton' => null]);
    }

    public function down(): void
    {
        // Intentionally non-destructive: removing this guard could allow a
        // second administrator to be created inadvertently.
    }
};
