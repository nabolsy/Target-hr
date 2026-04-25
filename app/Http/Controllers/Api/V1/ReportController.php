<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\Access\PermissionService;
use App\Services\ReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private PermissionService $permissions,
    ) {
    }

    /**
     * All report endpoints gate on `report.view`. Scope-aware aggregates
     * are applied by passing the user's visible-employee set into each
     * ReportService method: Company Admin / HR Manager get null (no
     * filter), Department Manager / Employee get their subtree's
     * employee IDs.
     */
    private function guard(): void
    {
        if (! $this->permissions->can(auth()->user(), 'report.view')) {
            throw new AuthorizationException('You are not allowed to view reports.');
        }
    }

    private function visibleIds(): ?array
    {
        return $this->permissions->visibleEmployeeIds(auth()->user(), 'report.view');
    }

    public function employeeReport(ReportFilterRequest $request): JsonResponse
    {
        $this->guard();

        $companyId = auth()->user()->company_id;
        $data = $this->reportService->employeeReport(
            $companyId,
            $request->validated(),
            $this->visibleIds()
        );

        return response()->json(['data' => $data]);
    }

    public function attendanceReport(ReportFilterRequest $request): JsonResponse
    {
        $this->guard();

        $companyId = auth()->user()->company_id;
        $data = $this->reportService->attendanceReport(
            $companyId,
            $request->validated(),
            $this->visibleIds()
        );

        return response()->json(['data' => $data]);
    }

    public function leaveReport(ReportFilterRequest $request): JsonResponse
    {
        $this->guard();

        $companyId = auth()->user()->company_id;
        $data = $this->reportService->leaveReport(
            $companyId,
            $request->validated(),
            $this->visibleIds()
        );

        return response()->json(['data' => $data]);
    }

    public function taskReport(ReportFilterRequest $request): JsonResponse
    {
        $this->guard();

        $companyId = auth()->user()->company_id;
        $data = $this->reportService->taskPerformanceReport(
            $companyId,
            $request->validated(),
            $this->visibleIds()
        );

        return response()->json(['data' => $data]);
    }

    public function overdueTaskReport(): JsonResponse
    {
        $this->guard();

        $companyId = auth()->user()->company_id;
        $data = $this->reportService->overdueTaskReport($companyId, $this->visibleIds());

        return response()->json(['data' => $data]);
    }

    public function documentExpiryReport(ReportFilterRequest $request): JsonResponse
    {
        $this->guard();

        $companyId = auth()->user()->company_id;
        $days = $request->integer('days', 30);
        $data = $this->reportService->documentExpiryReport(
            $companyId,
            $days,
            $this->visibleIds()
        );

        return response()->json(['data' => $data]);
    }
}
