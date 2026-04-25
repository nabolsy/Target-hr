<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->foreignId('department_id')
                ->nullable()
                ->after('company_id')
                ->constrained('departments')
                ->nullOnDelete();
            // String grade keeps the user-visible enum stable while the
            // existing integer `level` column continues to drive sort
            // order. Designations created via the new form get both
            // populated in the controller via a static map.
            $table->string('grade', 32)->nullable()->after('level');
            $table->boolean('is_active')->default(true)->after('grade');

            $table->index('department_id');
            $table->index('grade');
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['grade']);
            $table->dropColumn(['name_ar', 'department_id', 'grade', 'is_active']);
        });
    }
};
