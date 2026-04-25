<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleInterviewRequest;
use App\Http\Requests\SubmitInterviewFeedbackRequest;
use App\Http\Resources\InterviewFeedbackResource;
use App\Http\Resources\InterviewResource;
use App\Models\Interview;
use App\Services\RecruitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class InterviewController extends Controller
{
    public function __construct(private RecruitmentService $recruitmentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Interview::query()->with(['candidate', 'interviewer', 'feedback']);

        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->integer('candidate_id'));
        }

        if ($request->has('interviewer_id')) {
            $query->where('interviewer_id', $request->integer('interviewer_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $interviews = $query->orderBy('scheduled_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return InterviewResource::collection($interviews);
    }

    public function store(ScheduleInterviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = 'scheduled';

        $interview = $this->recruitmentService->scheduleInterview($data);

        return (new InterviewResource($interview->load(['candidate', 'interviewer'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Interview $interview): InterviewResource
    {
        $interview->load(['candidate', 'interviewer', 'feedback.user']);

        return new InterviewResource($interview);
    }

    public function update(Request $request, Interview $interview): InterviewResource
    {
        $validated = $request->validate([
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
            'duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'type' => ['sometimes', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $interview->update($validated);

        return new InterviewResource($interview->fresh(['candidate', 'interviewer', 'feedback']));
    }

    public function destroy(Interview $interview): JsonResponse
    {
        $interview->delete();

        return response()->json(['message' => 'Interview deleted successfully.'], Response::HTTP_OK);
    }

    public function submitFeedback(SubmitInterviewFeedbackRequest $request, Interview $interview): InterviewFeedbackResource
    {
        $feedback = $this->recruitmentService->submitFeedback(
            $interview->id,
            $request->validated()
        );

        return new InterviewFeedbackResource($feedback->load('user'));
    }
}
