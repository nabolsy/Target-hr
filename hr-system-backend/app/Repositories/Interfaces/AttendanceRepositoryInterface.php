<?php

namespace App\Repositories\Interfaces;

use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmployeeAndDate(int $employeeId, string $date): ?AttendanceRecord;

    public function getMonthlyRecords(int $employeeId, int $month, int $year): Collection;

    public function getTodayRecords(int $companyId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}
