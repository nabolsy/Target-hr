<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskBoardSeeder extends Seeder
{
    public function run(): void
    {
        $columnDefs = [
            ['name' => 'To Do', 'sort_order' => 1, 'color' => '#6B7280', 'is_done_column' => false],
            ['name' => 'In Progress', 'sort_order' => 2, 'color' => '#3B82F6', 'is_done_column' => false],
            ['name' => 'Review', 'sort_order' => 3, 'color' => '#F59E0B', 'is_done_column' => false],
            ['name' => 'Done', 'sort_order' => 4, 'color' => '#10B981', 'is_done_column' => true],
        ];

        $labelColors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899'];
        $labelNames = ['Bug', 'Feature', 'Improvement', 'Documentation', 'Urgent', 'Design'];

        $taskTemplates = [
            'Engineering' => [
                'Implement user authentication API', 'Set up CI/CD pipeline', 'Optimize database queries',
                'Refactor payment module', 'Write unit tests for core services', 'Deploy staging environment',
                'Fix memory leak in background jobs', 'Update API documentation',
            ],
            'Human Resources' => [
                'Update employee handbook', 'Plan team building event', 'Review compensation benchmarks',
                'Conduct training needs analysis', 'Process new hire paperwork', 'Update leave policy',
                'Organize quarterly town hall', 'Review performance templates',
            ],
            'Finance' => [
                'Prepare quarterly budget report', 'Reconcile vendor payments', 'Audit expense reports',
                'Update tax compliance docs', 'Review insurance policies', 'Process payroll adjustments',
                'Analyze cost reduction opportunities', 'Prepare annual financial forecast',
            ],
            'Sales' => [
                'Update CRM pipeline data', 'Prepare Q2 sales forecast', 'Onboard new sales reps',
                'Create competitive analysis deck', 'Review pricing strategy', 'Plan client workshops',
                'Follow up on enterprise leads', 'Build partner program proposal',
            ],
            'Marketing' => [
                'Launch social media campaign', 'Redesign landing page', 'Write blog post series',
                'Analyze SEO performance', 'Plan product launch event', 'Create email newsletter',
                'Update brand guidelines', 'Produce product demo video',
            ],
        ];

        foreach (Company::all() as $company) {
            // Create labels
            $labels = [];
            foreach ($labelNames as $i => $name) {
                $labels[] = TaskLabel::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'color' => $labelColors[$i],
                ]);
            }

            $adminUser = User::where("company_id", $company->id)->whereIn("role", ["company_admin", "hr_manager"])->first(); if (!$adminUser) continue;
            $departments = Department::where('company_id', $company->id)->get();

            foreach ($departments as $dept) {
                $board = Board::create([
                    'company_id' => $company->id,
                    'name' => "{$dept->name} Board",
                    'description' => "Task board for the {$dept->name} department",
                    'department_id' => $dept->id,
                    'type' => 'department',
                    'is_archived' => false,
                    'created_by' => $adminUser->id,
                ]);

                $columns = [];
                foreach ($columnDefs as $colDef) {
                    $columns[] = BoardColumn::create(array_merge($colDef, ['board_id' => $board->id]));
                }

                $deptEmployees = Employee::where('company_id', $company->id)
                    ->where('department_id', $dept->id)->get();
                $deptUsers = User::whereIn('id', $deptEmployees->pluck('user_id'))->get();

                $tasks = $taskTemplates[$dept->name] ?? $taskTemplates['Engineering'];
                $priorities = ['low', 'medium', 'medium', 'high', 'urgent', 'medium', 'low', 'high'];

                foreach ($tasks as $idx => $title) {
                    $col = $columns[$idx % count($columns)];
                    $creator = $deptUsers->isNotEmpty() ? $deptUsers->random() : $adminUser;
                    $completion = $col->is_done_column ? 100 : ($col->name === 'Review' ? rand(70, 90) : ($col->name === 'In Progress' ? rand(20, 60) : 0));

                    $task = Task::create([
                        'company_id' => $company->id,
                        'board_id' => $board->id,
                        'column_id' => $col->id,
                        'title' => $title,
                        'description' => fake()->paragraph(),
                        'creator_id' => $creator->id,
                        'priority' => $priorities[$idx] ?? 'medium',
                        'start_date' => Carbon::now()->subDays(rand(1, 14))->format('Y-m-d'),
                        'due_date' => Carbon::now()->addDays(rand(1, 30))->format('Y-m-d'),
                        'estimated_hours' => rand(4, 40),
                        'actual_hours' => $completion > 0 ? rand(2, 30) : null,
                        'completion_percentage' => $completion,
                        'sort_order' => $idx,
                    ]);

                    // Assign 1-2 labels
                    $taskLabels = collect($labels)->random(rand(1, 2));
                    foreach ($taskLabels as $label) {
                        DB::table('label_task')->insert([
                            'task_id' => $task->id,
                            'task_label_id' => $label->id,
                        ]);
                    }

                    // Assign employee if available
                    if ($deptEmployees->isNotEmpty()) {
                        $assignee = $deptEmployees->random();
                        DB::table('task_assignees')->insert([
                            'task_id' => $task->id,
                            'employee_id' => $assignee->id,
                            'assigned_by' => $creator->id,
                            'assigned_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
