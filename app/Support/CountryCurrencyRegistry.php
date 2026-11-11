<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;

/**
 * Authoritative registry of supported countries, currencies, tax defaults,
 * phone dialing rules, and print currency symbols.
 */
final class CountryCurrencyRegistry
{
    public const DEFAULT_COUNTRY = 'SA';

    public const DEFAULT_CURRENCY = 'SAR';

    /**
     * Supported countries keyed by ISO 3166-1 alpha-2 code.
     *
     * @var array<string, array<string, mixed>>
     */
    private const COUNTRIES = [
        'SA' => [
            'code' => 'SA',
            'name_ar' => 'المملكة العربية السعودية',
            'name_en' => 'Saudi Arabia',
            'default_currency' => 'SAR',
            'dial_code' => '+966',
            'dial_digits' => '966',
            'local_lengths' => [9],
            'local_regex' => '/^5\d{8}$/',
            'e164_regex' => '/^\+9665\d{8}$/',
            'phone_example' => '+966501234567',
            'default_vat_rate' => 15.00,
            'vat_rate_locked' => true,
            'vat_number_regex' => '/^3[0-9]{13}3$/',
            'vat_number_min' => 15,
            'vat_number_max' => 15,
            'zatca_applicable' => true,
            'timezone' => 'Asia/Riyadh',
        ],
        'AE' => [
            'code' => 'AE',
            'name_ar' => 'الإمارات العربية المتحدة',
            'name_en' => 'United Arab Emirates',
            'default_currency' => 'AED',
            'dial_code' => '+971',
            'dial_digits' => '971',
            'local_lengths' => [9],
            'local_regex' => '/^5\d{8}$/',
            'e164_regex' => '/^\+9715\d{8}$/',
            'phone_example' => '+971501234567',
            'default_vat_rate' => 5.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[0-9]{15}$/',
            'vat_number_min' => 15,
            'vat_number_max' => 15,
            'zatca_applicable' => false,
            'timezone' => 'Asia/Dubai',
        ],
        'KW' => [
            'code' => 'KW',
            'name_ar' => 'الكويت',
            'name_en' => 'Kuwait',
            'default_currency' => 'KWD',
            'dial_code' => '+965',
            'dial_digits' => '965',
            'local_lengths' => [8],
            'local_regex' => '/^[4569]\d{7}$/',
            'e164_regex' => '/^\+965[4569]\d{7}$/',
            'phone_example' => '+96550123456',
            'default_vat_rate' => 0.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[A-Za-z0-9\-]{5,30}$/',
            'vat_number_min' => 5,
            'vat_number_max' => 30,
            'zatca_applicable' => false,
            'timezone' => 'Asia/Kuwait',
        ],
        'BH' => [
            'code' => 'BH',
            'name_ar' => 'البحرين',
            'name_en' => 'Bahrain',
            'default_currency' => 'BHD',
            'dial_code' => '+973',
            'dial_digits' => '973',
            'local_lengths' => [8],
            'local_regex' => '/^[36]\d{7}$/',
            'e164_regex' => '/^\+973[36]\d{7}$/',
            'phone_example' => '+97336123456',
            'default_vat_rate' => 10.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[0-9]{15}$/',
            'vat_number_min' => 15,
            'vat_number_max' => 15,
            'zatca_applicable' => false,
            'timezone' => 'Asia/Bahrain',
        ],
        'OM' => [
            'code' => 'OM',
            'name_ar' => 'عُمان',
            'name_en' => 'Oman',
            'default_currency' => 'OMR',
            'dial_code' => '+968',
            'dial_digits' => '968',
            'local_lengths' => [8],
            'local_regex' => '/^[79]\d{7}$/',
            'e164_regex' => '/^\+968[79]\d{7}$/',
            'phone_example' => '+96891234567',
            'default_vat_rate' => 5.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[A-Za-z0-9\-]{8,20}$/',
            'vat_number_min' => 8,
            'vat_number_max' => 20,
            'zatca_applicable' => false,
            'timezone' => 'Asia/Muscat',
        ],
        'QA' => [
            'code' => 'QA',
            'name_ar' => 'قطر',
            'name_en' => 'Qatar',
            'default_currency' => 'QAR',
            'dial_code' => '+974',
            'dial_digits' => '974',
            'local_lengths' => [8],
            'local_regex' => '/^[3567]\d{7}$/',
            'e164_regex' => '/^\+974[3567]\d{7}$/',
            'phone_example' => '+97450123456',
            'default_vat_rate' => 0.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[A-Za-z0-9\-]{5,30}$/',
            'vat_number_min' => 5,
            'vat_number_max' => 30,
            'zatca_applicable' => false,
            'timezone' => 'Asia/Qatar',
        ],
        'EG' => [
            'code' => 'EG',
            'name_ar' => 'مصر',
            'name_en' => 'Egypt',
            'default_currency' => 'EGP',
            'dial_code' => '+20',
            'dial_digits' => '20',
            'local_lengths' => [10],
            'local_regex' => '/^1[0125]\d{8}$/',
            'e164_regex' => '/^\+201[0125]\d{8}$/',
            'phone_example' => '+201012345678',
            'default_vat_rate' => 14.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[0-9]{9}$/',
            'vat_number_min' => 9,
            'vat_number_max' => 15,
            'zatca_applicable' => false,
            'timezone' => 'Africa/Cairo',
        ],
        'JO' => [
            'code' => 'JO',
            'name_ar' => 'الأردن',
            'name_en' => 'Jordan',
            'default_currency' => 'JOD',
            'dial_code' => '+962',
            'dial_digits' => '962',
            'local_lengths' => [9],
            'local_regex' => '/^7[789]\d{7}$/',
            'e164_regex' => '/^\+9627[789]\d{7}$/',
            'phone_example' => '+962791234567',
            'default_vat_rate' => 16.00,
            'vat_rate_locked' => false,
            'vat_number_regex' => '/^[A-Za-z0-9\-]{6,20}$/',
            'vat_number_min' => 6,
            'vat_number_max' => 20,
            'zatca_applicable' => false,
            'timezone' => 'Asia/Amman',
        ],
    ];

