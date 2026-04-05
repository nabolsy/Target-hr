<?php

namespace App\Services;

use App\DTOs\CandidateDTO;
use App\DTOs\JobOpeningDTO;
use App\Enums\CandidateStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\RecruitmentStage;
use App\Events\CandidateHired;
use App\Events\CandidateStageChanged;
use App\Exceptions\BusinessException;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use App\Models\JobOpening;
use App\Repositories\Interfaces\CandidateRepositoryInterface;
use App\Repositories\Interfaces\JobOpeningRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RecruitmentService
{
    public function __construct(
        protected JobOpeningRepositoryInterface $jobOpeningRepository,
        protected CandidateRepositoryInterface $candidateRepository,
    ) {
    }

    // --- Job Openings ---

    public function createJobOpening(JobOpeningDTO $dto): JobOpening
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        return $this->jobOpeningRepository->create($data);
    }

    public function updateJobOpening(int $id, JobOpeningDTO $dto): JobOpening
    {
        return $this->jobOpeningRepository->update($id, $dto->toArray());
    }

    public function paginateJobOpenings(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->jobOpeningRepository->paginateWithFilters($filters, $perPage);
    }

    // --- Candidates ---

    public function createCandidate(CandidateDTO $dto): Candidate
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['stage'] = $data['stage'] ?? RecruitmentStage::Applied->value;
        $data['status'] = $data['status'] ?? CandidateStatus::Active->value;
        $data['applied_at'] = $data['applied_at'] ?? now();

        return $this->candidateRepository->create($data);
    }

    public function paginateCandidates(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->candidateRepository->paginateWithFilters($filters, $perPage);
    }

    public function moveToStage(int $candidateId, RecruitmentStage $newStage): Candidate
    {
        $candidate = $this->candidateRepository->findOrFail($candidateId);
        $oldStage = $candidate->stage;

        if ($candidate->status !== CandidateStatus::Active) {
            throw new BusinessException('Cannot move a non-active candidate to a new stage.');
        }

        if (!$oldStage->canTransitionTo($newStage)) {
            throw new BusinessException(
                "Invalid stage transition from '{$oldStage->label()}' to '{$newStage->label()}'."
            );
        }

        $candidate->update(['stage' => $newStage]);
        $candidate = $candidate->fresh(['jobOpening', 'interviews']);

        event(new CandidateStageChanged($candidate, $oldStage, $newStage));

        return $candidate;
    }

    public function scheduleInterview(array $data): Interview
    {
        $candidate = $this->candidateRepository->findOrFail($data['candidate_id']);
        $data['company_id'] = $candidate->company_id;

        return Interview::create($data);
    }

    public function submitFeedback(int $interviewId, array $data): InterviewFeedback
    {
        $interview = Interview::findOrFail($interviewId);
        $data['interview_id'] = $interview->id;
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        return InterviewFeedback::create($data);
    }

    public function hireCandidate(int $candidateId): Candidate
    {
        return DB::transaction(function () use ($candidateId) {
            $candidate = $this->candidateRepository->findOrFail($candidateId);

            if ($candidate->status !== CandidateStatus::Active) {
                throw new BusinessException('Only active candidates can be hired.');
            }

            // Update candidate status and stage
            $oldStage = $candidate->stage;
            $candidate->update([
                'status' => CandidateStatus::Hired,
                'stage' => RecruitmentStage::Hired,
            ]);

            // Create Employee record from candidate data
            $jobOpening = $candidate->jobOpening;

            Employee::create([
                'company_id' => $candidate->company_id,
                'department_id' => $jobOpening?->department_id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'employment_type' => $jobOpening?->employment_type ?? EmploymentType::FullTime->value,
                'status' => EmployeeStatus::Active->value,
                'join_date' => now()->toDateString(),
                'employee_id_number' => 'EMP-' . strtoupper(uniqid()),
            ]);

            $candidate = $candidate->fresh(['jobOpening', 'interviews']);

            event(new CandidateHired($candidate));
            event(new CandidateStageChanged($candidate, $oldStage, RecruitmentStage::Hired));

            return $candidate;
        });
    }

    public function rejectCandidate(int $candidateId): Candidate
    {
        $candidate = $this->candidateRepository->findOrFail($candidateId);

        if ($candidate->status !== CandidateStatus::Active) {
            throw new BusinessException('Only active candidates can be rejected.');
        }

        $oldStage = $candidate->stage;

        $candidate->update([
            'status' => CandidateStatus::Rejected,
            'stage' => RecruitmentStage::Rejected,
        ]);

        $candidate = $candidate->fresh(['jobOpening', 'interviews']);

        event(new CandidateStageChanged($candidate, $oldStage, RecruitmentStage::Rejected));

        return $candidate;
    }
}
