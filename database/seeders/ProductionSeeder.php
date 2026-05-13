<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\CompanySetting;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@targetco.io')->exists()) {
            $this->command->warn('ProductionSeeder: admin@targetco.io already exists — aborting to avoid duplicates.');
            return;
        }

        $this->call([
            RolePermissionSeeder::class,
            RoleAccessMatrixSeeder::class,
        ]);

        $company = Company::create([
            'name' => 'Target',
            'email' => 'info@targetco.io',
            'phone' => '+1-555-100-2000',
            'address' => '123 Innovation Drive',
            'city' => 'San Francisco',
            'state' => 'California',
            'country' => 'United States',
            'postal_code' => '94105',
            'website' => 'https://targetco.io',
            'logo' => 'logos/target-logo.png',
            'industry' => 'Technology',
            'employee_limit' => 200,
            'status' => CompanyStatus::Active->value,
            'subscription_plan' => SubscriptionPlan::Professional->value,
        ]);

        CompanySetting::create([
            'company_id' => $company->id,
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => 'America/Los_Angeles',
            'date_format' => 'Y-m-d',
            'currency' => 'USD',
            'grace_period_minutes' => 15,
            'allow_remote_checkin' => true,
            'leave_approval_levels' => 2,
            'probation_period_days' => 90,
        ]);

        CompanyBranch::create([
            'company_id' => $company->id,
            'name' => 'Headquarters',
            'address' => $company->address,
            'city' => $company->city,
            'state' => $company->state,
            'country' => $company->country,
            'postal_code' => $company->postal_code,
            'phone' => $company->phone,
            'email' => $company->email,
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->call([
            PlanSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            ShiftSeeder::class,
        ]);

        $leaveTypes = [
            ['name' => 'Annual Leave', 'slug' => 'annual', 'days_per_year' => 21, 'is_paid' => true, 'requires_attachment' => false, 'description' => 'Paid annual vacation leave'],
            ['name' => 'Sick Leave',   'slug' => 'sick',   'days_per_year' => 10, 'is_paid' => true, 'requires_attachment' => true,  'description' => 'Medical sick leave with doctor note'],
            ['name' => 'Casual Leave', 'slug' => 'casual', 'days_per_year' => 5,  'is_paid' => true, 'requires_attachment' => false, 'description' => 'Short-notice personal leave'],
        ];
        foreach ($leaveTypes as $lt) {
            LeaveType::create(array_merge($lt, ['company_id' => $company->id]));
        }

        User::create([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@targetco.io',
            'password'          => Hash::make('cf3ef87dc3e2a4a7'),
            'company_id'        => null,
            'role'              => UserRole::SuperAdmin->value,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name'              => 'Target Admin',
            'email'             => 'admin@targetco.io',
            'password'          => Hash::make('e5c7db31e72932c7'),
            'company_id'        => $company->id,
            'role'              => UserRole::CompanyAdmin->value,
            'email_verified_at' => now(),
        ]);
    }
}
