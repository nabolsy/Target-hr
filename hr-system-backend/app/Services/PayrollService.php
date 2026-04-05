<?php

namespace App\Services;

use App\DTOs\SalaryStructureDTO;
use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Enums\SalaryComponentType;
use App\Exceptions\BusinessException;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SalaryStructure;
use App\Repositories\Interfaces\PayrollRepositoryInterface;
use App\Repositories\Interfaces\SalaryStructureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        protected SalaryStructureRepositoryInterface $salaryStructureRepository,
        protected PayrollRepositoryInterface $payrollRepository,
    ) {
    }

    public function createSalaryStructure(SalaryStructureDTO $dto, array $components = []): SalaryStructure
    {
        return DB::transaction(function () use ($dto, $components) {
            $data = $dto->toArray();
            $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;

            $structure = $this->salaryStructureRepository->create($data);

            foreach ($components as $index => $component) {
                $structure->components()->create([
                    'name' => $component['name'],
                    'type' => $component['type'],
                    'amount' => $component['amount'],
                    'is_percentage' => $component['is_percentage'] ?? false,
                    'is_taxable' => $component['is_taxable'] ?? true,
                    'sort_order' => $component['sort_order'] ?? $index,
                ]);
            }

            return $structure->load('components');
        });
    }

    public function updateSalaryStructure(int $id, SalaryStructureDTO $dto, array $components = []): SalaryStructure
    {
        return DB::transaction(function () use ($id, $dto, $components) {
            $data = $dto->toArray();
            $structure = $this->salaryStructureRepository->update($id, $data);

            if (! empty($components)) {
                $structure->components()->delete();

                foreach ($components as $index => $component) {
                    $structure->components()->create([
                        'name' => $component['name'],
                        'type' => $component['type'],
                        'amount' => $component['amount'],
                        'is_percentage' => $component['is_percentage'] ?? false,
                        'is_taxable' => $component['is_taxable'] ?? true,
                        'sort_order' => $component['sort_order'] ?? $index,
                    ]);
                }
            }

            return $structure->load('components');
        });
    }

    public function getSalaryStructureByEmployee(int $employeeId): ?SalaryStructure
    {
        return $this->salaryStructureRepository->getByEmployee($employeeId);
    }

    public function generatePayroll(int $companyId, int $month, int $year): PayrollPeriod
    {
        return DB::transaction(function () use ($companyId, $month, $year) {
            $existing = PayrollPeriod::where('company_id', $companyId)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($existing && $existing->status === PayrollStatus::Locked) {
                throw new BusinessException('Payroll period is already locked and cannot be regenerated.');
            }

            if ($existing) {
                $existing->records()->delete();
                $period = $existing;
                $period->update([
                    'status' => PayrollStatus::Generated,
                    'generated_by' => auth()->id(),
                    'generated_at' => now(),
                ]);
            } else {
                $period = PayrollPeriod::create([
                    'company_id' => $companyId,
                    'month' => $month,
                    'year' => $year,
                    'status' => PayrollStatus::Generated,
                    'generated_by' => auth()->id(),
                    'generated_at' => now(),
                ]);
            }

            $employees = \App\Models\Employee::where('company_id', $companyId)
                ->where('status', EmployeeStatus::Active)
                ->get();

            foreach ($employees as $employee) {
                $salaryStructure = SalaryStructure::where('employee_id', $employee->id)
                    ->where('effective_date', '<=', now())
                    ->latest('effective_date')
                    ->with('components')
                    ->first();

                if (! $salaryStructure) {
                    continue;
                }

                $basicSalary = (float) $salaryStructure->basic_salary;
                $totalAllowances = 0;
                $totalDeductions = 0;

                foreach ($salaryStructure->components as $component) {
                    $amount = (float) $component->amount;

                    if ($component->is_percentage) {
                        $amount = $basicSalary * ($amount / 100);
                    }

                    if ($component->type === SalaryComponentType::Allowance) {
                        $totalAllowances += $amount;
                    } else {
                        $totalDeductions += $amount;
                    }
                }

                $grossSalary = $basicSalary + $totalAllowances;
                $netSalary = $grossSalary - $totalDeductions;

                PayrollRecord::create([
                    'company_id' => $companyId,
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'total_allowances' => $totalAllowances,
                    'total_deductions' => $totalDeductions,
                    'gross_salary' => $grossSalary,
                    'net_salary' => $netSalary,
                    'working_days' => 0,
                    'present_days' => 0,
                    'absent_days' => 0,
                    'leave_days' => 0,
                    'overtime_hours' => 0,
                ]);
            }

            return $period->load('records.employee');
        });
    }

    public function lockPeriod(int $periodId): PayrollPeriod
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status === PayrollStatus::Locked) {
            throw new BusinessException('Payroll period is already locked.');
        }

        if ($period->status !== PayrollStatus::Generated) {
            throw new BusinessException('Only generated payroll periods can be locked.');
        }

        $period->update([
            'status' => PayrollStatus::Locked,
            'locked_at' => now(),
        ]);

        return $period->fresh();
    }

    public function exportToCsv(int $periodId): string
    {
        $period = PayrollPeriod::with('records.employee')->findOrFail($periodId);

        $lines = [];
        $lines[] = implode(',', [
            'employee_name',
            'basic',
            'allowances',
            'deductions',
            'gross',
            'net',
            'working_days',
            'present',
            'absent',
            'leave',
            'overtime',
        ]);

        foreach ($period->records as $record) {
            $employeeName = $record->employee
                ? '"' . str_replace('"', '""', $record->employee->full_name) . '"'
                : 'N/A';

            $lines[] = implode(',', [
                $employeeName,
                $record->basic_salary,
                $record->total_allowances,
                $record->total_deductions,
                $record->gross_salary,
                $record->net_salary,
                $record->working_days,
                $record->present_days,
                $record->absent_days,
                $record->leave_days,
                $record->overtime_hours,
            ]);
        }

        return implode("\n", $lines);
    }

    public function getPeriods(int $companyId): Collection
    {
        return PayrollPeriod::where('company_id', $companyId)
            ->withCount('records')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    public function getPeriod(int $periodId): PayrollPeriod
    {
        return PayrollPeriod::with('records.employee')->findOrFail($periodId);
    }

    public function paginateRecords(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->payrollRepository->paginateWithFilters($filters, $perPage);
    }
}
