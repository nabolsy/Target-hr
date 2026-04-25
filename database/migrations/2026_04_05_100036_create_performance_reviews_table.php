<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('review_cycle_id')->constrained('review_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // manager_review, self_review
            $table->decimal('overall_score', 3, 2)->nullable();
            $table->string('rating')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, submitted, acknowledged
            $table->text('manager_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->text('goals_for_next_period')->nullable();
            $table->text('development_plan')->nullable();
            $table->boolean('promotion_recommendation')->default(false);
            $table->datetime('submitted_at')->nullable();
            $table->datetime('acknowledged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'review_cycle_id']);
            $table->index(['reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
