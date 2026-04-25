<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LeaveRequestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLeaveRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\Access\PermissionService;
use App\Services\LeaveService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LeaveRequestController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $this->permissions->can($user, 'leave.view') && ! $this->permissions->can($user, 'leave.create')) {
            throw new AuthorizationException('You are not allowed to view leave requests.');
        }

        $filters = $request->only([
            'employee_id', 'leave_type_id', 'status',
            'start_date', 'end_date', 'year', 'sort_by', 'sort_dir',
        ]);

        // Inject visible employees for the leave.view scope. If the user
        // only has leave.create (self-service), fall back to listing their
        // own employee rows.
        $perm = $this->permissions->can($user, 'leave.view') ? 'leave.view' : 'leave.create';
        $filters['__visible_employee_ids'] = $this->permissions
            ->visibleEmployeeIds($user, $perm);

        $leaveRequests = $this->leaveService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return LeaveRequestResource::collection($leaveRequests);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->permissions->can($user, 'leave.create')) {
            throw new AuthorizationException('You are not allowed to create leave requests.');
        }

        // Resolve employee_id. The frontend doesn't send it (would be a
        // security smell — users could request leave on someone else's
        // behalf). Self-service users implicitly file FOR THEMSELVES;
        // managers/HR can pass it explicitly.
        //
        // Two-link reality: some seeders set `users.employee_id` while
        // others set `employees.user_id` (and the Department Manager
        // seeder leaves the User-side NULL). We use PermissionService
        // which checks BOTH directions, so a user with only the reverse
        // link still resolves cleanly.
        $payload = $request->validated();
        $payload['employee_id'] = $request->input('employee_id')
            ?: $user->employee_id
            ?: $this->permissions->employeeIdForSelf($user);
        if (empty($payload['employee_id'])) {
            return response()->json([
                'message' => 'Your user account is not linked to an employee record. Contact your administrator.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Persist the optional attachment to disk and pass the storage
        // path to the DTO. The validator already capped size + types.
        if ($request->hasFile('attachment')) {
            $payload['attachment_path'] = $request->file('attachment')
                ->store('leave-attachments', 'public');
        }

        $dto = LeaveRequestDTO::fromArray($payload);
        $leaveRequest = $this->leaveService->applyForLeave($dto);

        return (new LeaveRequestResource($leaveRequest))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->assertCanSeeLeaveRequest($request->user(), $leaveRequest);

        $leaveRequest->load(['employee', 'leaveType', 'approver']);

        return new LeaveRequestResource($leaveRequest);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->assertCanApproveLeaveRequest($request->user(), $leaveRequest);

        $result = $this->leaveService->approveLeave($leaveRequest->id);

        return new LeaveRequestResource($result);
    }

    public function reject(RejectLeaveRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->assertCanApproveLeaveRequest($request->user(), $leaveRequest);

        $result = $this->leaveService->rejectLeave($leaveRequest->id, $request->validated('rejection_reason'));

        return new LeaveRequestResource($result);
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        // Owner can always cancel their own request; approvers can cancel
        // anything in their scope.
        $user = $request->user();
        $selfId = $this->permissions->employeeIdForSelf($user);

        if ($selfId !== (int) $leaveRequest->employee_id) {
            $this->assertCanApproveLeaveRequest($user, $leaveRequest);
        }

        $result = $this->leaveService->cancelLeave($leaveRequest->id);

        return new LeaveRequestResource($result);
    }

    public function balance(Request $request, int $employeeId): AnonymousResourceCollection
    {
        $user = $request->user();
        $selfId = $this->permissions->employeeIdForSelf($user);

        // Non-self balance lookups require leave.view permission AND
        // the target employee to be in the visible set.
        if ($selfId !== $employeeId) {
            $visible = $this->permissions->visibleEmployeeIds($user, 'leave.view');
            if ($visible !== null && ! in_array($employeeId, $visible, true)) {
                throw new AuthorizationException('You are not allowed to view this employee\'s balance.');
            }
        }

        $year = $request->integer('year', now()->year);
        $balances = $this->leaveService->getBalance($employeeId, $year);

        return LeaveBalanceResource::collection($balances);
    }

    /**
     * GET /api/v1/employees/{id}/leave-summary
     *
     * One-shot profile widget for a single employee. Returns balances
     * for the requested year, the five most recent leave requests, the
     * upcoming approved requests, and a small totals payload so the
     * frontend profile tab can render without stitching together three
     * separate round-trips.
     *
     * Visibility is the same as every other per-employee leave read:
     * self is always allowed; anyone else needs leave.view AND the
     * target employee must be inside the caller's visible set.
     */
    public function summary(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();
        $selfId = $this->permissions->employeeIdForSelf($user);

        if ($selfId !== $employeeId) {
            if (! $this->permissions->can($user, 'leave.view')) {
                throw new AuthorizationException('You are not allowed to view this employee\'s leave summary.');
            }
            $visible = $this->permissions->visibleEmployeeIds($user, 'leave.view');
            if ($visible !== null && ! in_array($employeeId, $visible, true)) {
                throw new AuthorizationException('This employee is outside your department scope.');
            }
        }

        $year = $request->integer('year', now()->year);
        $today = now()->toDateString();

        $balances = \App\Models\LeaveBalance::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->orderBy('leave_type_id')
            ->get();

        $recentRequests = \App\Models\LeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $upcomingRequests = \App\Models\LeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '>=', $today)
            ->orderBy('start_date')
            ->take(5)
            ->get();

        // Totals for the tiny stat strip at the top of the profile tab.
        $pendingCount = \App\Models\LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->count();

        $totalEntitlement = (float) $balances->sum('total_days');
        $totalUsed        = (float) $balances->sum('used_days');
        $totalRemaining   = (float) $balances->sum('remaining_days');

        return response()->json([
            'data' => [
                'employee_id' => $employeeId,
                'year' => $year,
                'totals' => [
                    'total_entitlement' => round($totalEntitlement, 1),
                    'total_used' => round($totalUsed, 1),
                    'total_remaining' => round($totalRemaining, 1),
                    'pending_requests' => $pendingCount,
                    'upcoming_requests' => $upcomingRequests->count(),
                ],
                'balances' => LeaveBalanceResource::collection($balances)->resolve(),
                'recent_requests' => LeaveRequestResource::collection($recentRequests)->resolve(),
                'upcoming_requests' => LeaveRequestResource::collection($upcomingRequests)->resolve(),
            ],
        ]);
    }

    /**
     * A user can see a leave request if they own it (self scope) or if the
     * request's employee is in their visible-employee set for leave.view.
     */
    private function assertCanSeeLeaveRequest($user, LeaveRequest $leaveRequest): void
    {
        $selfId = $this->permissions->employeeIdForSelf($user);
        if ($selfId === (int) $leaveRequest->employee_id) {
            return;
        }

        $visible = $this->permissions->visibleEmployeeIds($user, 'leave.view');
        if ($visible === null) {
            return; // company scope
        }

        if (empty($visible) || ! in_array((int) $leaveRequest->employee_id, $visible, true)) {
            throw new AuthorizationException('You are not allowed to view this leave request.');
        }
    }

    /**
     * Approvers need leave.approve AND the request's employee must be in
     * their visible set for that permission.
     */
    private function assertCanApproveLeaveRequest($user, LeaveRequest $leaveRequest): void
    {
        if (! $this->permissions->can($user, 'leave.approve')) {
            throw new AuthorizationException('You are not allowed to approve leave requests.');
        }

        $visible = $this->permissions->visibleEmployeeIds($user, 'leave.approve');
        if ($visible === null) {
            return;
        }

        if (empty($visible) || ! in_array((int) $leaveRequest->employee_id, $visible, true)) {
            throw new AuthorizationException('This leave request is outside your department scope.');
        }
    }
}
