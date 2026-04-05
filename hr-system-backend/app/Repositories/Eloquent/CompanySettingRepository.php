<?php

namespace App\Repositories\Eloquent;

use App\Models\CompanySetting;
use App\Repositories\Interfaces\CompanySettingRepositoryInterface;

class CompanySettingRepository implements CompanySettingRepositoryInterface
{
    public function __construct(protected CompanySetting $model)
    {
    }

    public function getByCompany(int $companyId): ?CompanySetting
    {
        return $this->model->where('company_id', $companyId)->first();
    }

    public function updateOrCreate(int $companyId, array $data): CompanySetting
    {
        return $this->model->updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }
}
