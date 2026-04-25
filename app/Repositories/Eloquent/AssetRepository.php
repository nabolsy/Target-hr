<?php

namespace App\Repositories\Eloquent;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AssetRepository extends BaseRepository implements AssetRepositoryInterface
{
    public function __construct(Asset $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    public function getAvailable(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->available()
            ->get();
    }

    public function getAssigned(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->assigned()
            ->with(['currentAssignment.employee'])
            ->get();
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model
            ->whereHas('currentAssignment', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->with(['currentAssignment'])
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = [
            'created_at', 'name', 'asset_code', 'category',
            'status', 'condition', 'purchase_date', 'purchase_cost',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function getByCategory(string $category, int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->byCategory($category)
            ->get();
    }
}
