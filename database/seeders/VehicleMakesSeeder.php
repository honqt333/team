<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleMakesSeeder extends Seeder
{
    public function run(): void
    {
        $makes = [
            'Toyota' => [
                'name_ar' => 'تويوتا',
                'models' => [
                    ['en' => 'Camry', 'ar' => 'كامري'],
                    ['en' => 'Corolla', 'ar' => 'كورولا'],
                    ['en' => 'Land Cruiser', 'ar' => 'لاند كروزر'],
                    ['en' => 'Hilux', 'ar' => 'هايلكس'],
                    ['en' => 'Prado', 'ar' => 'برادو'],
                    ['en' => 'Yaris', 'ar' => 'يارس'],
                    ['en' => 'RAV4', 'ar' => 'راف فور'],
                    ['en' => 'Fortuner', 'ar' => 'فورتشنر'],
                    ['en' => 'Avalon', 'ar' => 'أفالون'],
                    ['en' => 'Innova', 'ar' => 'إنوفا'],
                    ['en' => 'FJ Cruiser', 'ar' => 'إف جي كروزر'],
                    ['en' => 'Rush', 'ar' => 'راش'],
                    ['en' => 'Supra', 'ar' => 'سوبرا'],
                    ['en' => 'C-HR', 'ar' => 'سي اتش آر'],
                ],
            ],
            'Lexus' => [
                'name_ar' => 'لكزس',
                'models' => [
                    ['en' => 'LX', 'ar' => 'LX'],
                    ['en' => 'ES', 'ar' => 'ES'],
                    ['en' => 'LS', 'ar' => 'LS'],
                    ['en' => 'RX', 'ar' => 'RX'],
                    ['en' => 'IS', 'ar' => 'IS'],
                    ['en' => 'GX', 'ar' => 'GX'],
                    ['en' => 'NX', 'ar' => 'NX'],
                    ['en' => 'UX', 'ar' => 'UX'],
                ],
            ],
            'Hyundai' => [
                'name_ar' => 'هيونداي',
                'models' => [
                    ['en' => 'Sonata', 'ar' => 'سوناتا'],
                    ['en' => 'Elantra', 'ar' => 'إلنترا'],
                    ['en' => 'Tucson', 'ar' => 'توسان'],
                    ['en' => 'Santa Fe', 'ar' => 'سنتافي'],
                    ['en' => 'Accent', 'ar' => 'أكسنت'],
                    ['en' => 'Creta', 'ar' => 'كريتا'],
                    ['en' => 'Kona', 'ar' => 'كونا'],
                    ['en' => 'Palisade', 'ar' => 'باليسيد'],
                    ['en' => 'Azera', 'ar' => 'أزيرا'],
                    ['en' => 'H1', 'ar' => 'إتش 1'],
                    ['en' => 'Grandeur', 'ar' => 'غراندور'],
                    ['en' => 'Stararia', 'ar' => 'ستاريا'],
                ],
            ],
            'Kia' => [
                'name_ar' => 'كيا',
                'models' => [
                    ['en' => 'Optima', 'ar' => 'أوبتيما'],
                    ['en' => 'K5', 'ar' => 'كي 5'],
                    ['en' => 'Cerato', 'ar' => 'سيراتو'],
                    ['en' => 'Rio', 'ar' => 'ريو'],
                    ['en' => 'Sportage', 'ar' => 'سبورتاج'],
                    ['en' => 'Sorento', 'ar' => 'سورينتو'],
                    ['en' => 'Carnival', 'ar' => 'كارنيفال'],
                    ['en' => 'Cadenza', 'ar' => 'كادينزا'],
                    ['en' => 'Telluride', 'ar' => 'تيلورايد'],
                    ['en' => 'Pegas', 'ar' => 'بيجاس'],
                    ['en' => 'Sonet', 'ar' => 'سونيت'],
                    ['en' => 'Picanto', 'ar' => 'بيكانتو'],
                ],
            ],
            'Nissan' => [
                'name_ar' => 'نيسان',
                'models' => [
                    ['en' => 'Patrol', 'ar' => 'باترول'],
                    ['en' => 'Sunny', 'ar' => 'صني'],
                    ['en' => 'Altima', 'ar' => 'ألتيما'],
                    ['en' => 'Maxima', 'ar' => 'مكسيما'],
                    ['en' => 'Pathfinder', 'ar' => 'باثفندر'],
                    ['en' => 'X-Trail', 'ar' => 'اكس تريل'],
                    ['en' => 'Sentra', 'ar' => 'سنترا'],
                    ['en' => 'Navara', 'ar' => 'نافارا'],
                    ['en' => 'Kicks', 'ar' => 'كيكس'],
                    ['en' => 'Urvan', 'ar' => 'أورفان'],
                ],
            ],
            'Honda' => [
                'name_ar' => 'هوندا',
                'models' => [
                    ['en' => 'Accord', 'ar' => 'أكورد'],
                    ['en' => 'Civic', 'ar' => 'سيفيك'],
                    ['en' => 'City', 'ar' => 'سيتي'],
                    ['en' => 'CR-V', 'ar' => 'سي آر في'],
                    ['en' => 'Pilot', 'ar' => 'بايلوت'],
                    ['en' => 'Odyssey', 'ar' => 'أوديسي'],
                    ['en' => 'HR-V', 'ar' => 'إتش آر في'],
                ],
            ],
            'Mazda' => [
                'name_ar' => 'مازدا',
                'models' => [
                    ['en' => 'Mazda 6', 'ar' => 'مازدا 6'],
                    ['en' => 'Mazda 3', 'ar' => 'مازدا 3'],
                    ['en' => 'CX-9', 'ar' => 'سي إكس 9'],
                    ['en' => 'CX-5', 'ar' => 'سي إكس 5'],
                    ['en' => 'CX-30', 'ar' => 'سي إكس 30'],
                    ['en' => 'CX-3', 'ar' => 'سي إكس 3'],
                    ['en' => 'CX-60', 'ar' => 'سي إكس 60'],
                ],
            ],
            'Ford' => [
                'name_ar' => 'فورد',
                'models' => [
                    ['en' => 'Expedition', 'ar' => 'إكسبديشن'],
                    ['en' => 'Explorer', 'ar' => 'إكسبلورر'],
                    ['en' => 'Taurus', 'ar' => 'تورس'],
                    ['en' => 'F-150', 'ar' => 'إف 150'],
                    ['en' => 'Mustang', 'ar' => 'موستنج'],
                    ['en' => 'Edge', 'ar' => 'إيدج'],
                    ['en' => 'Territory', 'ar' => 'تيريتوري'],
                    ['en' => 'Ranger', 'ar' => 'رينجر'],
                    ['en' => 'Crown Victoria', 'ar' => 'كراون فكتوريا'],
                ],
            ],
            'Chevrolet' => [
                'name_ar' => 'شيفروليه',
                'models' => [
                    ['en' => 'Tahoe', 'ar' => 'تاهو'],
                    ['en' => 'Suburban', 'ar' => 'سوبربان'],
                    ['en' => 'Silverado', 'ar' => 'سلفرادو'],
                    ['en' => 'Caprice', 'ar' => 'كابرس'],
                    ['en' => 'Impala', 'ar' => 'إمبالا'],
                    ['en' => 'Malibu', 'ar' => 'ماليبو'],
                    ['en' => 'Cruze', 'ar' => 'كروز'],
                    ['en' => 'Spark', 'ar' => 'سبارك'],
                    ['en' => 'Traverse', 'ar' => 'ترافيرس'],
                ],
            ],
            'GMC' => [
                'name_ar' => 'جي إم سي',
                'models' => [
                    ['en' => 'Yukon', 'ar' => 'يوكن'],
                    ['en' => 'Sierra', 'ar' => 'سييرا'],
                    ['en' => 'Acadia', 'ar' => 'أكاديا'],
                    ['en' => 'Terrain', 'ar' => 'تيرين'],
                    ['en' => 'Savana', 'ar' => 'سافانا'],
                ],
            ],
            'Jeep' => [
                'name_ar' => 'جيب',
                'models' => [
                    ['en' => 'Wrangler', 'ar' => 'رنجلر'],
                    ['en' => 'Grand Cherokee', 'ar' => 'جراند شيروكي'],
                    ['en' => 'Cherokee', 'ar' => 'شيروكي'],
                    ['en' => 'Compass', 'ar' => 'كومباس'],
                    ['en' => 'Gladiator', 'ar' => 'غلاديتور'],
                ],
            ],
            'Dodge' => [
                'name_ar' => 'دودج',
                'models' => [
                    ['en' => 'Charger', 'ar' => 'تشارجر'],
                    ['en' => 'Challenger', 'ar' => 'تشالنجر'],
                    ['en' => 'Durango', 'ar' => 'دورانجو'],
                    ['en' => 'Ram', 'ar' => 'رام'],
                ],
            ],
            'Chrysler' => [
                'name_ar' => 'كرايسلر',
                'models' => [
                    ['en' => '300C', 'ar' => '300 سي'],
                    ['en' => 'Pacifica', 'ar' => 'باسيفيكا'],
                ],
            ],
            'Cadillac' => [
                'name_ar' => 'كاديلاك',
                'models' => [
                    ['en' => 'Escalade', 'ar' => 'إسكاليد'],
                    ['en' => 'CTS', 'ar' => 'سي تي إس'],
                    ['en' => 'XT5', 'ar' => 'إكس تي 5'],
                    ['en' => 'CT6', 'ar' => 'سي تي 6'],
                ],
            ],
            'Lincoln' => [
                'name_ar' => 'لينكون',
                'models' => [
                    ['en' => 'Navigator', 'ar' => 'نافيجاتور'],
                    ['en' => 'Aviator', 'ar' => 'أفياتور'],
                    ['en' => 'Continental', 'ar' => 'كونتيننتال'],
                ],
            ],
            'BMW' => [
                'name_ar' => 'بي إم دبليو',
                'models' => [
                    ['en' => '3 Series', 'ar' => 'الفئة الثالثة'],
                    ['en' => '5 Series', 'ar' => 'الفئة الخامسة'],
                    ['en' => '7 Series', 'ar' => 'الفئة السابعة'],
                    ['en' => 'X5', 'ar' => 'إكس 5'],
                    ['en' => 'X6', 'ar' => 'إكس 6'],
                    ['en' => 'X7', 'ar' => 'إكس 7'],
                    ['en' => 'i8', 'ar' => 'آي 8'],
                ],
            ],
            'Mercedes-Benz' => [
                'name_ar' => 'مرسيدس بنز',
                'models' => [
                    ['en' => 'C-Class', 'ar' => 'الفئة C'],
                    ['en' => 'E-Class', 'ar' => 'الفئة E'],
                    ['en' => 'S-Class', 'ar' => 'الفئة S'],
                    ['en' => 'G-Class', 'ar' => 'الفئة G'],
                    ['en' => 'GLC', 'ar' => 'جي إل سي'],
                    ['en' => 'GLE', 'ar' => 'جي إل إي'],
                    ['en' => 'GLS', 'ar' => 'جي إل إس'],
                    ['en' => 'A-Class', 'ar' => 'الفئة A'],
                ],
            ],
            'Audi' => [
                'name_ar' => 'أودي',
                'models' => [
                    ['en' => 'A4', 'ar' => 'أيه 4'],
                    ['en' => 'A6', 'ar' => 'أيه 6'],
                    ['en' => 'A8', 'ar' => 'أيه 8'],
                    ['en' => 'Q5', 'ar' => 'كيو 5'],
                    ['en' => 'Q7', 'ar' => 'كيو 7'],
                    ['en' => 'Q8', 'ar' => 'كيو 8'],
                    ['en' => 'e-tron', 'ar' => 'إي ترون'],
                ],
            ],
            'Porsche' => [
                'name_ar' => 'بورش',
                'models' => [
                    ['en' => 'Cayenne', 'ar' => 'كايين'],
                    ['en' => 'Panamera', 'ar' => 'باناميرا'],
                    ['en' => 'Macan', 'ar' => 'ماكان'],
                    ['en' => '911', 'ar' => '911'],
                    ['en' => 'Taycan', 'ar' => 'تايكان'],
                ],
            ],
            'Land Rover' => [
                'name_ar' => 'لاند روفر',
                'models' => [
                    ['en' => 'Range Rover', 'ar' => 'رينج روفر'],
                    ['en' => 'Range Rover Sport', 'ar' => 'رينج روفر سبورت'],
                    ['en' => 'Range Rover Velar', 'ar' => 'رينج روفر فيلار'],
                    ['en' => 'Range Rover Evoque', 'ar' => 'رينج روفر إيفوك'],
                    ['en' => 'Defender', 'ar' => 'ديفندر'],
                    ['en' => 'Discovery', 'ar' => 'ديسكفري'],
                ],
            ],
            'Jaguar' => [
                'name_ar' => 'جاكوار',
                'models' => [
                    ['en' => 'F-Pace', 'ar' => 'إف بيس'],
                    ['en' => 'XF', 'ar' => 'إكس إف'],
                    ['en' => 'XJ', 'ar' => 'إكس جي'],
                ],
            ],
            'Volkswagen' => [
                'name_ar' => 'فولكس فاجن',
                'models' => [
                    ['en' => 'Passat', 'ar' => 'باسات'],
                    ['en' => 'Jetta', 'ar' => 'جيتا'],
                    ['en' => 'Golf', 'ar' => 'غولف'],
                    ['en' => 'Touareg', 'ar' => 'طوارق'],
                    ['en' => 'Tiguan', 'ar' => 'تيجوان'],
                    ['en' => 'Teramont', 'ar' => 'تيرامونت'],
                ],
            ],
            'Volvo' => [
                'name_ar' => 'فولفو',
                'models' => [
                    ['en' => 'XC90', 'ar' => 'إكس سي 90'],
                    ['en' => 'XC60', 'ar' => 'إكس سي 60'],
                    ['en' => 'S90', 'ar' => 'إس 90'],
                ],
            ],
            'Infiniti' => [
                'name_ar' => 'إنفينيتي',
                'models' => [
                    ['en' => 'QX80', 'ar' => 'كيو إكس 80'],
                    ['en' => 'QX60', 'ar' => 'كيو إكس 60'],
                    ['en' => 'Q50', 'ar' => 'كيو 50'],
                ],
            ],
            'Changan' => [
                'name_ar' => 'شانجان',
                'models' => [
                    ['en' => 'CS95', 'ar' => 'سي إس 95'],
                    ['en' => 'CS75', 'ar' => 'سي إس 75'],
                    ['en' => 'CS35 Plus', 'ar' => 'سي إس 35 بلس'],
                    ['en' => 'Eado', 'ar' => 'إيدو'],
                    ['en' => 'Alsvin', 'ar' => 'ألسفن'],
                    ['en' => 'UNI-K', 'ar' => 'يوني كي'],
                    ['en' => 'UNI-T', 'ar' => 'يوني تي'],
                ],
            ],
            'Geely' => [
                'name_ar' => 'جيلي',
                'models' => [
                    ['en' => 'Coolray', 'ar' => 'كولراي'],
                    ['en' => 'Emgrand', 'ar' => 'إمجراند'],
                    ['en' => 'Tugella', 'ar' => 'توجيلا'],
                    ['en' => 'Monjaro', 'ar' => 'مونجارو'],
                    ['en' => 'Okavango', 'ar' => 'أوكافانجو'],
                    ['en' => 'Geometry C', 'ar' => 'جيومتري سي'],
                ],
            ],
            'MG' => [
                'name_ar' => 'إم جي',
                'models' => [
                    ['en' => 'MG 5', 'ar' => 'إم جي 5'],
                    ['en' => 'MG 6', 'ar' => 'إم جي 6'],
                    ['en' => 'MG ZS', 'ar' => 'إم جي زد إس'],
                    ['en' => 'MG HS', 'ar' => 'إم جي إتش إس'],
                    ['en' => 'RX8', 'ar' => 'أر إكس 8'],
                ],
            ],
            'Haval' => [
                'name_ar' => 'هافال',
                'models' => [
                    ['en' => 'H6', 'ar' => 'إتش 6'],
                    ['en' => 'Jolion', 'ar' => 'جوليان'],
                    ['en' => 'Dargo', 'ar' => 'دارغو'],
                    ['en' => 'H9', 'ar' => 'إتش 9'],
                ],
            ],
            'BYD' => [
                'name_ar' => 'بي واي دي',
                'models' => [
                    ['en' => 'Han', 'ar' => 'هان'],
                    ['en' => 'Song Plus', 'ar' => 'سونغ بلس'],
                    ['en' => 'Qin Plus', 'ar' => 'تشين بلس'],
                    ['en' => 'Tang', 'ar' => 'تانغ'],
                    ['en' => 'Atto 3', 'ar' => 'أتو 3'],
                ],
            ],
            'Genesis' => [
                'name_ar' => 'جينيسيس',
                'models' => [
                    ['en' => 'G70', 'ar' => 'جي 70'],
                    ['en' => 'G80', 'ar' => 'جي 80'],
                    ['en' => 'G90', 'ar' => 'جي 90'],
                    ['en' => 'GV80', 'ar' => 'جي في 80'],
                    ['en' => 'GV70', 'ar' => 'جي في 70'],
                ],
            ],
            'Suzuki' => [
                'name_ar' => 'سوزوكي',
                'models' => [
                    ['en' => 'Swift', 'ar' => 'سويفت'],
                    ['en' => 'Jimny', 'ar' => 'جيمني'],
                    ['en' => 'Dzire', 'ar' => 'ديزاير'],
                    ['en' => 'Baleno', 'ar' => 'بالينو'],
                    ['en' => 'Ertiga', 'ar' => 'أرتيجا'],
                ],
            ],
            'Mitsubishi' => [
                'name_ar' => 'ميتسوبيشي',
                'models' => [
                    ['en' => 'Pajero', 'ar' => 'باجيرو'],
                    ['en' => 'L200', 'ar' => 'إل 200'],
                    ['en' => 'Outlander', 'ar' => 'أوتلاندر'],
                    ['en' => 'Attrage', 'ar' => 'أتراج'],
                    ['en' => 'Eclipse Cross', 'ar' => 'إكليبس كروس'],
                    ['en' => 'Mirage', 'ar' => 'ميراج'],
                    ['en' => 'Montero Sport', 'ar' => 'مونتيرو سبورت'],
                ],
            ],
            'Isuzu' => [
                'name_ar' => 'ايسوزو',
                'models' => [
                    ['en' => 'D-Max', 'ar' => 'دي ماكس'],
                    ['en' => 'MU-X', 'ar' => 'ام يو اكس'],
                ],
            ],
            'Tesla' => [
                'name_ar' => 'تسلا',
                'models' => [
                    ['en' => 'Model 3', 'ar' => 'موديل 3'],
                    ['en' => 'Model Y', 'ar' => 'موديل واي'],
                    ['en' => 'Model S', 'ar' => 'موديل إس'],
                    ['en' => 'Model X', 'ar' => 'موديل إكس'],
                ],
            ],
            'Great Wall' => [
                'name_ar' => 'جريت وول',
                'models' => [
                    ['en' => 'Poer', 'ar' => 'باور'],
                    ['en' => 'Wingle', 'ar' => 'وينجل'],
                ],
            ],
        ];

        foreach ($makes as $makeName => $makeData) {
            $make = VehicleMake::updateOrCreate([
                'name_en' => $makeName,
                'source' => 'system',
            ], [
                'name_ar' => $makeData['name_ar'],
                'is_active' => true,
                'tenant_id' => null,
                'center_id' => null,
            ]);

            foreach ($makeData['models'] as $modelData) {
                VehicleModel::updateOrCreate([
                    'make_id' => $make->id,
                    'name_en' => $modelData['en'],
                    'source' => 'system',
                ], [
                    'name_ar' => $modelData['ar'],
                    'is_active' => true,
                    'tenant_id' => null,
                    'center_id' => null,
                ]);
            }
        }
    }
}
