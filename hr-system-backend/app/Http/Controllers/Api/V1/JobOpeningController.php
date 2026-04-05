<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\JobOpeningDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobOpeningRequest;
use App\Http\Requests\UpdateJobOpeningRequest;
use App\Http\Resources\CandidateResource;
use App\Http\Resources\JobOpeningResource;
use App\Models\JobOpening;
use App\Repositories\Interfaces\CandidateRepositoryInterface;
use App\Services\RecruitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class JobOpeningController extends Controller
{
    public function __construct(
        private RecruitmentService $recruitmentService,
        private CandidateRepositoryInterface $candidateRepository,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $jobOpenings = $this->recruitmentService->paginateJobOpenings(
            $request->only([
                'company_id', 'department_id', 'status', 'employment_type',
                'search', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return JobOpeningResource::collection($jobOpenings);
    }

    public function store(StoreJobOpeningRequest $request): JsonResponse
    {
        $dto = JobOpeningDTO::fromArray($request->validated());
        $jobOpening = $this->recruitmentService->createJobOpening($dto);

        return (new JobOpeningResource($jobOpening->load(['department', 'creator'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(JobOpening $jobOpening): JobOpeningResource
    {
        $jobOpening->load(['department', 'creator']);
        $jobOpening->loadCount('candidates');

        return new JobOpeningResource($jobOpening);
    }

    public function update(UpdateJobOpeningRequest $request, JobOpening $jobOpening): JobOpeningResource
    {
        $dto = JobOpeningDTO::fromArray($request->validated());
        $result = $this->recruitmentService->updateJobOpening($jobOpening->id, $dto);

        return new JobOpeningResource($result->load(['department', 'creator']));
    }

    public function destroy(JobOpening $jobOpening): JsonResponse
    {
        $jobOpening->delete();

        return response()->json(['message' => 'Job opening deleted successfully.'], Response::HTTP_OK);
    }

    public function candidates(JobOpening $jobOpening): AnonymousResourceCollection
    {
        $candidates = $this->candidateRepository->getByJobOpening($jobOpening->id);

        return CandidateResource::collection($candidates);
    }
}
