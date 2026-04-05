<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LeaveRequestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLeaveRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveService $leaveService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $leaveRequests = $this->leaveService->paginateWithFilters(
            $request->only([
                'employee_id', 'leave_type_id', 'status',
                'start_date', 'end_date', 'year', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return LeaveRequestResource::collection($leaveRequests);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $dto = LeaveRequestDTO::fromArray($request->validated());
        $leaveRequest = $this->leaveService->applyForLeave($dto);

        return (new LeaveRequestResource($leaveRequest))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $leaveRequest->load(['employee', 'leaveType', 'approver']);

        return new LeaveRequestResource($leaveRequest);
    }

    public function approve(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $result = $this->leaveService->approveLeave($leaveRequest->id);

        return new LeaveRequestResource($result);
    }

    public function reject(RejectLeaveRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $result = $this->leaveService->rejectLeave($leaveRequest->id, $request->validated('rejection_reason'));

        return new LeaveRequestResource($result);
    }

    public function cancel(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $result = $this->leaveService->cancelLeave($leaveRequest->id);

        return new LeaveRequestResource($result);
    }

    public function balance(Request $request, int $employeeId): AnonymousResourceCollection
    {
        $year = $request->integer('year', now()->year);
        $balances = $this->leaveService->getBalance($employeeId, $year);

        return LeaveBalanceResource::collection($balances);
    }
}
