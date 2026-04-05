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
        $companyId = auth()->user()->company_id;

        if (! $companyId) {
            return response()->json(['message' => 'No company associated with this user.'], 403);
        }

        $data = $this->dashboardService->getCompanyDashboard($companyId);

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
