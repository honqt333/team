import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { getCurrencyDecimals, getCurrencyLabel, resolveTenantCurrency } from '@/Utils/currency';

/**
 * Unified composable for all numeral and currency operations.
 *
 * Handles:
 * 1. Conversion between Arabic (٠-٩) and English (0-9) numerals
 * 2. Formatting numbers for display with English numerals only
 * 3. Tenant-aware currency formatting (code, localized symbol, decimals)
 */
export function useNumberFormat() {
    const arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    const englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    let page = null;
    try {
        page = usePage();
    } catch {
        page = null;
    }

    let localeRef = null;
    try {
        localeRef = useI18n().locale;
    } catch {
        localeRef = null;
    }

    const activeCurrencyCode = computed(() => resolveTenantCurrency(page?.props?.tenant));
    const activeCurrencyDecimals = computed(() => getCurrencyDecimals(activeCurrencyCode.value));
    const activeLocale = computed(() => localeRef?.value || page?.props?.locale || 'ar');
    const activeCurrencySymbol = computed(() =>
        getCurrencyLabel(activeCurrencyCode.value, activeLocale.value)
    );

    // ============================================
    // CONVERSION FUNCTIONS (Input Processing)
    // ============================================

    function toEnglish(value) {
        if (value === null || value === undefined) return '';
        const str = String(value);
        return str
            .replace(/[\u0660-\u0669]/g, (c) => String.fromCharCode(c.charCodeAt(0) - 0x0660 + 48))
            .replace(/[\u06F0-\u06F9]/g, (c) => String.fromCharCode(c.charCodeAt(0) - 0x06f0 + 48))
            .replace(/\u066B/g, '.')
            .replace(/\u066C/g, '');
    }

    function toArabic(value) {
        if (typeof value !== 'string') return value;

        let result = value;
        englishNumerals.forEach((english, index) => {
            result = result.replaceAll(english, arabicNumerals[index]);
        });
        return result;
    }

    function sanitizeInput(event) {
        const value = event.target.value;
        const sanitized = toEnglish(value);
        if (value !== sanitized) {
            event.target.value = sanitized;
        }
        return sanitized;
    }

    // ============================================
    // FORMATTING FUNCTIONS (Display Processing)
    // ============================================

    function formatNumber(value, decimals = 2) {
        if (value === null || value === undefined || value === '') {
            return (0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
                useGrouping: false,
            });
        }

        const num = typeof value === 'string' ? parseFloat(value) : value;

        if (isNaN(num)) {
            return (0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
                useGrouping: false,
            });
        }

        // Force en-US locale to ensure English numerals (0-9)
        return num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
            useGrouping: false,
        });
    }

    /**
     * Format currency numeric value with English numerals.
     * Accepts either a decimal count (number) or a currency code (string) as the 2nd argument.
     */
    function formatCurrency(value, decimalsOrCurrency = undefined) {
        const decimals =
            typeof decimalsOrCurrency === 'number'
                ? decimalsOrCurrency
                : typeof decimalsOrCurrency === 'string'
                  ? getCurrencyDecimals(decimalsOrCurrency)
                  : activeCurrencyDecimals.value;

        return formatNumber(value, decimals);
    }

    /**
     * Format an amount paired with its currency label (e.g. "100.00 ر.س" / "100.00 AED").
     */
    function formatMoney(value, currencyCode = undefined, showSymbol = true) {
        const code = resolveTenantCurrency(page?.props?.tenant, currencyCode);
        const decimals = getCurrencyDecimals(code);
        const num = typeof value === 'string' ? parseFloat(value) : Number(value);
        const safe = Number.isFinite(num) ? num : 0;

        const formatted = safe.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });

        if (!showSymbol) {
            return formatted;
        }

        return `${formatted} ${getCurrencyLabel(code, activeLocale.value)}`;
    }

    function getCurrencySuffix(currencyCode = undefined) {
        const code = resolveTenantCurrency(page?.props?.tenant, currencyCode);
        return getCurrencyLabel(code, activeLocale.value);
    }

    function formatInteger(value) {
        return formatNumber(value, 0);
    }

    function formatQuantity(value) {
        if (value === null || value === undefined || value === '') return '0';
        const num = typeof value === 'string' ? parseFloat(toEnglish(value)) : value;
        if (isNaN(num)) return '0';
        return num.toString();
    }

    return {
        // Reactive tenant currency helpers
        activeCurrencyCode,
        activeCurrencySymbol,
        activeCurrencyDecimals,

        // Conversion functions
        toEnglish,
        toArabic,
        sanitizeInput,

        // Formatting functions
        formatNumber,
        formatCurrency,
        currency: formatCurrency,
        formatMoney,
        getCurrencySuffix,
        formatInteger,
        formatQuantity,
    };
}
