<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Legacy CRUD-style permission seeder ({action}_{module}).
 *
 * SystemPermissionSeeder is now the source of truth for the dotted
 * (`module.action`) permissions consumed by the role picker UI. This
 * seeder remains in place to keep older policies that still reference
 * the underscore form working, but it is scoped strictly to modules
 * that currently have a sidebar entry.
 *
 * Hidden modules (attendance, announcements, performance, onboarding,
 * recruitment) are intentionally omitted from BOTH the permission
 * matrix and the per-role grant lists below — granting them would be
 * misleading since the UI doesn't expose them today.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'employees', 'departments', 'designations', 'leaves',
            'payroll', 'documents', 'assets', 'tasks',
            'reports', 'settings',
        ];

        $actions = ['view', 'create', 'update', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$action}_{$module}", 'guard_name' => 'web']
                );
            }
        }

        // Cross-cutting capabilities
        Permission::firstOrCreate(['name' => 'manage_roles', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_company', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_audit_logs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'approve_leaves', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'generate_payroll', 'guard_name' => 'web']);

        // Super Admin - all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Company Admin - all except manage_roles (Super Admin owns role mgmt)
        $companyAdmin = Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => 'web']);
        $companyAdmin->givePermissionTo(Permission::where('name', '!=', 'manage_roles')->get());

        // HR Manager
        $hrManager = Role::firstOrCreate(['name' => 'HR Manager', 'guard_name' => 'web']);
        $hrPerms = [];
        foreach (['employees', 'departments', 'designations', 'leaves', 'payroll', 'documents', 'assets', 'reports'] as $mod) {
            foreach ($actions as $act) {
                $hrPerms[] = "{$act}_{$mod}";
            }
        }
        $hrPerms[] = 'approve_leaves';
        $hrPerms[] = 'generate_payroll';
        $hrManager->givePermissionTo($hrPerms);

        // Department Manager
        $deptManager = Role::firstOrCreate(['name' => 'Department Manager', 'guard_name' => 'web']);
        $deptPerms = ['view_employees', 'view_leaves', 'view_tasks', 'view_documents', 'view_reports'];
        $deptPerms = array_merge($deptPerms, [
            'create_tasks', 'update_tasks', 'delete_tasks',
            'approve_leaves',
        ]);
        $deptManager->givePermissionTo($deptPerms);

        // Employee
        $employee = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $employee->givePermissionTo([
            'view_employees', 'view_leaves',
            'create_leaves', 'view_tasks', 'create_tasks', 'update_tasks',
            'view_documents', 'view_assets',
        ]);
    }
}
