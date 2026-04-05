<?php

namespace App\Repositories\Eloquent;

use App\Models\OnboardingChecklist;
use App\Repositories\Interfaces\OnboardingChecklistRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OnboardingChecklistRepository extends BaseRepository implements OnboardingChecklistRepositoryInterface
{
    public function __construct(OnboardingChecklist $model)
    {
        parent::__construct($model);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->with(['items', 'template', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPending(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->pending()
            ->with(['employee', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['employee', 'items', 'template', 'creator']);

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = ['created_at', 'started_at', 'completed_at', 'status', 'type'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }
}
