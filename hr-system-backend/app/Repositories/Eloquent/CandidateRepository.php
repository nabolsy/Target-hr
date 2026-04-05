<?php

namespace App\Repositories\Eloquent;

use App\Enums\RecruitmentStage;
use App\Models\Candidate;
use App\Repositories\Interfaces\CandidateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CandidateRepository extends BaseRepository implements CandidateRepositoryInterface
{
    public function __construct(Candidate $model)
    {
        parent::__construct($model);
    }

    public function getByJobOpening(int $jobOpeningId): Collection
    {
        return $this->model->where('job_opening_id', $jobOpeningId)
            ->with(['jobOpening', 'interviews'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByStage(RecruitmentStage $stage, ?int $companyId = null): Collection
    {
        $query = $this->model->where('stage', $stage);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->with(['jobOpening', 'interviews'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['jobOpening', 'interviews']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['job_opening_id'])) {
            $query->where('job_opening_id', $filters['job_opening_id']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = ['created_at', 'first_name', 'last_name', 'applied_at', 'stage', 'status'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }
}
