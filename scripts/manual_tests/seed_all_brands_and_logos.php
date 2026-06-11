<?php

use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$makes = [
    'Toyota' => [
        'name_ar' => 'تويوتا',
        'slug' => 'toyota',
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
        ]
    ],
    'Lexus' => [
        'name_ar' => 'لكزس',
        'slug' => 'lexus',
        'models' => [
            ['en' => 'LX', 'ar' => 'LX'],
            ['en' => 'ES', 'ar' => 'ES'],
            ['en' => 'LS', 'ar' => 'LS'],
            ['en' => 'RX', 'ar' => 'RX'],
            ['en' => 'IS', 'ar' => 'IS'],
            ['en' => 'GX', 'ar' => 'GX'],
            ['en' => 'NX', 'ar' => 'NX'],
            ['en' => 'UX', 'ar' => 'UX'],
        ]
    ],
    'Hyundai' => [
        'name_ar' => 'هيونداي',
        'slug' => 'hyundai',
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
        ]
    ],
    'Kia' => [
        'name_ar' => 'كيا',
        'slug' => 'kia',
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
        ]
    ],
    'Nissan' => [
        'name_ar' => 'نيسان',
        'slug' => 'nissan',
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
        ]
    ],
    'Honda' => [
        'name_ar' => 'هوندا',
        'slug' => 'honda',
        'models' => [
            ['en' => 'Accord', 'ar' => 'أكورد'],
            ['en' => 'Civic', 'ar' => 'سيفيك'],
            ['en' => 'City', 'ar' => 'سيتي'],
            ['en' => 'CR-V', 'ar' => 'سي آر في'],
            ['en' => 'Pilot', 'ar' => 'بايلوت'],
            ['en' => 'Odyssey', 'ar' => 'أوديسي'],
            ['en' => 'HR-V', 'ar' => 'إتش آر في'],
        ]
    ],
    'Mazda' => [
        'name_ar' => 'مازدا',
        'slug' => 'mazda',
        'models' => [
            ['en' => 'Mazda 6', 'ar' => 'مازدا 6'],
            ['en' => 'Mazda 3', 'ar' => 'مازدا 3'],
            ['en' => 'CX-9', 'ar' => 'سي إكس 9'],
            ['en' => 'CX-5', 'ar' => 'سي إكس 5'],
            ['en' => 'CX-30', 'ar' => 'سي إكس 30'],
            ['en' => 'CX-3', 'ar' => 'سي إكس 3'],
            ['en' => 'CX-60', 'ar' => 'سي إكس 60'],
        ]
    ],
    'Ford' => [
        'name_ar' => 'فورد',
        'slug' => 'ford',
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
        ]
    ],
    'Chevrolet' => [
        'name_ar' => 'شيفروليه',
        'slug' => 'chevrolet',
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
        ]
    ],
    'GMC' => [
        'name_ar' => 'جي إم سي',
        'slug' => 'gmc',
        'models' => [
            ['en' => 'Yukon', 'ar' => 'يوكن'],
            ['en' => 'Sierra', 'ar' => 'سييرا'],
            ['en' => 'Acadia', 'ar' => 'أكاديا'],
            ['en' => 'Terrain', 'ar' => 'تيرين'],
            ['en' => 'Savana', 'ar' => 'سافانا'],
        ]
    ],
    'Jeep' => [
        'name_ar' => 'جيب',
        'slug' => 'jeep',
        'models' => [
            ['en' => 'Wrangler', 'ar' => 'رنجلر'],
            ['en' => 'Grand Cherokee', 'ar' => 'جراند شيروكي'],
            ['en' => 'Cherokee', 'ar' => 'شيروكي'],
            ['en' => 'Compass', 'ar' => 'كومباس'],
            ['en' => 'Gladiator', 'ar' => 'غلاديتور'],
        ]
    ],
    'Dodge' => [
        'name_ar' => 'دودج',
        'slug' => 'dodge',
        'models' => [
            ['en' => 'Charger', 'ar' => 'تشارجر'],
            ['en' => 'Challenger', 'ar' => 'تشالنجر'],
            ['en' => 'Durango', 'ar' => 'دورانجو'],
            ['en' => 'Ram', 'ar' => 'رام'],
        ]
    ],
    'Chrysler' => [
        'name_ar' => 'كرايسلر',
        'slug' => 'chrysler',
        'models' => [
            ['en' => '300C', 'ar' => '300 سي'],
            ['en' => 'Pacifica', 'ar' => 'باسيفيكا'],
        ]
    ],
    'Cadillac' => [
        'name_ar' => 'كاديلاك',
        'slug' => 'cadillac',
        'models' => [
            ['en' => 'Escalade', 'ar' => 'إسكاليد'],
            ['en' => 'CTS', 'ar' => 'سي تي إس'],
            ['en' => 'XT5', 'ar' => 'إكس تي 5'],
            ['en' => 'CT6', 'ar' => 'سي تي 6'],
        ]
    ],
    'Lincoln' => [
        'name_ar' => 'لينكون',
        'slug' => 'lincoln',
        'models' => [
            ['en' => 'Navigator', 'ar' => 'نافيجاتور'],
            ['en' => 'Aviator', 'ar' => 'أفياتور'],
            ['en' => 'Continental', 'ar' => 'كونتيننتال'],
        ]
    ],
    'BMW' => [
        'name_ar' => 'بي إم دبليو',
        'slug' => 'bmw',
        'models' => [
            ['en' => '3 Series', 'ar' => 'الفئة الثالثة'],
            ['en' => '5 Series', 'ar' => 'الفئة الخامسة'],
            ['en' => '7 Series', 'ar' => 'الفئة السابعة'],
            ['en' => 'X5', 'ar' => 'إكس 5'],
            ['en' => 'X6', 'ar' => 'إكس 6'],
            ['en' => 'X7', 'ar' => 'إكس 7'],
            ['en' => 'i8', 'ar' => 'آي 8'],
        ]
    ],
    'Mercedes-Benz' => [
        'name_ar' => 'مرسيدس بنز',
        'slug' => 'mercedes-benz',
        'models' => [
            ['en' => 'C-Class', 'ar' => 'الفئة C'],
            ['en' => 'E-Class', 'ar' => 'الفئة E'],
            ['en' => 'S-Class', 'ar' => 'الفئة S'],
            ['en' => 'G-Class', 'ar' => 'الفئة G'],
            ['en' => 'GLC', 'ar' => 'جي إل سي'],
            ['en' => 'GLE', 'ar' => 'جي إل إي'],
            ['en' => 'GLS', 'ar' => 'جي إل إس'],
            ['en' => 'A-Class', 'ar' => 'الفئة A'],
        ]
    ],
    'Audi' => [
        'name_ar' => 'أودي',
        'slug' => 'audi',
        'models' => [
            ['en' => 'A4', 'ar' => 'أيه 4'],
            ['en' => 'A6', 'ar' => 'أيه 6'],
            ['en' => 'A8', 'ar' => 'أيه 8'],
            ['en' => 'Q5', 'ar' => 'كيو 5'],
            ['en' => 'Q7', 'ar' => 'كيو 7'],
            ['en' => 'Q8', 'ar' => 'كيو 8'],
            ['en' => 'e-tron', 'ar' => 'إي ترون'],
        ]
    ],
    'Porsche' => [
        'name_ar' => 'بورش',
        'slug' => 'porsche',
        'models' => [
            ['en' => 'Cayenne', 'ar' => 'كايين'],
            ['en' => 'Panamera', 'ar' => 'باناميرا'],
            ['en' => 'Macan', 'ar' => 'ماكان'],
            ['en' => '911', 'ar' => '911'],
            ['en' => 'Taycan', 'ar' => 'تايكان'],
        ]
    ],
    'Land Rover' => [
        'name_ar' => 'لاند روفر',
        'slug' => 'land-rover',
        'models' => [
            ['en' => 'Range Rover', 'ar' => 'رينج روفر'],
            ['en' => 'Range Rover Sport', 'ar' => 'رينج روفر سبورت'],
            ['en' => 'Range Rover Velar', 'ar' => 'رينج روفر فيلار'],
            ['en' => 'Range Rover Evoque', 'ar' => 'رينج روفر إيفوك'],
            ['en' => 'Defender', 'ar' => 'ديفندر'],
            ['en' => 'Discovery', 'ar' => 'ديسكفري'],
        ]
    ],
    'Jaguar' => [
        'name_ar' => 'جاكوار',
        'slug' => 'jaguar',
        'models' => [
            ['en' => 'F-Pace', 'ar' => 'إف بيس'],
            ['en' => 'XF', 'ar' => 'إكس إف'],
            ['en' => 'XJ', 'ar' => 'إكس جي'],
        ]
    ],
    'Volkswagen' => [
        'name_ar' => 'فولكس فاجن',
        'slug' => 'volkswagen',
        'models' => [
            ['en' => 'Passat', 'ar' => 'باسات'],
            ['en' => 'Jetta', 'ar' => 'جيتا'],
            ['en' => 'Golf', 'ar' => 'غولف'],
            ['en' => 'Touareg', 'ar' => 'طوارق'],
            ['en' => 'Tiguan', 'ar' => 'تيجوان'],
            ['en' => 'Teramont', 'ar' => 'تيرامونت'],
        ]
    ],
    'Volvo' => [
        'name_ar' => 'فولفو',
        'slug' => 'volvo',
        'models' => [
            ['en' => 'XC90', 'ar' => 'إكس سي 90'],
            ['en' => 'XC60', 'ar' => 'إكس سي 60'],
            ['en' => 'S90', 'ar' => 'إس 90'],
        ]
    ],
    'Infiniti' => [
        'name_ar' => 'إنفينيتي',
        'slug' => 'infiniti',
        'models' => [
            ['en' => 'QX80', 'ar' => 'كيو إكس 80'],
            ['en' => 'QX60', 'ar' => 'كيو إكس 60'],
            ['en' => 'Q50', 'ar' => 'كيو 50'],
        ]
    ],
    'Changan' => [
        'name_ar' => 'شانجان',
        'slug' => 'changan',
        'models' => [
            ['en' => 'CS95', 'ar' => 'سي إس 95'],
            ['en' => 'CS75', 'ar' => 'سي إس 75'],
            ['en' => 'CS35 Plus', 'ar' => 'سي إس 35 بلس'],
            ['en' => 'Eado', 'ar' => 'إيدو'],
            ['en' => 'Alsvin', 'ar' => 'ألسفن'],
            ['en' => 'UNI-K', 'ar' => 'يوني كي'],
            ['en' => 'UNI-T', 'ar' => 'يوني تي'],
        ]
    ],
    'Geely' => [
        'name_ar' => 'جيلي',
        'slug' => 'geely',
        'models' => [
            ['en' => 'Coolray', 'ar' => 'كولراي'],
            ['en' => 'Emgrand', 'ar' => 'إمجراند'],
            ['en' => 'Tugella', 'ar' => 'توجيلا'],
            ['en' => 'Monjaro', 'ar' => 'مونجارو'],
            ['en' => 'Okavango', 'ar' => 'أوكافانجو'],
            ['en' => 'Geometry C', 'ar' => 'جيومتري سي'],
        ]
    ],
    'MG' => [
        'name_ar' => 'إم جي',
        'slug' => 'mg',
        'models' => [
            ['en' => 'MG 5', 'ar' => 'إم جي 5'],
            ['en' => 'MG 6', 'ar' => 'إم جي 6'],
            ['en' => 'MG ZS', 'ar' => 'إم جي زد إس'],
            ['en' => 'MG HS', 'ar' => 'إم جي إتش إس'],
            ['en' => 'RX8', 'ar' => 'أر إكس 8'],
        ]
    ],
    'Haval' => [
        'name_ar' => 'هافال',
        'slug' => 'haval',
        'models' => [
            ['en' => 'H6', 'ar' => 'إتش 6'],
            ['en' => 'Jolion', 'ar' => 'جوليان'],
            ['en' => 'Dargo', 'ar' => 'دارغو'],
            ['en' => 'H9', 'ar' => 'إتش 9'],
        ]
    ],
    'BYD' => [
        'name_ar' => 'بي واي دي',
        'slug' => 'byd',
        'models' => [
            ['en' => 'Han', 'ar' => 'هان'],
            ['en' => 'Song Plus', 'ar' => 'سونغ بلس'],
            ['en' => 'Qin Plus', 'ar' => 'تشين بلس'],
            ['en' => 'Tang', 'ar' => 'تانغ'],
            ['en' => 'Atto 3', 'ar' => 'أتو 3'],
        ]
    ],
    'Genesis' => [
        'name_ar' => 'جينيسيس',
        'slug' => 'genesis',
        'models' => [
            ['en' => 'G70', 'ar' => 'جي 70'],
            ['en' => 'G80', 'ar' => 'جي 80'],
            ['en' => 'G90', 'ar' => 'جي 90'],
            ['en' => 'GV80', 'ar' => 'جي في 80'],
            ['en' => 'GV70', 'ar' => 'جي في 70'],
        ]
    ],
    'Suzuki' => [
        'name_ar' => 'سوزوكي',
        'slug' => 'suzuki',
        'models' => [
            ['en' => 'Swift', 'ar' => 'سويفت'],
            ['en' => 'Jimny', 'ar' => 'جيمني'],
            ['en' => 'Dzire', 'ar' => 'ديزاير'],
            ['en' => 'Baleno', 'ar' => 'بالينو'],
            ['en' => 'Ertiga', 'ar' => 'أرتيجا'],
        ]
    ],
    'Mitsubishi' => [
        'name_ar' => 'ميتسوبيشي',
        'slug' => 'mitsubishi',
        'models' => [
            ['en' => 'Pajero', 'ar' => 'باجيرو'],
            ['en' => 'L200', 'ar' => 'إل 200'],
            ['en' => 'Outlander', 'ar' => 'أوتلاندر'],
            ['en' => 'Attrage', 'ar' => 'أتراج'],
            ['en' => 'Eclipse Cross', 'ar' => 'إكليبس كروس'],
            ['en' => 'Mirage', 'ar' => 'ميراج'],
            ['en' => 'Montero Sport', 'ar' => 'مونتيرو سبورت'],
        ]
    ],
    'Isuzu' => [
        'name_ar' => 'ايسوزو',
        'slug' => 'isuzu',
        'models' => [
            ['en' => 'D-Max', 'ar' => 'دي ماكس'],
            ['en' => 'MU-X', 'ar' => 'ام يو اكس'],
        ]
    ],
    'Tesla' => [
        'name_ar' => 'تسلا',
        'slug' => 'tesla',
        'models' => [
            ['en' => 'Model 3', 'ar' => 'موديل 3'],
            ['en' => 'Model Y', 'ar' => 'موديل واي'],
            ['en' => 'Model S', 'ar' => 'موديل إس'],
            ['en' => 'Model X', 'ar' => 'موديل إكس'],
        ]
    ],
    'Great Wall' => [
        'name_ar' => 'جريت وول',
        'slug' => 'great-wall',
        'models' => [
            ['en' => 'Poer', 'ar' => 'باور'],
            ['en' => 'Wingle', 'ar' => 'وينجل'],
        ]
    ],
    // ========================================
    // NEW BRANDS REQUESTED (22 Brands)
    // ========================================
    'Renault' => [
        'name_ar' => 'رينو',
        'slug' => 'renault',
        'models' => [
            ['en' => 'Duster', 'ar' => 'داستر'],
            ['en' => 'Koleos', 'ar' => 'كوليوس'],
            ['en' => 'Megane', 'ar' => 'ميغان'],
            ['en' => 'Talisman', 'ar' => 'تالسمان'],
            ['en' => 'Symbol', 'ar' => 'سيمبول'],
        ]
    ],
    'Peugeot' => [
        'name_ar' => 'بيجو',
        'slug' => 'peugeot',
        'models' => [
            ['en' => '3008', 'ar' => '3008'],
            ['en' => '5008', 'ar' => '5008'],
            ['en' => '508', 'ar' => '508'],
            ['en' => '2008', 'ar' => '2008'],
            ['en' => 'Partner', 'ar' => 'بارتنر'],
        ]
    ],
    'Subaru' => [
        'name_ar' => 'سوبارو',
        'slug' => 'subaru',
        'models' => [
            ['en' => 'Outback', 'ar' => 'أوتباك'],
            ['en' => 'Forester', 'ar' => 'فورستر'],
            ['en' => 'XV', 'ar' => 'إكس في'],
            ['en' => 'WRX', 'ar' => 'دبليو آر إكس'],
        ]
    ],
    'Fiat' => [
        'name_ar' => 'فيات',
        'slug' => 'fiat',
        'models' => [
            ['en' => '500', 'ar' => '500'],
            ['en' => '500X', 'ar' => '500 إكس'],
            ['en' => 'Fiorino', 'ar' => 'فيورينو'],
        ]
    ],
    'Chery' => [
        'name_ar' => 'شيري',
        'slug' => 'chery',
        'models' => [
            ['en' => 'Tiggo 7 Pro', 'ar' => 'تيجو 7 برو'],
            ['en' => 'Tiggo 8 Pro', 'ar' => 'تيجو 8 برو'],
            ['en' => 'Tiggo 4 Pro', 'ar' => 'تيجو 4 برو'],
            ['en' => 'Arrizo 6 Pro', 'ar' => 'أريزو 6 برو'],
        ]
    ],
    'Jetour' => [
        'name_ar' => 'جيتور',
        'slug' => 'jetour',
        'models' => [
            ['en' => 'X70', 'ar' => 'إكس 70'],
            ['en' => 'X90', 'ar' => 'إكس 90'],
            ['en' => 'Dashing', 'ar' => 'داشينج'],
            ['en' => 'T2', 'ar' => 'تي 2'],
        ]
    ],
    'JAC' => [
        'name_ar' => 'جاك',
        'slug' => 'jac',
        'models' => [
            ['en' => 'J7', 'ar' => 'جي 7'],
            ['en' => 'S3', 'ar' => 'إس 3'],
            ['en' => 'T8', 'ar' => 'تي 8'],
        ]
    ],
    'GAC' => [
        'name_ar' => 'جي أي سي',
        'slug' => 'gac',
        'models' => [
            ['en' => 'GS8', 'ar' => 'جي إس 8'],
            ['en' => 'GS4', 'ar' => 'جي إس 4'],
            ['en' => 'GA6', 'ar' => 'جي أيه 6'],
            ['en' => 'EMPOW', 'ar' => 'إمباو'],
        ]
    ],
    'Hongqi' => [
        'name_ar' => 'هونشي',
        'slug' => 'hongqi',
        'models' => [
            ['en' => 'H5', 'ar' => 'إتش 5'],
            ['en' => 'H9', 'ar' => 'إتش 9'],
            ['en' => 'HS5', 'ar' => 'إتش إس 5'],
            ['en' => 'Oseado', 'ar' => 'أوسادو'],
        ]
    ],
    'Tank' => [
        'name_ar' => 'تانك',
        'slug' => 'tank',
        'models' => [
            ['en' => 'Tank 300', 'ar' => 'تانك 300'],
            ['en' => 'Tank 500', 'ar' => 'تانك 500'],
        ]
    ],
    'Exeed' => [
        'name_ar' => 'إكسيد',
        'slug' => 'exeed',
        'models' => [
            ['en' => 'TXL', 'ar' => 'تي إكس إل'],
            ['en' => 'VX', 'ar' => 'في إكس'],
            ['en' => 'RX', 'ar' => 'آر إكس'],
        ]
    ],
    'Bestune' => [
        'name_ar' => 'بيستون',
        'slug' => 'bestune',
        'models' => [
            ['en' => 'T77 Pro', 'ar' => 'تي 77 برو'],
            ['en' => 'T99', 'ar' => 'تي 99'],
            ['en' => 'B70', 'ar' => 'بي 70'],
        ]
    ],
    'Aston Martin' => [
        'name_ar' => 'أستون مارتن',
        'slug' => 'aston-martin',
        'models' => [
            ['en' => 'DBX', 'ar' => 'دي بي إكس'],
            ['en' => 'Vantage', 'ar' => 'فانتاج'],
            ['en' => 'DB11', 'ar' => 'دي بي 11'],
        ]
    ],
    'Ferrari' => [
        'name_ar' => 'فيراري',
        'slug' => 'ferrari',
        'models' => [
            ['en' => '488', 'ar' => '488'],
            ['en' => 'Roma', 'ar' => 'روما'],
            ['en' => 'Portofino', 'ar' => 'بورتوفينو'],
            ['en' => 'F8', 'ar' => 'إف 8'],
        ]
    ],
    'Lamborghini' => [
        'name_ar' => 'لامبورغيني',
        'slug' => 'lamborghini',
        'models' => [
            ['en' => 'Urus', 'ar' => 'أوروس'],
            ['en' => 'Huracan', 'ar' => 'هوراكان'],
            ['en' => 'Aventador', 'ar' => 'أفنتادور'],
        ]
    ],
    'Bentley' => [
        'name_ar' => 'بنتلي',
        'slug' => 'bentley',
        'models' => [
            ['en' => 'Bentayga', 'ar' => 'بينتايجا'],
            ['en' => 'Continental GT', 'ar' => 'كونتيننتال جي تي'],
            ['en' => 'Flying Spur', 'ar' => 'فلاينج سبير'],
        ]
    ],
    'Rolls-Royce' => [
        'name_ar' => 'رولز رويس',
        'slug' => 'rolls-royce',
        'models' => [
            ['en' => 'Cullinan', 'ar' => 'كولينان'],
            ['en' => 'Ghost', 'ar' => 'غوست'],
            ['en' => 'Phantom', 'ar' => 'فانتوم'],
            ['en' => 'Wraith', 'ar' => 'ريث'],
        ]
    ],
    'Maserati' => [
        'name_ar' => 'مازيراتي',
        'slug' => 'maserati',
        'models' => [
            ['en' => 'Levante', 'ar' => 'ليفانتي'],
            ['en' => 'Ghibli', 'ar' => 'جيبلي'],
            ['en' => 'Quattroporte', 'ar' => 'كواتروبورتي'],
        ]
    ],
    'Skoda' => [
        'name_ar' => 'سكودا',
        'slug' => 'skoda',
        'models' => [
            ['en' => 'Octavia', 'ar' => 'أوكتافيا'],
            ['en' => 'Kodiaq', 'ar' => 'كودياك'],
            ['en' => 'Superb', 'ar' => 'سوبيرب'],
        ]
    ],
    'Foton' => [
        'name_ar' => 'فوتون',
        'slug' => 'foton',
        'models' => [
            ['en' => 'Tunland', 'ar' => 'تونلاند'],
            ['en' => 'View', 'ar' => 'فيو'],
        ]
    ],
    'DFSK' => [
        'name_ar' => 'دي اف اس كي',
        'slug' => 'dfsk',
        'models' => [
            ['en' => 'Glory 580', 'ar' => 'غلوري 580'],
            ['en' => 'EC35', 'ar' => 'إي سي 35'],
        ]
    ],
    'SsangYong' => [
        'name_ar' => 'سانج يونج',
        'slug' => 'ssangyong',
        'models' => [
            ['en' => 'Korando', 'ar' => 'كوراندو'],
            ['en' => 'Rexton', 'ar' => 'ريكتون'],
            ['en' => 'Tivoli', 'ar' => 'تيفولي'],
        ]
    ],
];

