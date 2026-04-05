<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface LeaveBalanceRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployeeAndYear(int $employeeId, int $year): Collection;

    public function getByEmployeeTypeAndYear(int $employeeId, int $leaveTypeId, int $year): ?Model;
}
