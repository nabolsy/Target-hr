<?php

namespace App\Repositories\Eloquent;

use App\Models\CompanyBranch;
use App\Repositories\Interfaces\CompanyBranchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompanyBranchRepository extends BaseRepository implements CompanyBranchRepositoryInterface
{
    public function __construct(CompanyBranch $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    public function getActive(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->active()
            ->get();
    }
}