// Ensure public/makes storage directory exists
if (!Storage::disk('public')->exists('makes')) {
    Storage::disk('public')->makeDirectory('makes');
}

echo "Starting seeding for " . count($makes) . " brands and their models...\n";

foreach ($makes as $makeName => $makeData) {
    // 1. Check if logo already exists or download it
    $logoPath = null;
    $slug = $makeData['slug'];
    $potentialFile = "makes/{$slug}.png";
    
    // Attempt download from the optimized github dataset
    $url = "https://raw.githubusercontent.com/filippofilip95/car-logos-dataset/master/logos/optimized/{$slug}.png";
    
    echo "Processing {$makeName}... ";
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $content) {
            // Save with unique name to prevent cache issues and match system pattern
            $hash = Str::random(40);
            $logoFilename = "makes/{$hash}.png";
            Storage::disk('public')->put($logoFilename, $content);
            $logoPath = $logoFilename;
            echo "Logo Downloaded. ";
        } else {
            echo "Logo not found (HTTP {$httpCode}). ";
        }
    } catch (\Exception $e) {
        echo "Logo download error: " . $e->getMessage() . ". ";
    }
    
    // 2. Create or Update Make
    $make = VehicleMake::updateOrCreate([
        'name_en' => $makeName,
        'source' => 'system'
    ], [
        'name_ar' => $makeData['name_ar'],
        'is_active' => true,
        'tenant_id' => null,
        'center_id' => null,
    ]);
    
    // Update logo if we downloaded one
    if ($logoPath) {
        $make->update(['logo_path' => $logoPath]);
    }
    
    // 3. Create or Update Models
    foreach ($makeData['models'] as $modelData) {
        VehicleModel::updateOrCreate([
            'make_id' => $make->id,
            'name_en' => $modelData['en'],
            'source' => 'system'
        ], [
            'name_ar' => $modelData['ar'],
            'is_active' => true,
            'tenant_id' => null,
            'center_id' => null,
        ]);
    }
    
    echo "Done.\n";
}

echo "All brands, models, and logos seeded successfully!\n";
