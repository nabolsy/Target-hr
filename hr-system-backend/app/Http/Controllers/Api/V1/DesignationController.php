<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DesignationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Http\Resources\DesignationResource;
use App\Models\Designation;
use App\Services\Access\PermissionService;
use App\Services\DesignationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DesignationController extends Controller
{
    public function __construct(
        private DesignationService $designationService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // Read access is open within the company so designation
        // dropdowns on the employee form work for everyone.
        $companyId = $request->user()->company_id;

        // Optional department filter — the employee form uses this to
        // narrow designations when a department is selected, falling
        // back to company-wide ones when department_id is null.
        $deptId = $request->input('department_id');

        $query = Designation::where('company_id', $companyId)
            ->with('department:id,name')
            ->withCount('employees')
            ->orderBy('level')
            ->orderBy('name');

        if ($deptId !== null && $deptId !== '') {
            // When a department is specified, show its designations
            // PLUS all company-wide designations (department_id=null).
            $query->where(function ($q) use ($deptId) {
                $q->where('department_id', $deptId)->orWhereNull('department_id');
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        return DesignationResource::collection($query->get());
    }

    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $this->assertAdmin($request->user());

        $dto = DesignationDTO::fromArray($request->validated());
        $designation = $this->designationService->createDesignation($dto);
        $designation->load('department:id,name');

        return (new DesignationResource($designation))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $designation): DesignationResource
    {
        $result = $this->designationService->findOrFail($designation);
        $result->load('department:id,name');
        $result->loadCount('employees');

        return new DesignationResource($result);
    }

    public function update(UpdateDesignationRequest $request, int $designation): DesignationResource
    {
        $this->assertAdmin($request->user());

        $dto = DesignationDTO::fromArray($request->validated());
        $result = $this->designationService->updateDesignation($designation, $dto);
        $result->load('department:id,name');

        return new DesignationResource($result);
    }

    public function destroy(Request $request, int $designation): JsonResponse
    {
        $this->assertAdmin($request->user());

        $this->designationService->deleteDesignation($designation);

        return response()->json(['message' => 'Designation deleted successfully.'], Response::HTTP_OK);
    }

    private function assertAdmin(\App\Models\User $user): void
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        $scope = $this->permissions->getScope($user, 'department.manage');
        if ($scope !== 'company') {
            throw new AuthorizationException('Only administrators can manage designations.');
        }
    }
}
