<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\SalaryStructureDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalaryStructureRequest;
use App\Http\Requests\UpdateSalaryStructureRequest;
use App\Http\Resources\SalaryStructureResource;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SalaryStructureController extends Controller
{
    public function __construct(private PayrollService $payrollService)
    {
    }

    public function show(Employee $employee): JsonResponse
    {
        $structure = $this->payrollService->getSalaryStructureByEmployee($employee->id);

        if (! $structure) {
            return response()->json([
                'message' => 'No salary structure found for this employee.',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new SalaryStructureResource($structure))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function store(StoreSalaryStructureRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dto = SalaryStructureDTO::fromArray($validated);
        $components = $validated['components'] ?? [];

        $structure = $this->payrollService->createSalaryStructure($dto, $components);

        return (new SalaryStructureResource($structure))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateSalaryStructureRequest $request, SalaryStructure $salaryStructure): JsonResponse
    {
        $validated = $request->validated();
        $dto = SalaryStructureDTO::fromArray($validated);
        $components = $validated['components'] ?? [];

        $structure = $this->payrollService->updateSalaryStructure(
            $salaryStructure->id,
            $dto,
            $components
        );

        return (new SalaryStructureResource($structure))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
