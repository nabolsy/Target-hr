<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Per-type half-day opt-in. Defaults to false so the new
            // column is strictly additive — existing leave types keep
            // behaving as full-day-only until an admin explicitly flips
            // the toggle in the UI.
            $table->boolean('allows_half_day')->default(false)->after('requires_attachment');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('allows_half_day');
        });
    }
};
