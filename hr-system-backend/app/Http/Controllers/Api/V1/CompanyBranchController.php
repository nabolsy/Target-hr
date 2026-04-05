<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CompanyBranchDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyBranchRequest;
use App\Http\Requests\UpdateCompanyBranchRequest;
use App\Http\Resources\CompanyBranchResource;
use App\Models\CompanyBranch;
use App\Services\CompanyBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CompanyBranchController extends Controller
{
    public function __construct(private CompanyBranchService $branchService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->input('company_id', auth()->user()->company_id);

        $branches = $this->branchService->getByCompany($companyId);

        return CompanyBranchResource::collection($branches);
    }

    public function store(StoreCompanyBranchRequest $request): JsonResponse
    {
        $dto = CompanyBranchDTO::fromArray($request->validated());
        $branch = $this->branchService->createBranch($dto);

        return (new CompanyBranchResource($branch))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(CompanyBranch $companyBranch): CompanyBranchResource
    {
        return new CompanyBranchResource($companyBranch);
    }

    public function update(UpdateCompanyBranchRequest $request, CompanyBranch $companyBranch): JsonResponse
    {
        $dto = CompanyBranchDTO::fromArray($request->validated());
        $result = $this->branchService->updateBranch($companyBranch->id, $dto);

        return (new CompanyBranchResource($result))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(CompanyBranch $companyBranch): JsonResponse
    {
        $this->branchService->deleteBranch($companyBranch->id);

        return response()->json([
            'message' => 'Branch deleted successfully.',
        ], Response::HTTP_OK);
    }
}
