<?php

namespace App\Observers;

use App\Models\Employee;
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
