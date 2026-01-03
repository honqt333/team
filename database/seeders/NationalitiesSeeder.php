<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nationalities = [
            // Arab Countries
            ['code' => 'SAU', 'name_en' => 'Saudi Arabia', 'name_ar' => 'المملكة العربية السعودية'],
            ['code' => 'EGY', 'name_en' => 'Egypt', 'name_ar' => 'مصر'],
            ['code' => 'ARE', 'name_en' => 'United Arab Emirates', 'name_ar' => 'الإمارات العربية المتحدة'],
            ['code' => 'KWT', 'name_en' => 'Kuwait', 'name_ar' => 'الكويت'],
            ['code' => 'QAT', 'name_en' => 'Qatar', 'name_ar' => 'قطر'],
            ['code' => 'BHR', 'name_en' => 'Bahrain', 'name_ar' => 'البحرين'],
            ['code' => 'OMN', 'name_en' => 'Oman', 'name_ar' => 'سلطنة عمان'],
            ['code' => 'YEM', 'name_en' => 'Yemen', 'name_ar' => 'اليمن'],
            ['code' => 'JOR', 'name_en' => 'Jordan', 'name_ar' => 'الأردن'],
            ['code' => 'LBN', 'name_en' => 'Lebanon', 'name_ar' => 'لبنان'],
            ['code' => 'SYR', 'name_en' => 'Syria', 'name_ar' => 'سوريا'],
            ['code' => 'IRQ', 'name_en' => 'Iraq', 'name_ar' => 'العراق'],
            ['code' => 'PSE', 'name_en' => 'Palestine', 'name_ar' => 'فلسطين'],
            ['code' => 'SDN', 'name_en' => 'Sudan', 'name_ar' => 'السودان'],
            ['code' => 'LBY', 'name_en' => 'Libya', 'name_ar' => 'ليبيا'],
            ['code' => 'MAR', 'name_en' => 'Morocco', 'name_ar' => 'المغرب'],
            ['code' => 'TUN', 'name_en' => 'Tunisia', 'name_ar' => 'تونس'],
            ['code' => 'DZA', 'name_en' => 'Algeria', 'name_ar' => 'الجزائر'],
            ['code' => 'MRT', 'name_en' => 'Mauritania', 'name_ar' => 'موريتانيا'],
            ['code' => 'SOM', 'name_en' => 'Somalia', 'name_ar' => 'الصومال'],
            ['code' => 'DJI', 'name_en' => 'Djibouti', 'name_ar' => 'جيبوتي'],
            ['code' => 'COM', 'name_en' => 'Comoros', 'name_ar' => 'جزر القمر'],

            // Major Countries
            ['code' => 'USA', 'name_en' => 'United States', 'name_ar' => 'الولايات المتحدة الأمريكية'],
            ['code' => 'GBR', 'name_en' => 'United Kingdom', 'name_ar' => 'المملكة المتحدة'],
            ['code' => 'IND', 'name_en' => 'India', 'name_ar' => 'الهند'],
            ['code' => 'PAK', 'name_en' => 'Pakistan', 'name_ar' => 'باكستان'],
            ['code' => 'PHL', 'name_en' => 'Philippines', 'name_ar' => 'الفلبين'],
            ['code' => 'BGD', 'name_en' => 'Bangladesh', 'name_ar' => 'بنغلاديش'],
            ['code' => 'IDN', 'name_en' => 'Indonesia', 'name_ar' => 'إندونيسيا'],
        ];

        foreach ($nationalities as $nationality) {
            DB::table('nationalities')->updateOrInsert(
                ['code' => $nationality['code']], // Check by Code
                [
                    'name_en' => $nationality['name_en'],
                    'name_ar' => $nationality['name_ar'],
                    'is_active' => true,
                    'created_at' => now(), // only inserts current time if created
                    'updated_at' => now(),
                ]
            );
        }
    }
}
