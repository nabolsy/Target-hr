<?php

/*
|--------------------------------------------------------------------------
| Role Access Matrix
|--------------------------------------------------------------------------
|
| The single source of truth for which roles get which permissions and at
| what scope. Used by:
|
|   - RoleAccessMatrixSeeder  (creates Spatie roles + permissions + grants)
|   - PermissionService       (resolves a user's effective scope at runtime)
|
| Scopes (lowest → highest visibility):
|   self                  → only the user's own employee record / own row
|   own_department        → records belonging to the user's primary department
|   managed_department    → records belonging to departments where the user
|                            is the department manager (recursive children)
|   company               → every record in the user's company (no filter)
|
| The PermissionService takes the BROADEST scope across all roles assigned
| to a user. So if a user has both Employee (employee.view = own_department)
| and Recruiter (employee.view = company), they see the entire company.
|
| To grant a permission without scope semantics (i.e. a binary capability
| like "can publish announcements"), use scope `company` — it imposes no
| query-time filter.
|
| Adding a new role/permission:
|   1. Add the permission key to the `permissions` array.
|   2. Add a row in `roles` mapping that role to the new permission + scope.
|   3. Re-run `php artisan db:seed --class=RoleAccessMatrixSeeder` (idempotent).
|
*/

return [

    // Permissions are flat strings. Scope lives on the role↔permission edge.
    //
    // Hidden modules (attendance, announcements, performance, onboarding,
    // recruitment) are intentionally NOT listed here — they don't have
    // active sidebar tabs today, so PermissionService should never have
    // to resolve scope for them.
    'permissions' => [
        'employee.view',
        'employee.create',
        'employee.update',
        'employee.transfer',
        'employee.delete',
        'department.view',
        'department.manage',
        'leave.view',
        'leave.create',
        'leave.approve',
        'board.view',
        'board.manage',
        'task.create',
        'task.assign',
        'task.move',
        'task.update',
        'document.view',
        'document.upload',
        'document.manage',
        'report.view',
        'role.assign',
        'permission.manage',
        'payroll.view',
        'payroll.manage',
    ],

    // Role → [permission => scope].
    // Order matters only for documentation; PermissionService walks all roles.
    'roles' => [

        'Company Admin' => [
            // Full company-wide control over everything except the
            // super-admin platform plane.
            'employee.view'        => 'company',
            'employee.create'      => 'company',
            'employee.update'      => 'company',
            'employee.transfer'    => 'company',
            'employee.delete'      => 'company',
            'department.view'      => 'company',
            'department.manage'    => 'company',
            'leave.view'           => 'company',
            'leave.create'         => 'company',
            'leave.approve'        => 'company',
            'board.view'           => 'company',
            'board.manage'         => 'company',
            'task.create'          => 'company',
            'task.assign'          => 'company',
            'task.move'            => 'company',
            'task.update'          => 'company',
            'document.view'        => 'company',
            'document.upload'      => 'company',
            'document.manage'      => 'company',
            'report.view'          => 'company',
            'role.assign'          => 'company',
            'permission.manage'    => 'company',
            'payroll.view'         => 'company',
            'payroll.manage'       => 'company',
        ],

        'HR Manager' => [
            // Whole-company HR scope, no platform/role admin.
            'employee.view'        => 'company',
            'employee.create'      => 'company',
            'employee.update'      => 'company',
            'employee.transfer'    => 'company',
            'employee.delete'      => 'company',
            'department.view'      => 'company',
            'department.manage'    => 'company',
            'leave.view'           => 'company',
            'leave.create'         => 'company',
            'leave.approve'        => 'company',
            'board.view'           => 'company',
            'board.manage'         => 'company',
            'task.create'          => 'company',
            'task.assign'          => 'company',
            'task.move'            => 'company',
            'task.update'          => 'company',
            'document.view'        => 'company',
            'document.upload'      => 'company',
            'document.manage'      => 'company',
            'report.view'          => 'company',
            'payroll.view'         => 'company',
            'payroll.manage'       => 'company',
        ],

        'HR Staff' => [
            // Operational HR, no destructive actions, no payroll write.
            'employee.view'        => 'company',
            'employee.create'      => 'company',
            'employee.update'      => 'company',
            'department.view'      => 'company',
            'leave.view'           => 'company',
            'leave.create'         => 'company',
            'document.view'        => 'company',
            'document.upload'      => 'company',
            'report.view'          => 'company',
        ],

        'Department Manager' => [
            // Sees and acts on their managed department(s) + descendants.
            'employee.view'        => 'managed_department',
            'employee.update'      => 'managed_department',
            'department.view'      => 'managed_department',
            'leave.view'           => 'managed_department',
            'leave.approve'        => 'managed_department',
            'board.view'           => 'managed_department',
            'board.manage'         => 'managed_department',
            'task.create'          => 'managed_department',
            'task.assign'          => 'managed_department',
            'task.move'            => 'managed_department',
            'task.update'          => 'managed_department',
            'document.view'        => 'managed_department',
            'document.upload'      => 'managed_department',
            'report.view'          => 'managed_department',
            // Self-service that every employee gets too:
            'leave.create'         => 'self',
        ],

        'Team Lead' => [
            // Mid-tier — sees own department, can create/assign tasks
            // for own department but cannot edit employees.
            'employee.view'        => 'own_department',
            'department.view'      => 'own_department',
            'leave.view'           => 'own_department',
            'leave.approve'        => 'own_department',
            'board.view'           => 'own_department',
            'board.manage'         => 'own_department',
            'task.create'          => 'own_department',
            'task.assign'          => 'own_department',
            'task.move'            => 'own_department',
            'task.update'          => 'own_department',
            'document.view'        => 'own_department',
            'leave.create'         => 'self',
        ],

        'Payroll Officer' => [
            'employee.view'        => 'company',
            'department.view'      => 'company',
            'payroll.view'         => 'company',
            'payroll.manage'       => 'company',
            'document.view'        => 'company',
            'report.view'          => 'company',
            'leave.create'         => 'self',
        ],

        'Employee' => [
            // Self-service only.
            'employee.view'        => 'own_department', // colleagues read-only
            'department.view'      => 'own_department',
            'leave.view'           => 'self',
            'leave.create'         => 'self',
            'board.view'           => 'own_department',
            'task.create'          => 'own_department',
            'task.update'          => 'self', // their own tasks
            'document.view'        => 'self', // own documents
            'document.upload'      => 'self',
        ],

    ],

    // Scope precedence for "broadest wins" resolution. Higher index = broader.
    'scope_order' => [
        'self',
        'own_department',
        'managed_department',
        'company',
    ],
];
