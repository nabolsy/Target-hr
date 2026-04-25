<?php

namespace App\Repositories\Eloquent;

use App\Enums\JobOpeningStatus;
use App\Models\JobOpening;
use App\Repositories\Interfaces\JobOpeningRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class JobOpeningRepository extends BaseRepository implements JobOpeningRepositoryInterface
{
    public function __construct(JobOpening $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->with(['department', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOpen(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', JobOpeningStatus::Open)
            ->with(['department', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['department', 'creator']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = ['created_at', 'title', 'published_at', 'closes_at', 'positions_count'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }
}
