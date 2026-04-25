<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-off + repeatable backfill: ensures every user's Spatie role assignment
 * matches their legacy `users.role` enum column. Idempotent.
 *
 * Run after editing config/role_access.php or after a fresh seeder run on
 * an existing database where users predate the Spatie integration.
 *
 * Usage:
 *   php artisan users:sync-roles
 *   php artisan users:sync-roles --dry
 */
class SyncUserRoles extends Command
{
    protected $signature = 'users:sync-roles {--dry : Print intended actions without writing}';

    protected $description = 'Mirror the legacy users.role enum into Spatie role assignments';

    public function handle(): int
    {
        $map = [
            UserRole::SuperAdmin->value        => 'Super Admin',
            UserRole::CompanyAdmin->value      => 'Company Admin',
            UserRole::HrManager->value         => 'HR Manager',
            UserRole::DepartmentManager->value => 'Department Manager',
            UserRole::Employee->value          => 'Employee',
        ];

        $dry = $this->option('dry');
        $changed = 0;
        $skipped = 0;

        foreach (User::all() as $user) {
            $enum = $user->role?->value;
            $target = $enum ? ($map[$enum] ?? null) : null;

            if (! $target) {
                $skipped++;
                continue;
            }

            $current = $user->getRoleNames()->all();

            if (in_array($target, $current, true)) {
                $skipped++;
                continue;
            }

            $this->line(sprintf('  %s [%s] → assigning %s', $user->email, $enum, $target));

            if (! $dry) {
                $user->assignRole($target);
            }

            $changed++;
        }

        $this->info(sprintf(
            '%s %d user(s), skipped %d (already in sync or no enum).',
            $dry ? 'Would update' : 'Updated',
            $changed,
            $skipped
        ));

        return self::SUCCESS;
    }
}
