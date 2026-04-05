<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class HolidayController extends Controller
{
    public function __construct(private HolidayService $holidayService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->company_id;
        $year = $request->integer('year', now()->year);

        $holidays = $this->holidayService->getByCompanyAndYear($companyId, $year);

        return HolidayResource::collection($holidays);
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = $this->holidayService->createHoliday($request->validated());

        return (new HolidayResource($holiday))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Holiday $holiday): HolidayResource
    {
        return new HolidayResource($holiday);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): HolidayResource
    {
        $result = $this->holidayService->updateHoliday($holiday->id, $request->validated());

        return new HolidayResource($result);
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $this->holidayService->deleteHoliday($holiday->id);

        return response()->json(['message' => 'Holiday deleted successfully.'], Response::HTTP_OK);
    }
}
