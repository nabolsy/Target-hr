<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name_ar');
            $table->foreignId('branch_id')
                ->nullable()
                ->after('manager_id')
                ->constrained('company_branches')
                ->nullOnDelete();

            $table->unique(['company_id', 'code']);
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn(['code', 'branch_id']);
        });
    }
};
