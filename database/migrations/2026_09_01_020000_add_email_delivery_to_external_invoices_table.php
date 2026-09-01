<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_invoices', function (Blueprint $table): void {
            $table->timestamp('email_claimed_at')->nullable()->after('issued_at');
            $table->timestamp('email_sent_at')->nullable()->after('email_claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('external_invoices', function (Blueprint $table): void {
            $table->dropColumn(['email_claimed_at', 'email_sent_at']);
        });
    }
};
