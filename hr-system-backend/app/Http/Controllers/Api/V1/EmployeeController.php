<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\EmployeeDTO;
use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeStatusRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employeeService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $employees = $this->employeeService->paginateWithFilters(
            $request->only([
                'company_id', 'status', 'department_id', 'designation_id',
                'employment_type', 'manager_id', 'search', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return EmployeeResource::collection($employees);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $dto = EmployeeDTO::fromArray($request->validated());
        $employee = $this->employeeService->createEmployee($dto);

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Employee $employee): EmployeeResource
    {
        $employee->load(['department', 'designation', 'manager', 'user']);

        return new EmployeeResource($employee);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $dto = EmployeeDTO::fromArray($request->validated());
        $result = $this->employeeService->updateEmployee($employee->id, $dto);

        return new EmployeeResource($result);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->employeeService->deleteEmployee($employee->id);

        return response()->json(['message' => 'Employee deleted successfully.'], Response::HTTP_OK);
    }

    public function updateStatus(UpdateEmployeeStatusRequest $request, Employee $employee): EmployeeResource
    {
        $status = EmployeeStatus::from($request->validated('status'));
        $result = $this->employeeService->changeStatus($employee->id, $status);

        return new EmployeeResource($result);
    }
}
