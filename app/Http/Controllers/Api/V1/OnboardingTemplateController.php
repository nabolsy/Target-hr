<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OnboardingTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOnboardingTemplateRequest;
use App\Http\Requests\UpdateOnboardingTemplateRequest;
use App\Http\Resources\OnboardingTemplateResource;
use App\Models\OnboardingTemplate;
use App\Services\OnboardingTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OnboardingTemplateController extends Controller
{
    public function __construct(private OnboardingTemplateService $templateService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->integer('company_id', auth()->user()->company_id);
        $templates = $this->templateService->getByCompany($companyId);

        return OnboardingTemplateResource::collection($templates);
    }

    public function store(StoreOnboardingTemplateRequest $request): JsonResponse
    {
        $dto = OnboardingTemplateDTO::fromArray($request->validated());
        $template = $this->templateService->createTemplate($dto);

        return (new OnboardingTemplateResource($template))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(OnboardingTemplate $onboardingTemplate): OnboardingTemplateResource
    {
        $onboardingTemplate->load('items');

        return new OnboardingTemplateResource($onboardingTemplate);
    }

    public function update(UpdateOnboardingTemplateRequest $request, OnboardingTemplate $onboardingTemplate): OnboardingTemplateResource
    {
        $dto = OnboardingTemplateDTO::fromArray($request->validated());
        $template = $this->templateService->updateTemplate($onboardingTemplate->id, $dto);

        return new OnboardingTemplateResource($template);
    }

    public function destroy(OnboardingTemplate $onboardingTemplate): JsonResponse
    {
        $this->templateService->delete($onboardingTemplate->id);

        return response()->json(['message' => 'Onboarding template deleted successfully.'], Response::HTTP_OK);
    }
}
