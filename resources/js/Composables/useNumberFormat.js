/**
 * Unified composable for all numeral operations.
 * 
 * Handles both:
 * 1. Conversion between Arabic (٠-٩) and English (0-9) numerals
 * 2. Formatting numbers for display with English numerals only
 * 
 * This consolidates the functionality from useArabicNumerals and useNumberFormat.
 */
export function useNumberFormat() {
    const arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    const englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    // ============================================
    // CONVERSION FUNCTIONS (Input Processing)
    // ============================================

    /**
     * Convert Arabic numerals to English numerals.
     * 
     * @param {string|any} value - The value to convert
     * @returns {string|any} - The converted value
     */
    function toEnglish(value) {
        if (typeof value !== 'string') return value;

        let result = value;
        arabicNumerals.forEach((arabic, index) => {
            result = result.replaceAll(arabic, englishNumerals[index]);
        });
        return result;
    }

    /**
     * Convert English numerals to Arabic numerals.
     * 
     * @param {string|any} value - The value to convert
     * @returns {string|any} - The converted value
     */
    function toArabic(value) {
        if (typeof value !== 'string') return value;

        let result = value;
        englishNumerals.forEach((english, index) => {
            result = result.replaceAll(english, arabicNumerals[index]);
        });
        return result;
    }

    /**
     * Sanitize input event value by converting Arabic numerals to English.
     * Useful for v-on:input handlers.
     * 
     * @param {Event} event - Input event
     * @returns {string} - Sanitized value
     */
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

    /**
     * Format a number with English numerals and specified decimal places.
     * 
     * @param {number|string|null|undefined} value - The value to format
     * @param {number} decimals - Number of decimal places (default: 2)
     * @returns {string} Formatted number with English numerals
     */
    function formatNumber(value, decimals = 2) {
        if (value === null || value === undefined || value === '') {
            return '0.00';
        }

        const num = typeof value === 'string' ? parseFloat(value) : value;

        if (isNaN(num)) {
            return '0.00';
        }

        // Force en-US locale to ensure English numerals (0-9)
        return num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
            useGrouping: false // No thousand separators
        });
    }

    /**
     * Format currency value with English numerals.
     * 
     * @param {number|string|null|undefined} value - The value to format
     * @param {number} decimals - Number of decimal places (default: 2)
     * @returns {string} Formatted currency value
     */
    function formatCurrency(value, decimals = 2) {
        return formatNumber(value, decimals);
    }

    /**
     * Format an integer (no decimal places).
     * 
     * @param {number|string|null|undefined} value - The value to format
     * @returns {string} Formatted integer
     */
    function formatInteger(value) {
        return formatNumber(value, 0);
    }

    return {
        // Conversion functions
        toEnglish,
        toArabic,
        sanitizeInput,

        // Formatting functions
        formatNumber,
        formatCurrency,
        formatInteger
    };
}
