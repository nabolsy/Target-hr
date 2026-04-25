<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->integer('wip_limit')->nullable();
            $table->boolean('is_done_column')->default(false);
            $table->timestamps();

            $table->index(['board_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_columns');
    }
};
