<?php

namespace App\Repositories\Eloquent;

use App\Models\LeaveBalance;
use App\Repositories\Interfaces\LeaveBalanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LeaveBalanceRepository extends BaseRepository implements LeaveBalanceRepositoryInterface
{
    public function __construct(LeaveBalance $model)
    {
        parent::__construct($model);
    }

    public function getByEmployeeAndYear(int $employeeId, int $year): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->with('leaveType')
            ->get();
    }

    public function getByEmployeeTypeAndYear(int $employeeId, int $leaveTypeId, int $year): ?Model
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }
}
