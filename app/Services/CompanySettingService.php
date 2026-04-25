<?php

namespace App\Services;

use App\DTOs\CompanySettingDTO;
use App\Models\CompanySetting;
use App\Repositories\Interfaces\CompanySettingRepositoryInterface;
use Carbon\Carbon;

class CompanySettingService
{
    public function __construct(
        protected CompanySettingRepositoryInterface $settingRepository,
    ) {
    }

    public function get(int $companyId): ?CompanySetting
    {
        return $this->settingRepository->getByCompany($companyId);
    }

    public function update(int $companyId, CompanySettingDTO $dto): CompanySetting
    {
        $data = $dto->toArray();

        return $this->settingRepository->updateOrCreate($companyId, $data);
    }

    public function getWorkDays(int $companyId): array
    {
        $setting = $this->settingRepository->getByCompany($companyId);

        if (! $setting || ! $setting->work_days) {
            // Default: Monday to Friday
            return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }

        return $setting->work_days;
    }

    public function isWorkDay(int $companyId, Carbon|string $date): bool
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $workDays = $this->getWorkDays($companyId);
        $dayName = strtolower($date->format('l'));

        return in_array($dayName, $workDays);
    }
}
