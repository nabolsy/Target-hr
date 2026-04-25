<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('level')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
