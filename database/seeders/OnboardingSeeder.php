<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OnboardingChecklist;
use App\Models\OnboardingChecklistItem;
use App\Models\OnboardingTemplate;
use App\Models\OnboardingTemplateItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OnboardingSeeder extends Seeder
{
    public function run(): void
    {
        $templateItems = [
            ['title' => 'Complete tax forms (W-4, I-9)', 'assigned_to_role' => 'hr', 'sort_order' => 1],
            ['title' => 'Set up workstation and equipment', 'assigned_to_role' => 'it', 'sort_order' => 2],
            ['title' => 'Create email and system accounts', 'assigned_to_role' => 'it', 'sort_order' => 3],
            ['title' => 'Review employee handbook', 'assigned_to_role' => 'employee', 'sort_order' => 4],
            ['title' => 'Meet with department manager', 'assigned_to_role' => 'manager', 'sort_order' => 5],
            ['title' => 'Complete safety training', 'assigned_to_role' => 'employee', 'sort_order' => 6],
            ['title' => 'Set up direct deposit', 'assigned_to_role' => 'hr', 'sort_order' => 7],
            ['title' => 'Office tour and introductions', 'assigned_to_role' => 'manager', 'sort_order' => 8],
        ];

        foreach (Company::all() as $company) {
            $hrUser = User::where('company_id', $company->id)->where('role', 'hr_manager')->first() ?? User::where('company_id', $company->id)->where('role', 'company_admin')->first();

            $template = OnboardingTemplate::create([
                'company_id' => $company->id,
                'name' => 'Standard Onboarding',
                'type' => 'onboarding',
                'is_default' => true,
            ]);

            foreach ($templateItems as $item) {
                OnboardingTemplateItem::create([
                    'template_id' => $template->id,
                    'title' => $item['title'],
                    'description' => fake()->sentence(),
                    'is_required' => true,
                    'assigned_to_role' => $item['assigned_to_role'],
                    'sort_order' => $item['sort_order'],
                ]);
            }

            // Get 2 newest employees
            $newEmployees = Employee::where('company_id', $company->id)
                ->orderByDesc('join_date')
                ->take(2)
                ->get();

            foreach ($newEmployees as $employee) {
                $checklist = OnboardingChecklist::create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'template_id' => $template->id,
                    'type' => 'onboarding',
                    'status' => 'in_progress',
                    'started_at' => $employee->join_date,
                    'created_by' => $hrUser->id,
                ]);

                foreach ($templateItems as $i => $item) {
                    $completed = $i < 4; // First 4 items completed
                    OnboardingChecklistItem::create([
                        'checklist_id' => $checklist->id,
                        'title' => $item['title'],
                        'description' => fake()->sentence(),
                        'is_required' => true,
                        'is_completed' => $completed,
                        'completed_by' => $completed ? $hrUser->id : null,
                        'completed_at' => $completed ? Carbon::parse($employee->join_date)->addDays($i + 1) : null,
                        'assigned_to' => $hrUser->id,
                        'due_date' => Carbon::parse($employee->join_date)->addDays(($i + 1) * 2)->format('Y-m-d'),
                        'sort_order' => $item['sort_order'],
                    ]);
                }
            }
        }
    }
}
