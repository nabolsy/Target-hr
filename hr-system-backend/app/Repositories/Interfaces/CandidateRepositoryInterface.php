<?php

namespace App\Repositories\Interfaces;

use App\Enums\RecruitmentStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CandidateRepositoryInterface extends BaseRepositoryInterface
{
    public function getByJobOpening(int $jobOpeningId): Collection;

    public function getByStage(RecruitmentStage $stage, ?int $companyId = null): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}
