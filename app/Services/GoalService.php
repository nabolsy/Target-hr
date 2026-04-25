<?php

namespace App\Services;

use App\DTOs\GoalDTO;
use App\Enums\GoalStatus;
use App\Exceptions\BusinessException;
use App\Models\Goal;
use App\Repositories\Interfaces\GoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GoalService extends BaseService
{
    public function __construct(
        protected GoalRepositoryInterface $goalRepository,
    ) {
        parent::__construct($goalRepository);
    }

    public function createGoal(GoalDTO $dto): Goal
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['status'] = $data['status'] ?? GoalStatus::NotStarted->value;

        return $this->goalRepository->create($data);
    }

    public function updateGoal(int $id, GoalDTO $dto): Goal
    {
        $goal = $this->goalRepository->findOrFail($id);

        if ($goal->status === GoalStatus::Completed) {
            throw new BusinessException('Cannot update a completed goal.');
        }

        return $this->goalRepository->update($id, $dto->toArray());
    }

    public function updateProgress(int $id, float $currentValue, ?string $status = null): Goal
    {
        $goal = $this->goalRepository->findOrFail($id);

        if ($goal->status === GoalStatus::Completed || $goal->status === GoalStatus::Cancelled) {
            throw new BusinessException('Cannot update progress on a completed or cancelled goal.');
        }

        $updateData = ['current_value' => $currentValue];

        if ($status !== null) {
            $updateData['status'] = $status;
        } elseif ($goal->target_value && $currentValue >= $goal->target_value) {
            $updateData['status'] = GoalStatus::Completed->value;
        } elseif ($currentValue > 0 && $goal->status === GoalStatus::NotStarted) {
            $updateData['status'] = GoalStatus::InProgress->value;
        }

        return $this->goalRepository->update($id, $updateData);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->goalRepository->getByEmployee($employeeId);
    }

    public function getByCycle(int $reviewCycleId): Collection
    {
        return $this->goalRepository->getByCycle($reviewCycleId);
    }
}