    /**
     * Supported currencies keyed by ISO 4217 code.
     *
     * @var array<string, array<string, mixed>>
     */
    private const CURRENCIES = [
        'SAR' => [
            'code' => 'SAR',
            'name_ar' => 'ريال سعودي',
            'name_en' => 'Saudi Riyal',
            'symbol_ar' => 'ر.س',
            'symbol_en' => 'SAR',
            'glyph' => 'svg:sar',
            'decimals' => 2,
        ],
        'AED' => [
            'code' => 'AED',
            'name_ar' => 'درهم إماراتي',
            'name_en' => 'UAE Dirham',
            'symbol_ar' => 'د.إ',
            'symbol_en' => 'AED',
            'glyph' => 'د.إ',
            'decimals' => 2,
        ],
        'KWD' => [
            'code' => 'KWD',
            'name_ar' => 'دينار كويتي',
            'name_en' => 'Kuwaiti Dinar',
            'symbol_ar' => 'د.ك',
            'symbol_en' => 'KWD',
            'glyph' => 'د.ك',
            'decimals' => 3,
        ],
        'BHD' => [
            'code' => 'BHD',
            'name_ar' => 'دينار بحريني',
            'name_en' => 'Bahraini Dinar',
            'symbol_ar' => 'د.ب',
            'symbol_en' => 'BHD',
            'glyph' => 'د.ب',
            'decimals' => 3,
        ],
        'OMR' => [
            'code' => 'OMR',
            'name_ar' => 'ريال عماني',
            'name_en' => 'Omani Rial',
            'symbol_ar' => 'ر.ع',
            'symbol_en' => 'OMR',
            'glyph' => 'ر.ع',
            'decimals' => 3,
        ],
        'QAR' => [
            'code' => 'QAR',
            'name_ar' => 'ريال قطري',
            'name_en' => 'Qatari Riyal',
            'symbol_ar' => 'ر.ق',
            'symbol_en' => 'QAR',
            'glyph' => 'ر.ق',
            'decimals' => 2,
        ],
        'EGP' => [
            'code' => 'EGP',
            'name_ar' => 'جنيه مصري',
            'name_en' => 'Egyptian Pound',
            'symbol_ar' => 'ج.م',
            'symbol_en' => 'EGP',
            'glyph' => 'ج.م',
            'decimals' => 2,
        ],
        'JOD' => [
            'code' => 'JOD',
            'name_ar' => 'دينار أردني',
            'name_en' => 'Jordanian Dinar',
            'symbol_ar' => 'د.أ',
            'symbol_en' => 'JOD',
            'glyph' => 'د.أ',
            'decimals' => 3,
        ],
        'USD' => [
            'code' => 'USD',
            'name_ar' => 'دولار أمريكي',
            'name_en' => 'US Dollar',
            'symbol_ar' => 'دولار',
            'symbol_en' => 'USD',
            'glyph' => '$',
            'decimals' => 2,
        ],
        'EUR' => [
            'code' => 'EUR',
            'name_ar' => 'يورو',
            'name_en' => 'Euro',
            'symbol_ar' => 'يورو',
            'symbol_en' => 'EUR',
            'glyph' => '€',
            'decimals' => 2,
        ],
    ];

    /**
     * Official Saudi Riyal inline SVG markup (currentColor, 1em height).
     */
    public const SAR_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1124.14 1256.39" style="display:inline-block;width:0.9em;height:0.95em;vertical-align:-0.12em;" fill="currentColor" aria-label="SAR"><path d="M699.62 1113.02h0c-20.06 44.48-33.32 92.75-38.4 143.37l424.51-90.24c20.06-44.47 33.31-92.75 38.4-143.37l-424.51 90.24z"/><path d="M1085.73 895.8c20.06-44.47 33.32-92.75 38.4-143.37l-330.68 70.33v-135.2l292.27-62.11c20.06-44.47 33.32-92.75 38.4-143.37l-330.68 70.27V66.13c-50.67 28.45-95.67 66.32-132.25 110.99v403.35l-132.25 28.11V0c-50.67 28.44-95.67 66.32-132.25 110.99v525.69l-295.91 62.88c-20.06 44.47-33.33 92.75-38.42 143.37l334.33-71.05v170.26l-358.3 76.14c-20.06 44.47-33.32 92.75-38.4 143.37l375.04-79.7c30.53-6.35 56.77-24.4 73.83-49.24l68.78-101.97v-.02c7.14-10.55 11.3-23.27 11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28z"/></svg>';

