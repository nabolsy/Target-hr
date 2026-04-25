<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CompanyBranchDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyBranchRequest;
use App\Http\Requests\UpdateCompanyBranchRequest;
use App\Http\Resources\CompanyBranchResource;
use App\Models\CompanyBranch;
use App\Services\Access\PermissionService;
use App\Services\CompanyBranchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CompanyBranchController extends Controller
{
    public function __construct(
        private CompanyBranchService $branchService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // Everyone inside the company can SEE the list (needed for
        // branch dropdowns on department/employee forms). Writes are
        // gated on the admin check below.
        $companyId = $request->user()->company_id;

        $branches = CompanyBranch::where('company_id', $companyId)
            ->with('manager:id,first_name,last_name,email')
            ->withCount(['employees', 'departments'])
            ->orderBy('name')
            ->get();

        return CompanyBranchResource::collection($branches);
    }

    public function store(StoreCompanyBranchRequest $request): JsonResponse
    {
        $this->assertAdmin($request->user());

        $data = $request->validated();
        $data['company_id'] = $data['company_id'] ?? $request->user()->company_id;

        $dto = CompanyBranchDTO::fromArray($data);
        $branch = $this->branchService->createBranch($dto);
        $branch->load('manager:id,first_name,last_name,email');

        return (new CompanyBranchResource($branch))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(CompanyBranch $companyBranch): CompanyBranchResource
    {
        $companyBranch->load('manager:id,first_name,last_name,email');
        $companyBranch->loadCount(['employees', 'departments']);

        return new CompanyBranchResource($companyBranch);
    }

    public function update(UpdateCompanyBranchRequest $request, CompanyBranch $companyBranch): JsonResponse
    {
        $this->assertAdmin($request->user());

        $dto = CompanyBranchDTO::fromArray($request->validated());
        $result = $this->branchService->updateBranch($companyBranch->id, $dto);
        $result->load('manager:id,first_name,last_name,email');

        return (new CompanyBranchResource($result))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(Request $request, CompanyBranch $companyBranch): JsonResponse
    {
        $this->assertAdmin($request->user());

        $this->branchService->deleteBranch($companyBranch->id);

        return response()->json([
            'message' => 'Branch deleted successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Writes are gated on department.manage at company scope. That
     * matches the existing authority tier for organizational structure
     * (departments) — Company Admin / HR Manager / Super Admin pass;
     * Department Manager and below are blocked. Reused to avoid
     * introducing a separate `branch.manage` permission.
     */
    private function assertAdmin(\App\Models\User $user): void
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        $scope = $this->permissions->getScope($user, 'department.manage');
        if ($scope !== 'company') {
            throw new AuthorizationException('Only administrators can manage branches.');
        }
    }
}
