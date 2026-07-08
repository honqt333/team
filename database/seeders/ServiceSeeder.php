<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Service;
use App\Models\Department;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // الإطارات والميزان - Tires
            ['name' => 'فحص الإطارات', 'category' => 'maintenance', 'default_price' => 0],
            ['name' => 'تبديل الإطارات', 'category' => 'maintenance', 'default_price' => 50],
            ['name' => 'موازنة الإطارات', 'category' => 'maintenance', 'default_price' => 40],
            ['name' => 'ترصيص الإطارات', 'category' => 'maintenance', 'default_price' => 30],
            
            // السمكرة والدهان - Body & Paint
            ['name' => 'إصلاح صدمات', 'category' => 'body_paint', 'default_price' => 0],
            ['name' => 'دهان جزئي', 'category' => 'body_paint', 'default_price' => 0],
            ['name' => 'دهان كامل', 'category' => 'body_paint', 'default_price' => 0],
            ['name' => 'إزالة خدوش', 'category' => 'body_paint', 'default_price' => 100],
            
            // الكهرباء - Electrical
            ['name' => 'فحص كهرباء', 'category' => 'electrical', 'default_price' => 50],
            ['name' => 'تبديل بطارية', 'category' => 'electrical', 'default_price' => 0],
            ['name' => 'إصلاح إضاءة', 'category' => 'electrical', 'default_price' => 0],
            ['name' => 'برمجة كمبيوتر', 'category' => 'electrical', 'default_price' => 150],
            
            // الميكانيكا - Mechanical
            ['name' => 'تغيير زيت', 'category' => 'mechanical', 'default_price' => 100],
            ['name' => 'تغيير فلتر زيت', 'category' => 'mechanical', 'default_price' => 30],
            ['name' => 'تغيير فلتر هواء', 'category' => 'mechanical', 'default_price' => 40],
            ['name' => 'فحص فرامل', 'category' => 'mechanical', 'default_price' => 0],
            ['name' => 'تبديل فحمات', 'category' => 'mechanical', 'default_price' => 150],
            ['name' => 'صيانة دورية', 'category' => 'mechanical', 'default_price' => 300],
            
            // الغسيل والتلميع - Washing
            ['name' => 'غسيل خارجي', 'category' => 'washing', 'default_price' => 30],
            ['name' => 'غسيل داخلي', 'category' => 'washing', 'default_price' => 50],
            ['name' => 'غسيل شامل', 'category' => 'washing', 'default_price' => 70],
            ['name' => 'تلميع خارجي', 'category' => 'washing', 'default_price' => 150],
            ['name' => 'حماية سيراميك', 'category' => 'washing', 'default_price' => 500],
            
            // خدمات أخرى - Other
            ['name' => 'فحص كمبيوتر', 'category' => 'other', 'default_price' => 100],
            ['name' => 'تعبئة غاز مكيف', 'category' => 'other', 'default_price' => 80],
            ['name' => 'فحص شامل', 'category' => 'other', 'default_price' => 200],
        ];

        $departmentNames = [
            'maintenance' => ['ar' => 'الإطارات والميزان', 'en' => 'Tires & Alignment'],
            'body_paint'  => ['ar' => 'السمكرة والدهان', 'en' => 'Body & Paint'],
            'electrical'  => ['ar' => 'الكهرباء', 'en' => 'Electrical'],
            'mechanical'  => ['ar' => 'الميكانيكا', 'en' => 'Mechanical'],
            'washing'     => ['ar' => 'الغسيل والتلميع', 'en' => 'Washing & Detailing'],
            'other'       => ['ar' => 'خدمات أخرى', 'en' => 'Other Services'],
        ];

        // Get all centers and create services for each
        $centers = Center::all();
        
        foreach ($centers as $center) {
            // 1. Pre-create departments for this center
            $departmentsMap = [];
            $orderDept = 1;
            foreach ($departmentNames as $key => $names) {
                $dept = Department::firstOrCreate(
                    [
                        'tenant_id' => $center->tenant_id,
                        'center_id' => $center->id,
                        'name_ar' => $names['ar'],
                    ],
                    [
                        'name_en' => $names['en'],
                        'description' => $names['en'],
                        'sort_order' => $orderDept++,
                        'is_active' => true,
                    ]
                );
                $departmentsMap[$key] = $dept->id;
            }

            // 2. Create services and assign them to departments
            $order = 1;
            foreach ($services as $serviceData) {
                $deptId = $departmentsMap[$serviceData['category']] ?? null;

                Service::firstOrCreate(
                    [
                        'tenant_id' => $center->tenant_id,
                        'center_id' => $center->id,
                        'name_ar' => $serviceData['name'],
                    ],
                    [
                        'name_en' => $serviceData['name'],
                        'base_price' => $serviceData['default_price'],
                        'type' => Service::TYPE_INTERNAL,
                        'department_id' => $deptId,
                        'is_active' => true,
                        'sort_order' => $order++,
                    ]
                );
            }
        }
    }
}
