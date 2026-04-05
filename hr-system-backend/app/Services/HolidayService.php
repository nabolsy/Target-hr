<?php

namespace App\Services;

use App\Models\Holiday;
use App\Repositories\Interfaces\HolidayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class HolidayService extends BaseService
{
    public function __construct(
        protected HolidayRepositoryInterface $holidayRepository,
    ) {
        parent::__construct($holidayRepository);
    }

    public function getByCompanyAndYear(int $companyId, int $year): Collection
    {
        return $this->holidayRepository->getByCompanyAndYear($companyId, $year);
    }

    public function createHoliday(array $data): Model
    {
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;

        return $this->holidayRepository->create($data);
    }

    public function updateHoliday(int $id, array $data): Model
    {
        return $this->holidayRepository->update($id, $data);
    }

    public function deleteHoliday(int $id): bool
    {
        return $this->holidayRepository->delete($id);
    }

    public function isHoliday(int $companyId, string $date): bool
    {
        return $this->holidayRepository->isHoliday($companyId, $date);
    }
}
