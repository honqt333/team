<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\InspectionTemplate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class InspectionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();

        if (!$tenant || !$center) {
            return;
        }

        $template = InspectionTemplate::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => [
                'ar' => 'فحص النقاط الأساسية',
                'en' => 'Basic Point Inspection'
            ],
            'description' => [
                'ar' => 'فحص سريع يشمل أهم نقاط السلامة في المركبة',
                'en' => 'A quick check covering the most important safety points of the vehicle'
            ],
        ]);

        $items = [
            [
                'category' => ['ar' => 'تحت غطاء المحرك', 'en' => 'Under the Hood'],
                'items' => [
                    ['ar' => 'مستوى زيت المحرك', 'en' => 'Engine Oil Level'],
                    ['ar' => 'سائل التبريد', 'en' => 'Engine Coolant Level'],
                    ['ar' => 'زيت الفرامل', 'en' => 'Brake Fluid Level'],
                    ['ar' => 'حالة البطارية', 'en' => 'Battery Condition'],
                ]
            ],
            [
                'category' => ['ar' => 'الإطارات والفرامل', 'en' => 'Tires and Brakes'],
                'items' => [
                    ['ar' => 'ضغط الإطارات', 'en' => 'Tire Pressure'],
                    ['ar' => 'حالة فحمات الفرامل الأمامية', 'en' => 'Front Brake Pads Condition'],
                    ['ar' => 'حالة فحمات الفرامل الخلفية', 'en' => 'Rear Brake Pads Condition'],
                ]
            ],
            [
                'category' => ['ar' => 'الأنوار والسوائل الأخرى', 'en' => 'Lights and Other Fluids'],
                'items' => [
                    ['ar' => 'الأنوار الأمامية والخلفية', 'en' => 'Exterior Lights'],
                    ['ar' => 'مساحات الزجاج', 'en' => 'Wiper Blades'],
                    ['ar' => 'سائل المساحات', 'en' => 'Washer Fluid'],
                ]
            ],
        ];

        $order = 1;
        foreach ($items as $catGroup) {
            foreach ($catGroup['items'] as $itemData) {
                $template->items()->create([
                    'category' => $catGroup['category'],
                    'name' => [
                        'ar' => $itemData['ar'],
                        'en' => $itemData['en']
                    ],
                    'sort_order' => $order++,
                ]);
            }
        }
    }
}
