<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_contents')) {
            return;
        }

        Schema::table('course_contents', function (Blueprint $table): void {
            if (! Schema::hasColumn('course_contents', 'video_original_name')) {
                $table->string('video_original_name')->nullable()->after('video_url');
            }
            if (! Schema::hasColumn('course_contents', 'video_file_size')) {
                $table->unsignedBigInteger('video_file_size')->nullable()->after('video_original_name');
            }
            if (! Schema::hasColumn('course_contents', 'slide_original_name')) {
                $table->string('slide_original_name')->nullable()->after('slide_url');
            }
            if (! Schema::hasColumn('course_contents', 'slide_file_size')) {
                $table->unsignedBigInteger('slide_file_size')->nullable()->after('slide_original_name');
            }
        });
    }

    public function down(): void
    {
        // These metadata fields are non-destructive and retained for auditability.
    }
};
