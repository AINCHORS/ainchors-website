<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'first_name')) $table->string('first_name')->nullable()->after('full_name');
            if (! Schema::hasColumn('users', 'last_name')) $table->string('last_name')->nullable()->after('first_name');
            if (! Schema::hasColumn('users', 'date_of_birth')) $table->date('date_of_birth')->nullable()->after('last_name');
            if (! Schema::hasColumn('users', 'address_line_1')) $table->string('address_line_1')->nullable()->after('country');
            if (! Schema::hasColumn('users', 'address_line_2')) $table->string('address_line_2')->nullable()->after('address_line_1');
            if (! Schema::hasColumn('users', 'city')) $table->string('city', 120)->nullable()->after('address_line_2');
            if (! Schema::hasColumn('users', 'state')) $table->string('state', 120)->nullable()->after('city');
            if (! Schema::hasColumn('users', 'postal_code')) $table->string('postal_code', 30)->nullable()->after('state');
        });

        DB::table('users')->whereNull('first_name')->orderBy('id')->each(function (object $user): void {
            $parts = preg_split('/\s+/', trim((string) $user->full_name), 2);
            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0] ?? $user->full_name,
                'last_name' => $parts[1] ?? '',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'date_of_birth', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code']);
        });
    }
};
