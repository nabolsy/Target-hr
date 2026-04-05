<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CandidateDTO;
use App\Enums\RecruitmentStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveCandidateStageRequest;
use App\Http\Requests\StoreCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Services\RecruitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CandidateController extends Controller
{
    public function __construct(private RecruitmentService $recruitmentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $candidates = $this->recruitmentService->paginateCandidates(
            $request->only([
                'company_id', 'job_opening_id', 'stage', 'status',
                'source', 'search', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return CandidateResource::collection($candidates);
    }

    public function store(StoreCandidateRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle CV file upload
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('candidates/cvs', 'private');
        }

        $dto = CandidateDTO::fromArray($data);
        $candidate = $this->recruitmentService->createCandidate($dto);

        return (new CandidateResource($candidate->load(['jobOpening', 'interviews'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Candidate $candidate): CandidateResource
    {
        $candidate->load(['jobOpening', 'interviews.interviewer', 'interviews.feedback']);

        return new CandidateResource($candidate);
    }

    public function update(Request $request, Candidate $candidate): CandidateResource
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $candidate->update($validated);

        return new CandidateResource($candidate->fresh(['jobOpening', 'interviews']));
    }

    public function destroy(Candidate $candidate): JsonResponse
    {
        $candidate->delete();

        return response()->json(['message' => 'Candidate deleted successfully.'], Response::HTTP_OK);
    }

    public function moveStage(MoveCandidateStageRequest $request, Candidate $candidate): CandidateResource
    {
        $stage = RecruitmentStage::from($request->validated('stage'));
        $result = $this->recruitmentService->moveToStage($candidate->id, $stage);

        return new CandidateResource($result);
    }

    public function hire(Candidate $candidate): CandidateResource
    {
        $result = $this->recruitmentService->hireCandidate($candidate->id);

        return new CandidateResource($result);
    }

    public function reject(Candidate $candidate): CandidateResource
    {
        $result = $this->recruitmentService->rejectCandidate($candidate->id);

        return new CandidateResource($result);
    }
}
