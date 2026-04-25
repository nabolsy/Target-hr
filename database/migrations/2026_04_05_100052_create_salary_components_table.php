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
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // allowance or deduction
            $table->decimal('amount', 12, 2);
            $table->boolean('is_percentage')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('salary_structure_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
