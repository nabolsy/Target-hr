<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Company::all() as $company) {
            Shift::create([
                'company_id' => $company->id,
                'name' => 'Morning Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'break_duration_minutes' => 60,
                'is_default' => true,
            ]);

            Shift::create([
                'company_id' => $company->id,
                'name' => 'Evening Shift',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'break_duration_minutes' => 60,
                'is_default' => false,
            ]);
        }
    }
}
