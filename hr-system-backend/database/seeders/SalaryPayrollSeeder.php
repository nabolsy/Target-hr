<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic payroll data so the new tabbed Payroll module has
 * something meaningful to render:
 *
 *   • Salary structure per active employee (basic + 3 allowances +
 *     3 deductions).
 *   • 6 payroll periods per company — the 3 oldest are `locked`,
 *     the next 2 are `generated`, the current month is `draft`.
 *   • Per-employee PayrollRecord rows for every period with realistic
 *     month-over-month variance so the Overview tab's trend chart
 *     doesn't look flat.
 *
 * Idempotent: re-running skips employees that already have a
 * structure and periods that already exist for the same (company,
 * year, month).
 */
class SalaryPayrollSeeder extends Seeder
{
    // Number of months of history to seed, including the current month.
    private const MONTHS_OF_HISTORY = 6;

    // Anything older than this is closed out.
    private const MONTHS_LOCKED = 3;

    public function run(): void
    {
        foreach (Company::all() as $company) {
            $employees = Employee::where('company_id', $company->id)->get();
            if ($employees->isEmpty()) {
                continue;
            }

            $adminUser = User::where('company_id', $company->id)
                ->whereIn('role', ['company_admin', 'hr_manager'])
                ->first();
            if (!$adminUser) {
                // Fall back to ANY user in the company so the seeder
                // doesn't silently skip companies whose admin hasn't
                // been seeded yet.
                $adminUser = User::where('company_id', $company->id)->first();
                if (!$adminUser) continue;
            }

            $this->seedSalaryStructures($company, $employees);
            $this->seedPayrollPeriods($company, $employees, $adminUser);
        }
    }

    /**
     * Create a salary structure + components for any employee that
     * doesn't have one yet. Skips existing rows so the seeder is
     * safe to re-run.
     */
    private function seedSalaryStructures(Company $company, $employees): void
    {
        foreach ($employees as $employee) {
            // Skip if this employee already has a structure — preserves
            // any manual revisions made via the UI.
            $existing = SalaryStructure::where('employee_id', $employee->id)->first();
            if ($existing) continue;

            $basicSalary = $employee->salary ?? random_int(3500, 9500);

            $structure = SalaryStructure::create([
                'company_id'       => $company->id,
                'employee_id'      => $employee->id,
                'basic_salary'     => $basicSalary,
                'currency'         => 'USD',
                'payment_frequency' => 'monthly',
                'effective_date'   => $employee->join_date,
            ]);

            $components = [
                // [name, type, amount, is_percentage, is_taxable, sort_order]
                ['Housing Allowance',        'allowance', 25,  true,  true,  1],
                ['Transportation Allowance', 'allowance', 500, false, false, 2],
                ['Meal Allowance',           'allowance', 300, false, false, 3],
                ['Income Tax',               'deduction', 15,  true,  false, 4],
                ['Health Insurance',         'deduction', 350, false, false, 5],
                ['Retirement Fund',          'deduction', 5,   true,  false, 6],
            ];

            foreach ($components as [$name, $type, $amount, $isPct, $isTaxable, $order]) {
                SalaryComponent::create([
                    'salary_structure_id' => $structure->id,
                    'name'          => $name,
                    'type'          => $type,
                    'amount'        => $amount,
                    'is_percentage' => $isPct,
                    'is_taxable'    => $isTaxable,
                    'sort_order'    => $order,
                ]);
            }
        }
    }

    /**
     * Generate MONTHS_OF_HISTORY payroll periods per company, from
     * (current month - N + 1) through the current month, inclusive.
     * Oldest periods are locked, middle ones generated, this month
     * is draft. Each period gets a PayrollRecord for every employee
     * with month-over-month variance.
     */
    private function seedPayrollPeriods(Company $company, $employees, User $adminUser): void
    {
        $now = Carbon::now();

        for ($offset = self::MONTHS_OF_HISTORY - 1; $offset >= 0; $offset--) {
            $month = $now->copy()->subMonths($offset);

            $period = PayrollPeriod::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'month'      => $month->month,
                    'year'       => $month->year,
                ],
                [
                    'status'       => $this->statusForOffset($offset),
                    'generated_by' => $adminUser->id,
                    'generated_at' => $month->copy()->endOfMonth()->subDays(2),
                    'locked_at'    => $offset >= self::MONTHS_LOCKED
                        ? $month->copy()->endOfMonth()->addDay()
                        : null,
                ],
            );

            // If the period already existed (re-run), keep its records.
            if ($period->records()->exists()) continue;

            foreach ($employees as $employee) {
                $this->createPayrollRecord($company, $period, $employee, $offset);
            }
        }
    }

    private function statusForOffset(int $offset): string
    {
        if ($offset >= self::MONTHS_LOCKED) return 'locked';
        if ($offset === 0) return 'draft';
        return 'generated';
    }

    /**
     * One payroll record with realistic variance. Seeded bonuses and
     * small random fluctuations on deductions give the monthly trend
     * chart visible movement instead of a flat line.
     */
    private function createPayrollRecord(Company $company, PayrollPeriod $period, Employee $employee, int $offset): void
    {
        $basic = (float) ($employee->salary ?? random_int(3500, 9500));

        // Allowances match the structure (25% housing + $500 + $300).
        $housing   = $basic * 0.25;
        $transport = 500;
        $meal      = 300;
        // Small random extra bonus 150-400, shown only on a few months
        // so the chart has obvious peaks instead of drifting upward.
        $bonus = ($offset === 1 || $offset === 3) ? random_int(150, 400) : 0;
        $totalAllowances = $housing + $transport + $meal + $bonus;

        // Deductions: 15% tax + $350 insurance + 5% retirement, with
        // ±$30 randomness so month-over-month isn't identical.
        $incomeTax  = $basic * 0.15;
        $health     = 350;
        $retirement = $basic * 0.05;
        $variance   = random_int(-30, 30);
        $totalDeductions = $incomeTax + $health + $retirement + $variance;

        $gross = $basic + $totalAllowances;
        $net   = $gross - $totalDeductions;

        $working = 22;
        $present = random_int(18, 22);
        $absent  = random_int(0, 2);
        $leave   = max(0, $working - $present - $absent);

        PayrollRecord::create([
            'company_id'        => $company->id,
            'payroll_period_id' => $period->id,
            'employee_id'       => $employee->id,
            'basic_salary'      => round($basic, 2),
            'total_allowances'  => round($totalAllowances, 2),
            'total_deductions'  => round($totalDeductions, 2),
            'gross_salary'      => round($gross, 2),
            'net_salary'        => round($net, 2),
            'working_days'      => $working,
            'present_days'      => $present,
            'absent_days'       => $absent,
            'leave_days'        => $leave,
            'overtime_hours'    => random_int(0, 20),
        ]);
    }
}
