<?php

namespace App\Repositories\Eloquent;

use App\Enums\OnboardingType;
use App\Models\OnboardingTemplate;
use App\Repositories\Interfaces\OnboardingTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OnboardingTemplateRepository extends BaseRepository implements OnboardingTemplateRepositoryInterface
{
    public function __construct(OnboardingTemplate $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->with('items')->get();
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->model->where('department_id', $departmentId)->with('items')->get();
    }

    public function getDefault(int $companyId, OnboardingType $type): ?OnboardingTemplate
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('is_default', true)
            ->with('items')
            ->first();
    }
}
