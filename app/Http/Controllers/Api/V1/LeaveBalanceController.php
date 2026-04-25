<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLeaveBalanceRequest;
use App\Http\Resources\LeaveBalanceResource;
use App\Models\LeaveBalance;
use App\Services\Access\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveBalanceController extends Controller
{
    public function __construct(private PermissionService $permissions)
    {
    }

    /**
     * GET /api/v1/leave-balances
     *
     * Params:
     *   employee_id — required: the employee whose balances to return
     *   year        — optional: defaults to the current year
     *
     * A user can view their own balances at any time. Viewing someone
     * else's balances requires leave.view permission AND the target
     * employee being inside the caller's visible-employee set.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $employeeId = $request->integer('employee_id');

        if (! $employeeId) {
            // Fall back to the caller's own employee if they omit the filter,
            // so the endpoint is useful from the "My Leave" page without a
            // separate self-specific route.
            $employeeId = $this->permissions->employeeIdForSelf($user) ?? 0;
        }

        $this->assertCanViewBalances($user, $employeeId);

        $year = $request->integer('year', now()->year);

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->orderBy('leave_type_id')
            ->get();

        return LeaveBalanceResource::collection($balances);
    }

    /**
     * PUT /api/v1/leave-balances/{balance}
     *
     * Admin-only manual adjustment. Recomputes remaining_days from
     * total_days - used_days so the three fields stay consistent.
     */
    public function update(UpdateLeaveBalanceRequest $request, LeaveBalance $leaveBalance): LeaveBalanceResource
    {
        $this->assertAdmin($request->user());

        $tenantId = $request->user()->company_id;
        if ($tenantId && (int) $leaveBalance->company_id !== (int) $tenantId) {
            throw new AuthorizationException('Leave balance is outside your company.');
        }

        $updates = $request->validated();
        $total = isset($updates['total_days']) ? (float) $updates['total_days'] : (float) $leaveBalance->total_days;
        $used  = isset($updates['used_days'])  ? (float) $updates['used_days']  : (float) $leaveBalance->used_days;

        // Clamp: used can't exceed total.
        if ($used > $total) {
            $used = $total;
        }

        $leaveBalance->update([
            'total_days' => $total,
            'used_days' => $used,
            'remaining_days' => max(0, $total - $used),
        ]);

        $leaveBalance->load('leaveType');

        return new LeaveBalanceResource($leaveBalance);
    }

    private function assertCanViewBalances(\App\Models\User $user, int $employeeId): void
    {
        $selfId = $this->permissions->employeeIdForSelf($user);
        if ($selfId === $employeeId) {
            return;
        }

        if (! $this->permissions->can($user, 'leave.view')) {
            throw new AuthorizationException('You are not allowed to view this employee\'s leave balances.');
        }

        $visible = $this->permissions->visibleEmployeeIds($user, 'leave.view');
        if ($visible === null) {
            return; // company scope
        }

        if (empty($visible) || ! in_array($employeeId, $visible, true)) {
            throw new AuthorizationException('This employee is outside your department scope.');
        }
    }

    private function assertAdmin(\App\Models\User $user): void
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        $scope = $this->permissions->getScope($user, 'leave.approve');
        if ($scope !== 'company') {
            throw new AuthorizationException('Only administrators can adjust leave balances.');
        }
    }
}
