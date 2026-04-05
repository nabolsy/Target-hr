<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->datetime('requested_check_in')->nullable();
            $table->datetime('requested_check_out')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustment_requests');
    }
};
