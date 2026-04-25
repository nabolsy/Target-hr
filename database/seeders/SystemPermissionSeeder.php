<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Mirrors the live sidebar tabs in MainLayout.jsx — every
        // permission listed below maps to a UI control the user can
        // see today. Modules that are hidden from the sidebar
        // (attendance, announcements, performance, onboarding,
        // recruitment) are intentionally omitted so admins can't grant
        // permissions that don't gate anything.
        $permissions = [
            // ORGANIZATION
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'departments.view', 'departments.create', 'departments.edit', 'departments.delete',

            // HR
            'leave.view', 'leave.create', 'leave.approve', 'leave.delete',

            // WORK
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'documents.view', 'documents.upload', 'documents.delete',
            'assets.view', 'assets.create', 'assets.edit', 'assets.delete',

            // PAYROLL
            'payroll.view', 'payroll.manage',

            // INSIGHTS
            'reports.view',
            'notifications.view', 'notifications.send', 'notifications.delete',

            // SYSTEM
            'audit-logs.view',
            'settings.view', 'settings.manage',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
        ];

        // Sweep out leftover Spatie permissions for hidden modules
        // (attendance, announcements, performance, onboarding,
        // recruitment) — both singular and plural shapes.
        //
        // IMPORTANT: We do NOT delete the active-module singular forms
        // (employee.view, department.manage, board.view, task.create,
        // document.view, report.view, notification.send, etc.).
        // Those are required by PermissionService — the scope-aware
        // permission system that policies, controllers, and
        // EmployeeResource consult via `permissions->can($user, 'x.y')`
        // and `permissions->getScope($user, 'x.y')`. The dotted plural
        // forms above coexist with them: Spatie role checks use plural,
        // PermissionService uses singular.
        $obsoletePrefixes = [
            'attendance.', 'attendances.',
            'announcement.', 'announcements.',
            'performance.', 'performances.',
            'onboarding.', 'onboardings.',
            'recruitment.', 'recruitments.',
        ];
        Permission::query()
            ->where(function ($q) use ($obsoletePrefixes) {
                foreach ($obsoletePrefixes as $prefix) {
                    $q->orWhere('name', 'like', $prefix.'%');
                }
            })
            ->delete();

        // Underscore-form leftovers from RolePermissionSeeder for
        // hidden modules. Keep active-module underscore perms — older
        // policies may still reference them.
        $obsoleteUnderscoreSuffixes = [
            '_attendance', '_announcements', '_performance', '_onboarding', '_recruitment',
        ];
        Permission::query()
            ->where(function ($q) use ($obsoleteUnderscoreSuffixes) {
                foreach ($obsoleteUnderscoreSuffixes as $suffix) {
                    $q->orWhere('name', 'like', '%'.$suffix);
                }
            })
            ->delete();

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Grant all new permissions to Super Admin and Company Admin.
        $all = Permission::whereIn('name', $permissions)->get();

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($all);

        $companyAdmin = Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => 'web']);
        $companyAdmin->givePermissionTo($all->reject(fn ($p) => in_array($p->name, ['roles.create', 'roles.edit', 'roles.delete'], true)));

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
