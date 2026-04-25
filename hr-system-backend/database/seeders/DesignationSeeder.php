<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            ['name' => 'Chief Executive Officer', 'description' => 'Top executive leadership', 'level' => 10],
            ['name' => 'Chief Technology Officer', 'description' => 'Head of technology and engineering', 'level' => 9],
            ['name' => 'HR Director', 'description' => 'Head of human resources', 'level' => 8],
            ['name' => 'Department Manager', 'description' => 'Manages a department or team', 'level' => 7],
            ['name' => 'Senior Engineer', 'description' => 'Senior-level software engineer', 'level' => 5],
            ['name' => 'Software Engineer', 'description' => 'Mid-level software engineer', 'level' => 4],
            ['name' => 'Junior Engineer', 'description' => 'Entry-level software engineer', 'level' => 3],
            ['name' => 'Intern', 'description' => 'Internship position', 'level' => 1],
        ];

        foreach (Company::all() as $company) {
            foreach ($designations as $desig) {
                Designation::create([
                    'company_id' => $company->id,
                    'name' => $desig['name'],
                    'description' => $desig['description'],
                    'level' => $desig['level'],
                ]);
            }
        }
    }
}
