<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Resources\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->input('company_id', auth()->user()->company_id);

        $periods = $this->payrollService->getPeriods($companyId);

        return PayrollPeriodResource::collection($periods);
    }

    public function generate(GeneratePayrollRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $period = $this->payrollService->generatePayroll(
            $companyId,
            $request->validated('month'),
            $request->validated('year')
        );

        return (new PayrollPeriodResource($period))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(PayrollPeriod $payrollPeriod): PayrollPeriodResource
    {
        // Eager-load everything the frontend tabs need in one trip:
        // records with their employee + department so the Overview,
        // Runs, and Payslips tabs can render without per-row queries.
        $payrollPeriod->load([
            'records.employee:id,first_name,last_name,email,employee_id_number,department_id,company_id',
            'records.employee.department:id,name',
        ]);

        return new PayrollPeriodResource($payrollPeriod);
    }

    public function lock(PayrollPeriod $payrollPeriod): JsonResponse
    {
        $period = $this->payrollService->lockPeriod($payrollPeriod->id);

        return (new PayrollPeriodResource($period))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function export(PayrollPeriod $payrollPeriod): \Illuminate\Http\Response
    {
        $csv = $this->payrollService->exportToCsv($payrollPeriod->id);

        $filename = "payroll_{$payrollPeriod->year}_{$payrollPeriod->month}.csv";

        return response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
