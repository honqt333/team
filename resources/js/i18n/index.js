import { createI18n } from 'vue-i18n';
import ar from './lang/ar.json';
import en from './lang/en.json';

// Get saved locale from localStorage or default to 'ar'
function getInitialLocale() {
    if (typeof window !== 'undefined') {
        return localStorage.getItem('app.locale') || 'ar';
    }
    return 'ar';
}

// Apply locale to document
function applyLocaleToDocument(locale) {
    if (typeof document !== 'undefined') {
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
    }
}

const initialLocale = getInitialLocale();

const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {
        ar,
        en,
    },
});

// Apply locale on initial load
applyLocaleToDocument(initialLocale);

// Export helper functions
export function setLocale(locale) {
    i18n.global.locale.value = locale;
    localStorage.setItem('app.locale', locale);
    applyLocaleToDocument(locale);
}

export function getCurrentLocale() {
    return i18n.global.locale.value;
}

export default i18n;
