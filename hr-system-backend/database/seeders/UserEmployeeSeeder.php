<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // Super Admin (no company)
        User::create([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@hrflow.com',
            'password'          => $password,
            'company_id'        => null,
            'role'              => UserRole::SuperAdmin->value,
            'email_verified_at' => now(),
        ]);

        $company = Company::first();
        if (! $company) return;

        $depts  = Department::where('company_id', $company->id)->get()->keyBy('name');
        $desigs = Designation::where('company_id', $company->id)->get()->keyBy('name');

        // ── Exact employees matching the screenshots ────────────────────────

        $employees = [
            // Managers / Leads
            [
                'first_name' => 'Sarah', 'last_name' => 'Johnson', 'email_prefix' => 'sarah.j',
                'position' => 'Senior Engineer', 'role' => UserRole::DepartmentManager,
                'dept' => 'Engineering', 'desig' => 'Senior Engineer',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2021-03-15', 'salary' => 9500, 'dob' => '1992-06-14',
                'emp_id' => 'EMP-0001', 'manages_dept' => 'Engineering',
                'bank' => 'Chase Bank', 'bank_branch' => 'NYC Main', 'bank_account' => '8847294892',
                'location' => 'Office',
            ],
            [
                'first_name' => 'Alex', 'last_name' => 'Kim', 'email_prefix' => 'alex.k',
                'position' => 'Backend Lead', 'role' => UserRole::DepartmentManager,
                'dept' => 'Backend', 'desig' => 'Senior Engineer',
                'gender' => 'male', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2020-07-01', 'salary' => 8800, 'dob' => '1990-03-22',
                'emp_id' => 'EMP-0002', 'manages_dept' => 'Backend', 'manager_ref' => 'Sarah Johnson',
                'bank' => 'Bank of America', 'bank_account' => '6639201847',
                'location' => 'Hybrid',
            ],
            [
                'first_name' => 'Maya', 'last_name' => 'Patel', 'email_prefix' => 'maya.p',
                'position' => 'Product Manager', 'role' => UserRole::DepartmentManager,
                'dept' => 'Product', 'desig' => 'Department Manager',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2022-01-10', 'salary' => 8200, 'dob' => '1991-11-05',
                'emp_id' => 'EMP-0003', 'manages_dept' => 'Product',
                'bank' => 'Wells Fargo', 'bank_account' => '7723918456',
                'location' => 'Office',
            ],
            [
                'first_name' => 'Dana', 'last_name' => 'Moore', 'email_prefix' => 'dana.m',
                'position' => 'HR Director', 'role' => UserRole::HrManager,
                'dept' => 'Human Resources', 'desig' => 'HR Director',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2019-11-20', 'salary' => 7800, 'dob' => '1988-02-18',
                'emp_id' => 'EMP-0004', 'manages_dept' => 'Human Resources',
                'bank' => 'Citibank', 'bank_account' => '3394018273',
                'location' => 'Office',
            ],
            // Regular employees
            [
                'first_name' => 'Tom', 'last_name' => 'Walsh', 'email_prefix' => 'tom.w',
                'position' => 'Finance Manager', 'role' => UserRole::DepartmentManager,
                'dept' => 'Finance', 'desig' => 'Department Manager',
                'gender' => 'male', 'status' => 'probation', 'type' => 'full_time',
                'join_date' => '2024-09-01', 'salary' => 7200, 'dob' => '1987-07-30',
                'emp_id' => 'EMP-0005', 'manages_dept' => 'Finance',
                'bank' => 'Chase Bank', 'bank_account' => '9912847561',
                'location' => 'Office',
            ],
            [
                'first_name' => 'Lily', 'last_name' => 'Chen', 'email_prefix' => 'lily.c',
                'position' => 'Sales Executive', 'role' => UserRole::DepartmentManager,
                'dept' => 'Sales', 'desig' => 'Department Manager',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2022-06-15', 'salary' => 5500, 'dob' => '1993-04-12',
                'emp_id' => 'EMP-0006', 'manages_dept' => 'Sales',
                'location' => 'Office',
            ],
            [
                'first_name' => 'James', 'last_name' => 'Rivera', 'email_prefix' => 'james.r',
                'position' => 'UI/UX Designer', 'role' => UserRole::Employee,
                'dept' => 'Product', 'desig' => 'Software Engineer',
                'gender' => 'male', 'status' => 'active', 'type' => 'contract',
                'join_date' => '2023-02-20', 'salary' => 6800, 'dob' => '1994-09-08',
                'emp_id' => 'EMP-0007', 'manager_ref' => 'Maya Patel',
                'location' => 'Remote',
            ],
            [
                'first_name' => 'Priya', 'last_name' => 'Sharma', 'email_prefix' => 'priya.s',
                'position' => 'DevOps Engineer', 'role' => UserRole::Employee,
                'dept' => 'Engineering', 'desig' => 'Senior Engineer',
                'gender' => 'female', 'status' => 'on_leave', 'type' => 'full_time',
                'join_date' => '2021-08-05', 'salary' => 8100, 'dob' => '1991-12-20',
                'emp_id' => 'EMP-0008', 'manager_ref' => 'Sarah Johnson',
                'location' => 'Hybrid',
            ],
            [
                'first_name' => 'Carlos', 'last_name' => 'Mendes', 'email_prefix' => 'carlos.m',
                'position' => 'Sales Intern', 'role' => UserRole::Employee,
                'dept' => 'Sales', 'desig' => 'Intern',
                'gender' => 'male', 'status' => 'active', 'type' => 'intern',
                'join_date' => '2024-06-01', 'salary' => 1800, 'dob' => '2001-05-14',
                'emp_id' => 'EMP-0009', 'manager_ref' => 'Lily Chen',
                'location' => 'Office',
            ],
            [
                'first_name' => 'Rachel', 'last_name' => 'Adams', 'email_prefix' => 'rachel.a',
                'position' => 'HR Coordinator', 'role' => UserRole::Employee,
                'dept' => 'Human Resources', 'desig' => 'Junior Engineer',
                'gender' => 'female', 'status' => 'active', 'type' => 'part_time',
                'join_date' => '2023-04-10', 'salary' => 3200, 'dob' => '1996-01-25',
                'emp_id' => 'EMP-0010', 'manager_ref' => 'Dana Moore',
                'location' => 'Office',
            ],
            [
                'first_name' => 'Kevin', 'last_name' => 'Park', 'email_prefix' => 'kevin.p',
                'position' => 'Frontend Engineer', 'role' => UserRole::Employee,
                'dept' => 'Engineering', 'desig' => 'Software Engineer',
                'gender' => 'male', 'status' => 'resigned', 'type' => 'full_time',
                'join_date' => '2020-05-12', 'salary' => 7600, 'dob' => '1993-08-17',
                'emp_id' => 'EMP-0011', 'manager_ref' => 'Alex Kim',
                'location' => 'Remote',
            ],
            [
                'first_name' => 'Amina', 'last_name' => 'Hassan', 'email_prefix' => 'amina.h',
                'position' => 'Financial Analyst', 'role' => UserRole::Employee,
                'dept' => 'Finance', 'desig' => 'Software Engineer',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2022-11-01', 'salary' => 6400, 'dob' => '1995-10-03',
                'emp_id' => 'EMP-0012', 'manager_ref' => 'Tom Walsh',
                'location' => 'Office',
            ],
            // Extra employees to fill department member counts
            [
                'first_name' => 'David', 'last_name' => 'Chen', 'email_prefix' => 'david.c',
                'position' => 'Full Stack Developer', 'role' => UserRole::Employee,
                'dept' => 'Engineering', 'desig' => 'Software Engineer',
                'gender' => 'male', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2023-01-15', 'salary' => 6500, 'dob' => '1994-03-11',
                'emp_id' => 'EMP-0013', 'manager_ref' => 'Sarah Johnson', 'location' => 'Office',
            ],
            [
                'first_name' => 'Sofia', 'last_name' => 'Garcia', 'email_prefix' => 'sofia.g',
                'position' => 'QA Engineer', 'role' => UserRole::Employee,
                'dept' => 'Engineering', 'desig' => 'Software Engineer',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2023-06-01', 'salary' => 5800, 'dob' => '1995-07-22',
                'emp_id' => 'EMP-0014', 'manager_ref' => 'Sarah Johnson', 'location' => 'Hybrid',
            ],
            [
                'first_name' => 'Marcus', 'last_name' => 'Brown', 'email_prefix' => 'marcus.b',
                'position' => 'Sales Representative', 'role' => UserRole::Employee,
                'dept' => 'Sales', 'desig' => 'Software Engineer',
                'gender' => 'male', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2022-09-15', 'salary' => 4200, 'dob' => '1997-11-08',
                'emp_id' => 'EMP-0015', 'manager_ref' => 'Lily Chen', 'location' => 'Office',
            ],
            [
                'first_name' => 'Elena', 'last_name' => 'Popov', 'email_prefix' => 'elena.p',
                'position' => 'Support Lead', 'role' => UserRole::DepartmentManager,
                'dept' => 'Customer Support', 'desig' => 'Department Manager',
                'gender' => 'female', 'status' => 'active', 'type' => 'full_time',
                'join_date' => '2021-04-01', 'salary' => 5900, 'dob' => '1990-06-30',
                'emp_id' => 'EMP-0016', 'manages_dept' => 'Customer Support', 'location' => 'Office',
            ],
        ];

        // Create all users + employees
        $employeeModels = [];
        foreach ($employees as $emp) {
            $user = User::create([
                'name'              => "{$emp['first_name']} {$emp['last_name']}",
                'email'             => "{$emp['email_prefix']}@acme.com",
                'password'          => $password,
                'company_id'        => $company->id,
                'role'              => $emp['role']->value,
                'email_verified_at' => now(),
            ]);

            $dept  = $depts[$emp['dept']] ?? $depts->first();
            $desig = $desigs[$emp['desig']] ?? $desigs->first();

            $employeeModel = Employee::create([
                'company_id'          => $company->id,
                'user_id'             => $user->id,
                'department_id'       => $dept->id,
                'designation_id'      => $desig?->id,
                'employee_id_number'  => $emp['emp_id'],
                'first_name'          => $emp['first_name'],
                'last_name'           => $emp['last_name'],
                'email'               => $user->email,
                'phone'               => '+1-555-' . rand(1000, 9999),
                'gender'              => $emp['gender'] === 'male' ? Gender::Male->value : Gender::Female->value,
                'date_of_birth'       => $emp['dob'],
                'employment_type'     => $emp['type'],
                'status'              => $emp['status'],
                'join_date'           => $emp['join_date'],
                'probation_end_date'  => $emp['status'] === 'probation' ? now()->addMonths(3)->format('Y-m-d') : null,
                'salary'              => $emp['salary'],
                'work_location'       => $emp['location'] ?? 'Office',
                'address'             => fake()->streetAddress(),
                'city'                => 'San Francisco',
                'state'               => 'CA',
                'country'             => 'United States',
                'bank_name'           => $emp['bank'] ?? fake()->randomElement(['Chase Bank', 'Bank of America', 'Wells Fargo', 'Citibank']),
                'bank_account_number' => $emp['bank_account'] ?? fake()->numerify('##########'),
                'emergency_contact_name'  => fake()->name(),
                'emergency_contact_phone' => fake()->phoneNumber(),
                'emergency_contact_relation' => fake()->randomElement(['Spouse', 'Parent', 'Sibling']),
            ]);

            $employeeModels["{$emp['first_name']} {$emp['last_name']}"] = $employeeModel;

            // Set department manager
            if (isset($emp['manages_dept']) && isset($depts[$emp['manages_dept']])) {
                $depts[$emp['manages_dept']]->update(['manager_id' => $user->id]);
            }
        }

        // Set manager references (second pass)
        foreach ($employees as $emp) {
            if (isset($emp['manager_ref']) && isset($employeeModels[$emp['manager_ref']])) {
                $key = "{$emp['first_name']} {$emp['last_name']}";
                if (isset($employeeModels[$key])) {
                    $employeeModels[$key]->update([
                        'manager_id' => $employeeModels[$emp['manager_ref']]->id,
                    ]);
                }
            }
        }

        // ── Second company (TechFlow) — minimal data ────────────────────────
        $company2 = Company::where('id', '!=', $company->id)->first();
        if ($company2) {
            $depts2  = Department::where('company_id', $company2->id)->get()->keyBy('name');
            $desigs2 = Designation::where('company_id', $company2->id)->get();
            $desig2  = $desigs2->first();

            $adminUser = User::create([
                'name'              => 'Jane Admin',
                'email'             => 'admin@techflow.com',
                'password'          => $password,
                'company_id'        => $company2->id,
                'role'              => UserRole::CompanyAdmin->value,
                'email_verified_at' => now(),
            ]);

            Employee::create([
                'company_id'         => $company2->id,
                'user_id'            => $adminUser->id,
                'department_id'      => $depts2->first()?->id,
                'designation_id'     => $desig2?->id,
                'employee_id_number' => 'TF-001',
                'first_name'         => 'Jane',
                'last_name'          => 'Admin',
                'email'              => $adminUser->email,
                'phone'              => '+1-555-9999',
                'gender'             => Gender::Female->value,
                'date_of_birth'      => '1985-03-15',
                'employment_type'    => EmploymentType::FullTime->value,
                'status'             => EmployeeStatus::Active->value,
                'join_date'          => '2020-01-01',
                'salary'             => 12000,
                'work_location'      => 'Office',
                'address'            => '500 Tech Blvd',
                'city'               => 'Austin',
                'state'              => 'TX',
                'country'            => 'United States',
            ]);
        }
    }
}
