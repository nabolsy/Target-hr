<?php

namespace App\Services;

use App\DTOs\CompanyDTO;
use App\Enums\CompanyStatus;
use App\Events\CompanyCreated;
use App\Events\CompanyStatusChanged;
use App\Exceptions\BusinessException;
use App\Models\Company;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CompanyService extends BaseService
{
    public function __construct(
        protected CompanyRepositoryInterface $companyRepository,
    ) {
        parent::__construct($companyRepository);
    }

    public function getActiveCompanies(): Collection
    {
        return $this->companyRepository->getActiveCompanies();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->companyRepository->paginateWithFilters($filters, $perPage);
    }

    public function createCompany(CompanyDTO $dto): Company
    {
        return DB::transaction(function () use ($dto) {
            $existing = $this->companyRepository->findByEmail($dto->email);

            if ($existing) {
                throw new BusinessException("A company with email '{$dto->email}' already exists.");
            }

            $data = $dto->toArray();

            if (! isset($data['status'])) {
                $data['status'] = CompanyStatus::Active->value;
            }

            $company = $this->companyRepository->create($data);

            event(new CompanyCreated($company));

            return $company;
        });
    }

    public function updateCompany(int $id, CompanyDTO $dto): Company
    {
        return DB::transaction(function () use ($id, $dto) {
            $existing = $this->companyRepository->findByEmail($dto->email);

            if ($existing && $existing->id !== $id) {
                throw new BusinessException("A company with email '{$dto->email}' already exists.");
            }

            return $this->companyRepository->update($id, $dto->toArray());
        });
    }

    public function changeStatus(int $id, CompanyStatus $status): Company
    {
        $company = $this->companyRepository->findOrFail($id);
        $oldStatus = $company->status;

        $updated = $this->companyRepository->updateStatus($id, $status);

        event(new CompanyStatusChanged($updated, $oldStatus, $status));

        return $updated;
    }

    public function deleteCompany(int $id): bool
    {
        $company = $this->companyRepository->findOrFail($id);

        if ($company->users()->count() > 0) {
            throw new BusinessException('Cannot delete a company that has users. Remove all users first.');
        }

        return $this->companyRepository->delete($id);
    }
}
