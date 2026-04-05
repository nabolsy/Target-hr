<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CompanySettingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanySettingRequest;
use App\Http\Resources\CompanySettingResource;
use App\Services\CompanySettingService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanySettingController extends Controller
{
    public function __construct(private CompanySettingService $settingService)
    {
    }

    public function show(): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $setting = $this->settingService->get($companyId);

        if (! $setting) {
            return response()->json([
                'message' => 'No settings found for this company.',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new CompanySettingResource($setting))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function update(UpdateCompanySettingRequest $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $dto = CompanySettingDTO::fromArray($request->validated());

        $setting = $this->settingService->update($companyId, $dto);

        return (new CompanySettingResource($setting))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
