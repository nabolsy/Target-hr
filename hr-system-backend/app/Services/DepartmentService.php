<?php

namespace App\Services;

use App\DTOs\DepartmentDTO;
use App\Enums\DepartmentStatus;
use App\Events\DepartmentCreated;
use App\Exceptions\BusinessException;
use App\Models\Department;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepartmentService extends BaseService
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository,
    ) {
        parent::__construct($departmentRepository);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->departmentRepository->getByCompany($companyId);
    }

    public function getActiveByCompany(int $companyId): Collection
    {
        return $this->departmentRepository->getActiveByCompany($companyId);
    }

    public function getRootDepartments(int $companyId): Collection
    {
        return $this->departmentRepository->getRootDepartments($companyId);
    }

    public function getSubDepartments(int $parentId): Collection
    {
        return $this->departmentRepository->getSubDepartments($parentId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->departmentRepository->paginateWithFilters($filters, $perPage);
    }

    public function createDepartment(DepartmentDTO $dto): Department
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();

            if ($dto->parentId) {
                $parent = $this->departmentRepository->findOrFail($dto->parentId);

                if ((int) $parent->company_id !== $dto->companyId) {
                    throw new BusinessException('Parent department must belong to the same company.');
                }
            }

            if (! isset($data['status'])) {
                $data['status'] = DepartmentStatus::Active->value;
            }

            $department = $this->departmentRepository->create($data);

            event(new DepartmentCreated($department));

            return $department;
        });
    }

    public function updateDepartment(int $id, DepartmentDTO $dto): Department
    {
        return DB::transaction(function () use ($id, $dto) {
            $department = $this->departmentRepository->findOrFail($id);

            if ($dto->parentId) {
                if ($dto->parentId === $id) {
                    throw new BusinessException('A department cannot be its own parent.');
                }

                $parent = $this->departmentRepository->findOrFail($dto->parentId);

                if ((int) $parent->company_id !== $dto->companyId) {
                    throw new BusinessException('Parent department must belong to the same company.');
                }
            }

            return $this->departmentRepository->update($id, $dto->toArray());
        });
    }

    public function deleteDepartment(int $id): bool
    {
        $department = $this->departmentRepository->findOrFail($id);

        if ($department->children()->count() > 0) {
            throw new BusinessException('Cannot delete a department that has sub-departments. Remove all sub-departments first.');
        }

        if ($department->employees()->count() > 0) {
            throw new BusinessException('Cannot delete a department that has employees. Reassign all employees first.');
        }

        return $this->departmentRepository->delete($id);
    }
}
