<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_review_id')->constrained('performance_reviews')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->decimal('score', 3, 2)->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['performance_review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_metrics');
    }
};
