<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->string('recommendation');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['interview_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedback');
    }
};
