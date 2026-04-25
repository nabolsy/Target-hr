<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Foundation
            CompanySeeder::class,
            RolePermissionSeeder::class,
            // Role Access Matrix — the single source of truth for the
            // 9 scoped roles and 30 permissions defined in
            // config/role_access.php. Runs after RolePermissionSeeder
            // so it additively extends any permissions already seeded.
            RoleAccessMatrixSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            ShiftSeeder::class,
            HolidaySeeder::class,

            // Users & Employees
            UserEmployeeSeeder::class,

            // Core HR modules
            AttendanceSeeder::class,
            LeaveSeeder::class,
            DocumentSeeder::class,
            SalaryPayrollSeeder::class,

            // Task management
            TaskBoardSeeder::class,

            // Announcements
            AnnouncementSeeder::class,

            // Performance
            PerformanceSeeder::class,

            // Recruitment & Onboarding
            RecruitmentSeeder::class,
            OnboardingSeeder::class,

            // Assets
            AssetSeeder::class,

            // System logs
            NotificationSeeder::class,
            AuditLogSeeder::class,

            // SaaS Plans & Subscriptions
            PlanSeeder::class,
        ]);
    }
}
