<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface HolidayRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompanyAndYear(int $companyId, int $year): Collection;

    public function isHoliday(int $companyId, string $date): bool;
}
