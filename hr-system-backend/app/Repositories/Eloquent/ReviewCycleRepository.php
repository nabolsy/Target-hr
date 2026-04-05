<?php

namespace App\Repositories\Eloquent;

use App\Enums\ReviewStatus;
use App\Models\ReviewCycle;
use App\Repositories\Interfaces\ReviewCycleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReviewCycleRepository extends BaseRepository implements ReviewCycleRepositoryInterface
{
    public function __construct(ReviewCycle $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->with(['creator'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getActive(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', ReviewStatus::Active)
            ->with(['creator'])
            ->orderBy('start_date', 'desc')
            ->get();
    }
}
