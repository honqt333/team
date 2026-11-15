<template>
    <Head>
        <title v-if="title">{{ title }} — {{ tenant.site_name }}</title>
        <slot name="head" />
    </Head>

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="relative min-h-screen overflow-x-hidden bg-[var(--color-background)] pb-24 text-text-primary md:pb-10 print:min-h-0 print:bg-white print:pb-0"
    >
        <Toast />
        <ConfirmModal />
        <!-- Ambient brand glow -->
        <div class="pointer-events-none fixed inset-0 print:hidden" aria-hidden="true">
            <div
                class="absolute -end-28 -top-36 h-[28rem] w-[28rem] rounded-full opacity-[0.09] blur-3xl"
                :style="{ backgroundColor: 'var(--tenant-primary)' }"
            />
            <div
                class="absolute -start-28 bottom-0 h-96 w-96 rounded-full opacity-[0.07] blur-3xl"
                :style="{ backgroundColor: 'var(--tenant-secondary)' }"
            />
        </div>

        <!-- Sticky Top Header -->
        <header
            class="sticky top-0 z-30 border-b border-border/80 bg-surface/90 shadow-xs backdrop-blur-xl print:hidden"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8"
            >
                <a
                    :href="`/${tenant.slug}/portal/dashboard`"
                    class="flex min-w-0 items-center gap-3 transition hover:opacity-90"
                >
                    <BrandMark :tenant="tenant" />
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-extrabold leading-tight text-text-primary">
                            {{ title || tenant.site_name }}
                        </h1>
                        <p v-if="subtitle" class="truncate text-xs text-text-secondary">
                            {{ subtitle }}
                        </p>
                    </div>
                </a>

                <!-- Desktop Navigation Pills -->
                <nav
                    v-if="showBottomNav"
                    class="hidden items-center gap-1 rounded-2xl border border-border/70 bg-surface-muted/90 p-1 lg:flex"
                >
                    <a
                        v-for="item in bottomNav"
                        :key="item.key"
                        :href="item.href"
                        class="relative inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition-all"
                        :class="
                            item.active
                                ? 'bg-surface text-text-primary shadow-sm ring-1 ring-border/60'
                                : 'text-text-secondary hover:bg-surface/60 hover:text-text-primary'
                        "
                    >
                        <span
                            class="shrink-0"
                            :class="item.active ? 'text-brand-primary' : ''"
                            v-safe-html="item.icon"
                        />
                        <span>{{ item.label }}</span>
                        <span
                            v-if="item.badge > 0"
                            class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-[10px] font-extrabold"
                            :style="{
                                backgroundColor: item.badgeAlert
                                    ? '#dc2626'
                                    : 'var(--tenant-primary, var(--color-brand-primary))',
                                color: item.badgeAlert ? '#ffffff' : badgeTextColor,
                            }"
                        >
                            {{ item.badge }}
                        </span>
                    </a>
                </nav>

                <!-- Right Actions -->
                <div class="flex shrink-0 items-center gap-2">
                    <LanguageToggle
                        v-if="showLanguageToggle"
                        :tenant="tenant"
                        :locale="locale"
                        :base-path="languageBasePath"
                    />

                    <!-- Theme Toggle -->
                    <button
                        type="button"
                        data-testid="portal-theme-toggle"
                        @click="toggleThemeHandler"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-border bg-surface text-text-secondary transition hover:bg-surface-muted hover:text-text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus-ring"
                        :aria-label="t('common.toggle_theme')"
                        :title="t('common.toggle_theme')"
                    >
                        <svg
                            v-if="isDark"
                            class="h-4 w-4 text-accent-gold"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-4 w-4 text-text-secondary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>
                    </button>

                    <a
                        v-if="showBackToSite"
                        :href="`/${tenant.slug}`"
                        class="hidden items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-2 text-xs font-bold text-text-secondary transition hover:bg-surface-muted hover:text-text-primary sm:inline-flex"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                        {{ t('customer_portal.dashboard.back_to_site') }}
                    </a>

                    <form
                        v-if="showLogout"
                        :action="`/${tenant.slug}/portal/logout`"
                        method="post"
                        class="inline"
                    >
                        <input type="hidden" name="_token" :value="csrf" />
                        <button
                            type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-surface text-xs font-bold text-text-secondary transition hover:bg-danger-soft hover:border-danger/30 hover:text-danger sm:w-auto sm:px-3"
                            :aria-label="t('customer_portal.dashboard.logout')"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                />
                            </svg>
                            <span class="hidden sm:inline sm:ms-1.5">
                                {{ t('customer_portal.dashboard.logout') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="relative mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8 print:max-w-none print:p-0">
            <slot />
        </main>

        <!-- Mobile / Tablet Floating Bottom Navigation -->
        <nav
            v-if="showBottomNav"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-border/90 bg-surface/95 shadow-[0_-10px_30px_-20px_rgba(15,23,42,0.35)] backdrop-blur-xl lg:hidden print:hidden"
            :aria-label="t('customer_portal.nav.bottom_label')"
        >
            <ul class="mx-auto grid max-w-xl grid-cols-5">
                <li v-for="item in bottomNav" :key="item.key">
                    <a
                        :href="item.href"
                        class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-bold tracking-tight transition"
                        :class="
                            item.active
                                ? 'text-brand-primary dark:text-blue-300'
                                : 'text-text-secondary hover:text-text-primary'
                        "
                    >
                        <div class="relative">
                            <span v-safe-html="item.icon" />
                            <span
                                v-if="item.badge > 0"
                                class="absolute -top-1.5 -end-2.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full px-1 text-[9px] font-extrabold shadow-sm"
                                :style="{
                                    backgroundColor: item.badgeAlert
                                        ? '#dc2626'
                                        : 'var(--tenant-primary, var(--color-brand-primary))',
                                    color: item.badgeAlert ? '#ffffff' : badgeTextColor,
                                }"
                            >
                                {{ item.badge }}
                            </span>
                        </div>
                        <span class="truncate max-w-[4.5rem]">{{ item.label }}</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import BrandMark from '@/Components/CustomerPortal/BrandMark.vue';
import LanguageToggle from '@/Components/CustomerPortal/LanguageToggle.vue';
import Toast from '@/Components/Toast.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useFlashToasts } from '@/Composables/useFlashToasts';
import { useTheme } from '@/Composables/useTheme';
import { accessibleTextOn } from '@/Utils/colorContrast';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    showLogout: { type: Boolean, default: false },
    showBackToSite: { type: Boolean, default: false },
    showLanguageToggle: { type: Boolean, default: true },
    showBottomNav: { type: Boolean, default: false },
    languageBasePath: { type: String, default: '' },
    currentRoute: { type: String, default: '' },
});

