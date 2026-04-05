<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface ReviewCycleRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getActive(int $companyId): Collection;
}
