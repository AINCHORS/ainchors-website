<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DATA_ANALYSIS_COURSE_SKUS = [
        'SL-DA-003',
        'SL-SQL-004',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'course_category')) {
            return;
        }

        DB::table('products')
            ->where('type', 'course')
            ->whereIn('sku', self::DATA_ANALYSIS_COURSE_SKUS)
            ->update(['course_category' => 'data_analysis']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'course_category')) {
            return;
        }

        DB::table('products')
            ->where('type', 'course')
            ->whereIn('sku', self::DATA_ANALYSIS_COURSE_SKUS)
            ->where('course_category', 'data_analysis')
            ->update(['course_category' => 'self_training']);
    }
};
