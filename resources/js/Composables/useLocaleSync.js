import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const synced = ref(false);

export function useLocaleSync() {
    const { locale } = useI18n();

    // Sync locale to backend when it changes
    const syncLocale = async (newLocale) => {
        try {
            await axios.post('/locale', { locale: newLocale });
        } catch (error) {
            console.error('Failed to sync locale:', error);
        }
    };

    // Initialize: sync current locale to backend on first load
    const init = async () => {
        if (!synced.value) {
            await syncLocale(locale.value);
            synced.value = true;
        }
    };

    // Watch for locale changes and sync to backend
    watch(locale, (newLocale) => {
        syncLocale(newLocale);
    });

    return {
        init,
        syncLocale,
    };
}
