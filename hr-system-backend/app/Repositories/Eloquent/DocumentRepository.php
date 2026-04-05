<?php

namespace App\Repositories\Eloquent;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\EmployeeDocument;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    public function __construct(EmployeeDocument $model)
    {
        parent::__construct($model);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->forEmployee($employeeId)->latest()->get();
    }

    public function getExpiring(int $days = 30): Collection
    {
        return $this->model
            ->where('status', DocumentStatus::Active)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();
    }

    public function getExpired(): Collection
    {
        return $this->model
            ->whereIn('status', [DocumentStatus::Active, DocumentStatus::Expiring])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('file_name', 'like', "%{$filters['search']}%")
                  ->orWhere('notes', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['expiry_from'])) {
            $query->where('expiry_date', '>=', $filters['expiry_from']);
        }

        if (! empty($filters['expiry_to'])) {
            $query->where('expiry_date', '<=', $filters['expiry_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function getByType(DocumentType $type): Collection
    {
        return $this->model->byType($type)->latest()->get();
    }
}
