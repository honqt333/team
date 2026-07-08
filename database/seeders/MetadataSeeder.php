<?php

namespace Database\Seeders;

use App\Models\InventoryUnit;
use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\Tenant;
use App\Models\Department;
use Illuminate\Database\Seeder;

class MetadataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Vehicle Colors, Makes, and Models
        $this->call(VehicleColorsSeeder::class);
        $this->call(VehicleMakesSeeder::class);

        // Get all tenants
        $tenants = Tenant::all();

        // 2. Seed Default Inventory Units for all tenants
        $units = [
            ['name_ar' => 'حبة', 'name_en' => 'Piece'],
            ['name_ar' => 'لتر', 'name_en' => 'Liter'],
            ['name_ar' => 'كرتون', 'name_en' => 'Box'],
            ['name_ar' => 'متر', 'name_en' => 'Meter'],
            ['name_ar' => 'طقم', 'name_en' => 'Set'],
            ['name_ar' => 'كيلو جرام', 'name_en' => 'Kilogram'],
            ['name_ar' => 'جالون', 'name_en' => 'Gallon'],
        ];

        foreach ($tenants as $tenant) {
            foreach ($units as $unit) {
                InventoryUnit::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name_ar' => $unit['name_ar'],
                    ],
                    [
                        'name_en' => $unit['name_en'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // 3. Seed Default Employee Types for all tenants
        $employeeTypes = [
            ['name_ar' => 'دوام كامل', 'name_en' => 'Full-time'],
            ['name_ar' => 'دوام جزئي', 'name_en' => 'Part-time'],
            ['name_ar' => 'عقد مؤقت', 'name_en' => 'Contract'],
            ['name_ar' => 'بالساعة', 'name_en' => 'Hourly'],
        ];

        foreach ($tenants as $tenant) {
            foreach ($employeeTypes as $type) {
                EmployeeType::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name_ar' => $type['name_ar'],
                    ],
                    [
                        'name_en' => $type['name_en'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // 4. Seed Default Job Titles for all tenants
        foreach ($tenants as $tenant) {
            // Find departments for this tenant
            $mechanicalDept = Department::where('tenant_id', $tenant->id)
                ->where(function($q) {
                    $q->where('name_ar', 'like', '%ميكانيكا%')
                      ->orWhere('name_en', 'like', '%Mechanical%');
                })->first();

            $electricalDept = Department::where('tenant_id', $tenant->id)
                ->where(function($q) {
                    $q->where('name_ar', 'like', '%كهرباء%')
                      ->orWhere('name_en', 'like', '%Electrical%');
                })->first();

            $bodyDept = Department::where('tenant_id', $tenant->id)
                ->where(function($q) {
                    $q->where('name_ar', 'like', '%سمكرة%')
                      ->orWhere('name_en', 'like', '%Body%');
                })->first();

            $jobTitles = [
                [
                    'name_ar' => 'مدير المركز',
                    'name_en' => 'Center Manager',
                    'default_role_name' => 'manager',
                    'department_id' => null,
                ],
                [
                    'name_ar' => 'فني ميكانيكا',
                    'name_en' => 'Mechanical Technician',
                    'default_role_name' => 'technician',
                    'department_id' => $mechanicalDept?->id,
                ],
                [
                    'name_ar' => 'فني كهرباء',
                    'name_en' => 'Electrical Technician',
                    'default_role_name' => 'technician',
                    'department_id' => $electricalDept?->id,
                ],
                [
                    'name_ar' => 'فني سمكرة',
                    'name_en' => 'Bodywork Technician',
                    'default_role_name' => 'technician',
                    'department_id' => $bodyDept?->id,
                ],
                [
                    'name_ar' => 'فني دهان',
                    'name_en' => 'Paint Technician',
                    'default_role_name' => 'technician',
                    'department_id' => $bodyDept?->id,
                ],
                [
                    'name_ar' => 'مهندس فحص',
                    'name_en' => 'Inspection Engineer',
                    'default_role_name' => 'technician',
                    'department_id' => null,
                ],
                [
                    'name_ar' => 'محاسب',
                    'name_en' => 'Accountant',
                    'default_role_name' => 'receptionist',
                    'department_id' => null,
                ],
                [
                    'name_ar' => 'موظف استقبال',
                    'name_en' => 'Receptionist',
                    'default_role_name' => 'receptionist',
                    'department_id' => null,
                ],
            ];

            foreach ($jobTitles as $title) {
                JobTitle::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name_ar' => $title['name_ar'],
                    ],
                    [
                        'name_en' => $title['name_en'],
                        'default_role_name' => $title['default_role_name'],
                        'department_id' => $title['department_id'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
