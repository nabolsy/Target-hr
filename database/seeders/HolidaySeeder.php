<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['name' => 'New Year\'s Day', 'date' => now()->year . '-01-01', 'is_recurring' => true],
            ['name' => 'Martin Luther King Jr. Day', 'date' => now()->year . '-01-20', 'is_recurring' => true],
            ['name' => 'Presidents\' Day', 'date' => now()->year . '-02-17', 'is_recurring' => true],
            ['name' => 'Memorial Day', 'date' => now()->year . '-05-26', 'is_recurring' => true],
            ['name' => 'Independence Day', 'date' => now()->year . '-07-04', 'is_recurring' => true],
            ['name' => 'Labor Day', 'date' => now()->year . '-09-01', 'is_recurring' => true],
            ['name' => 'Thanksgiving', 'date' => now()->year . '-11-27', 'is_recurring' => true],
            ['name' => 'Christmas Day', 'date' => now()->year . '-12-25', 'is_recurring' => true],
        ];

        foreach (Company::all() as $company) {
            foreach ($holidays as $holiday) {
                Holiday::create(array_merge($holiday, ['company_id' => $company->id]));
            }
        }
    }
}
