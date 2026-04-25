<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_branches', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->foreignId('manager_id')
                ->nullable()
                ->after('email')
                ->constrained('employees')
                ->nullOnDelete();

            $table->index('manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('company_branches', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropIndex(['manager_id']);
            $table->dropColumn(['name_ar', 'manager_id']);
        });
    }
};
