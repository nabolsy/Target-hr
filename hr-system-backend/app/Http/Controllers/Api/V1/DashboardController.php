<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function companyDashboard(): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        if (! $companyId) {
            return response()->json(['message' => 'No company associated with this user.'], 403);
        }

        // Pass the user so DashboardService can apply per-role scope.
        // Company Admin / HR Manager still get the full company view;
        // Department Manager / Employee see their subtree only.
        $data = $this->dashboardService->getCompanyDashboard($companyId, $user);

        return response()->json(['data' => $data]);
    }

    public function superAdminDashboard(): JsonResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $this->dashboardService->getSuperAdminDashboard();

        return response()->json(['data' => $data]);
    }
}
