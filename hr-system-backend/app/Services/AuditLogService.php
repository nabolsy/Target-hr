<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogService
{
    public function __construct(
        private AuditLogRepositoryInterface $repository
    ) {
    }

    public function log(Model $model, string $action, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        return AuditLog::create([
            'company_id' => $model->company_id ?? null,
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getByModel(string $type, int $id): Collection
    {
        return $this->repository->getByModel($type, $id);
    }

    public function getByUser(int $userId): Collection
    {
        return $this->repository->getByUser($userId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $perPage);
    }

    public function getRecent(int $companyId, int $limit = 50): Collection
    {
        return $this->repository->getRecent($companyId, $limit);
    }
}
