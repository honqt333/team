import { useI18n } from 'vue-i18n';

/**
 * Get localized value based on current locale
 * @param {Object} item - Object with _ar and _en suffixed properties
 * @param {string} field - Base field name (e.g., 'name' for name_ar/name_en)
 * @returns {string} The localized value
 */
export function useLocalized() {
    const { locale } = useI18n();

    function getLocalized(item, field = 'name') {
        if (!item) return '';

        const arField = `${field}_ar`;
        const enField = `${field}_en`;

        if (locale.value === 'ar') {
            return item[arField] || item[enField] || '';
        }
        return item[enField] || item[arField] || '';
    }

    function getName(item) {
        return getLocalized(item, 'name');
    }

    function getDescription(item) {
        return getLocalized(item, 'description');
    }

    return {
        getLocalized,
        getName,
        getDescription,
    };
}
