<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DepartmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    public function __construct(private DepartmentService $departmentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $departments = $this->departmentService->paginateWithFilters(
            $request->only(['company_id', 'status', 'parent_id', 'search', 'sort_by', 'sort_dir']),
            $request->integer('per_page', 15)
        );

        return DepartmentResource::collection($departments);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $dto = DepartmentDTO::fromArray($request->validated());
        $department = $this->departmentService->createDepartment($dto);

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $department): DepartmentResource
    {
        $result = $this->departmentService->findOrFail($department);

        return new DepartmentResource($result->load(['parent', 'manager', 'children']));
    }

    public function update(UpdateDepartmentRequest $request, int $department): DepartmentResource
    {
        $dto = DepartmentDTO::fromArray($request->validated());
        $result = $this->departmentService->updateDepartment($department, $dto);

        return new DepartmentResource($result);
    }

    public function destroy(int $department): JsonResponse
    {
        $this->departmentService->deleteDepartment($department);

        return response()->json(['message' => 'Department deleted successfully.'], Response::HTTP_OK);
    }
}
