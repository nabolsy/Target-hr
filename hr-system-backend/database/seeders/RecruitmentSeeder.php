<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Department;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use App\Models\JobOpening;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        $jobTemplates = [
            ['title' => 'Senior Software Engineer', 'type' => 'full_time', 'dept' => 'Engineering', 'min' => 90000, 'max' => 130000],
            ['title' => 'Product Coordinator', 'type' => 'full_time', 'dept' => 'Product', 'min' => 50000, 'max' => 70000],
            ['title' => 'Sales Representative', 'type' => 'full_time', 'dept' => 'Sales', 'min' => 45000, 'max' => 65000],
        ];

        $stages = ['applied', 'screening', 'interview', 'technical_test', 'hr_interview'];
        $candidateNames = [
            ['Alex', 'Johnson'], ['Maria', 'Santos'], ['Chris', 'Kim'],
            ['Priya', 'Patel'], ['Jordan', 'Williams'], ['Yuki', 'Tanaka'],
            ['Omar', 'Hassan'], ['Sofia', 'Reyes'], ['Liam', 'Murphy'],
            ['Nina', 'Volkov'], ['Aiden', 'Clark'], ['Zara', 'Ahmed'],
            ['Tyler', 'Scott'], ['Maya', 'Singh'], ['Noah', 'Baker'],
        ];

        foreach (Company::all() as $company) {
            $adminUser = User::where("company_id", $company->id)->whereIn("role", ["company_admin", "hr_manager"])->first(); if (!$adminUser) continue;
            $companyUsers = User::where('company_id', $company->id)->get();
            $nameIdx = 0;

            foreach ($jobTemplates as $job) {
                $dept = Department::where("company_id", $company->id)->where("name", $job["dept"])->first(); if (!$dept) $dept = Department::where("company_id", $company->id)->first();

                $opening = JobOpening::create([
                    'company_id' => $company->id,
                    'department_id' => $dept->id,
                    'title' => $job['title'],
                    'description' => fake()->paragraphs(3, true),
                    'requirements' => "- 3+ years experience\n- Strong communication skills\n- Team player\n- Relevant degree preferred",
                    'employment_type' => $job['type'],
                    'location' => $company->city . ', ' . $company->state,
                    'salary_range_min' => $job['min'],
                    'salary_range_max' => $job['max'],
                    'positions_count' => rand(1, 3),
                    'status' => 'open',
                    'created_by' => $adminUser->id,
                    'published_at' => Carbon::now()->subDays(rand(10, 30)),
                    'closes_at' => Carbon::now()->addDays(rand(15, 45)),
                ]);

                // 5 candidates per opening at different stages
                for ($c = 0; $c < 5; $c++) {
                    $name = $candidateNames[$nameIdx % count($candidateNames)];
                    $nameIdx++;
                    $stage = $stages[$c % count($stages)];

                    $candidate = Candidate::create([
                        'company_id' => $company->id,
                        'job_opening_id' => $opening->id,
                        'first_name' => $name[0],
                        'last_name' => $name[1],
                        'email' => strtolower($name[0]) . '.' . strtolower($name[1]) . rand(1, 99) . '@email.com',
                        'phone' => '+1-555-' . rand(1000, 9999),
                        'cv_path' => "cvs/{$company->id}/" . strtolower($name[0]) . '_' . strtolower($name[1]) . '_cv.pdf',
                        'stage' => $stage,
                        'status' => 'active',
                        'source' => fake()->randomElement(['linkedin', 'website', 'referral', 'job_board']),
                        'applied_at' => Carbon::now()->subDays(rand(5, 25)),
                    ]);

                    // Create interview for candidates past screening
                    if (in_array($stage, ['interview', 'technical_test', 'hr_interview'])) {
                        $interviewer = $companyUsers->random();
                        $interview = Interview::create([
                            'company_id' => $company->id,
                            'candidate_id' => $candidate->id,
                            'interviewer_id' => $interviewer->id,
                            'scheduled_at' => Carbon::now()->addDays(rand(1, 14)),
                            'duration_minutes' => fake()->randomElement([30, 45, 60]),
                            'type' => $stage === 'technical_test' ? 'technical' : fake()->randomElement(['phone', 'video', 'in_person']),
                            'location' => fake()->randomElement(['Zoom Meeting', 'Conference Room A', 'Google Meet', null]),
                            'status' => fake()->randomElement(['scheduled', 'completed']),
                        ]);

                        if ($interview->status === 'completed') {
                            InterviewFeedback::create([
                                'interview_id' => $interview->id,
                                'user_id' => $interviewer->id,
                                'rating' => rand(2, 5),
                                'strengths' => fake()->sentence(),
                                'weaknesses' => fake()->sentence(),
                                'recommendation' => fake()->randomElement(['strong_hire', 'hire', 'no_hire', 'strong_no_hire']),
                                'comments' => fake()->paragraph(),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
