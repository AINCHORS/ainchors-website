<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'country')) {
            Schema::table('leads', fn (Blueprint $table) => $table->string('country', 100)->nullable()->after('phone'));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'country')) {
            Schema::table('leads', fn (Blueprint $table) => $table->dropColumn('country'));
        }
    }
};
