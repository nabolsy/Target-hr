<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ShiftDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Repositories\Interfaces\ShiftRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ShiftController extends Controller
{
    public function __construct(private ShiftRepositoryInterface $shiftRepository)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $companyId = auth()->user()->company_id;
        $shifts = $this->shiftRepository->getByCompany($companyId);

        return ShiftResource::collection($shifts);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $dto = ShiftDTO::fromArray(array_merge(
            $request->validated(),
            ['company_id' => auth()->user()->company_id]
        ));

        $shift = $this->shiftRepository->create($dto->toArray());

        return (new ShiftResource($shift))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $shift): ShiftResource
    {
        $result = $this->shiftRepository->findOrFail($shift);

        return new ShiftResource($result);
    }

    public function update(UpdateShiftRequest $request, int $shift): ShiftResource
    {
        $result = $this->shiftRepository->update($shift, $request->validated());

        return new ShiftResource($result);
    }

    public function destroy(int $shift): JsonResponse
    {
        $this->shiftRepository->delete($shift);

        return response()->json(['message' => 'Shift deleted successfully.'], Response::HTTP_OK);
    }
}
