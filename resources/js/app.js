import '../css/app.css';
import './bootstrap';

// Design System — Track A Phase 1
// Order matters: tokens (variables) → reset (normalize) → typography (uses tokens)
import '@/Design/tokens.css';
import '@/Design/reset.css';
import '@/Design/typography.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import i18n from './i18n';
import { getInitialTheme, applyTheme } from './theme';
import { ArabicNumeralsPlugin } from './Plugins/arabicNumerals';
import { SafeHtmlPlugin } from './Plugins/safeHtml';

import VueTelInput from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';

// Design System — App components (Track A Phase 1)
// Optional global registration: pages can use <AppButton>, <AppInput>, etc.
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppSelect from '@/Components/App/AppSelect.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppCheckbox from '@/Components/App/AppCheckbox.vue';

const appName = i18n.global.t('common.app_name') || import.meta.env.VITE_APP_NAME || 'Laravel';

// Apply initial theme on startup
applyTheme(getInitialTheme());

// Force page reload on back-forward cache restore to ensure correct auth states
if (typeof window !== 'undefined') {
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // ...

        return (
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .use(i18n)
                .use(ArabicNumeralsPlugin)
                .use(SafeHtmlPlugin)
                .use(VueTelInput, {
                    defaultCountry: 'SA',
                    mode: 'international',
                    inputOptions: {
                        showDialCode: true,
                        placeholder: '5xxxxxxxx',
                        maxlength: 15,
                    },
                    validCharactersOnly: true, // Only allow numbers
                    dropdownOptions: {
                        showFlags: true,
                        showDialCodeInList: true,
                        showSearchBox: true,
                        width: '390px', // Wider dropdown for better visibility
                    },
                })
                // Design System — global App component registration
                // (Track A Phase 1). Pages can use <AppButton>, <AppInput>, etc.
                .component('AppButton', AppButton)
                .component('AppInput', AppInput)
                .component('AppSelect', AppSelect)
                .component('AppTextarea', AppTextarea)
                .component('AppCheckbox', AppCheckbox)
                .mount(el)
        );
    },
    progress: {
        color: '#4B5563',
    },
});
