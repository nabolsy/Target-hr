<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Concerns\AppliesAccessScope;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    use AppliesAccessScope;
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    public function getActiveByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->active()->get();
    }

    public function getRootDepartments(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->root()->get();
    }

    public function getSubDepartments(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Access scope: for Department itself, "visible department IDs" maps
        // to the table's own `id` column (not a foreign key). We pass the
        // `department` column override so the trait filters on `id`.
        $this->applyAccessScope($query, $filters, ['department' => 'departments.id']);

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null || $filters['parent_id'] === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->with(['parent', 'manager', 'branch'])->withCount('children', 'employees')->paginate($perPage);
    }

    /**
     * Return the full department hierarchy for a company as root nodes with
     * eager-loaded descendants (2 levels deep by default). Used by the
     * /departments/tree endpoint on the frontend.
     */
    public function getTree(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->whereNull('parent_id')
            ->with([
                'manager',
                'branch',
                'children.manager',
                'children.branch',
                'children.children.manager',
            ])
            ->withCount('employees')
            ->orderBy('name')
            ->get();
    }
}
