<?php

namespace App\Services;

use App\DTOs\AnnouncementDTO;
use App\Events\AnnouncementPosted;
use App\Exceptions\BusinessException;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnnouncementService extends BaseService
{
    public function __construct(
        protected AnnouncementRepositoryInterface $announcementRepository,
    ) {
        parent::__construct($announcementRepository);
    }

    public function create(AnnouncementDTO $dto): Announcement
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();

            if (! isset($data['created_by'])) {
                $data['created_by'] = auth()->id();
            }

            if (! isset($data['type'])) {
                $data['type'] = 'general';
            }

            /** @var Announcement $announcement */
            $announcement = $this->announcementRepository->create($data);

            if ($announcement->is_published) {
                event(new AnnouncementPosted($announcement));
            }

            return $announcement->load(['creator', 'department']);
        });
    }

    public function update(int $id, AnnouncementDTO $dto): Announcement
    {
        return DB::transaction(function () use ($id, $dto) {
            $announcement = $this->announcementRepository->findOrFail($id);

            $data = $dto->toArray();
            unset($data['created_by'], $data['company_id']);

            /** @var Announcement $updated */
            $updated = $this->announcementRepository->update($id, $data);

            return $updated->load(['creator', 'department']);
        });
    }

    public function publish(int $id): Announcement
    {
        /** @var Announcement $announcement */
        $announcement = $this->announcementRepository->findOrFail($id);

        if ($announcement->is_published) {
            throw new BusinessException('Announcement is already published.');
        }

        /** @var Announcement $updated */
        $updated = $this->announcementRepository->update($id, [
            'published_at' => now(),
        ]);

        event(new AnnouncementPosted($updated));

        return $updated->load(['creator', 'department']);
    }

    public function markAsRead(int $announcementId, int $userId): AnnouncementRead
    {
        $this->announcementRepository->findOrFail($announcementId);

        return AnnouncementRead::firstOrCreate(
            [
                'announcement_id' => $announcementId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    public function acknowledge(int $announcementId, int $userId): AnnouncementRead
    {
        /** @var Announcement $announcement */
        $announcement = $this->announcementRepository->findOrFail($announcementId);

        if (! $announcement->requires_acknowledgement) {
            throw new BusinessException('This announcement does not require acknowledgement.');
        }

        $read = AnnouncementRead::firstOrCreate(
            [
                'announcement_id' => $announcementId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ]
        );

        if ($read->acknowledged_at) {
            throw new BusinessException('You have already acknowledged this announcement.');
        }

        $read->update(['acknowledged_at' => now()]);

        return $read->fresh();
    }

    public function getForCurrentUser(int $userId, ?int $departmentId, int $perPage = 15): LengthAwarePaginator
    {
        $filters = [
            'status' => 'active',
        ];

        if ($departmentId) {
            $filters['department_id'] = $departmentId;
        }

        return $this->announcementRepository->paginateWithFilters($filters, $perPage);
    }

    public function getUnreadCount(int $userId, ?int $departmentId = null): int
    {
        return $this->announcementRepository->getUnreadCount($userId, $departmentId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->announcementRepository->paginateWithFilters($filters, $perPage);
    }
}
