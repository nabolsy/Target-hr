<?php

namespace App\Repositories\Eloquent;

use App\Models\Shift;
use App\Repositories\Interfaces\ShiftRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ShiftRepository extends BaseRepository implements ShiftRepositoryInterface
{
    public function __construct(Shift $model)
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

    public function getDefault(int $companyId): ?Shift
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->first();
    }
}