    /**
     * @return list<string>
     */
    public static function countryCodes(): array
    {
        return array_keys(self::COUNTRIES);
    }

    /**
     * @return list<string>
     */
    public static function currencyCodes(): array
    {
        return array_keys(self::CURRENCIES);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function allCountries(): array
    {
        return self::COUNTRIES;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function allCurrencies(): array
    {
        return self::CURRENCIES;
    }

    public static function normalizeCountry(?string $country): string
    {
        $code = strtoupper(trim((string) $country));

        if ($code !== '' && isset(self::COUNTRIES[$code])) {
            return $code;
        }

        $defaultCountry = function_exists('app') && app()->bound('config')
            ? config('app.default_country', self::DEFAULT_COUNTRY)
            : self::DEFAULT_COUNTRY;
        $fallback = strtoupper((string) $defaultCountry);

        return isset(self::COUNTRIES[$fallback]) ? $fallback : self::DEFAULT_COUNTRY;
    }

    public static function normalizeCurrency(?string $currency, ?string $country = null): string
    {
        $code = strtoupper(trim((string) $currency));

        if ($code !== '' && isset(self::CURRENCIES[$code])) {
            return $code;
        }

        if ($country !== null) {
            $countryMeta = self::countryMeta($country);

            return (string) $countryMeta['default_currency'];
        }

        $defaultCurrency = function_exists('app') && app()->bound('config')
            ? config('app.default_currency', self::DEFAULT_CURRENCY)
            : self::DEFAULT_CURRENCY;
        $fallback = strtoupper((string) $defaultCurrency);

        return isset(self::CURRENCIES[$fallback]) ? $fallback : self::DEFAULT_CURRENCY;
    }

    /**
     * @return array<string, mixed>
     */
    public static function countryMeta(?string $country): array
    {
        $code = self::normalizeCountry($country);

        return self::COUNTRIES[$code];
    }

    /**
     * @return array<string, mixed>
     */
    public static function currencyMeta(?string $currency): array
    {
        $code = self::normalizeCurrency($currency);

        return self::CURRENCIES[$code];
    }

    public static function tenantCountry(?Tenant $tenant = null): string
    {
        return self::normalizeCountry($tenant?->country);
    }

    public static function tenantCurrency(?Tenant $tenant = null): string
    {
        if ($tenant?->currency) {
            return self::normalizeCurrency($tenant->currency, $tenant->country);
        }

        if ($tenant?->relationLoaded('taxSettings') && $tenant->taxSettings?->currency_code) {
            return self::normalizeCurrency($tenant->taxSettings->currency_code, $tenant->country);
        }

        return self::normalizeCurrency(null, $tenant?->country);
    }

    public static function isZatcaApplicable(?string $country): bool
    {
        return (bool) (self::countryMeta($country)['zatca_applicable'] ?? false);
    }

    public static function defaultVatRate(?string $country): float
    {
        return (float) (self::countryMeta($country)['default_vat_rate'] ?? 15.00);
    }

    public static function isVatRateLocked(?string $country): bool
    {
        return (bool) (self::countryMeta($country)['vat_rate_locked'] ?? false);
    }

    public static function decimalsFor(?string $currency): int
    {
        return (int) (self::currencyMeta($currency)['decimals'] ?? 2);
    }

    public static function currencyLabel(?string $currency, string $locale = 'ar'): string
    {
        $meta = self::currencyMeta($currency);

        return $locale === 'en' ? (string) $meta['symbol_en'] : (string) $meta['symbol_ar'];
    }

    /**
     * Render currency HTML/SVG or plain text for Blade PDF / email templates.
     *
     * @param array<string, mixed>|null $visualSettings
     */
    public static function renderCurrencyHtml(?string $currency, ?array $visualSettings = null, string $locale = 'ar'): string
    {
        $showSymbol = $visualSettings['show_currency_symbol'] ?? true;

        if ($showSymbol === false) {
            return '';
        }

        $style = (string) ($visualSettings['currency_display_style'] ?? 'text');
        $meta = self::currencyMeta($currency);

        if ($style === 'symbol') {
            if (($meta['glyph'] ?? null) === 'svg:sar') {
                return self::SAR_SVG;
            }

            return e((string) ($meta['glyph'] ?? $meta['symbol_ar']));
        }

        return e($locale === 'en' ? (string) $meta['symbol_en'] : (string) $meta['symbol_ar']);
    }

    /**
     * @return list<string>
     */
    public static function dialDigitsList(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $c): string => (string) $c['dial_digits'],
            self::COUNTRIES
        )));
    }
}
