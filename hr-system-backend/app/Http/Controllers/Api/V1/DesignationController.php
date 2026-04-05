<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DesignationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Http\Resources\DesignationResource;
use App\Services\DesignationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DesignationController extends Controller
{
    public function __construct(private DesignationService $designationService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $designations = $this->designationService->paginateWithFilters(
            $request->only(['company_id', 'search', 'sort_by', 'sort_dir']),
            $request->integer('per_page', 15)
        );

        return DesignationResource::collection($designations);
    }

    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $dto = DesignationDTO::fromArray($request->validated());
        $designation = $this->designationService->createDesignation($dto);

        return (new DesignationResource($designation))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $designation): DesignationResource
    {
        $result = $this->designationService->findOrFail($designation);

        return new DesignationResource($result);
    }

    public function update(UpdateDesignationRequest $request, int $designation): DesignationResource
    {
        $dto = DesignationDTO::fromArray($request->validated());
        $result = $this->designationService->updateDesignation($designation, $dto);

        return new DesignationResource($result);
    }

    public function destroy(int $designation): JsonResponse
    {
        $this->designationService->deleteDesignation($designation);

        return response()->json(['message' => 'Designation deleted successfully.'], Response::HTTP_OK);
    }
}
