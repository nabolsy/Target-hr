<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Kept as a nullable string rather than an enum so we can
            // evolve the allowed values (e.g. a future 'third_quarter')
            // without a schema migration.
            $table->string('duration_type', 20)->nullable()->default('full')->after('is_half_day');
        });

        // Backfill: anything flagged as is_half_day gets 'first_half' by
        // default; everything else is 'full'. We can't distinguish
        // first vs second half retroactively, so first is the safe
        // default that keeps old requests valid under the new enum.
        DB::table('leave_requests')
            ->where('is_half_day', true)
            ->whereNull('duration_type')
            ->update(['duration_type' => 'first_half']);

        DB::table('leave_requests')
            ->whereNull('duration_type')
            ->update(['duration_type' => 'full']);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('duration_type');
        });
    }
};
