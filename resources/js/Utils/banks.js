/**
 * Curated bank presets for Saudi/GCC bank transfer accounts.
 * Used by PaymentSettingsForm (system admin) and BankAccountCard (tenant UI).
 */
export const BANK_PRESETS = [
    {
        code: 'alrajhi',
        name_ar: 'مصرف الراجحي',
        name_en: 'Al Rajhi Bank',
        short: 'ARB',
        color: '#221C78',
    },
    {
        code: 'snb',
        name_ar: 'البنك الأهلي السعودي (SNB)',
        name_en: 'Saudi National Bank (SNB)',
        short: 'SNB',
        color: '#006C35',
    },
    {
        code: 'alinma',
        name_ar: 'مصرف الإنماء',
        name_en: 'Alinma Bank',
        short: 'INMA',
        color: '#5B3A29',
    },
    {
        code: 'riyad',
        name_ar: 'بنك الرياض',
        name_en: 'Riyad Bank',
        short: 'RIBL',
        color: '#004F71',
    },
    {
        code: 'albilad',
        name_ar: 'بنك البلاد',
        name_en: 'Bank Albilad',
        short: 'ALBI',
        color: '#1E293B',
    },
    {
        code: 'sab',
        name_ar: 'البنك السعودي الأول (SAB)',
        name_en: 'Saudi Awwal Bank (SAB)',
        short: 'SAB',
        color: '#DB0011',
    },
    {
        code: 'anb',
        name_ar: 'البنك العربي الوطني (anb)',
        name_en: 'Arab National Bank (anb)',
        short: 'ANB',
        color: '#007A33',
    },
    {
        code: 'aljazira',
        name_ar: 'بنك الجزيرة',
        name_en: 'Bank AlJazira',
        short: 'BJAZ',
        color: '#003B71',
    },
    {
        code: 'stcbank',
        name_ar: 'بنك إس تي سي (stc bank)',
        name_en: 'stc bank',
        short: 'STC',
        color: '#4F008C',
    },
    {
        code: 'other',
        name_ar: 'بنك آخر',
        name_en: 'Other Bank',
        short: 'BANK',
        color: '#334155',
    },
];

/**
 * Resolve bank preset metadata by code or fallback name match.
 *
 * @param {string|null|undefined} code
 * @param {string|null|undefined} bankName
 */
export function resolveBankPreset(code, bankName = '') {
    if (code) {
        const byCode = BANK_PRESETS.find((b) => b.code === code);
        if (byCode) return byCode;
    }

    const normalized = String(bankName || '').toLowerCase();
    if (normalized) {
        if (normalized.includes('راجحي') || normalized.includes('rajhi')) {
            return BANK_PRESETS.find((b) => b.code === 'alrajhi');
        }
        if (normalized.includes('أهلي') || normalized.includes('اهلي') || normalized.includes('snb')) {
            return BANK_PRESETS.find((b) => b.code === 'snb');
        }
        if (normalized.includes('إنماء') || normalized.includes('انماء') || normalized.includes('alinma')) {
            return BANK_PRESETS.find((b) => b.code === 'alinma');
        }
        if (normalized.includes('رياض') || normalized.includes('riyad')) {
            return BANK_PRESETS.find((b) => b.code === 'riyad');
        }
        if (normalized.includes('بلاد') || normalized.includes('bilad')) {
            return BANK_PRESETS.find((b) => b.code === 'albilad');
        }
        if (normalized.includes('الأول') || normalized.includes('ساب') || normalized.includes('sab')) {
            return BANK_PRESETS.find((b) => b.code === 'sab');
        }
        if (normalized.includes('عربي') || normalized.includes('anb')) {
            return BANK_PRESETS.find((b) => b.code === 'anb');
        }
        if (normalized.includes('جزيرة') || normalized.includes('jazira')) {
            return BANK_PRESETS.find((b) => b.code === 'aljazira');
        }
        if (normalized.includes('stc')) {
            return BANK_PRESETS.find((b) => b.code === 'stcbank');
        }
    }

    return BANK_PRESETS.find((b) => b.code === 'other');
}
