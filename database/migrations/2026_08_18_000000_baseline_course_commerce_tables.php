<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['course', 'course_package', 'consulting', 'service']);
                $table->string('sku', 100)->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('short_description')->nullable();
                $table->mediumText('description')->nullable();
                $table->string('image', 500)->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->char('currency', 3)->default('USD');
                $table->enum('billing_type', ['one_time', 'monthly', 'custom'])->default('one_time');
                $table->enum('status', ['draft', 'active', 'inactive'])->default('active');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index('type');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('product_relations')) {
            Schema::create('product_relations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('child_product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
                $table->enum('relation_type', ['bundle_item', 'related'])->default('bundle_item');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['parent_product_id', 'child_product_id', 'relation_type'], 'uq_product_relation');
            });
        }

        if (! Schema::hasTable('course_contents')) {
            Schema::create('course_contents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('video_title')->nullable();
                $table->string('video_provider', 100)->nullable();
                $table->string('video_url', 1000);
                $table->string('video_original_name')->nullable();
                $table->unsignedBigInteger('video_file_size')->nullable();
                $table->unsignedInteger('video_duration_seconds')->nullable();
                $table->string('slide_name')->nullable();
                $table->string('slide_url', 1000)->nullable();
                $table->string('slide_original_name')->nullable();
                $table->unsignedBigInteger('slide_file_size')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number', 100)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate();
                $table->enum('status', ['pending', 'awaiting_payment', 'paid', 'completed', 'cancelled', 'refunded'])->default('pending');
                $table->char('currency', 3);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount_total', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->dateTime('placed_at')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate();
                $table->string('product_name');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('line_total', 12, 2);
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate();
                $table->string('provider', 100);
                $table->string('provider_transaction_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->char('currency', 3);
                $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'refunded'])->default('pending');
                $table->dateTime('paid_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->json('provider_data')->nullable();
                $table->timestamps();
                $table->unique(['provider', 'provider_transaction_id'], 'uq_payment_provider_transaction');
            });
        }

        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate();
                $table->foreignId('source_order_item_id')->nullable()->constrained('order_items')->cascadeOnUpdate()->nullOnDelete();
                $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
                $table->decimal('progress_percent', 5, 2)->default(0);
                $table->dateTime('enrolled_at');
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'product_id'], 'uq_enrollments_user_product');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        // The production schema predates Laravel migration tracking. Dropping
        // these tables during rollback could destroy established data.
    }
};
