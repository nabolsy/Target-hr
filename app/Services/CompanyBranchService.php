<?php

namespace App\Services;

use App\DTOs\CompanyBranchDTO;
use App\Models\CompanyBranch;
use App\Repositories\Interfaces\CompanyBranchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompanyBranchService extends BaseService
{
    public function __construct(
        protected CompanyBranchRepositoryInterface $branchRepository,
    ) {
        parent::__construct($branchRepository);
    }

    public function createBranch(CompanyBranchDTO $dto): CompanyBranch
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;

        return $this->branchRepository->create($data);
    }

    public function updateBranch(int $id, CompanyBranchDTO $dto): CompanyBranch
    {
        $data = $dto->toArray();

        return $this->branchRepository->update($id, $data);
    }

    public function deleteBranch(int $id): bool
    {
        return $this->branchRepository->delete($id);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->branchRepository->getByCompany($companyId);
    }

    public function getActive(int $companyId): Collection
    {
        return $this->branchRepository->getActive($companyId);
    }
}
