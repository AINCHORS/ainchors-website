<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'course_category')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('course_category', 80)->nullable()->after('type')->index();
            });
        }

        $categories = [
            'self_training' => [
                'AI Prompt Engineering 101',
                'Digital Marketing using AI',
                'Data Analytics',
                'SQL for Data Analytics',
            ],
            'digital_money_mastery' => [
                'Financial Literacy Mastery',
                'E-Payment Fundamentals',
                'Fintech Fundamentals',
                'Central Bank Digital Currency (CBDC)',
            ],
            'career_advancement' => [
                "Becoming Your Supervisor's Advisor",
                'Influencing with Data & KPIs',
            ],
        ];

        foreach ($categories as $category => $courseNames) {
            DB::table('products')
                ->where('type', 'course')
                ->whereIn('name', $courseNames)
                ->update(['course_category' => $category]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'course_category')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('course_category');
            });
        }
    }
};
