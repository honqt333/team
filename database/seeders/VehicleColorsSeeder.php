<?php

namespace Database\Seeders;

use App\Models\VehicleColor;
use Illuminate\Database\Seeder;

class VehicleColorsSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['en' => 'White', 'ar' => 'أبيض', 'hex' => '#FFFFFF'],
            ['en' => 'Black', 'ar' => 'أسود', 'hex' => '#000000'],
            ['en' => 'Silver', 'ar' => 'فضي', 'hex' => '#C0C0C0'],
            ['en' => 'Gray', 'ar' => 'رمادي', 'hex' => '#808080'],
            ['en' => 'Red', 'ar' => 'أحمر', 'hex' => '#FF0000'],
            ['en' => 'Blue', 'ar' => 'أزرق', 'hex' => '#0000FF'],
            ['en' => 'Brown', 'ar' => 'بني', 'hex' => '#A52A2A'],
            ['en' => 'Green', 'ar' => 'أخضر', 'hex' => '#008000'],
            ['en' => 'Beige', 'ar' => 'بيج', 'hex' => '#F5F5DC'],
            ['en' => 'Orange', 'ar' => 'برتقالي', 'hex' => '#FFA500'],
            ['en' => 'Gold', 'ar' => 'ذهبي', 'hex' => '#FFD700'],
            ['en' => 'Yellow', 'ar' => 'أصفر', 'hex' => '#FFFF00'],
            ['en' => 'Purple', 'ar' => 'بنفسجي', 'hex' => '#800080'],
            ['en' => 'Bronze', 'ar' => 'برونزي', 'hex' => '#CD7F32'],
            ['en' => 'Maroon', 'ar' => 'كستنائي', 'hex' => '#800000'],
            ['en' => 'Navy', 'ar' => 'كحلي', 'hex' => '#000080'],
        ];

        foreach ($colors as $color) {
            // Using withoutGlobalScopes to avoid any tenant scoping issues during seeding
            VehicleColor::withoutGlobalScopes()->firstOrCreate(
                [
                    'name_en' => $color['en'],
                    'source' => 'system',
                ],
                [
                    'name_ar' => $color['ar'],
                    'hex_code' => $color['hex'],
                    'tenant_id' => null,
                    'center_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
