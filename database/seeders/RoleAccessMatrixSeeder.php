<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Materializes config/role_access.php into Spatie's roles + permissions
 * tables. Idempotent — safe to re-run after editing the config.
 *
 * Existing role rows (Super Admin, Company Admin, etc.) are kept;
 * permissions previously granted to them via earlier seeders stay attached
 * unless this seeder explicitly grants the same set of permissions, in
 * which case syncPermissions only writes the union.
 *
 * Note: this seeder uses syncPermissions() PER ROLE on its current
 * matrix slice — so previously granted permissions OUTSIDE the new
 * singular-form set (e.g. the legacy plural employees.view from
 * SystemPermissionSeeder) remain attached.
 */
class RoleAccessMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('role_access');
        $guard = 'web';

        // 1. Ensure every permission row exists.
        foreach ($config['permissions'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        // 2. For each role in the matrix, ensure the role exists and
        //    grant the listed permissions WITHOUT removing previously
        //    attached permissions (use givePermissionTo, not syncPermissions).
        foreach ($config['roles'] as $roleName => $matrix) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

            $permissionNames = array_keys($matrix);
            $permissions = Permission::whereIn('name', $permissionNames)
                ->where('guard_name', $guard)
                ->get();

            $role->givePermissionTo($permissions);
        }

        // 3. Always make Super Admin omnipotent over everything in the
        //    permissions table, so adding a new permission later doesn't
        //    silently lock super-admin out of it.
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $superAdmin->givePermissionTo(Permission::where('guard_name', $guard)->get());

        // 4. Reset Spatie's cache so the next request sees the new state.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(sprintf(
            'Role access matrix synced: %d permissions, %d roles processed.',
            count($config['permissions']),
            count($config['roles'])
        ));
    }
}
