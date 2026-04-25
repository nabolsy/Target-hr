<?php

namespace App\Services;

use App\DTOs\AssetDTO;
use App\Enums\AssetStatus;
use App\Events\AssetAssigned;
use App\Events\AssetReturned;
use App\Exceptions\BusinessException;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssetService extends BaseService
{
    public function __construct(
        protected AssetRepositoryInterface $assetRepository,
    ) {
        parent::__construct($assetRepository);
    }

    public function createAsset(AssetDTO $dto): Asset
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['status'] = $data['status'] ?? AssetStatus::Available->value;

        /** @var Asset $asset */
        $asset = $this->assetRepository->create($data);

        return $asset;
    }

    public function updateAsset(int $id, AssetDTO $dto): Asset
    {
        $data = $dto->toArray();

        /** @var Asset $asset */
        $asset = $this->assetRepository->update($id, $data);

        return $asset;
    }

    public function assignToEmployee(int $assetId, int $employeeId, string $conditionOnAssign, ?string $notes = null): AssetAssignment
    {
        return DB::transaction(function () use ($assetId, $employeeId, $conditionOnAssign, $notes) {
            /** @var Asset $asset */
            $asset = $this->assetRepository->findOrFail($assetId);

            if ($asset->status === AssetStatus::Assigned) {
                throw new BusinessException('This asset is already assigned. Please return it first.');
            }

            if ($asset->status === AssetStatus::Disposed) {
                throw new BusinessException('Cannot assign a disposed asset.');
            }

            $employee = Employee::findOrFail($employeeId);

            $assignment = AssetAssignment::create([
                'asset_id' => $assetId,
                'employee_id' => $employeeId,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'condition_on_assign' => $conditionOnAssign,
                'notes' => $notes,
            ]);

            $asset->update(['status' => AssetStatus::Assigned->value]);

            event(new AssetAssigned($asset->fresh(), $employee));

            return $assignment->load(['asset', 'employee', 'assignedBy']);
        });
    }

    public function returnAsset(int $assetId, string $conditionOnReturn, ?string $notes = null): AssetAssignment
    {
        return DB::transaction(function () use ($assetId, $conditionOnReturn, $notes) {
            /** @var Asset $asset */
            $asset = $this->assetRepository->findOrFail($assetId);

            if ($asset->status !== AssetStatus::Assigned) {
                throw new BusinessException('This asset is not currently assigned.');
            }

            $assignment = AssetAssignment::where('asset_id', $assetId)
                ->whereNull('returned_at')
                ->latest('assigned_at')
                ->firstOrFail();

            $assignment->update([
                'returned_at' => now(),
                'condition_on_return' => $conditionOnReturn,
                'notes' => $notes ?? $assignment->notes,
            ]);

            $asset->update(['status' => AssetStatus::Available->value]);

            $employee = $assignment->employee;

            event(new AssetReturned($asset->fresh(), $employee));

            return $assignment->fresh()->load(['asset', 'employee', 'assignedBy']);
        });
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->assetRepository->getByEmployee($employeeId);
    }

    public function getHistory(int $assetId): Collection
    {
        $this->assetRepository->findOrFail($assetId);

        return AssetAssignment::where('asset_id', $assetId)
            ->with(['employee', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->assetRepository->paginateWithFilters($filters, $perPage);
    }
}
