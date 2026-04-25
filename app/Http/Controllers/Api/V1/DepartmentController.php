<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DepartmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\Access\PermissionService;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentService $departmentService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Department::class);

        $user = $request->user();

        $filters = $request->only(['company_id', 'status', 'parent_id', 'branch_id', 'search', 'sort_by', 'sort_dir']);

        if ($user && $user->company_id) {
            $filters['company_id'] = $user->company_id;
        }

        // Inject visible department IDs from the user's effective
        // department.view scope. null means no filter (company scope).
        $filters['__visible_department_ids'] = $this->permissions
            ->visibleDepartmentIds($user, 'department.view');

        $departments = $this->departmentService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return DepartmentResource::collection($departments);
    }

    /**
     * Return the full department hierarchy for the current user's company
     * as a nested tree. Used by the hierarchy view on the frontend.
     */
    public function tree(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Department::class);

        $companyId = $request->integer('company_id') ?: $request->user()->company_id;
        $tree = $this->departmentService->getTree($companyId);

        return DepartmentResource::collection($tree);
    }

    /**
     * Per-department stats bundle used by DepartmentDetail.jsx for the
     * summary widgets. Gated via the existing view policy — the caller
     * must be able to see the department to see its stats.
     */
    public function stats(int $department): JsonResponse
    {
        $model = $this->departmentService->findOrFail($department);

        $this->authorize('view', $model);

        $data = $this->departmentService->getStats($department);

        return response()->json(['data' => $data]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $dto = DepartmentDTO::fromArray($request->validated());
        $department = $this->departmentService->createDepartment($dto);

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $department): DepartmentResource
    {
        $result = $this->departmentService->findOrFail($department);

        $this->authorize('view', $result);

        return new DepartmentResource(
            $result->load(['parent', 'manager', 'branch', 'children.manager'])
                ->loadCount(['employees', 'children'])
        );
    }

    public function update(UpdateDepartmentRequest $request, int $department): DepartmentResource
    {
        $model = $this->departmentService->findOrFail($department);

        $this->authorize('update', $model);

        $dto = DepartmentDTO::fromArray($request->validated());
        $result = $this->departmentService->updateDepartment($department, $dto);

        return new DepartmentResource($result);
    }

    public function destroy(int $department): JsonResponse
    {
        $model = $this->departmentService->findOrFail($department);

        $this->authorize('delete', $model);

        $this->departmentService->deleteDepartment($department);

        return response()->json(['message' => 'Department deleted successfully.'], Response::HTTP_OK);
    }

    /**
     * Flip a department's active/inactive status.
     * PATCH /departments/{department}/status
     */
    public function toggleStatus(int $department): DepartmentResource
    {
        $model = $this->departmentService->findOrFail($department);

        $this->authorize('update', $model);

        $result = $this->departmentService->toggleStatus($department);

        return new DepartmentResource($result->load(['parent', 'manager', 'branch']));
    }

    /**
     * Assign a user as department manager.
     * POST /departments/{department}/manager  { user_id }
     */
    public function assignManager(Request $request, int $department): DepartmentResource
    {
        $model = $this->departmentService->findOrFail($department);

        $this->authorize('assignManager', $model);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $result = $this->departmentService->assignManager($department, (int) $data['user_id']);

        return new DepartmentResource($result->load(['manager']));
    }

    /**
     * Remove the department manager.
     * DELETE /departments/{department}/manager
     */
    public function removeManager(int $department): DepartmentResource
    {
        $model = $this->departmentService->findOrFail($department);

        $this->authorize('assignManager', $model);

        $result = $this->departmentService->removeManager($department);

        return new DepartmentResource($result->load(['manager']));
    }
}
