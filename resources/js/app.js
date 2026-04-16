import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import i18n from './i18n';
import { getInitialTheme, applyTheme } from './theme';
import { ArabicNumeralsPlugin } from './Plugins/arabicNumerals';

import VueTelInput from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Apply initial theme on startup
applyTheme(getInitialTheme());

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // ...

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .use(ArabicNumeralsPlugin)
            .use(VueTelInput, {
                defaultCountry: 'SA',
                mode: 'international',
                inputOptions: {
                    showDialCode: true,
                    placeholder: '5xxxxxxxx',
                    maxlength: 15
                },
                validCharactersOnly: true, // Only allow numbers
                dropdownOptions: {
                    showFlags: true,
                    showDialCodeInList: true,
                    showSearchBox: true,
                    width: '390px' // Wider dropdown for better visibility
                }
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
