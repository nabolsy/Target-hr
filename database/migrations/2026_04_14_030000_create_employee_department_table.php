<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the employee_department pivot table.
 *
 * Design intent: `employees.department_id` remains the canonical "primary
 * department pointer" for performance and backwards compatibility — every
 * existing query, eager-load, and policy continues to read it directly.
 *
 * The pivot is additive. It exists so that:
 *   1. We can reason about historical department assignments
 *      (start_date / end_date), and
 *   2. Future multi-department membership can be enabled without a schema
 *      rewrite — just by writing additional rows with is_primary = false.
 *
 * The EmployeeObserver keeps the primary pivot row in sync whenever
 * `department_id` changes on the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('role', 50)->nullable(); // e.g. "Head", "Member", "Lead"
            $table->timestamps();

            $table->unique(['employee_id', 'department_id']);
            $table->index(['department_id', 'is_primary']);
            $table->index(['employee_id', 'is_primary']);
        });

        // Backfill: every existing employee with a department_id gets a
        // primary pivot row. start_date defaults to the employee's join_date
        // when present so historical context is preserved.
        $now = now()->toDateTimeString();

        DB::statement("
            INSERT INTO employee_department
                (employee_id, department_id, is_primary, start_date, end_date, role, created_at, updated_at)
            SELECT
                id, department_id, 1, join_date, NULL, NULL, ?, ?
            FROM employees
            WHERE department_id IS NOT NULL
              AND deleted_at IS NULL
        ", [$now, $now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_department');
    }
};
