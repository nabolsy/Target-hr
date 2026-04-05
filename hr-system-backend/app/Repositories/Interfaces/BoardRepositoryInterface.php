<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BoardRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getByDepartment(int $departmentId): Collection;

    public function getWithColumns(int $boardId): Model;
}
