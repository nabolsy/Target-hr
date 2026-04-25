<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use App\Repositories\Interfaces\LeaveTypeRepositoryInterface;
use App\Services\Access\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LeaveTypeController extends Controller
{
    public function __construct(
        private LeaveTypeRepositoryInterface $leaveTypeRepository,
        private PermissionService $permissions,
    ) {
    }

    /**
     * Every authenticated user can SEE the list of leave types (needed
     * for the request form dropdown). Write operations are gated on
     * company-scope leave.approve — i.e. Company Admin and HR Manager.
     * Anything narrower than company scope (Department Manager) cannot
     * manage leave types.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->company_id;
        $leaveTypes = $companyId
            ? $this->leaveTypeRepository->getActiveByCompany($companyId)
            : $this->leaveTypeRepository->all();

        return LeaveTypeResource::collection($leaveTypes);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $this->assertAdmin($request->user());

        $data = $request->validated();
        $data['company_id'] = $data['company_id'] ?? $request->user()->company_id;
        // Auto-generate slug from the name when not explicitly provided —
        // the slug has a unique [company_id, slug] index, so leaving it
        // nullable would blow up on the second seed.
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $leaveType = $this->leaveTypeRepository->create($data);

        return (new LeaveTypeResource($leaveType))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(LeaveType $leaveType): LeaveTypeResource
    {
        return new LeaveTypeResource($leaveType);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): LeaveTypeResource
    {
        $this->assertAdmin($request->user());

        $result = $this->leaveTypeRepository->update($leaveType->id, $request->validated());

        return new LeaveTypeResource($result);
    }

    public function destroy(Request $request, LeaveType $leaveType): JsonResponse
    {
        $this->assertAdmin($request->user());

        // SoftDeletes is now enabled on LeaveType, so this is reversible.
        $this->leaveTypeRepository->delete($leaveType->id);

        return response()->json(['message' => 'Leave type deleted successfully.'], Response::HTTP_OK);
    }

    /**
     * Leave type / balance management is restricted to users with
     * company-wide leave.approve (Company Admin, HR Manager, Super Admin).
     * Department Managers have leave.approve at managed_department scope
     * and must not be allowed to mutate the shared leave type catalog.
     */
    private function assertAdmin(\App\Models\User $user): void
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        $scope = $this->permissions->getScope($user, 'leave.approve');
        if ($scope !== 'company') {
            throw new AuthorizationException('Only administrators can manage leave types.');
        }
    }
}
