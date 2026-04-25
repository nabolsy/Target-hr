<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assetTemplates = [
            // Laptops (8)
            ['name' => 'MacBook Pro 14"', 'category' => 'laptop', 'cost' => 2499.00],
            ['name' => 'MacBook Pro 16"', 'category' => 'laptop', 'cost' => 3499.00],
            ['name' => 'Dell XPS 15', 'category' => 'laptop', 'cost' => 1899.00],
            ['name' => 'ThinkPad X1 Carbon', 'category' => 'laptop', 'cost' => 1649.00],
            ['name' => 'MacBook Air M3', 'category' => 'laptop', 'cost' => 1299.00],
            ['name' => 'HP EliteBook 840', 'category' => 'laptop', 'cost' => 1549.00],
            ['name' => 'Dell Latitude 5540', 'category' => 'laptop', 'cost' => 1399.00],
            ['name' => 'ThinkPad T14s', 'category' => 'laptop', 'cost' => 1449.00],
            // Phones (5)
            ['name' => 'iPhone 15 Pro', 'category' => 'phone', 'cost' => 999.00],
            ['name' => 'iPhone 15', 'category' => 'phone', 'cost' => 799.00],
            ['name' => 'Samsung Galaxy S24', 'category' => 'phone', 'cost' => 849.00],
            ['name' => 'Google Pixel 8', 'category' => 'phone', 'cost' => 699.00],
            ['name' => 'iPhone 14', 'category' => 'phone', 'cost' => 699.00],
            // Monitors (7)
            ['name' => 'Dell UltraSharp 27" 4K', 'category' => 'monitor', 'cost' => 619.00],
            ['name' => 'LG 27" UHD Monitor', 'category' => 'monitor', 'cost' => 449.00],
            ['name' => 'Samsung 32" Curved', 'category' => 'monitor', 'cost' => 399.00],
            ['name' => 'Dell 24" FHD Monitor', 'category' => 'monitor', 'cost' => 249.00],
            ['name' => 'ASUS ProArt 27"', 'category' => 'monitor', 'cost' => 529.00],
            ['name' => 'BenQ 27" Designer', 'category' => 'monitor', 'cost' => 499.00],
            ['name' => 'LG 34" Ultrawide', 'category' => 'monitor', 'cost' => 699.00],
        ];

        $conditions = ['new', 'good', 'good', 'good', 'fair'];
        $counter = 1;

        foreach (Company::all() as $company) {
            $adminUser = User::where("company_id", $company->id)->whereIn("role", ["company_admin", "hr_manager"])->first(); if (!$adminUser) continue;
            $employees = Employee::where('company_id', $company->id)->get();
            $empIdx = 0;

            foreach ($assetTemplates as $template) {
                $condition = $conditions[array_rand($conditions)];
                $isAssigned = $empIdx < $employees->count() && rand(1, 100) <= 60;

                $asset = Asset::create([
                    'company_id' => $company->id,
                    'name' => $template['name'],
                    'asset_code' => 'AST-' . str_pad($counter++, 5, '0', STR_PAD_LEFT),
                    'category' => $template['category'],
                    'description' => "Company {$template['category']} asset",
                    'serial_number' => strtoupper(fake()->bothify('??##-####-????')),
                    'purchase_date' => Carbon::now()->subMonths(rand(1, 24))->format('Y-m-d'),
                    'purchase_cost' => $template['cost'],
                    'condition' => $condition,
                    'status' => $isAssigned ? 'assigned' : 'available',
                    'location' => $isAssigned ? null : fake()->randomElement(['IT Storage Room', 'Floor 2 Cabinet', 'Reception']),
                ]);

                if ($isAssigned && $empIdx < $employees->count()) {
                    AssetAssignment::create([
                        'asset_id' => $asset->id,
                        'employee_id' => $employees[$empIdx]->id,
                        'assigned_by' => $adminUser->id,
                        'assigned_at' => Carbon::now()->subDays(rand(7, 180)),
                        'condition_on_assign' => $condition,
                    ]);
                    $empIdx++;
                }
            }
        }
    }
}
