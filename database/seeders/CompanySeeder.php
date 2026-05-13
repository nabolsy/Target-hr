<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
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
            ],
            [
                'name' => 'TechFlow Inc',
                'email' => 'info@techflow.com',
                'phone' => '+1-555-200-3000',
                'address' => '456 Digital Boulevard',
                'city' => 'Austin',
                'state' => 'Texas',
                'country' => 'United States',
                'postal_code' => '73301',
                'website' => 'https://techflow.com',
                'logo' => 'logos/techflow-logo.png',
                'industry' => 'Software',
                'employee_limit' => 200,
                'status' => CompanyStatus::Active->value,
                'subscription_plan' => SubscriptionPlan::Professional->value,
            ],
        ];

        foreach ($companies as $data) {
            $company = Company::create($data);

            CompanySetting::create([
                'company_id' => $company->id,
                'work_start_time' => '09:00:00',
                'work_end_time' => '17:00:00',
                'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'timezone' => $company->name === 'Target' ? 'America/Los_Angeles' : 'America/Chicago',
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
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'country' => $data['country'],
                'postal_code' => $data['postal_code'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'is_headquarters' => true,
                'is_active' => true,
            ]);
        }
    }
}
