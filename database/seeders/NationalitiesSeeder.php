<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Comprehensive list of all UN-recognized countries + territories
     * (≈195 entries). Each entry has ISO 3166-1 alpha-3 code + EN + AR names.
     *
     * Idempotent — uses updateOrInsert keyed on the `code` column.
     */
    public function run(): void
    {
        $nationalities = $this->getAllNationalities();

        // Deduplicate by `code` — first occurrence wins (keeps the
        // well-translated entries in the dedicated GCC/Arab League
        // sections, ignoring any later generic duplicates).
        $unique = [];
        $seenCodes = [];
        foreach ($nationalities as $nationality) {
            if (in_array($nationality['code'], $seenCodes, true)) {
                continue;
            }
            $seenCodes[] = $nationality['code'];
            $unique[] = $nationality;
        }

        foreach ($unique as $nationality) {
            DB::table('nationalities')->updateOrInsert(
                ['code' => $nationality['code']],
                [
                    'name_en' => $nationality['name_en'],
                    'name_ar' => $nationality['name_ar'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Full list of countries with ISO 3166-1 alpha-3 codes.
     * Names: official English + official Arabic.
     *
     * @return array<int, array{code: string, name_en: string, name_ar: string}>
     */
    private function getAllNationalities(): array
    {
        return [
            // ── GCC (Gulf Cooperation Council) ──────────────────────
            ['code' => 'SAU', 'name_en' => 'Saudi Arabia',         'name_ar' => 'المملكة العربية السعودية'],
            ['code' => 'ARE', 'name_en' => 'United Arab Emirates', 'name_ar' => 'الإمارات العربية المتحدة'],
            ['code' => 'KWT', 'name_en' => 'Kuwait',              'name_ar' => 'الكويت'],
            ['code' => 'QAT', 'name_en' => 'Qatar',               'name_ar' => 'قطر'],
            ['code' => 'BHR', 'name_en' => 'Bahrain',             'name_ar' => 'البحرين'],
            ['code' => 'OMN', 'name_en' => 'Oman',                'name_ar' => 'سلطنة عُمان'],

            // ── Arab League ─────────────────────────────────────────
            ['code' => 'YEM', 'name_en' => 'Yemen',               'name_ar' => 'اليمن'],
            ['code' => 'JOR', 'name_en' => 'Jordan',              'name_ar' => 'الأردن'],
            ['code' => 'LBN', 'name_en' => 'Lebanon',             'name_ar' => 'لبنان'],
            ['code' => 'SYR', 'name_en' => 'Syria',               'name_ar' => 'سوريا'],
            ['code' => 'IRQ', 'name_en' => 'Iraq',                'name_ar' => 'العراق'],
            ['code' => 'PSE', 'name_en' => 'Palestine',           'name_ar' => 'فلسطين'],
            ['code' => 'EGY', 'name_en' => 'Egypt',               'name_ar' => 'مصر'],
            ['code' => 'SDN', 'name_en' => 'Sudan',               'name_ar' => 'السودان'],
            ['code' => 'LBY', 'name_en' => 'Libya',               'name_ar' => 'ليبيا'],
            ['code' => 'TUN', 'name_en' => 'Tunisia',             'name_ar' => 'تونس'],
            ['code' => 'DZA', 'name_en' => 'Algeria',             'name_ar' => 'الجزائر'],
            ['code' => 'MAR', 'name_en' => 'Morocco',             'name_ar' => 'المغرب'],
            ['code' => 'MRT', 'name_en' => 'Mauritania',          'name_ar' => 'موريتانيا'],
            ['code' => 'SOM', 'name_en' => 'Somalia',             'name_ar' => 'الصومال'],
            ['code' => 'DJI', 'name_en' => 'Djibouti',            'name_ar' => 'جيبوتي'],
            ['code' => 'COM', 'name_en' => 'Comoros',             'name_ar' => 'جزر القمر'],

            // ── Asia ────────────────────────────────────────────────
            ['code' => 'AFG', 'name_en' => 'Afghanistan',         'name_ar' => 'أفغانستان'],
            ['code' => 'ARM', 'name_en' => 'Armenia',             'name_ar' => 'أرمينيا'],
            ['code' => 'AZE', 'name_en' => 'Azerbaijan',          'name_ar' => 'أذربيجان'],
            ['code' => 'BHR', 'name_en' => 'Bahrain',             'name_ar' => 'البحرين'],
            ['code' => 'BGD', 'name_en' => 'Bangladesh',          'name_ar' => 'بنغلاديش'],
            ['code' => 'BTN', 'name_en' => 'Bhutan',              'name_ar' => 'بوتان'],
            ['code' => 'BRN', 'name_en' => 'Brunei',              'name_ar' => 'بروناي'],
            ['code' => 'KHM', 'name_en' => 'Cambodia',            'name_ar' => 'كمبوديا'],
            ['code' => 'CHN', 'name_en' => 'China',               'name_ar' => 'الصين'],
            ['code' => 'CYP', 'name_en' => 'Cyprus',              'name_ar' => 'قبرص'],
            ['code' => 'PRK', 'name_en' => 'North Korea',         'name_ar' => 'كوريا الشمالية'],
            ['code' => 'KOR', 'name_en' => 'South Korea',         'name_ar' => 'كوريا الجنوبية'],
            ['code' => 'GEO', 'name_en' => 'Georgia',             'name_ar' => 'جورجيا'],
            ['code' => 'IND', 'name_en' => 'India',               'name_ar' => 'الهند'],
            ['code' => 'IDN', 'name_en' => 'Indonesia',           'name_ar' => 'إندونيسيا'],
            ['code' => 'IRN', 'name_en' => 'Iran',                'name_ar' => 'إيران'],
            ['code' => 'ISR', 'name_en' => 'Israel',              'name_ar' => 'إسرائيل'],
            ['code' => 'JPN', 'name_en' => 'Japan',               'name_ar' => 'اليابان'],
            ['code' => 'JOR', 'name_en' => 'Jordan',              'name_ar' => 'الأردن'],
            ['code' => 'KAZ', 'name_en' => 'Kazakhstan',          'name_ar' => 'كازاخستان'],
            ['code' => 'KGZ', 'name_en' => 'Kyrgyzstan',          'name_ar' => 'قيرغيزستان'],
            ['code' => 'LAO', 'name_en' => 'Laos',                'name_ar' => 'لاوس'],
            ['code' => 'LBN', 'name_en' => 'Lebanon',             'name_ar' => 'لبنان'],
            ['code' => 'MYS', 'name_en' => 'Malaysia',            'name_ar' => 'ماليزيا'],
            ['code' => 'MDV', 'name_en' => 'Maldives',            'name_ar' => 'المالديف'],
            ['code' => 'MNG', 'name_en' => 'Mongolia',            'name_ar' => 'منغوليا'],
            ['code' => 'MMR', 'name_en' => 'Myanmar',             'name_ar' => 'ميانمار'],
            ['code' => 'NPL', 'name_en' => 'Nepal',               'name_ar' => 'نيبال'],
            ['code' => 'OMN', 'name_en' => 'Oman',                'name_ar' => 'سلطنة عُمان'],
            ['code' => 'PAK', 'name_en' => 'Pakistan',            'name_ar' => 'باكستان'],
            ['code' => 'PHL', 'name_en' => 'Philippines',         'name_ar' => 'الفلبين'],
            ['code' => 'QAT', 'name_en' => 'Qatar',               'name_ar' => 'قطر'],
            ['code' => 'RUS', 'name_en' => 'Russia',              'name_ar' => 'روسيا'],
            ['code' => 'SAU', 'name_en' => 'Saudi Arabia',        'name_ar' => 'المملكة العربية السعودية'],
            ['code' => 'SGP', 'name_en' => 'Singapore',           'name_ar' => 'سنغافورة'],
            ['code' => 'LKA', 'name_en' => 'Sri Lanka',           'name_ar' => 'سريلانكا'],
            ['code' => 'SYR', 'name_en' => 'Syria',               'name_ar' => 'سوريا'],
            ['code' => 'TWN', 'name_en' => 'Taiwan',              'name_ar' => 'تايوان'],
            ['code' => 'TJK', 'name_en' => 'Tajikistan',          'name_ar' => 'طاجيكستان'],
            ['code' => 'THA', 'name_en' => 'Thailand',            'name_ar' => 'تايلاند'],
            ['code' => 'TLS', 'name_en' => 'Timor-Leste',         'name_ar' => 'تيمور الشرقية'],
            ['code' => 'TKM', 'name_en' => 'Turkmenistan',        'name_ar' => 'تركمانستان'],
            ['code' => 'ARE', 'name_en' => 'United Arab Emirates','name_ar' => 'الإمارات العربية المتحدة'],
            ['code' => 'UZB', 'name_en' => 'Uzbekistan',          'name_ar' => 'أوزبكستان'],
            ['code' => 'VNM', 'name_en' => 'Vietnam',             'name_ar' => 'فيتنام'],
            ['code' => 'YEM', 'name_en' => 'Yemen',               'name_ar' => 'اليمن'],

            // ── Europe ──────────────────────────────────────────────
            ['code' => 'ALB', 'name_en' => 'Albania',             'name_ar' => 'ألبانيا'],
            ['code' => 'AND', 'name_en' => 'Andorra',             'name_ar' => 'أندورا'],
            ['code' => 'AUT', 'name_en' => 'Austria',             'name_ar' => 'النمسا'],
            ['code' => 'BLR', 'name_en' => 'Belarus',             'name_ar' => 'بيلاروسيا'],
            ['code' => 'BEL', 'name_en' => 'Belgium',             'name_ar' => 'بلجيكا'],
            ['code' => 'BIH', 'name_en' => 'Bosnia and Herzegovina','name_ar' => 'البوسنة والهرسك'],
            ['code' => 'BGR', 'name_en' => 'Bulgaria',            'name_ar' => 'بلغاريا'],
            ['code' => 'HRV', 'name_en' => 'Croatia',             'name_ar' => 'كرواتيا'],
            ['code' => 'CYP', 'name_en' => 'Cyprus',              'name_ar' => 'قبرص'],
            ['code' => 'CZE', 'name_en' => 'Czech Republic',      'name_ar' => 'التشيك'],
            ['code' => 'DNK', 'name_en' => 'Denmark',             'name_ar' => 'الدنمارك'],
            ['code' => 'EST', 'name_en' => 'Estonia',             'name_ar' => 'إستونيا'],
            ['code' => 'FIN', 'name_en' => 'Finland',             'name_ar' => 'فنلندا'],
            ['code' => 'FRA', 'name_en' => 'France',              'name_ar' => 'فرنسا'],
            ['code' => 'DEU', 'name_en' => 'Germany',             'name_ar' => 'ألمانيا'],
            ['code' => 'GRC', 'name_en' => 'Greece',              'name_ar' => 'اليونان'],
            ['code' => 'HUN', 'name_en' => 'Hungary',             'name_ar' => 'المجر'],
            ['code' => 'ISL', 'name_en' => 'Iceland',             'name_ar' => 'أيسلندا'],
            ['code' => 'IRL', 'name_en' => 'Ireland',             'name_ar' => 'أيرلندا'],
            ['code' => 'ITA', 'name_en' => 'Italy',               'name_ar' => 'إيطاليا'],
            ['code' => 'XKX', 'name_en' => 'Kosovo',              'name_ar' => 'كوسوفو'],
            ['code' => 'LVA', 'name_en' => 'Latvia',              'name_ar' => 'لاتفيا'],
            ['code' => 'LIE', 'name_en' => 'Liechtenstein',       'name_ar' => 'ليختنشتاين'],
            ['code' => 'LTU', 'name_en' => 'Lithuania',           'name_ar' => 'ليتوانيا'],
            ['code' => 'LUX', 'name_en' => 'Luxembourg',          'name_ar' => 'لوكسمبورغ'],
            ['code' => 'MLT', 'name_en' => 'Malta',               'name_ar' => 'مالطا'],
            ['code' => 'MDA', 'name_en' => 'Moldova',             'name_ar' => 'مولدوفا'],
            ['code' => 'MCO', 'name_en' => 'Monaco',              'name_ar' => 'موناكو'],
            ['code' => 'MNE', 'name_en' => 'Montenegro',          'name_ar' => 'الجبل الأسود'],
            ['code' => 'NLD', 'name_en' => 'Netherlands',         'name_ar' => 'هولندا'],
            ['code' => 'MKD', 'name_en' => 'North Macedonia',     'name_ar' => 'مقدونيا الشمالية'],
            ['code' => 'NOR', 'name_en' => 'Norway',              'name_ar' => 'النرويج'],
            ['code' => 'POL', 'name_en' => 'Poland',              'name_ar' => 'بولندا'],
            ['code' => 'PRT', 'name_en' => 'Portugal',            'name_ar' => 'البرتغال'],
            ['code' => 'ROU', 'name_en' => 'Romania',             'name_ar' => 'رومانيا'],
            ['code' => 'RUS', 'name_en' => 'Russia',              'name_ar' => 'روسيا'],
            ['code' => 'SMR', 'name_en' => 'San Marino',          'name_ar' => 'سان مارينو'],
            ['code' => 'SRB', 'name_en' => 'Serbia',              'name_ar' => 'صربيا'],
            ['code' => 'SVK', 'name_en' => 'Slovakia',            'name_ar' => 'سلوفاكيا'],
            ['code' => 'SVN', 'name_en' => 'Slovenia',            'name_ar' => 'سلوفينيا'],
            ['code' => 'ESP', 'name_en' => 'Spain',               'name_ar' => 'إسبانيا'],
            ['code' => 'SWE', 'name_en' => 'Sweden',              'name_ar' => 'السويد'],
            ['code' => 'CHE', 'name_en' => 'Switzerland',         'name_ar' => 'سويسرا'],
            ['code' => 'TUR', 'name_en' => 'Turkey',              'name_ar' => 'تركيا'],
            ['code' => 'UKR', 'name_en' => 'Ukraine',             'name_ar' => 'أوكرانيا'],
            ['code' => 'GBR', 'name_en' => 'United Kingdom',      'name_ar' => 'المملكة المتحدة'],
            ['code' => 'VAT', 'name_en' => 'Vatican City',        'name_ar' => 'الفاتيكان'],

            // ── North America ──────────────────────────────────────
            ['code' => 'ATG', 'name_en' => 'Antigua and Barbuda', 'name_ar' => 'أنتيغوا وباربودا'],
            ['code' => 'BHS', 'name_en' => 'Bahamas',             'name_ar' => 'الباهاما'],
            ['code' => 'BRB', 'name_en' => 'Barbados',            'name_ar' => 'باربادوس'],
            ['code' => 'BLZ', 'name_en' => 'Belize',              'name_ar' => 'بليز'],
            ['code' => 'CAN', 'name_en' => 'Canada',              'name_ar' => 'كندا'],
            ['code' => 'CRI', 'name_en' => 'Costa Rica',          'name_ar' => 'كوستاريكا'],
            ['code' => 'CUB', 'name_en' => 'Cuba',                'name_ar' => 'كوبا'],
            ['code' => 'DMA', 'name_en' => 'Dominica',            'name_ar' => 'دومينيكا'],
            ['code' => 'DOM', 'name_en' => 'Dominican Republic',  'name_ar' => 'جمهورية الدومينيكان'],
            ['code' => 'SLV', 'name_en' => 'El Salvador',         'name_ar' => 'السلفادور'],
            ['code' => 'GRD', 'name_en' => 'Grenada',             'name_ar' => 'غرينادا'],
            ['code' => 'GTM', 'name_en' => 'Guatemala',           'name_ar' => 'غواتيمالا'],
            ['code' => 'HTI', 'name_en' => 'Haiti',               'name_ar' => 'هايتي'],
            ['code' => 'HND', 'name_en' => 'Honduras',            'name_ar' => 'هندوراس'],
            ['code' => 'JAM', 'name_en' => 'Jamaica',             'name_ar' => 'جامايكا'],
            ['code' => 'MEX', 'name_en' => 'Mexico',              'name_ar' => 'المكسيك'],
            ['code' => 'NIC', 'name_en' => 'Nicaragua',           'name_ar' => 'نيكاراغوا'],
            ['code' => 'PAN', 'name_en' => 'Panama',              'name_ar' => 'بنما'],
            ['code' => 'KNA', 'name_en' => 'Saint Kitts and Nevis','name_ar' => 'سانت كيتس ونيفيس'],
            ['code' => 'LCA', 'name_en' => 'Saint Lucia',         'name_ar' => 'سانت لوسيا'],
            ['code' => 'VCT', 'name_en' => 'Saint Vincent and the Grenadines','name_ar' => 'سانت فنسنت والغرينادين'],
            ['code' => 'TTO', 'name_en' => 'Trinidad and Tobago', 'name_ar' => 'ترينيداد وتوباغو'],
            ['code' => 'USA', 'name_en' => 'United States',       'name_ar' => 'الولايات المتحدة الأمريكية'],

            // ── South America ──────────────────────────────────────
            ['code' => 'ARG', 'name_en' => 'Argentina',           'name_ar' => 'الأرجنتين'],
            ['code' => 'BOL', 'name_en' => 'Bolivia',             'name_ar' => 'بوليفيا'],
            ['code' => 'BRA', 'name_en' => 'Brazil',              'name_ar' => 'البرازيل'],
            ['code' => 'CHL', 'name_en' => 'Chile',               'name_ar' => 'تشيلي'],
            ['code' => 'COL', 'name_en' => 'Colombia',            'name_ar' => 'كولومبيا'],
            ['code' => 'ECU', 'name_en' => 'Ecuador',             'name_ar' => 'الإكوادور'],
            ['code' => 'GUY', 'name_en' => 'Guyana',              'name_ar' => 'غيانا'],
            ['code' => 'PRY', 'name_en' => 'Paraguay',            'name_ar' => 'باراغواي'],
            ['code' => 'PER', 'name_en' => 'Peru',                'name_ar' => 'بيرو'],
            ['code' => 'SUR', 'name_en' => 'Suriname',            'name_ar' => 'سورينام'],
            ['code' => 'URY', 'name_en' => 'Uruguay',             'name_ar' => 'أوروغواي'],
            ['code' => 'VEN', 'name_en' => 'Venezuela',           'name_ar' => 'فنزويلا'],

            // ── Sub-Saharan Africa ─────────────────────────────────
            ['code' => 'AGO', 'name_en' => 'Angola',              'name_ar' => 'أنغولا'],
            ['code' => 'BEN', 'name_en' => 'Benin',               'name_ar' => 'بنين'],
            ['code' => 'BWA', 'name_en' => 'Botswana',            'name_ar' => 'بوتسوانا'],
            ['code' => 'BFA', 'name_en' => 'Burkina Faso',        'name_ar' => 'بوركينا فاسو'],
            ['code' => 'BDI', 'name_en' => 'Burundi',             'name_ar' => 'بوروندي'],
            ['code' => 'CPV', 'name_en' => 'Cape Verde',          'name_ar' => 'الرأس الأخضر'],
            ['code' => 'CMR', 'name_en' => 'Cameroon',            'name_ar' => 'الكاميرون'],
            ['code' => 'CAF', 'name_en' => 'Central African Republic','name_ar' => 'جمهورية أفريقيا الوسطى'],
            ['code' => 'TCD', 'name_en' => 'Chad',                'name_ar' => 'تشاد'],
            ['code' => 'COM', 'name_en' => 'Comoros',             'name_ar' => 'جزر القمر'],
            ['code' => 'COG', 'name_en' => 'Republic of the Congo','name_ar' => 'جمهورية الكونغو'],
            ['code' => 'COD', 'name_en' => 'DR Congo',            'name_ar' => 'جمهورية الكونغو الديمقراطية'],
            ['code' => 'CIV', 'name_en' => 'Côte d\'Ivoire',     'name_ar' => 'ساحل العاج'],
            ['code' => 'DJI', 'name_en' => 'Djibouti',            'name_ar' => 'جيبوتي'],
            ['code' => 'GNQ', 'name_en' => 'Equatorial Guinea',   'name_ar' => 'غينيا الاستوائية'],
            ['code' => 'ERI', 'name_en' => 'Eritrea',             'name_ar' => 'إريتريا'],
            ['code' => 'SWZ', 'name_en' => 'Eswatini',            'name_ar' => 'إسواتيني'],
            ['code' => 'ETH', 'name_en' => 'Ethiopia',            'name_ar' => 'إثيوبيا'],
            ['code' => 'GAB', 'name_en' => 'Gabon',               'name_ar' => 'الغابون'],
            ['code' => 'GMB', 'name_en' => 'Gambia',              'name_ar' => 'غامبيا'],
            ['code' => 'GHA', 'name_en' => 'Ghana',               'name_ar' => 'غانا'],
            ['code' => 'GIN', 'name_en' => 'Guinea',              'name_ar' => 'غينيا'],
            ['code' => 'GNB', 'name_en' => 'Guinea-Bissau',       'name_ar' => 'غينيا بيساو'],
            ['code' => 'KEN', 'name_en' => 'Kenya',               'name_ar' => 'كينيا'],
            ['code' => 'LSO', 'name_en' => 'Lesotho',             'name_ar' => 'ليسوتو'],
            ['code' => 'LBR', 'name_en' => 'Liberia',             'name_ar' => 'ليبيريا'],
            ['code' => 'MDG', 'name_en' => 'Madagascar',          'name_ar' => 'مدغشقر'],
            ['code' => 'MWI', 'name_en' => 'Malawi',              'name_ar' => 'مالاوي'],
            ['code' => 'MLI', 'name_en' => 'Mali',                'name_ar' => 'مالي'],
            ['code' => 'MRT', 'name_en' => 'Mauritania',          'name_ar' => 'موريتانيا'],
            ['code' => 'MUS', 'name_en' => 'Mauritius',           'name_ar' => 'موريشيوس'],
            ['code' => 'MOZ', 'name_en' => 'Mozambique',          'name_ar' => 'موزمبيق'],
            ['code' => 'NAM', 'name_en' => 'Namibia',             'name_ar' => 'ناميبيا'],
            ['code' => 'NER', 'name_en' => 'Niger',               'name_ar' => 'النيجر'],
            ['code' => 'NGA', 'name_en' => 'Nigeria',             'name_ar' => 'نيجيريا'],
            ['code' => 'RWA', 'name_en' => 'Rwanda',              'name_ar' => 'رواندا'],
            ['code' => 'STP', 'name_en' => 'São Tomé and Príncipe','name_ar' => 'ساو تومي وبرينسيبي'],
            ['code' => 'SEN', 'name_en' => 'Senegal',             'name_ar' => 'السنغال'],
            ['code' => 'SYC', 'name_en' => 'Seychelles',          'name_ar' => 'سيشل'],
            ['code' => 'SLE', 'name_en' => 'Sierra Leone',        'name_ar' => 'سيراليون'],
            ['code' => 'SOM', 'name_en' => 'Somalia',             'name_ar' => 'الصومال'],
            ['code' => 'ZAF', 'name_en' => 'South Africa',        'name_ar' => 'جنوب أفريقيا'],
            ['code' => 'SSD', 'name_en' => 'South Sudan',         'name_ar' => 'جنوب السودان'],
            ['code' => 'SDN', 'name_en' => 'Sudan',               'name_ar' => 'السودان'],
            ['code' => 'TZA', 'name_en' => 'Tanzania',            'name_ar' => 'تنزانيا'],
            ['code' => 'TGO', 'name_en' => 'Togo',                'name_ar' => 'توغو'],
            ['code' => 'UGA', 'name_en' => 'Uganda',              'name_ar' => 'أوغندا'],
            ['code' => 'ZMB', 'name_en' => 'Zambia',              'name_ar' => 'زامبيا'],
            ['code' => 'ZWE', 'name_en' => 'Zimbabwe',            'name_ar' => 'زيمبابوي'],

            // ── Oceania ────────────────────────────────────────────
            ['code' => 'AUS', 'name_en' => 'Australia',           'name_ar' => 'أستراليا'],
            ['code' => 'FJI', 'name_en' => 'Fiji',                'name_ar' => 'فيجي'],
            ['code' => 'KIR', 'name_en' => 'Kiribati',            'name_ar' => 'كيريباتي'],
            ['code' => 'MHL', 'name_en' => 'Marshall Islands',    'name_ar' => 'جزر مارشال'],
            ['code' => 'FSM', 'name_en' => 'Micronesia',          'name_ar' => 'ميكرونيزيا'],
            ['code' => 'NRU', 'name_en' => 'Nauru',               'name_ar' => 'ناورو'],
            ['code' => 'NZL', 'name_en' => 'New Zealand',         'name_ar' => 'نيوزيلندا'],
            ['code' => 'PLW', 'name_en' => 'Palau',               'name_ar' => 'بالاو'],
            ['code' => 'PNG', 'name_en' => 'Papua New Guinea',    'name_ar' => 'بابوا غينيا الجديدة'],
            ['code' => 'WSM', 'name_en' => 'Samoa',               'name_ar' => 'ساموا'],
            ['code' => 'SLB', 'name_en' => 'Solomon Islands',     'name_ar' => 'جزر سليمان'],
            ['code' => 'TON', 'name_en' => 'Tonga',               'name_ar' => 'تونغا'],
            ['code' => 'TUV', 'name_en' => 'Tuvalu',              'name_ar' => 'توفالو'],
            ['code' => 'VUT', 'name_en' => 'Vanuatu',             'name_ar' => 'فانواتو'],
        ];
    }
}
