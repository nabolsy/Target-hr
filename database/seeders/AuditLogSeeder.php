<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            ['action' => 'created', 'type' => 'App\\Models\\Employee', 'desc' => 'Created new employee record'],
            ['action' => 'updated', 'type' => 'App\\Models\\Employee', 'desc' => 'Updated employee information'],
            ['action' => 'created', 'type' => 'App\\Models\\LeaveRequest', 'desc' => 'Submitted leave request'],
            ['action' => 'updated', 'type' => 'App\\Models\\LeaveRequest', 'desc' => 'Leave request status changed'],
            ['action' => 'created', 'type' => 'App\\Models\\AttendanceRecord', 'desc' => 'Attendance check-in recorded'],
            ['action' => 'updated', 'type' => 'App\\Models\\AttendanceRecord', 'desc' => 'Attendance record updated'],
            ['action' => 'created', 'type' => 'App\\Models\\Task', 'desc' => 'New task created'],
            ['action' => 'updated', 'type' => 'App\\Models\\Task', 'desc' => 'Task status updated'],
            ['action' => 'created', 'type' => 'App\\Models\\Announcement', 'desc' => 'New announcement published'],
            ['action' => 'updated', 'type' => 'App\\Models\\Company', 'desc' => 'Company settings updated'],
            ['action' => 'created', 'type' => 'App\\Models\\EmployeeDocument', 'desc' => 'Document uploaded'],
            ['action' => 'deleted', 'type' => 'App\\Models\\EmployeeDocument', 'desc' => 'Document removed'],
            ['action' => 'created', 'type' => 'App\\Models\\Asset', 'desc' => 'New asset registered'],
            ['action' => 'updated', 'type' => 'App\\Models\\Asset', 'desc' => 'Asset assignment changed'],
            ['action' => 'created', 'type' => 'App\\Models\\JobOpening', 'desc' => 'Job opening published'],
        ];

        foreach (Company::all() as $company) {
            $users = User::where('company_id', $company->id)->get();

            for ($i = 0; $i < 50; $i++) {
                $template = $actions[array_rand($actions)];
                $user = $users->random();
                $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

                AuditLog::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'auditable_type' => $template['type'],
                    'auditable_id' => rand(1, 50),
                    'action' => $template['action'],
                    'old_values' => $template['action'] !== 'created' ? ['status' => 'old_value'] : null,
                    'new_values' => ['status' => 'new_value'],
                    'ip_address' => '192.168.1.' . rand(10, 254),
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
