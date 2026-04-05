<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AssetDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignAssetRequest;
use App\Http\Requests\ReturnAssetRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetAssignmentResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'company_id', 'status', 'category', 'condition',
            'search', 'sort_by', 'sort_dir',
        ]);

        if (empty($filters['company_id'])) {
            $filters['company_id'] = auth()->user()->company_id;
        }

        $assets = $this->assetService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return AssetResource::collection($assets);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $dto = AssetDTO::fromArray($request->validated());
        $asset = $this->assetService->createAsset($dto);

        return (new AssetResource($asset))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Asset $asset): AssetResource
    {
        $asset->load(['currentAssignment.employee', 'currentEmployee']);

        return new AssetResource($asset);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): AssetResource
    {
        $dto = AssetDTO::fromArray($request->validated());
        $updatedAsset = $this->assetService->updateAsset($asset->id, $dto);

        return new AssetResource($updatedAsset);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $this->assetService->delete($asset->id);

        return response()->json(['message' => 'Asset deleted successfully.'], Response::HTTP_OK);
    }

    public function assign(AssignAssetRequest $request, Asset $asset): JsonResponse
    {
        $validated = $request->validated();

        $assignment = $this->assetService->assignToEmployee(
            $asset->id,
            $validated['employee_id'],
            $validated['condition_on_assign'],
            $validated['notes'] ?? null
        );

        return (new AssetAssignmentResource($assignment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function return(ReturnAssetRequest $request, Asset $asset): AssetAssignmentResource
    {
        $validated = $request->validated();

        $assignment = $this->assetService->returnAsset(
            $asset->id,
            $validated['condition_on_return'],
            $validated['notes'] ?? null
        );

        return new AssetAssignmentResource($assignment);
    }

    public function history(Asset $asset): AnonymousResourceCollection
    {
        $assignments = $this->assetService->getHistory($asset->id);

        return AssetAssignmentResource::collection($assignments);
    }

    public function byEmployee(int $employee): AnonymousResourceCollection
    {
        $assets = $this->assetService->getByEmployee($employee);

        return AssetResource::collection($assets);
    }
}
