<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\PerformanceReview;
use App\Models\ReviewCycle;
use App\Models\ReviewMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PerformanceSeeder extends Seeder
{
    public function run(): void
    {
        $metrics = [
            ['name' => 'Technical Skills', 'description' => 'Proficiency in required technical competencies', 'weight' => 0.25],
            ['name' => 'Communication', 'description' => 'Effectiveness of written and verbal communication', 'weight' => 0.20],
            ['name' => 'Teamwork', 'description' => 'Collaboration and contribution to team goals', 'weight' => 0.20],
            ['name' => 'Initiative', 'description' => 'Proactive problem-solving and self-direction', 'weight' => 0.15],
            ['name' => 'Delivery', 'description' => 'Meeting deadlines and quality standards', 'weight' => 0.20],
        ];

        $goalTemplates = [
            'Complete advanced certification', 'Improve team velocity by 15%',
            'Mentor 2 junior team members', 'Reduce bug count by 20%',
            'Lead a cross-functional project', 'Achieve customer satisfaction score of 4.5+',
        ];

        $ratings = ['exceptional', 'exceeds_expectations', 'meets_expectations', 'meets_expectations', 'meets_expectations', 'below_expectations'];

        foreach (Company::all() as $company) {
            $adminUser = User::where("company_id", $company->id)->whereIn("role", ["company_admin", "hr_manager"])->first(); if (!$adminUser) continue;

            $cycle = ReviewCycle::create([
                'company_id' => $company->id,
                'name' => 'Annual Review ' . now()->year,
                'type' => 'annual',
                'start_date' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'status' => 'completed',
                'created_by' => $adminUser->id,
            ]);

            $employees = Employee::where('company_id', $company->id)->get();

            foreach ($employees as $employee) {
                $reviewerUser = $employee->manager
                    ? User::find($employee->manager->user_id)
                    : $adminUser;

                $rating = $ratings[array_rand($ratings)];
                $scoreMap = [
                    'exceptional' => rand(450, 500) / 100,
                    'exceeds_expectations' => rand(350, 449) / 100,
                    'meets_expectations' => rand(250, 349) / 100,
                    'below_expectations' => rand(150, 249) / 100,
                ];
                $score = $scoreMap[$rating];

                $review = PerformanceReview::create([
                    'company_id' => $company->id,
                    'review_cycle_id' => $cycle->id,
                    'employee_id' => $employee->id,
                    'reviewer_id' => $reviewerUser->id,
                    'type' => 'manager_review',
                    'overall_score' => $score,
                    'rating' => $rating,
                    'status' => 'submitted',
                    'manager_comments' => fake()->paragraph(),
                    'employee_comments' => fake()->paragraph(),
                    'goals_for_next_period' => fake()->sentence(),
                    'development_plan' => fake()->paragraph(),
                    'promotion_recommendation' => $rating === 'exceptional',
                    'submitted_at' => now()->subDays(rand(5, 30)),
                    'acknowledged_at' => now()->subDays(rand(1, 5)),
                ]);

                // Metrics for each review
                foreach ($metrics as $metric) {
                    ReviewMetric::create([
                        'performance_review_id' => $review->id,
                        'name' => $metric['name'],
                        'description' => $metric['description'],
                        'weight' => $metric['weight'],
                        'score' => rand(200, 500) / 100,
                        'comments' => fake()->sentence(),
                    ]);
                }

                // 1-2 goals per employee
                $goalCount = rand(1, 2);
                $selectedGoals = collect($goalTemplates)->random($goalCount);
                foreach ($selectedGoals as $goalTitle) {
                    Goal::create([
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'review_cycle_id' => $cycle->id,
                        'title' => $goalTitle,
                        'description' => fake()->paragraph(),
                        'target_value' => '100',
                        'current_value' => (string)rand(0, 100),
                        'unit' => 'percent',
                        'status' => fake()->randomElement(['not_started', 'in_progress', 'completed']),
                        'due_date' => Carbon::now()->addMonths(rand(1, 6))->format('Y-m-d'),
                    ]);
                }
            }
        }
    }
}
