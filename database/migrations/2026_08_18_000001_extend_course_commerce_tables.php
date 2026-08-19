<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_contents') && ! Schema::hasColumn('course_contents', 'lesson_content')) {
            Schema::table('course_contents', function (Blueprint $table) {
                $table->json('lesson_content')->nullable()->after('slide_url');
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('order_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            });
        }

        if (Schema::hasTable('course_contents') && Schema::hasColumn('course_contents', 'lesson_content')) {
            Schema::table('course_contents', function (Blueprint $table) {
                $table->dropColumn('lesson_content');
            });
        }
    }
};
