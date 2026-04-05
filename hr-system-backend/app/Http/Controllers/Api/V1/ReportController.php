<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function employeeReport(ReportFilterRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $data = $this->reportService->employeeReport($companyId, $request->validated());

        return response()->json(['data' => $data]);
    }

    public function attendanceReport(ReportFilterRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $data = $this->reportService->attendanceReport($companyId, $request->validated());

        return response()->json(['data' => $data]);
    }

    public function leaveReport(ReportFilterRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $data = $this->reportService->leaveReport($companyId, $request->validated());

        return response()->json(['data' => $data]);
    }

    public function taskReport(ReportFilterRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $data = $this->reportService->taskPerformanceReport($companyId, $request->validated());

        return response()->json(['data' => $data]);
    }

    public function overdueTaskReport(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $data = $this->reportService->overdueTaskReport($companyId);

        return response()->json(['data' => $data]);
    }

    public function documentExpiryReport(ReportFilterRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $days = $request->integer('days', 30);
        $data = $this->reportService->documentExpiryReport($companyId, $days);

        return response()->json(['data' => $data]);
    }
}
