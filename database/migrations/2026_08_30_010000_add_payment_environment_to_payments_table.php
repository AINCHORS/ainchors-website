<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'payment_environment')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('payment_environment', 20)->default('unknown')->after('provider');
            $table->index(['status', 'payment_environment', 'currency'], 'idx_payments_live_analytics');
        });

        DB::table('payments')->where('provider', 'demo')->update(['payment_environment' => 'test']);
        DB::table('payments')->where('provider_transaction_id', 'like', '%\_test\_%')->update(['payment_environment' => 'test']);
        DB::table('payments')->where('provider_transaction_id', 'like', '%\_live\_%')->update(['payment_environment' => 'live']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_environment')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('idx_payments_live_analytics');
            $table->dropColumn('payment_environment');
        });
    }
};
