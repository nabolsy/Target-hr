<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Company::all() as $company) {
            $employees = Employee::where('company_id', $company->id)->get();
            $shift = Shift::where('company_id', $company->id)->where('is_default', true)->first();

            for ($day = 30; $day >= 1; $day--) {
                $date = Carbon::now()->subDays($day);

                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                foreach ($employees as $employee) {
                    $rand = rand(1, 100);

                    if ($rand <= 75) {
                        // Present
                        $checkIn = $date->copy()->setTime(8, rand(45, 59), rand(0, 59));
                        $checkOut = $date->copy()->setTime(17, rand(0, 30), rand(0, 59));
                        $worked = round($checkOut->diffInMinutes($checkIn) / 60, 2);
                        $overtime = max(0, round($worked - 8, 2));

                        AttendanceRecord::create([
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'date' => $date->format('Y-m-d'),
                            'check_in' => $checkIn,
                            'check_out' => $checkOut,
                            'shift_id' => $shift->id,
                            'status' => 'present',
                            'worked_hours' => $worked,
                            'overtime_hours' => $overtime,
                            'break_minutes' => 60,
                            'ip_address' => '192.168.1.' . rand(10, 254),
                        ]);
                    } elseif ($rand <= 90) {
                        // Late
                        $checkIn = $date->copy()->setTime(9, rand(16, 45), rand(0, 59));
                        $checkOut = $date->copy()->setTime(17, rand(0, 30), rand(0, 59));
                        $worked = round($checkOut->diffInMinutes($checkIn) / 60, 2);

                        AttendanceRecord::create([
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'date' => $date->format('Y-m-d'),
                            'check_in' => $checkIn,
                            'check_out' => $checkOut,
                            'shift_id' => $shift->id,
                            'status' => 'late',
                            'worked_hours' => $worked,
                            'overtime_hours' => 0,
                            'break_minutes' => 60,
                            'notes' => fake()->randomElement(['Traffic delay', 'Doctor appointment', 'Child school drop-off']),
                            'ip_address' => '192.168.1.' . rand(10, 254),
                        ]);
                    } else {
                        // Absent
                        AttendanceRecord::create([
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'date' => $date->format('Y-m-d'),
                            'status' => 'absent',
                            'shift_id' => $shift->id,
                            'worked_hours' => 0,
                            'overtime_hours' => 0,
                        ]);
                    }
                }
            }
        }
    }
}
