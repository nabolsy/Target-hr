<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('review_cycle_id')->nullable()->constrained('review_cycles')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('target_value')->nullable();
            $table->string('current_value')->nullable();
            $table->string('unit')->nullable();
            $table->string('status')->default('not_started'); // not_started, in_progress, completed, cancelled
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['review_cycle_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
