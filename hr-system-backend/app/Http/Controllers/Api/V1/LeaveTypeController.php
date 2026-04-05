<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use App\Repositories\Interfaces\LeaveTypeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LeaveTypeController extends Controller
{
    public function __construct(private LeaveTypeRepositoryInterface $leaveTypeRepository)
    {
    }

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
        $data = $request->validated();
        $data['company_id'] = $data['company_id'] ?? $request->user()->company_id;

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
        $result = $this->leaveTypeRepository->update($leaveType->id, $request->validated());

        return new LeaveTypeResource($result);
    }

    public function destroy(LeaveType $leaveType): JsonResponse
    {
        $this->leaveTypeRepository->delete($leaveType->id);

        return response()->json(['message' => 'Leave type deleted successfully.'], Response::HTTP_OK);
    }
}
