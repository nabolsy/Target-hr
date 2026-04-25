<?php

namespace App\Repositories\Eloquent;

use App\Models\SalaryStructure;
use App\Repositories\Interfaces\SalaryStructureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SalaryStructureRepository extends BaseRepository implements SalaryStructureRepositoryInterface
{
    public function __construct(SalaryStructure $model)
    {
        parent::__construct($model);
    }

    public function getByEmployee(int $employeeId): ?SalaryStructure
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->with('components')
            ->latest('effective_date')
            ->first();
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->with(['employee', 'components'])
            ->get();
    }
}
