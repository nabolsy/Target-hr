<?php

namespace App\Repositories\Eloquent;

use App\Models\LeaveType;
use App\Repositories\Interfaces\LeaveTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(LeaveType $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    public function getActiveByCompany(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();
    }
}
