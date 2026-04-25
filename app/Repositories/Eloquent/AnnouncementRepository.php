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

    /**
     * Announcement scope filter is custom: a user sees any announcement
     * whose department_id is NULL (company-wide, visible to everyone) OR
     * whose department_id is in their visible-departments set.
     *
     * This differs from the generic AppliesAccessScope trait because
     * NULL means "visible to all" for this model, whereas the trait
     * treats NULL as "no filter".
     *
     * @param  array<int>|null  $visibleDepartmentIds  null → no restriction
     */
    protected function applyAnnouncementScope($query, ?array $visibleDepartmentIds): void
    {
        if ($visibleDepartmentIds === null) {
            return; // company scope
        }

        if (empty($visibleDepartmentIds)) {
            $query->whereNull('department_id'); // only company-wide
            return;
        }

        $query->where(function ($q) use ($visibleDepartmentIds) {
            $q->whereNull('department_id')
              ->orWhereIn('department_id', $visibleDepartmentIds);
        });
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

        // Access scope: NULL department_id = visible to everyone.
        if (array_key_exists('__visible_department_ids', $filters)) {
            $this->applyAnnouncementScope($query, $filters['__visible_department_ids']);
            unset($filters['__visible_department_ids']);
        }

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
