<template>
    <!--
        LanguageToggle — a pair of <a> tags that GET the page
        with `?lang=` set. The server reads the query, sets the
        cookie, and redirects to the same URL with the new locale
        in effect. We use plain links (not buttons) so the form's
        CSRF token is irrelevant — a GET to the page is harmless.

        The active language is highlighted in the tenant's primary
        color so the toggle is consistent with the rest of the
        brand chrome.
    -->
    <div
        class="inline-flex rounded-full border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/70 backdrop-blur p-0.5 text-xs font-semibold"
    >
        <a
            v-for="opt in options"
            :key="opt.value"
            :href="opt.href"
            class="px-2.5 py-1 rounded-full transition"
            :class="
                locale?.current === opt.value
                    ? 'text-white'
                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
            "
            :style="
                locale?.current === opt.value
                    ? { backgroundColor: 'var(--tenant-primary, #4f46e5)' }
                    : {}
            "
        >
            {{ opt.label }}
        </a>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    // The base path the toggle should target — usually
    // the current page's URL. Falls back to the login
    // path so the toggle still works on a deep link.
    basePath: { type: String, default: '' },
});

const base = computed(() => {
    if (props.basePath) {
        return props.basePath;
    }
    return `/${props.tenant?.slug || 'tenant'}/portal/dashboard`;
});

const options = computed(() => [
    { value: 'ar', label: t('common.lang.ar'), href: `${base.value}?lang=ar` },
    { value: 'en', label: 'English', href: `${base.value}?lang=en` },
]);
</script>
