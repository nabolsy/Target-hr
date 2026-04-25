<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $docTypes = [
            ['type' => 'passport', 'title' => 'Passport', 'mime' => 'application/pdf', 'ext' => 'pdf'],
            ['type' => 'national_id', 'title' => 'National ID Card', 'mime' => 'image/jpeg', 'ext' => 'jpg'],
            ['type' => 'contract', 'title' => 'Employment Contract', 'mime' => 'application/pdf', 'ext' => 'pdf'],
            ['type' => 'cv', 'title' => 'Curriculum Vitae', 'mime' => 'application/pdf', 'ext' => 'pdf'],
        ];

        foreach (Company::all() as $company) {
            $employees = Employee::where('company_id', $company->id)->get();
            $hrUser = User::where('company_id', $company->id)->where('role', 'hr_manager')->first() ?? User::where('company_id', $company->id)->where('role', 'company_admin')->first();

            foreach ($employees as $employee) {
                $docsToCreate = collect($docTypes)->random(rand(3, 4));

                foreach ($docsToCreate as $doc) {
                    $rand = rand(1, 100);
                    if ($rand <= 60) {
                        $status = 'active';
                        $expiry = Carbon::now()->addMonths(rand(6, 36))->format('Y-m-d');
                    } elseif ($rand <= 85) {
                        $status = 'expiring';
                        $expiry = Carbon::now()->addDays(rand(5, 25))->format('Y-m-d');
                    } else {
                        $status = 'expired';
                        $expiry = Carbon::now()->subDays(rand(5, 60))->format('Y-m-d');
                    }

                    // Contracts don't expire
                    if ($doc['type'] === 'contract') {
                        $status = 'active';
                        $expiry = null;
                    }

                    EmployeeDocument::create([
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'title' => $doc['title'],
                        'type' => $doc['type'],
                        'file_path' => "documents/{$company->id}/{$employee->id}/{$doc['type']}.{$doc['ext']}",
                        'file_name' => "{$employee->first_name}_{$employee->last_name}_{$doc['type']}.{$doc['ext']}",
                        'file_size' => rand(50000, 5000000),
                        'mime_type' => $doc['mime'],
                        'status' => $status,
                        'expiry_date' => $expiry,
                        'uploaded_by' => $hrUser->id,
                    ]);
                }
            }
        }
    }
}
