<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'work_start_time' => $this->work_start_time,
            'work_end_time' => $this->work_end_time,
            'work_days' => $this->work_days,
            'timezone' => $this->timezone,
            'date_format' => $this->date_format,
            'currency' => $this->currency,
            'grace_period_minutes' => $this->grace_period_minutes,
            'allow_remote_checkin' => $this->allow_remote_checkin,
            'leave_approval_levels' => $this->leave_approval_levels,
            'probation_period_days' => $this->probation_period_days,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
