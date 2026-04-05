<?php

namespace App\Repositories\Eloquent;

use App\Models\Announcement;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    public function __construct(Announcement $model)
    {
        parent::__construct($model);
    }

    public function getPublished(): Collection
    {
        return $this->model->query()
            ->published()
            ->active()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->with(['creator', 'department'])
            ->get();
    }

    public function getForEmployee(int $employeeId, ?int $departmentId): Collection
    {
        return $this->model->query()
            ->published()
            ->active()
            ->forDepartment($departmentId)
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->with(['creator', 'department'])
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_pinned'])) {
            $query->where('is_pinned', filter_var($filters['is_pinned'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['status'])) {
            match ($filters['status']) {
                'published' => $query->published(),
                'active' => $query->published()->active(),
                'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                'draft' => $query->whereNull('published_at'),
                default => null,
            };
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('body', 'like', "%{$filters['search']}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->with(['creator', 'department'])
            ->withCount('reads')
            ->paginate($perPage);
    }

    public function getUnreadCount(int $userId, ?int $departmentId = null): int
    {
        return $this->model->query()
            ->published()
            ->active()
            ->forDepartment($departmentId)
            ->whereDoesntHave('reads', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();
    }
}
