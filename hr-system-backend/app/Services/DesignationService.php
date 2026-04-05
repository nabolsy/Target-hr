<?php

namespace App\Services;

use App\DTOs\DesignationDTO;
use App\Exceptions\BusinessException;
use App\Models\Designation;
use App\Repositories\Interfaces\DesignationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DesignationService extends BaseService
{
    public function __construct(
        protected DesignationRepositoryInterface $designationRepository,
    ) {
        parent::__construct($designationRepository);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->designationRepository->getByCompany($companyId);
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->designationRepository->getByDepartment($departmentId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->designationRepository->paginateWithFilters($filters, $perPage);
    }

    public function createDesignation(DesignationDTO $dto): Designation
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();

            return $this->designationRepository->create($data);
        });
    }

    public function updateDesignation(int $id, DesignationDTO $dto): Designation
    {
        return DB::transaction(function () use ($id, $dto) {
            return $this->designationRepository->update($id, $dto->toArray());
        });
    }

    public function deleteDesignation(int $id): bool
    {
        $designation = $this->designationRepository->findOrFail($id);

        if ($designation->employees()->count() > 0) {
            throw new BusinessException('Cannot delete a designation that has employees. Reassign all employees first.');
        }

        return $this->designationRepository->delete($id);
    }
}
