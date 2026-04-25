<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface GoalRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployee(int $employeeId): Collection;

    public function getByCycle(int $reviewCycleId): Collection;
}
