<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\VehicleConditionCategory;
use App\Models\VehicleConditionItem;
use Illuminate\Database\Seeder;

class VehicleConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->warn('No tenant found in the database. Skipping VehicleConditionSeeder.');
            return;
        }
        $tenantId = $tenant->id;

        $checklist = [
            [
                'category' => ['ar' => 'تحت غطاء المحرك', 'en' => 'Under the Hood'],
                'items' => [
                    ['ar' => 'مستوى زيت المحرك', 'en' => 'Engine Oil Level'],
                    ['ar' => 'سائل تبريد المحرك', 'en' => 'Engine Coolant Level'],
                    ['ar' => 'زيت الفرامل', 'en' => 'Brake Fluid Level'],
                    ['ar' => 'حالة البطارية والأقطاب', 'en' => 'Battery Condition & Terminals'],
                    ['ar' => 'زيت الباور (علبة التوجيه)', 'en' => 'Power Steering Fluid'],
                    ['ar' => 'سيور المحرك', 'en' => 'Drive Belts'],
                    ['ar' => 'فلتر الهواء', 'en' => 'Air Filter'],
                    ['ar' => 'فلتر مكيف الهواء', 'en' => 'Cabin AC Filter'],
                    ['ar' => 'المبرد (الرديتر) والخراطيم', 'en' => 'Radiator & Hoses'],
                ]
            ],
            [
                'category' => ['ar' => 'الإطارات والفرامل', 'en' => 'Tires & Brakes'],
                'items' => [
                    ['ar' => 'ضغط الإطارات', 'en' => 'Tire Pressure'],
                    ['ar' => 'فحمات الفرامل الأمامية', 'en' => 'Front Brake Pads'],
                    ['ar' => 'فحمات الفرامل الخلفية', 'en' => 'Rear Brake Pads'],
                    ['ar' => 'حالة هوبات الفرامل', 'en' => 'Brake Rotors Condition'],
                    ['ar' => 'عمق ونقشة الإطارات', 'en' => 'Tire Tread & Wear'],
                    ['ar' => 'الإطار الاحتياطي', 'en' => 'Spare Tire'],
                ]
            ],
            [
                'category' => ['ar' => 'الأنوار والنظام الكهربائي', 'en' => 'Lights & Electrical'],
                'items' => [
                    ['ar' => 'الأنوار الأمامية (العالي والمنخفض)', 'en' => 'Headlights (Low & High beams)'],
                    ['ar' => 'الأنوار الخلفية وأنوار الفرامل', 'en' => 'Tail & Brake Lights'],
                    ['ar' => 'إشارات الانعطاف وأنوار التحذير', 'en' => 'Turn Signals & Hazards'],
                    ['ar' => 'شاشة العدادات ولمبات التحذير', 'en' => 'Instrument Cluster & Dashboard Lights'],
                    ['ar' => 'بوق السيارة (البوري)', 'en' => 'Horn Operation'],
                    ['ar' => 'مساحات الزجاج ورشاش الماء', 'en' => 'Windshield Wipers & Washer Jet'],
                ]
            ],
            [
                'category' => ['ar' => 'أسفل السيارة ونظام التعليق', 'en' => 'Under the Vehicle & Suspension'],
                'items' => [
                    ['ar' => 'المساعدين ونظام التعليق', 'en' => 'Shock Absorbers'],
                    ['ar' => 'أذرعة ومقود السيارة', 'en' => 'Steering Linkage & Gear'],
                    ['ar' => 'نظام العادم (الشكمان)', 'en' => 'Exhaust System'],
                    ['ar' => 'تهريبات السوائل (المحرك/القير/المبرد)', 'en' => 'Fluid Leaks (Engine/Transmission/Coolant)'],
                    ['ar' => 'العكوس وجلود العكوس', 'en' => 'CV Boots & Drive Shafts'],
                ]
            ],
            [
                'category' => ['ar' => 'المقصورة الداخلية', 'en' => 'Interior & Cabin'],
                'items' => [
                    ['ar' => 'برودة التكييف', 'en' => 'Air Conditioning Cooling'],
                    ['ar' => 'أحزمة الأمان', 'en' => 'Seat Belts'],
                    ['ar' => 'الفرامل اليدوية (الجلنط)', 'en' => 'Parking Brake'],
                    ['ar' => 'رافعات النوافذ الكهربائية', 'en' => 'Window Regulators'],
                ]
            ],
            [
                'category' => ['ar' => 'الهيكل الخارجي', 'en' => 'Exterior & Body'],
                'items' => [
                    ['ar' => 'خدوش وصدمات الهيكل', 'en' => 'Body Scratches & Dents'],
                    ['ar' => 'الزجاج الأمامي والخلفي', 'en' => 'Windshield & Glass'],
                    ['ar' => 'المرايا الجانبية', 'en' => 'Side Mirrors'],
                ]
            ],
        ];

        $catOrder = 1;
        foreach ($checklist as $catGroup) {
            $category = VehicleConditionCategory::withoutGlobalScopes()->updateOrCreate(
                [
                    'name_en' => $catGroup['category']['en'],
                    'source' => 'system',
                ],
                [
                    'tenant_id' => $tenantId,
                    'center_id' => null,
                    'name_ar' => $catGroup['category']['ar'],
                    'is_active' => true,
                    'sort_order' => $catOrder++,
                    'updated_by' => null,
                ]
            );

            $itemOrder = 1;
            foreach ($catGroup['items'] as $itemData) {
                VehicleConditionItem::withoutGlobalScopes()->updateOrCreate(
                    [
                        'name_en' => $itemData['en'],
                        'category_id' => $category->id,
                        'source' => 'system',
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'center_id' => null,
                        'name_ar' => $itemData['ar'],
                        'is_active' => true,
                        'sort_order' => $itemOrder++,
                        'updated_by' => null,
                    ]
                );
            }
        }
    }
}
