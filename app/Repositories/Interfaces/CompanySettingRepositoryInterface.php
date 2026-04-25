<?php

namespace App\Repositories\Interfaces;

use App\Models\CompanySetting;

interface CompanySettingRepositoryInterface
{
    public function getByCompany(int $companyId): ?CompanySetting;

    public function updateOrCreate(int $companyId, array $data): CompanySetting;
}
