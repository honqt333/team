<template>
    <Head>
        <title v-if="title">{{ title }} — {{ tenant.site_name }}</title>
        <slot name="head" />
    </Head>

    <!--
        Root surface.

        The page lives inside a single themed container so the
        CSS variables driving the brand colors (primary, secondary)
        are scoped to the whole portal session. The customer can
        flip the layout direction between RTL (Arabic) and LTR
        (English) without losing the brand — direction is set on
        the root, not the header.
    -->
    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="relative min-h-screen overflow-x-hidden bg-[#f6f8fb] pb-24 text-slate-950 md:pb-0"
    >
        <div class="pointer-events-none fixed inset-0" aria-hidden="true">
            <div
                class="absolute -end-28 -top-36 h-96 w-96 rounded-full opacity-[0.08] blur-3xl"
                :style="{ backgroundColor: 'var(--tenant-primary)' }"
            />
            <div
                class="absolute -start-28 bottom-0 h-80 w-80 rounded-full opacity-[0.06] blur-3xl"
                :style="{ backgroundColor: 'var(--tenant-secondary)' }"
            />
        </div>
        <!--
            Header. The header sits above the brand identity
            and the page title. The bottom navigation
            (md:hidden) replaces the header on mobile so the
            customer always has a way out.
        -->
        <header
            class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur-xl"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <BrandMark :tenant="tenant" />
                    <div class="min-w-0">
                        <h1 class="font-bold text-base truncate leading-tight">
                            {{ title || tenant.site_name }}
                        </h1>
                        <p v-if="subtitle" class="truncate text-xs text-slate-500">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <nav
                    v-if="showBottomNav"
                    class="hidden items-center gap-1 rounded-2xl bg-slate-100 p-1 md:flex"
                >
                    <a
                        v-for="item in bottomNav"
                        :key="item.key"
                        :href="item.href"
                        class="rounded-xl px-4 py-2 text-sm font-bold transition"
                        :class="
                            item.active
                                ? 'bg-white text-slate-950 shadow-sm'
                                : 'text-slate-500 hover:text-slate-900'
                        "
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    <LanguageToggle
                        v-if="showLanguageToggle"
                        :tenant="tenant"
                        :locale="locale"
                        :base-path="languageBasePath"
                    />

                    <a
                        v-if="showBackToSite"
                        :href="`/${tenant.slug}`"
                        class="hidden items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 sm:inline-flex"
                    >
                        <svg
                            class="w-3.5 h-3.5"
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
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 sm:w-auto sm:px-3"
                            :aria-label="t('customer_portal.dashboard.logout')"
                        >
                            <svg
                                class="w-4 h-4"
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

        <main class="relative mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <slot />
        </main>

        <!--
            Bottom navigation. Mobile-first. We only show the
            items we actually have routes for. Anything that
            does not have a route yet is rendered as a disabled
            entry so the customer does not navigate to a 404.
        -->
        <nav
            v-if="showBottomNav"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 shadow-[0_-10px_30px_-20px_rgba(15,23,42,0.35)] backdrop-blur-xl md:hidden"
            :aria-label="t('customer_portal.nav.bottom_label')"
        >
            <ul class="mx-auto grid max-w-md grid-cols-2">
                <li v-for="item in bottomNav" :key="item.key">
                    <component
                        :is="item.href ? 'a' : 'span'"
                        :href="item.href || undefined"
                        :aria-disabled="!item.href || undefined"
                        class="flex flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] font-semibold uppercase tracking-wide"
                        :class="
                            item.href
                                ? item.active
                                    ? 'text-[color:var(--tenant-primary,#2563eb)]'
                                    : 'text-slate-500 dark:text-slate-400'
                                : 'cursor-not-allowed text-slate-300'
                        "
                    >
                        <span v-html="item.icon" />
                        <span>{{ item.label }}</span>
                    </component>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BrandMark from '@/Components/CustomerPortal/BrandMark.vue';
import LanguageToggle from '@/Components/CustomerPortal/LanguageToggle.vue';

/*
    CustomerPortalLayout — single themed surface for every
    protected portal page.

    Why a layout and not a copy-paste header
    ----------------------------------------
    Login.vue, Verify.vue, Dashboard.vue, New.vue, Reschedule.vue,
    and Confirmation.vue all need the same brand chrome, the same
    language toggle, the same logout button, the same CSS
    variables, and the same RTL handling. Duplicating the
    header in every page meant the brand would drift as soon as
    one page was updated and the others were not. This layout
    gives every page a single source of truth for chrome — the
    page slot is the only place a page writes its own content.
*/

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    // Page-level opt-outs. Login and Verify are public
    // surfaces and should NOT show the back-to-site or
    // logout controls; the dashboard and the booking flow
    // are authenticated and show both.
    showLogout: { type: Boolean, default: false },
    showBackToSite: { type: Boolean, default: false },
    showLanguageToggle: { type: Boolean, default: true },
    showBottomNav: { type: Boolean, default: false },
    // Used by the LanguageToggle to build its `?lang=`
    // links — the toggle is just a GET to the same page
    // with a different locale.
    languageBasePath: { type: String, default: '' },
    // The route the customer is currently on, used to
    // highlight the right item in the bottom nav.
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

const isDark = ref(false);

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

// Keep navigation honest: only expose destinations that exist today.
const dashboardUrl = computed(() => `/${props.tenant.slug}/portal/dashboard`);
const newBookingUrl = computed(() => `/${props.tenant.slug}/portal/appointments/new`);

const bottomNav = computed(() => [
    {
        key: 'home',
        label: t('customer_portal.nav.home'),
        href: dashboardUrl.value,
        active: props.currentRoute === 'public.portal.dashboard',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>`,
    },
    {
        key: 'bookings',
        label: t('customer_portal.nav.bookings'),
        href: newBookingUrl.value,
        active: props.currentRoute === 'public.portal.appointments.new',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
    },
]);

onMounted(() => {
    if (typeof window !== 'undefined') {
        document.documentElement.classList.remove('dark');
    }
});
</script>
