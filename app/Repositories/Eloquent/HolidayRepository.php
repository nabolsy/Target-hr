<?php

namespace App\Repositories\Eloquent;

use App\Models\Holiday;
use App\Repositories\Interfaces\HolidayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HolidayRepository extends BaseRepository implements HolidayRepositoryInterface
{
    public function __construct(Holiday $model)
    {
        parent::__construct($model);
    }

    public function getByCompanyAndYear(int $companyId, int $year): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();
    }

    public function isHoliday(int $companyId, string $date): bool
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('date', $date)
            ->exists();
    }
}
