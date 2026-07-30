<template>
    <Head>
        <title>{{ t('customer_portal.dashboard.title') }} — {{ tenant.site_name }}</title>
    </Head>

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <header
            class="sticky top-0 z-40 backdrop-blur bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800"
        >
            <div
                class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="h-9 w-9 rounded-xl flex items-center justify-center text-white font-bold flex-shrink-0"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        {{ initial }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="font-bold text-base truncate">{{ tenant.site_name }}</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ t('customer_portal.dashboard.greeting', { name: customer.name }) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        v-if="canBook"
                        :href="bookUrl"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold text-white"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span>{{ t('customer_portal.dashboard.book_cta') }}</span>
                    </a>
                    <a
                        :href="`/${tenant.slug}`"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        {{ t('customer_portal.dashboard.back_to_site') }}
                    </a>
                    <form :action="`/${tenant.slug}/portal/logout`" method="post" class="inline">
                        <input type="hidden" name="_token" :value="csrf" />
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
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
                            <span class="hidden sm:inline">
                                {{ t('customer_portal.dashboard.logout') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10 space-y-8">
            <!-- Vehicles -->
            <section>
                <h2 class="text-lg font-bold mb-3">
                    {{ t('customer_portal.dashboard.vehicles') }}
                </h2>
                <div
                    v-if="vehicles.length === 0"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-8 text-center border border-slate-200 dark:border-slate-800"
                >
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.dashboard.no_vehicles') }}
                    </p>
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <article
                        v-for="v in vehicles"
                        :key="v.id"
                        class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                    >
                        <div class="font-bold text-base" dir="ltr">{{ v.plate_number }}</div>
                        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ [v.make, v.model, v.year].filter(Boolean).join(' · ') }}
                        </div>
                        <div v-if="v.color" class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                            {{ v.color }}
                        </div>
                    </article>
                </div>
            </section>

            <!-- Upcoming appointments -->
            <section>
                <h2 class="text-lg font-bold mb-3">
                    {{ t('customer_portal.dashboard.upcoming') }}
                </h2>
                <div
                    v-if="upcomingAppointments.length === 0"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-8 text-center border border-slate-200 dark:border-slate-800"
                >
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.dashboard.no_upcoming') }}
                    </p>
                </div>
                <div v-else class="space-y-2">
                    <article
                        v-for="a in upcomingAppointments"
                        :key="a.id"
                        class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold" dir="ltr">
                                    {{ formatDate(a.scheduled_at) }}
                                </div>
                                <div
                                    v-if="a.center_name || a.service_bay_name"
                                    class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                                >
                                    {{
                                        [a.center_name, a.service_bay_name]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </div>
                                <div
                                    v-if="a.estimated_end_at"
                                    class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"
                                    dir="ltr"
                                >
                                    {{ t('customer_portal.dashboard.until') }}
                                    {{ formatTime(a.estimated_end_at) }}
                                </div>
                            </div>
                            <span
                                class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                :style="badgeStyle(a.status)"
                            >
                                {{ t('customer_portal.dashboard.status_' + a.status) }}
                            </span>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Recent work orders -->
            <section>
                <h2 class="text-lg font-bold mb-3">{{ t('customer_portal.dashboard.recent') }}</h2>
                <div
                    v-if="recentWorkOrders.length === 0"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-8 text-center border border-slate-200 dark:border-slate-800"
                >
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.dashboard.no_recent') }}
                    </p>
                </div>
                <div v-else class="space-y-2">
                    <article
                        v-for="w in recentWorkOrders"
                        :key="w.id"
                        class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold" dir="ltr">#{{ w.code }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ t('customer_portal.dashboard.opened') }}
                                    {{ formatDate(w.created_at) }}
                                </div>
                                <div
                                    v-if="w.expected_end_date"
                                    class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                                    dir="ltr"
                                >
                                    {{ t('customer_portal.dashboard.expected_end') }}
                                    {{ formatDateOnly(w.expected_end_date) }}
                                </div>
                            </div>
                            <span
                                class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex-shrink-0"
                            >
                                {{ w.status }}
                            </span>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    tenant: { type: Object, required: true },
    customer: { type: Object, required: true },
    vehicles: { type: Array, default: () => [] },
    upcomingAppointments: { type: Array, default: () => [] },
    recentWorkOrders: { type: Array, default: () => [] },
});

const { t: tRaw, locale } = useI18n();
const page = usePage();
const isRtl = computed(() => (page.props.locale || locale.value) === 'ar');
const t = (key, params) => {
    const v = tRaw(key, params || {});
    return v === key ? key.split('.').pop() : v;
};

const isDark = ref(true);

// CSS variables for the tenant brand. The dashboard
// itself doesn't have a primary action button — those
// live on Login/Verify — but the header tile, the logo,
// and any future CTAs use these.
const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#4f46e5',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

const initial = computed(() => {
    const n = props.customer?.name || '';
    return n.trim().charAt(0).toUpperCase() || '?';
});

// Booking is only offered to customers with at least
// one vehicle — the booking form rejects requests
// without a vehicle and there's no self-service way
// to add one in the portal. Hiding the CTA on empty
// states keeps the UI honest.
const canBook = computed(() => (props.vehicles?.length ?? 0) > 0);
const bookUrl = computed(() => `/${props.tenant.slug}/portal/appointments/new`);

const csrf = computed(() => {
    if (typeof document === 'undefined') {
        return '';
    }
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
});

const dateLocale = computed(() => (isRtl.value ? 'ar-SA' : 'en-US'));

function formatDate(iso) {
    if (!iso) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(dateLocale.value, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch (e) {
        return iso;
    }
}

function formatTime(iso) {
    if (!iso) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(dateLocale.value, {
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(iso));
    } catch (e) {
        return iso;
    }
}

function formatDateOnly(iso) {
    if (!iso) {
        return '';
    }
    try {
        // expected_end_date arrives as YYYY-MM-DD (a date
        // string from Carbon's toDateString). Parsing it
        // as YYYY-MM-DD in UTC and then re-formatting in
        // the user's locale avoids the timezone shift that
        // would happen if we let JS interpret it as
        // local-midnight.
        const d = iso.length === 10 ? new Date(iso + 'T00:00:00') : new Date(iso);
        return new Intl.DateTimeFormat(dateLocale.value, {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(d);
    } catch (e) {
        return iso;
    }
}

function badgeStyle(status) {
    const palette = {
        scheduled: { bg: '#e0e7ff', fg: '#3730a3' },
        confirmed: { bg: '#dcfce7', fg: '#166534' },
        checked_in: { bg: '#fef3c7', fg: '#92400e' },
    };
    const c = palette[status] || { bg: '#e2e8f0', fg: '#334155' };
    return {
        backgroundColor: c.bg,
        color: c.fg,
    };
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('cp-dark');
        if (stored !== null) {
            isDark.value = stored === '1';
        } else if (window.matchMedia) {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        document.documentElement.classList.toggle('dark', isDark.value);
    }
});
</script>
