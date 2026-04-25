<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->json('work_days')->nullable();
            $table->string('timezone', 100)->default('UTC');
            $table->string('date_format', 50)->default('Y-m-d');
            $table->string('currency', 10)->default('USD');
            $table->integer('grace_period_minutes')->default(0);
            $table->boolean('allow_remote_checkin')->default(false);
            $table->integer('leave_approval_levels')->default(1);
            $table->integer('probation_period_days')->default(90);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
