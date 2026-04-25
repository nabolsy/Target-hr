<?php

namespace App\Repositories\Eloquent;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function getActiveCompanies(): Collection
    {
        return $this->model->active()->get();
    }

    public function findByEmail(string $email): ?Company
    {
        return $this->model->where('email', $email)->first();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['subscription_plan'])) {
            $query->where('subscription_plan', $filters['subscription_plan']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('industry', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function updateStatus(int $id, CompanyStatus $status): Company
    {
        $company = $this->findOrFail($id);
        $company->update(['status' => $status]);

        return $company->fresh();
    }
}
