<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AttendanceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustmentRequest;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Services\Access\PermissionService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $this->permissions->can($user, 'attendance.view')) {
            throw new AuthorizationException('You are not allowed to view attendance records.');
        }

        $filters = $request->only([
            'employee_id', 'status', 'date', 'date_from', 'date_to',
            'shift_id', 'sort_by', 'sort_dir',
        ]);

        $filters['__visible_employee_ids'] = $this->permissions
            ->visibleEmployeeIds($user, 'attendance.view');

        $records = $this->attendanceService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return AttendanceRecordResource::collection($records);
    }

    public function show(Request $request, int $attendanceRecord): AttendanceRecordResource
    {
        $record = $this->attendanceService->findOrFail($attendanceRecord);

        $user = $request->user();
        if (! $this->permissions->can($user, 'attendance.view')) {
            throw new AuthorizationException('You are not allowed to view this attendance record.');
        }

        $visible = $this->permissions->visibleEmployeeIds($user, 'attendance.view');
        if ($visible !== null && ! in_array((int) $record->employee_id, $visible, true)) {
            throw new AuthorizationException('This attendance record is outside your scope.');
        }

        $record->load(['employee', 'shift', 'adjustmentRequests']);

        return new AttendanceRecordResource($record);
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = AttendanceDTO::fromArray([
            'company_id' => auth()->user()->company_id,
            'employee_id' => $validated['employee_id'],
            'date' => now(),
            'check_in' => now(),
            'shift_id' => $validated['shift_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'ip_address' => $validated['ip_address'] ?? $request->ip(),
        ]);

        $record = $this->attendanceService->checkIn($dto);

        return (new AttendanceRecordResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function checkOut(CheckOutRequest $request): AttendanceRecordResource
    {
        $validated = $request->validated();

        $record = $this->attendanceService->checkOut(
            $validated['attendance_record_id'],
            $validated['notes'] ?? null
        );

        return new AttendanceRecordResource($record);
    }

    public function monthlyReport(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $records = $this->attendanceService->getMonthlyReport(
            $request->integer('employee_id'),
            $request->integer('month'),
            $request->integer('year')
        );

        return AttendanceRecordResource::collection($records);
    }

    public function requestAdjustment(AdjustmentRequest $request): JsonResponse
    {
        $adjustment = $this->attendanceService->requestAdjustment($request->validated());

        return response()->json([
            'message' => 'Adjustment request submitted successfully.',
            'data' => $adjustment,
        ], Response::HTTP_CREATED);
    }

    public function approveAdjustment(int $adjustmentId): JsonResponse
    {
        $adjustment = $this->attendanceService->approveAdjustment($adjustmentId, auth()->id());

        return response()->json([
            'message' => 'Adjustment request approved successfully.',
            'data' => $adjustment,
        ]);
    }

    public function rejectAdjustment(int $adjustmentId): JsonResponse
    {
        $adjustment = $this->attendanceService->rejectAdjustment($adjustmentId, auth()->id());

        return response()->json([
            'message' => 'Adjustment request rejected.',
            'data' => $adjustment,
        ]);
    }
}
