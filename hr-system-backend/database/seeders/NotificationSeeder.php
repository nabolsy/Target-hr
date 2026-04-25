<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\NotificationLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $notificationTemplates = [
            ['type' => 'leave_approved', 'title' => 'Leave Request Approved', 'body' => 'Your leave request has been approved by your manager.'],
            ['type' => 'leave_rejected', 'title' => 'Leave Request Rejected', 'body' => 'Your leave request has been rejected. Please contact HR for details.'],
            ['type' => 'attendance_reminder', 'title' => 'Check-in Reminder', 'body' => 'Don\'t forget to check in for today\'s shift.'],
            ['type' => 'task_assigned', 'title' => 'New Task Assigned', 'body' => 'You have been assigned a new task. Please check your task board.'],
            ['type' => 'payroll_generated', 'title' => 'Payslip Available', 'body' => 'Your payslip for last month is now available for download.'],
            ['type' => 'announcement', 'title' => 'New Company Announcement', 'body' => 'A new announcement has been posted. Please review it.'],
            ['type' => 'document_expiring', 'title' => 'Document Expiring Soon', 'body' => 'One of your documents is expiring within 30 days. Please renew it.'],
            ['type' => 'review_pending', 'title' => 'Performance Review Pending', 'body' => 'You have a pending performance review to complete.'],
            ['type' => 'birthday', 'title' => 'Happy Birthday!', 'body' => 'Wishing you a wonderful birthday from the entire team!'],
            ['type' => 'onboarding_task', 'title' => 'Onboarding Task Due', 'body' => 'You have an onboarding checklist item due soon.'],
        ];

        foreach (Company::all() as $company) {
            $users = User::where('company_id', $company->id)->get();

            foreach ($users as $user) {
                $selected = collect($notificationTemplates)->shuffle()->take(10);

                foreach ($selected as $i => $template) {
                    $isRead = rand(0, 1);
                    $createdAt = Carbon::now()->subDays(rand(0, 14))->subHours(rand(0, 23));

                    NotificationLog::create([
                        'company_id' => $company->id,
                        'user_id' => $user->id,
                        'type' => $template['type'],
                        'title' => $template['title'],
                        'body' => $template['body'],
                        'data' => json_encode(['source' => 'system', 'priority' => $i < 3 ? 'high' : 'normal']),
                        'read_at' => $isRead ? $createdAt->copy()->addHours(rand(1, 12)) : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }
    }
}
