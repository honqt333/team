<?php

namespace Database\Seeders;

use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Colors Data (System Level)
        $colors = [
            ['en' => 'White', 'ar' => 'أبيض', 'hex' => '#FFFFFF'],
            ['en' => 'Black', 'ar' => 'أسود', 'hex' => '#000000'],
            ['en' => 'Silver', 'ar' => 'فضي', 'hex' => '#C0C0C0'],
            ['en' => 'Gray', 'ar' => 'رصاصي', 'hex' => '#808080'],
            ['en' => 'Red', 'ar' => 'أحمر', 'hex' => '#FF0000'],
            ['en' => 'Blue', 'ar' => 'أزرق', 'hex' => '#0000FF'],
            ['en' => 'Brown', 'ar' => 'بني', 'hex' => '#A52A2A'],
            ['en' => 'Green', 'ar' => 'أخضر', 'hex' => '#008000'],
            ['en' => 'Yellow', 'ar' => 'أصفر', 'hex' => '#FFFF00'],
            ['en' => 'Gold', 'ar' => 'ذهبي', 'hex' => '#FFD700'],
            ['en' => 'Orange', 'ar' => 'برتقالي', 'hex' => '#FFA500'],
            ['en' => 'Beige', 'ar' => 'بيج', 'hex' => '#F5F5DC'],
        ];

        foreach ($colors as $color) {
            VehicleColor::updateOrCreate(
                [
                    'source' => 'system',
                    'name_en' => $color['en'],
                ],
                [
                    'tenant_id' => null,
                    'center_id' => null,
                    'name_ar' => $color['ar'],
                    'hex_code' => $color['hex'],
                    'is_active' => true,
                ]
            );
        }

        // Makes and Models Data (System Level)
        $makes = [
            [
                'en' => 'Toyota', 'ar' => 'تويوتا',
                'models' => [
                    ['en' => 'Camry', 'ar' => 'كامري'],
                    ['en' => 'Corolla', 'ar' => 'كورولا'],
                    ['en' => 'Land Cruiser', 'ar' => 'لاند كروزر'],
                    ['en' => 'Hilux', 'ar' => 'هايلكس'],
                    ['en' => 'Yaris', 'ar' => 'يارس'],
                    ['en' => 'Fortuner', 'ar' => 'فورتشنر'],
                    ['en' => 'Prado', 'ar' => 'برادو'],
                    ['en' => 'RAV4', 'ar' => 'راف فور'],
                    ['en' => 'Innova', 'ar' => 'إنوفا'],
                    ['en' => 'Avalon', 'ar' => 'أفالون'],
                ]
            ],
            [
                'en' => 'Hyundai', 'ar' => 'هونداي',
                'models' => [
                    ['en' => 'Sonata', 'ar' => 'سوناتا'],
                    ['en' => 'Elantra', 'ar' => 'إلنترا'],
                    ['en' => 'Accent', 'ar' => 'أكسنت'],
                    ['en' => 'Tucson', 'ar' => 'توسان'],
                    ['en' => 'Santa Fe', 'ar' => 'سنتافي'],
                    ['en' => 'Azera', 'ar' => 'أزيرا'],
                    ['en' => 'Creta', 'ar' => 'كريتا'],
                    ['en' => 'Kona', 'ar' => 'كونا'],
                ]
            ],
            [
                'en' => 'Nissan', 'ar' => 'نيسان',
                'models' => [
                    ['en' => 'Patrol', 'ar' => 'باترول'],
                    ['en' => 'Sunny', 'ar' => 'صني'],
                    ['en' => 'Altima', 'ar' => 'ألتيما'],
                    ['en' => 'Maxima', 'ar' => 'مكسيما'],
                    ['en' => 'X-Trail', 'ar' => 'اكس تريل'],
                    ['en' => 'Pathfinder', 'ar' => 'باثفندر'],
                    ['en' => 'Sentra', 'ar' => 'سنترا'],
                    ['en' => 'Kicks', 'ar' => 'كيكس'],
                ]
            ],
            [
                'en' => 'Ford', 'ar' => 'فورد',
                'models' => [
                    ['en' => 'Crown Victoria', 'ar' => 'فكتوريا'],
                    ['en' => 'Explorer', 'ar' => 'اكبسلورر'],
                    ['en' => 'Expedition', 'ar' => 'اكبديشن'],
                    ['en' => 'Taurus', 'ar' => 'تورس'],
                    ['en' => 'F-150', 'ar' => 'إف-150'],
                    ['en' => 'Mustang', 'ar' => 'موستنج'],
                    ['en' => 'Edge', 'ar' => 'إيدج'],
                    ['en' => 'Fusion', 'ar' => 'فيوجن'],
                ]
            ],
            [
                'en' => 'Honda', 'ar' => 'هوندا',
                'models' => [
                    ['en' => 'Accord', 'ar' => 'أكورد'],
                    ['en' => 'Civic', 'ar' => 'سيفيك'],
                    ['en' => 'City', 'ar' => 'سيتي'],
                    ['en' => 'Pilot', 'ar' => 'بايلوت'],
                    ['en' => 'CR-V', 'ar' => 'سي آر في'],
                    ['en' => 'Odyssey', 'ar' => 'أوديسي'],
                ]
            ],
            [
                'en' => 'Chevrolet', 'ar' => 'شيفروليه',
                'models' => [
                    ['en' => 'Caprice', 'ar' => 'كابرس'],
                    ['en' => 'Tahoe', 'ar' => 'تاهو'],
                    ['en' => 'Suburban', 'ar' => 'سوبربان'],
                    ['en' => 'Impala', 'ar' => 'إمبالا'],
                    ['en' => 'Malibu', 'ar' => 'ماليبو'],
                    ['en' => 'Cruze', 'ar' => 'كروز'],
                    ['en' => 'Camaro', 'ar' => 'كمارو'],
                    ['en' => 'Silverado', 'ar' => 'سلفرادو'],
                ]
            ],
            [
                'en' => 'Kia', 'ar' => 'كيا',
                'models' => [
                    ['en' => 'Optima', 'ar' => 'أوبتيما'],
                    ['en' => 'K5', 'ar' => 'K5'],
                    ['en' => 'Cerato', 'ar' => 'سيراتو'],
                    ['en' => 'Rio', 'ar' => 'ريو'],
                    ['en' => 'Sportage', 'ar' => 'سبورتاج'],
                    ['en' => 'Sorento', 'ar' => 'سورينتو'],
                    ['en' => 'Pegas', 'ar' => 'بيجاس'],
                    ['en' => 'Sonet', 'ar' => 'سونيت'],
                ]
            ],
            [
                'en' => 'Mazda', 'ar' => 'مازدا',
                'models' => [
                    ['en' => 'Mazda 6', 'ar' => 'مازدا 6'],
                    ['en' => 'Mazda 3', 'ar' => 'مازدا 3'],
                    ['en' => 'CX-9', 'ar' => 'CX-9'],
                    ['en' => 'CX-5', 'ar' => 'CX-5'],
                    ['en' => 'CX-30', 'ar' => 'CX-30'],
                ]
            ],
            [
                'en' => 'Lexus', 'ar' => 'لكزس',
                'models' => [
                    ['en' => 'LX', 'ar' => 'LX'],
                    ['en' => 'ES', 'ar' => 'ES'],
                    ['en' => 'LS', 'ar' => 'LS'],
                    ['en' => 'RX', 'ar' => 'RX'],
                    ['en' => 'IS', 'ar' => 'IS'],
                    ['en' => 'GX', 'ar' => 'GX'],
                ]
            ],
            [
                'en' => 'GMC', 'ar' => 'جي إم سي',
                'models' => [
                    ['en' => 'Yukon', 'ar' => 'يوكن'],
                    ['en' => 'Sierra', 'ar' => 'سييرا'],
                    ['en' => 'Acadia', 'ar' => 'أكاديا'],
                    ['en' => 'Terrain', 'ar' => 'تيرين'],
                ]
            ],
        ];

        foreach ($makes as $makeData) {
            $make = VehicleMake::updateOrCreate(
                [
                    'source' => 'system',
                    'name_en' => $makeData['en'],
                ],
                [
                    'tenant_id' => null,
                    'center_id' => null,
                    'name_ar' => $makeData['ar'],
                    'is_active' => true,
                ]
            );

            foreach ($makeData['models'] as $modelData) {
                VehicleModel::updateOrCreate(
                    [
                        'source' => 'system',
                        'make_id' => $make->id,
                        'name_en' => $modelData['en'],
                    ],
                    [
                        'tenant_id' => null,
                        'center_id' => null,
                        'name_ar' => $modelData['ar'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