const { t: tRaw, locale: i18nLocale } = useI18n();
const page = usePage();

const t = (path, params) => {
    const v = tRaw(path, params || {});
    return v === path ? path.split('.').pop() : v;
};

const isRtl = computed(
    () => props.locale?.is_rtl ?? (page.props.locale || i18nLocale.value || 'ar') === 'ar'
);

watchEffect(() => {
    if (typeof document === 'undefined') return;
    const lang = props.locale?.current || page.props.locale || (isRtl.value ? 'ar' : 'en');
    document.documentElement.lang = lang;
    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
});

const { isDark, toggle: toggleThemeHandler } = useTheme();

const badgeTextColor = computed(() =>
    accessibleTextOn(props.tenant?.primary_color || '#2563eb')
);

const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#2563eb',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

const csrf = computed(() => {
    if (typeof document === 'undefined') {
        return '';
    }
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
});

const badges = computed(() => props.tenant?.badges || {});

const bottomNav = computed(() => [
    {
        key: 'home',
        label: t('customer_portal.nav.home'),
        href: `/${props.tenant.slug}/portal/dashboard`,
        active: props.currentRoute === 'public.portal.dashboard',
        badge: 0,
        badgeAlert: false,
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>`,
    },
    {
        key: 'work_orders',
        label: t('customer_portal.nav.work_orders'),
        href: `/${props.tenant.slug}/portal/work-orders`,
        active:
            props.currentRoute === 'public.portal.workOrders.index' ||
            props.currentRoute === 'public.portal.workOrders.show',
        badge: Number(badges.value.active_work_orders_count || 0),
        badgeAlert: false,
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`,
    },
    {
        key: 'quotes',
        label: t('customer_portal.nav.quotes'),
        href: `/${props.tenant.slug}/portal/quotes`,
        active:
            props.currentRoute === 'public.portal.quotes.index' ||
            props.currentRoute === 'public.portal.quotes.show',
        badge: Number(badges.value.pending_quotes_count || 0),
        badgeAlert: true,
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
    },
    {
        key: 'invoices',
        label: t('customer_portal.nav.invoices'),
        href: `/${props.tenant.slug}/portal/invoices`,
        active:
            props.currentRoute === 'public.portal.invoices.index' ||
            props.currentRoute === 'public.portal.invoices.show',
        badge: Number(badges.value.unpaid_invoices_count || 0),
        badgeAlert: true,
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    },
    {
        key: 'bookings',
        label: t('customer_portal.nav.appointments'),
        href: `/${props.tenant.slug}/portal/appointments`,
        active:
            props.currentRoute === 'public.portal.appointments.index' ||
            props.currentRoute === 'public.portal.appointments.new' ||
            props.currentRoute === 'public.portal.appointments.show' ||
            props.currentRoute === 'public.portal.appointments.edit',
        badge: Number(badges.value.upcoming_appointments_count || 0),
        badgeAlert: false,
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
    },
]);

useFlashToasts(page, { immediate: true });
</script>
