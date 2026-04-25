<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable scope-application trait for repositories that accept the
 * private filter keys injected by controllers via PermissionService.
 *
 * Filter key contract (all optional):
 *
 *   __visible_department_ids
 *     null  → no filter (company scope)
 *     []    → deny all (returns 0 rows)
 *     [...] → WHERE department_id IN (...)
 *
 *   __visible_employee_ids
 *     null  → no filter
 *     []    → deny all
 *     [...] → WHERE employee_id IN (...)
 *
 *   __self_user_id
 *     int   → WHERE user_id = ? (only used by Employee model)
 *
 * The trait pulls each key from the filters array (mutating $filters by
 * reference so callers don't have to clean up) and applies it to the
 * given query builder. Returns $query for fluent chaining.
 */
trait AppliesAccessScope
{
    /**
     * Apply the three access-scope filter keys to the query and strip them
     * from the filters array so downstream filter handlers don't see them.
     *
     * @param  Builder  $query
     * @param  array<string,mixed>  $filters  passed by reference — trait keys are unset
     * @param  array{department?: string, employee?: string}  $columns  override column names (default: department_id / employee_id)
     */
    protected function applyAccessScope(Builder $query, array &$filters, array $columns = []): Builder
    {
        $deptColumn = $columns['department'] ?? 'department_id';
        $employeeColumn = $columns['employee'] ?? 'employee_id';

        if (array_key_exists('__visible_department_ids', $filters)) {
            $visible = $filters['__visible_department_ids'];
            unset($filters['__visible_department_ids']);

            if (is_array($visible)) {
                if (empty($visible)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn($deptColumn, $visible);
                }
            }
        }

        if (array_key_exists('__visible_employee_ids', $filters)) {
            $visible = $filters['__visible_employee_ids'];
            unset($filters['__visible_employee_ids']);

            if (is_array($visible)) {
                if (empty($visible)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn($employeeColumn, $visible);
                }
            }
        }

        // Board-scoped filter for models linked via board_id (Task).
        // Null = no restriction. [] = deny.
        if (array_key_exists('__visible_board_ids', $filters)) {
            $visible = $filters['__visible_board_ids'];
            unset($filters['__visible_board_ids']);

            if (is_array($visible)) {
                if (empty($visible)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('board_id', $visible);
                }
            }
        }

        if (array_key_exists('__self_user_id', $filters)) {
            $selfUserId = $filters['__self_user_id'];
            unset($filters['__self_user_id']);

            if ($selfUserId !== null) {
                $query->where('user_id', $selfUserId);
            }
        }

        return $query;
    }
}
