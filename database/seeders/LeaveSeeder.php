<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypesData = [
            ['name' => 'Annual Leave', 'slug' => 'annual', 'days_per_year' => 21, 'is_paid' => true, 'requires_attachment' => false, 'description' => 'Paid annual vacation leave'],
            ['name' => 'Sick Leave', 'slug' => 'sick', 'days_per_year' => 10, 'is_paid' => true, 'requires_attachment' => true, 'description' => 'Medical sick leave with doctor note'],
            ['name' => 'Casual Leave', 'slug' => 'casual', 'days_per_year' => 5, 'is_paid' => true, 'requires_attachment' => false, 'description' => 'Short-notice personal leave'],
        ];

        foreach (Company::all() as $company) {
            $leaveTypes = [];
            foreach ($leaveTypesData as $lt) {
                $leaveTypes[] = LeaveType::create(array_merge($lt, ['company_id' => $company->id]));
            }

            $employees = Employee::where('company_id', $company->id)->get();
            $year = now()->year;

            // Leave Balances for all employees
            foreach ($employees as $employee) {
                foreach ($leaveTypes as $lt) {
                    $used = fake()->randomFloat(1, 0, $lt->days_per_year * 0.3);
                    LeaveBalance::create([
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $lt->id,
                        'year' => $year,
                        'total_days' => $lt->days_per_year,
                        'used_days' => $used,
                        'remaining_days' => $lt->days_per_year - $used,
                    ]);
                }
            }

            // Leave Requests: 5 pending, 3 approved, 2 rejected
            $hrUser = User::where('company_id', $company->id)->where('role', 'hr_manager')->first()
                ?? User::where('company_id', $company->id)->where('role', 'company_admin')->first();
            $empIds = $employees->pluck('id')->shuffle();

            $statuses = array_merge(
                array_fill(0, 5, 'pending'),
                array_fill(0, 3, 'approved'),
                array_fill(0, 2, 'rejected')
            );

            foreach ($statuses as $i => $status) {
                $empId = $empIds[$i % $empIds->count()];
                $leaveType = $leaveTypes[array_rand($leaveTypes)];
                $startDate = Carbon::now()->addDays(rand(1, 30));
                $days = rand(1, 5);

                LeaveRequest::create([
                    'company_id' => $company->id,
                    'employee_id' => $empId,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $startDate->copy()->addDays($days - 1)->format('Y-m-d'),
                    'is_half_day' => $days === 1 && rand(0, 1),
                    'total_days' => $days,
                    'reason' => fake()->sentence(),
                    'status' => $status,
                    'approved_by' => $status !== 'pending' ? $hrUser->id : null,
                    'approved_at' => $status !== 'pending' ? now()->subDays(rand(1, 5)) : null,
                    'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
                ]);
            }
        }
    }
}
