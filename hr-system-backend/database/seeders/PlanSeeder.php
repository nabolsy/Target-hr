<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small teams getting started with HR management.',
                'price_monthly' => 99.00,
                'price_yearly' => 990.00,
                'currency' => 'SAR',
                'max_employees' => 10,
                'max_departments' => 3,
                'max_storage_gb' => 5,
                'features' => [
                    'employee_management', 'department_management', 'attendance_tracking',
                    'leave_management', 'basic_reports', 'document_storage',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'trial_days' => 14,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing companies that need full HR capabilities.',
                'price_monthly' => 249.00,
                'price_yearly' => 2490.00,
                'currency' => 'SAR',
                'max_employees' => 50,
                'max_departments' => 10,
                'max_storage_gb' => 25,
                'features' => [
                    'employee_management', 'department_management', 'attendance_tracking',
                    'leave_management', 'basic_reports', 'document_storage',
                    'task_boards', 'performance_reviews', 'recruitment',
                    'onboarding', 'asset_management', 'payroll_support',
                    'announcements', 'advanced_roles',
                ],
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
                'trial_days' => 14,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations needing unlimited access and premium support.',
                'price_monthly' => 499.00,
                'price_yearly' => 4990.00,
                'currency' => 'SAR',
                'max_employees' => -1, // unlimited
                'max_departments' => -1,
                'max_storage_gb' => 100,
                'features' => [
                    'employee_management', 'department_management', 'attendance_tracking',
                    'leave_management', 'basic_reports', 'document_storage',
                    'task_boards', 'performance_reviews', 'recruitment',
                    'onboarding', 'asset_management', 'payroll_support',
                    'announcements', 'advanced_roles', 'advanced_analytics',
                    'audit_logs', 'api_access', 'custom_branding',
                    'priority_support', 'sso_integration',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
                'trial_days' => 30,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }

        // Assign Professional plan to existing companies + create trial subscriptions
        $professionalPlan = Plan::where('slug', 'professional')->first();

        foreach (Company::all() as $company) {
            $company->update([
                'plan_id' => $professionalPlan->id,
                'subscription_status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'is_active' => true,
                'registered_at' => $company->created_at,
            ]);

            Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $professionalPlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addDays(14),
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'price' => $professionalPlan->price_monthly,
            ]);
        }
    }
}
