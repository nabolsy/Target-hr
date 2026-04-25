<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Engineering',      'code' => 'ENG', 'description' => 'Software development and technical operations'],
            ['name' => 'Backend',          'code' => 'BE',  'description' => 'Backend services and API development', 'parent' => 'Engineering'],
            ['name' => 'Product',          'code' => 'PRD', 'description' => 'Product management and design'],
            ['name' => 'Human Resources',  'code' => 'HR',  'description' => 'People management, recruitment, and employee relations'],
            ['name' => 'Sales',            'code' => 'SLS', 'description' => 'Revenue generation and client relationships'],
            ['name' => 'Finance',          'code' => 'FIN', 'description' => 'Accounting, budgeting, and financial planning'],
            ['name' => 'Customer Support', 'code' => 'SUP', 'description' => 'Customer service and support operations'],
            ['name' => 'DevOps',           'code' => 'OPS', 'description' => 'Infrastructure and deployment operations', 'parent' => 'Engineering', 'status' => 'inactive'],
        ];

        foreach (Company::all() as $company) {
            $created = [];
            foreach ($departments as $dept) {
                $parentId = null;
                if (isset($dept['parent']) && isset($created[$dept['parent']])) {
                    $parentId = $created[$dept['parent']]->id;
                }

                $d = Department::create([
                    'company_id'  => $company->id,
                    'parent_id'   => $parentId,
                    'name'        => $dept['name'],
                    'description' => $dept['description'],
                    'status'      => $dept['status'] ?? 'active',
                ]);
                $created[$dept['name']] = $d;
            }
        }
    }
}
