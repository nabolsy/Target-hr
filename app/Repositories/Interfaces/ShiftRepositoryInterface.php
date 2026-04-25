<?php

namespace App\Repositories\Interfaces;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;

interface ShiftRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getDefault(int $companyId): ?Shift;
}
