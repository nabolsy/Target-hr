<?php

namespace App\Repositories\Eloquent;

use App\Models\Goal;
use App\Repositories\Interfaces\GoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GoalRepository extends BaseRepository implements GoalRepositoryInterface
{
    public function __construct(Goal $model)
    {
        parent::__construct($model);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->where('employee_id', $employeeId)
            ->with(['reviewCycle'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByCycle(int $reviewCycleId): Collection
    {
        return $this->model->where('review_cycle_id', $reviewCycleId)
            ->with(['employee'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
