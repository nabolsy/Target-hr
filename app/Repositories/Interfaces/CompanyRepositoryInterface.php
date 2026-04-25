<?php

namespace App\Repositories\Interfaces;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveCompanies(): Collection;

    public function findByEmail(string $email): ?\App\Models\Company;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function updateStatus(int $id, CompanyStatus $status): \App\Models\Company;
}
