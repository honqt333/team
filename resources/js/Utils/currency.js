/**
 * Authoritative frontend registry of supported countries, currencies,
 * decimal precisions, and currency visual symbols.
 */

export const SAR_SVG_VIEWBOX = '0 0 1124.14 1256.39';

export const SAR_SVG_PATHS = [
    'M699.62 1113.02h0c-20.06 44.48-33.32 92.75-38.4 143.37l424.51-90.24c20.06-44.47 33.31-92.75 38.4-143.37l-424.51 90.24z',
    'M1085.73 895.8c20.06-44.47 33.32-92.75 38.4-143.37l-330.68 70.33v-135.2l292.27-62.11c20.06-44.47 33.32-92.75 38.4-143.37l-330.68 70.27V66.13c-50.67 28.45-95.67 66.32-132.25 110.99v403.35l-132.25 28.11V0c-50.67 28.44-95.67 66.32-132.25 110.99v525.69l-295.91 62.88c-20.06 44.47-33.33 92.75-38.42 143.37l334.33-71.05v170.26l-358.3 76.14c-20.06 44.47-33.32 92.75-38.4 143.37l375.04-79.7c30.53-6.35 56.77-24.4 73.83-49.24l68.78-101.97v-.02c7.14-10.55 11.3-23.27 11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28z',
];

export const COUNTRIES = {
    SA: {
        code: 'SA',
        flag: '🇸🇦',
        name_ar: 'المملكة العربية السعودية',
        name_en: 'Saudi Arabia',
        default_currency: 'SAR',
        dial_code: '+966',
        dial_digits: '966',
        local_placeholder: '501234567',
        default_vat_rate: 15,
        vat_rate_locked: true,
        zatca_applicable: true,
    },
    AE: {
        code: 'AE',
        flag: '🇦🇪',
        name_ar: 'الإمارات العربية المتحدة',
        name_en: 'United Arab Emirates',
        default_currency: 'AED',
        dial_code: '+971',
        dial_digits: '971',
        local_placeholder: '501234567',
        default_vat_rate: 5,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
    KW: {
        code: 'KW',
        flag: '🇰🇼',
        name_ar: 'الكويت',
        name_en: 'Kuwait',
        default_currency: 'KWD',
        dial_code: '+965',
        dial_digits: '965',
        local_placeholder: '50123456',
        default_vat_rate: 0,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
    BH: {
        code: 'BH',
        flag: '🇧🇭',
        name_ar: 'البحرين',
        name_en: 'Bahrain',
        default_currency: 'BHD',
        dial_code: '+973',
        dial_digits: '973',
        local_placeholder: '36123456',
        default_vat_rate: 10,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
    OM: {
        code: 'OM',
        flag: '🇴🇲',
        name_ar: 'عُمان',
        name_en: 'Oman',
        default_currency: 'OMR',
        dial_code: '+968',
        dial_digits: '968',
        local_placeholder: '91234567',
        default_vat_rate: 5,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
    QA: {
        code: 'QA',
        flag: '🇶🇦',
        name_ar: 'قطر',
        name_en: 'Qatar',
        default_currency: 'QAR',
        dial_code: '+974',
        dial_digits: '974',
        local_placeholder: '50123456',
        default_vat_rate: 0,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
    EG: {
        code: 'EG',
        flag: '🇪🇬',
        name_ar: 'مصر',
        name_en: 'Egypt',
        default_currency: 'EGP',
        dial_code: '+20',
        dial_digits: '20',
        local_placeholder: '1012345678',
        default_vat_rate: 14,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
    JO: {
        code: 'JO',
        flag: '🇯🇴',
        name_ar: 'الأردن',
        name_en: 'Jordan',
        default_currency: 'JOD',
        dial_code: '+962',
        dial_digits: '962',
        local_placeholder: '791234567',
        default_vat_rate: 16,
        vat_rate_locked: false,
        zatca_applicable: false,
    },
};

export const CURRENCIES = {
    SAR: { code: 'SAR', symbol_ar: 'ر.س', symbol_en: 'SAR', glyph: 'svg:sar', decimals: 2 },
    AED: { code: 'AED', symbol_ar: 'د.إ', symbol_en: 'AED', glyph: 'د.إ', decimals: 2 },
    KWD: { code: 'KWD', symbol_ar: 'د.ك', symbol_en: 'KWD', glyph: 'د.ك', decimals: 3 },
    BHD: { code: 'BHD', symbol_ar: 'د.ب', symbol_en: 'BHD', glyph: 'د.ب', decimals: 3 },
    OMR: { code: 'OMR', symbol_ar: 'ر.ع', symbol_en: 'OMR', glyph: 'ر.ع', decimals: 3 },
    QAR: { code: 'QAR', symbol_ar: 'ر.ق', symbol_en: 'QAR', glyph: 'ر.ق', decimals: 2 },
    EGP: { code: 'EGP', symbol_ar: 'ج.م', symbol_en: 'EGP', glyph: 'ج.م', decimals: 2 },
    JOD: { code: 'JOD', symbol_ar: 'د.أ', symbol_en: 'JOD', glyph: 'د.أ', decimals: 3 },
    USD: { code: 'USD', symbol_ar: 'دولار', symbol_en: 'USD', glyph: '$', decimals: 2 },
    EUR: { code: 'EUR', symbol_ar: 'يورو', symbol_en: 'EUR', glyph: '€', decimals: 2 },
};

export function getCountryMeta(code) {
    const normalized = String(code || 'SA').toUpperCase();
    return COUNTRIES[normalized] || COUNTRIES.SA;
}

export function getCurrencyMeta(code) {
    const normalized = String(code || 'SAR').toUpperCase();
    return CURRENCIES[normalized] || CURRENCIES.SAR;
}

export function getCurrencyDecimals(code) {
    return getCurrencyMeta(code).decimals ?? 2;
}

export function getCurrencyLabel(code, locale = 'ar') {
    const meta = getCurrencyMeta(code);
    return locale === 'en' ? meta.symbol_en : meta.symbol_ar;
}

export function getCurrencyGlyph(code) {
    const meta = getCurrencyMeta(code);
    return meta.glyph || meta.symbol_ar;
}

export function resolveTenantCurrency(tenant = null, explicitCode = null) {
    const candidate =
        explicitCode || tenant?.currency || tenant?.tax_settings?.currency_code || 'SAR';
    const normalized = String(candidate).toUpperCase();
    return CURRENCIES[normalized] ? normalized : 'SAR';
}

export function isSvgCurrencyGlyph(code) {
    return getCurrencyMeta(code).glyph === 'svg:sar';
}
