<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate();
            $table->string('provider', 50);
            $table->string('external_reference', 191);
            $table->string('invoice_number', 191)->nullable();
            $table->string('invoice_url', 2048);
            $table->string('status', 50)->default('issued');
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_reference'], 'uq_external_invoice_provider_reference');
            $table->index(['order_id', 'status'], 'idx_external_invoice_order_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_invoices');
    }
};
