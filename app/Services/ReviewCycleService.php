<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\BusinessException;
use App\Models\ReviewCycle;
use App\Repositories\Interfaces\ReviewCycleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReviewCycleService extends BaseService
{
    public function __construct(
        protected ReviewCycleRepositoryInterface $cycleRepository,
    ) {
        parent::__construct($cycleRepository);
    }

    public function createCycle(array $data): ReviewCycle
    {
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['status'] = ReviewStatus::Draft->value;

        return $this->cycleRepository->create($data);
    }

    public function updateCycle(int $id, array $data): ReviewCycle
    {
        $cycle = $this->cycleRepository->findOrFail($id);

        if ($cycle->status === ReviewStatus::Completed) {
            throw new BusinessException('Cannot update a completed review cycle.');
        }

        return $this->cycleRepository->update($id, $data);
    }

    public function activate(int $id): ReviewCycle
    {
        $cycle = $this->cycleRepository->findOrFail($id);

        if ($cycle->status === ReviewStatus::Active) {
            throw new BusinessException('This review cycle is already active.');
        }

        if ($cycle->status === ReviewStatus::Completed) {
            throw new BusinessException('Cannot activate a completed review cycle.');
        }

        return $this->cycleRepository->update($id, [
            'status' => ReviewStatus::Active->value,
        ]);
    }

    public function complete(int $id): ReviewCycle
    {
        $cycle = $this->cycleRepository->findOrFail($id);

        if ($cycle->status === ReviewStatus::Completed) {
            throw new BusinessException('This review cycle is already completed.');
        }

        if ($cycle->status !== ReviewStatus::Active) {
            throw new BusinessException('Only active review cycles can be completed.');
        }

        return $this->cycleRepository->update($id, [
            'status' => ReviewStatus::Completed->value,
        ]);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->cycleRepository->getByCompany($companyId);
    }
}
