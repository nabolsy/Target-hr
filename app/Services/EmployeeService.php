<?php

namespace App\Services;

use App\DTOs\EmployeeDTO;
use App\Enums\EmployeeStatus;
use App\Events\EmployeeHired;
use App\Events\EmployeeStatusChanged;
use App\Exceptions\BusinessException;
use App\Models\Employee;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeService extends BaseService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository,
    ) {
        parent::__construct($employeeRepository);
    }

    public function createEmployee(EmployeeDTO $dto): Employee
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $companyId = $data['company_id'] ?? auth()->user()->company_id;

            // Check company employee limit
            $company = \App\Models\Company::findOrFail($companyId);
            if ($company->employee_limit) {
                $currentCount = $this->employeeRepository->countByCompany($companyId);
                if ($currentCount >= $company->employee_limit) {
                    throw new BusinessException(
                        "Employee limit reached. Your company allows a maximum of {$company->employee_limit} employees."
                    );
                }
            }

            // Check for duplicate employee ID number within company
            if (isset($data['employee_id_number'])) {
                $existing = $this->employeeRepository->findByEmployeeIdNumber(
                    $data['employee_id_number'],
                    $companyId
                );
                if ($existing) {
                    throw new BusinessException(
                        "An employee with ID number '{$data['employee_id_number']}' already exists in this company."
                    );
                }
            }

            if (! isset($data['status'])) {
                $data['status'] = EmployeeStatus::Active->value;
            }

            $data['company_id'] = $companyId;

            $employee = $this->employeeRepository->create($data);

            event(new EmployeeHired($employee));

            return $employee;
        });
    }

    public function updateEmployee(int $id, EmployeeDTO $dto): Employee
    {
        return DB::transaction(function () use ($id, $dto) {
            $data = $dto->toArray();
            $employee = $this->employeeRepository->findOrFail($id);

            // Check for duplicate employee ID number if changed
            if (isset($data['employee_id_number'])) {
                $existing = $this->employeeRepository->findByEmployeeIdNumber(
                    $data['employee_id_number'],
                    $employee->company_id
                );
                if ($existing && $existing->id !== $id) {
                    throw new BusinessException(
                        "An employee with ID number '{$data['employee_id_number']}' already exists in this company."
                    );
                }
            }

            return $this->employeeRepository->update($id, $data);
        });
    }

    public function changeStatus(int $id, EmployeeStatus $status): Employee
    {
        $employee = $this->employeeRepository->findOrFail($id);
        $oldStatus = $employee->status;

        $employee->update(['status' => $status]);
        $employee = $employee->fresh();

        event(new EmployeeStatusChanged($employee, $oldStatus, $status));

        return $employee;
    }

    public function deleteEmployee(int $id): bool
    {
        $employee = $this->employeeRepository->findOrFail($id);

        if ($employee->subordinates()->count() > 0) {
            throw new BusinessException(
                'Cannot delete an employee who manages other employees. Reassign their subordinates first.'
            );
        }

        return $this->employeeRepository->delete($id);
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->employeeRepository->getByDepartment($departmentId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->employeeRepository->paginateWithFilters($filters, $perPage);
    }
}
