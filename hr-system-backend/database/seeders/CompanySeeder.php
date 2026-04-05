<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo company
        Company::factory()->create([
            'name' => 'Acme Corporation',
            'email' => 'admin@acme.com',
            'industry' => 'Technology',
            'status' => CompanyStatus::Active,
            'subscription_plan' => SubscriptionPlan::Professional,
            'employee_limit' => 200,
        ]);

        // Create additional sample companies
        Company::factory()->count(5)->create();
    }
}
