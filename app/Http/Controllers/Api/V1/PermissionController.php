<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get(['id', 'name', 'guard_name']);

        // Group by module prefix (portion before the first dot) for easier UI rendering.
        $grouped = $permissions->groupBy(function (Permission $p) {
            $parts = explode('.', $p->name, 2);
            return $parts[0] ?? 'other';
        });

        return response()->json([
            'data' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
