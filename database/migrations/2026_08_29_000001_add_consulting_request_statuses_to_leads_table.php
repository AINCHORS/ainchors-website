<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `leads` MODIFY `status` ENUM('new', 'contacted', 'qualified', 'consultation_requested', 'consultation_booked', 'proposal', 'won', 'lost', 'new_request', 'consultation_scheduled', 'closed') NOT NULL DEFAULT 'new'");
        }

        DB::table('leads')
            ->where('source', 'consulting_booking')
            ->whereIn('status', ['new', 'consultation_requested'])
            ->update(['status' => 'new_request']);

        DB::table('leads')
            ->where('source', 'consulting_booking')
            ->whereIn('status', ['qualified', 'proposal'])
            ->update(['status' => 'contacted']);

        DB::table('leads')
            ->where('source', 'consulting_booking')
            ->where('status', 'consultation_booked')
            ->update(['status' => 'consultation_scheduled']);

        DB::table('leads')
            ->where('source', 'consulting_booking')
            ->whereIn('status', ['won', 'lost'])
            ->update(['status' => 'closed']);
    }

    public function down(): void
    {
        // Lead status history is business data. Do not reverse mapped records.
    }
};
