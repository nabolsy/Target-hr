<?php

namespace App\Repositories\Interfaces;

use App\Enums\OnboardingType;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Collection;

interface OnboardingTemplateRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getByDepartment(int $departmentId): Collection;

    public function getDefault(int $companyId, OnboardingType $type): ?OnboardingTemplate;
}
