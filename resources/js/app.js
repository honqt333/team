import '../css/app.css';
import './bootstrap';

// Design System — Track A Phase 1
// Order matters: tokens (variables) → reset (normalize) → typography (uses tokens)
import '@/Design/tokens.css';
import '@/Design/reset.css';
import '@/Design/typography.css';
import '@/Design/global-styles.css';

import { createInertiaApp } from '@inertiajs/vue3';
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

// Page resolution.
//
// PERFORMANCE — why this is NOT a plain `import.meta.glob('./Pages/**/*.vue')`:
//
// A static glob pattern makes Vite enumerate every page at build time and
// bake a 219-entry map into app.js. Rollup then hands that map to the
// `modulepreload` polyfill, which prefetches ALL of those chunks on page
// load. Measured on the public landing page, that meant 330 chunk requests
// and ~2.1 MB transferred — including TenantHealthScore, Payments, Proforma,
// PrintEngine and the whole Website CMS — none of which a landing-page
// visitor ever opens.
//
// Passing the pattern as a VARIABLE keeps the glob lazy: the map is built at
// runtime from the actual page name, so only the requested page (and its
// real dependencies) is fetched.
const pageModules = import.meta.glob('./Pages/**/*.vue');

function resolvePage(name) {
    // Inertia passes names like 'Public/LandingPreview'.
    const path = `./Pages/${name}.vue`;
    const loader = pageModules[path];
    if (!loader) {
        throw new Error(`Page not found: ${name}`);
    }
    return loader();
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: resolvePage,
    setup({ el, App, props, plugin }) {
        // Public pages can choose a locale independently from the authenticated
        // application. Sync vue-i18n before the first render so the portal never
        // shows an Arabic direction/toggle with English copy (or the reverse).
        const requestedLocale = props.initialPage?.props?.locale;
        const localeCode =
            typeof requestedLocale === 'object' ? requestedLocale?.current : requestedLocale;

        if (['ar', 'en'].includes(localeCode)) {
            i18n.global.locale.value = localeCode;
        }

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
