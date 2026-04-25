<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        Log::channel('audit')->info('Employee created', [
            'employee_id' => $employee->id,
            'employee_id_number' => $employee->employee_id_number,
            'name' => $employee->full_name,
            'company_id' => $employee->company_id,
            'user_id' => auth()->id(),
        ]);

        $this->syncPrimaryDepartmentPivot($employee);
        $this->provisionLeaveBalances($employee);
    }

    /**
     * Seed a LeaveBalance row for every active leave type in the
     * employee's company for the current year. Uses updateOrInsert so
     * it's safe to re-run (e.g. if an employee is restored).
     *
     * Only runs for the current calendar year — historical balances for
     * employees hired mid-year are not synthesized. Future years are
     * created on-demand by the leave request flow itself.
     */
    protected function provisionLeaveBalances(Employee $employee): void
    {
        if (! $employee->company_id) {
            return;
        }

        $year = (int) now()->year;

        $types = LeaveType::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get(['id', 'days_per_year']);

        foreach ($types as $type) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $employee->id,
                    'leave_type_id' => $type->id,
                    'year'          => $year,
                ],
                [
                    'company_id'     => $employee->company_id,
                    'total_days'     => (float) $type->days_per_year,
                    'used_days'      => 0,
                    'remaining_days' => (float) $type->days_per_year,
                ]
            );
        }
    }

    public function updated(Employee $employee): void
    {
        Log::channel('audit')->info('Employee updated', [
            'employee_id' => $employee->id,
            'employee_id_number' => $employee->employee_id_number,
            'changes' => $employee->getChanges(),
            'company_id' => $employee->company_id,
            'user_id' => auth()->id(),
        ]);

        // Only resync the pivot when the canonical department_id column
        // actually changed — otherwise this is a normal profile update.
        if (array_key_exists('department_id', $employee->getChanges())) {
            $this->syncPrimaryDepartmentPivot($employee);
        }
    }

    /**
     * Mirror employees.department_id into the employee_department pivot:
     *  - drop the previous primary row (if any),
     *  - upsert the current department as the new primary row.
     *
     * If department_id is null, we simply clear the primary flag from any
     * existing rows but do not delete historical memberships.
     */
    protected function syncPrimaryDepartmentPivot(Employee $employee): void
    {
        DB::table('employee_department')
            ->where('employee_id', $employee->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false, 'updated_at' => now()]);

        if (! $employee->department_id) {
            return;
        }

        $existing = DB::table('employee_department')
            ->where('employee_id', $employee->id)
            ->where('department_id', $employee->department_id)
            ->first();

        if ($existing) {
            DB::table('employee_department')
                ->where('id', $existing->id)
                ->update([
                    'is_primary' => true,
                    'end_date'   => null,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('employee_department')->insert([
            'employee_id'   => $employee->id,
            'department_id' => $employee->department_id,
            'is_primary'    => true,
            'start_date'    => $employee->join_date,
            'end_date'      => null,
            'role'          => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function deleted(Employee $employee): void
    {
        Log::channel('audit')->info('Employee deleted', [
            'employee_id' => $employee->id,
            'employee_id_number' => $employee->employee_id_number,
            'name' => $employee->full_name,
            'company_id' => $employee->company_id,
            'user_id' => auth()->id(),
        ]);
    }

    public function restored(Employee $employee): void
    {
        Log::channel('audit')->info('Employee restored', [
            'employee_id' => $employee->id,
            'employee_id_number' => $employee->employee_id_number,
            'name' => $employee->full_name,
            'company_id' => $employee->company_id,
            'user_id' => auth()->id(),
        ]);
    }

    public function forceDeleted(Employee $employee): void
    {
        Log::channel('audit')->info('Employee permanently deleted', [
            'employee_id' => $employee->id,
            'employee_id_number' => $employee->employee_id_number,
            'name' => $employee->full_name,
            'company_id' => $employee->company_id,
            'user_id' => auth()->id(),
        ]);
    }
}
