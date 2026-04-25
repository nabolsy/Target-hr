<?php

namespace App\Repositories\Eloquent;

use App\Models\PerformanceReview;
use App\Repositories\Interfaces\PerformanceReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PerformanceReviewRepository extends BaseRepository implements PerformanceReviewRepositoryInterface
{
    public function __construct(PerformanceReview $model)
    {
        parent::__construct($model);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->where('employee_id', $employeeId)
            ->with(['reviewCycle', 'reviewer', 'metrics'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByCycle(int $reviewCycleId): Collection
    {
        return $this->model->where('review_cycle_id', $reviewCycleId)
            ->with(['employee', 'reviewer', 'metrics'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['employee', 'reviewer', 'reviewCycle', 'metrics']);

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['review_cycle_id'])) {
            $query->where('review_cycle_id', $filters['review_cycle_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['reviewer_id'])) {
            $query->where('reviewer_id', $filters['reviewer_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = [
            'created_at', 'overall_score', 'submitted_at', 'acknowledged_at', 'status',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }
}
