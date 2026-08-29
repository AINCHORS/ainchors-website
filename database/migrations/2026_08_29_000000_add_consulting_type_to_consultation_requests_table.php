<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultation_requests') && ! Schema::hasColumn('consultation_requests', 'consulting_type')) {
            Schema::table('consultation_requests', function (Blueprint $table): void {
                $table->string('consulting_type', 20)->nullable()->index()->after('source_page');
            });
        }
    }

    public function down(): void
    {
        // Consultation records are business data. Keep the field and its values.
    }
};
