<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CompanyDTO;
use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Requests\UpdateCompanyStatusRequest;
use App\Http\Resources\CompanyCollection;
use App\Http\Resources\CompanyResource;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function __construct(private CompanyService $companyService)
    {
    }

    public function index(Request $request): CompanyCollection
    {
        $companies = $this->companyService->paginateWithFilters(
            $request->only(['status', 'subscription_plan', 'search', 'industry', 'sort_by', 'sort_dir']),
            $request->integer('per_page', 15)
        );

        return new CompanyCollection($companies);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $dto = CompanyDTO::fromArray($request->validated());
        $company = $this->companyService->createCompany($dto);

        return (new CompanyResource($company))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $company): CompanyResource
    {
        $result = $this->companyService->findOrFail($company);

        return new CompanyResource($result);
    }

    public function update(UpdateCompanyRequest $request, int $company): CompanyResource
    {
        $dto = CompanyDTO::fromArray($request->validated());
        $result = $this->companyService->updateCompany($company, $dto);

        return new CompanyResource($result);
    }

    public function destroy(int $company): JsonResponse
    {
        $this->companyService->deleteCompany($company);

        return response()->json(['message' => 'Company deleted successfully.'], Response::HTTP_OK);
    }

    public function updateStatus(UpdateCompanyStatusRequest $request, int $company): CompanyResource
    {
        $status = CompanyStatus::from($request->validated('status'));
        $result = $this->companyService->changeStatus($company, $status);

        return new CompanyResource($result);
    }
}
