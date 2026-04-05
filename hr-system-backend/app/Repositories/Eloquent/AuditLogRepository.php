<?php

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogRepository extends BaseRepository implements AuditLogRepositoryInterface
{
    public function __construct(AuditLog $model)
    {
        parent::__construct($model);
    }

    public function getByModel(string $type, int $id): Collection
    {
        return $this->model
            ->forModel($type, $id)
            ->recent()
            ->with('user')
            ->get();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model
            ->byUser($userId)
            ->recent()
            ->with('user')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('user');

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (! empty($filters['auditable_id'])) {
            $query->where('auditable_id', $filters['auditable_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        return $query->recent()->paginate($perPage);
    }

    public function getRecent(int $companyId, int $limit = 50): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->recent()
            ->with('user')
            ->limit($limit)
            ->get();
    }
}
