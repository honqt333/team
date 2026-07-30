<template>
    <Head>
        <title>{{ t('customer_portal.booking.confirmed_title') }} — {{ tenant.site_name }}</title>
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
                        <h1 class="font-bold text-base truncate">
                            {{ t('customer_portal.booking.confirmed_title') }}
                        </h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="dashboardUrl"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        {{ t('customer_portal.booking.back_to_dashboard_cta') }}
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
                    :style="{
                        backgroundColor:
                            'color-mix(in srgb, var(--tenant-primary, #4f46e5) 12%, transparent)',
                        color: 'var(--tenant-primary, #4f46e5)',
                    }"
                >
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-center mb-1">
                    {{ t('customer_portal.booking.confirmed_title') }}
                </h2>
                <p class="text-center text-sm text-slate-500 dark:text-slate-400 mb-6">
                    {{ t('customer_portal.booking.confirmed_subtitle') }}
                </p>

                <dl class="space-y-3 text-sm">
                    <div
                        class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800"
                    >
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.confirmed_code') }}
                        </dt>
                        <dd class="font-mono font-bold" dir="ltr">#{{ appointment.id }}</dd>
                    </div>
                    <div
                        class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800"
                    >
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.confirmed_branch') }}
                        </dt>
                        <dd class="font-semibold text-end">
                            {{ appointment.center_name }}
                        </dd>
                    </div>
                    <div
                        class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800"
                    >
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.confirmed_vehicle') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{ appointment.vehicle_label }}
                        </dd>
                    </div>
                    <div
                        class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800"
                    >
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.confirmed_when') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{ formatDateTime(appointment.scheduled_at) }}
                        </dd>
                    </div>
                    <div
                        v-if="appointment.estimated_end_at"
                        class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800"
                    >
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.confirmed_until') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{ formatTime(appointment.estimated_end_at) }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.confirmed_services') }}
                        </dt>
                        <dd class="font-semibold text-end">
                            <ul class="space-y-0.5">
                                <li v-for="s in appointment.services" :key="s.id">
                                    {{ s.name }}
                                </li>
                            </ul>
                        </dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a
                        :href="dashboardUrl"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        {{ t('customer_portal.booking.back_to_dashboard_cta') }}
                    </a>
                    <a
                        :href="bookAnotherUrl"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        {{ t('customer_portal.booking.book_another') }}
                    </a>
                </div>

                <!-- Self-service cancel + reschedule.
                     Both actions are only offered when
                     the appointment is at least 2 hours
                     away, in scheduled/confirmed status.
                     The server re-checks the same window
                     before persisting, so this is a
                     hint, not a security boundary. -->
                <div
                    v-if="canManage"
                    class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800"
                >
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            type="button"
                            @click="showRescheduleForm"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            {{ t('customer_portal.actions.reschedule_button') }}
                        </button>
                        <button
                            type="button"
                            @click="showCancelModal = true"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-rose-600 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                        >
                            {{ t('customer_portal.actions.cancel_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Cancel confirmation modal. A lightweight
             overlay: a small dialog, optional reason,
             and two buttons. The reason is plain text
             only; we never echo it back to anyone but
             the audit log. -->
        <div
            v-if="showCancelModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
            @click.self="showCancelModal = false"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-xl"
            >
                <h3 class="text-lg font-bold mb-2">
                    {{ t('customer_portal.actions.cancel_title') }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    {{ t('customer_portal.actions.cancel_body') }}
                </p>
                <form @submit.prevent="submitCancel" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">
                            {{ t('customer_portal.actions.cancel_reason_label') }}
                        </label>
                        <textarea
                            v-model="cancelReason"
                            :placeholder="t('customer_portal.actions.cancel_reason_placeholder')"
                            rows="3"
                            maxlength="255"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm"
                        ></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showCancelModal = false"
                            class="flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            {{ t('customer_portal.actions.cancel_keep') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="cancelSubmitting"
                            class="flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50"
                        >
                            {{ t('customer_portal.actions.cancel_confirm') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    appointment: { type: Object, required: true },
});

const { t: tRaw } = useI18n();
const page = usePage();
const isRtl = computed(() => props.locale?.is_rtl ?? (page.props.locale || 'ar') === 'ar');
const t = (key, params) => {
    const v = tRaw(key, params || {});
    return v === key ? key.split('.').pop() : v;
};

const isDark = ref(true);

const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#4f46e5',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

const dashboardUrl = computed(() => `/${props.tenant.slug}/portal/dashboard`);
const bookAnotherUrl = computed(() => `/${props.tenant.slug}/portal/appointments/new`);
const editUrl = computed(
    () => `/${props.tenant.slug}/portal/appointment/${props.appointment.id}/edit`
);

// The server enforces the 2-hour window and the
// scheduled/confirmed status; the client mirrors
// the same hint so we don't show buttons the
// server will reject. This is a UX nicety, not a
// security gate.
const MIN_HOURS_BEFORE = 2;
const canManage = computed(() => {
    if (!props.appointment?.scheduled_at) {
        return false;
    }
    const start = new Date(props.appointment.scheduled_at);
    const earliest = new Date();
    earliest.setHours(earliest.getHours() + MIN_HOURS_BEFORE);
    if (start < earliest) {
        return false;
    }
    const status = props.appointment?.status;
    return status === 'scheduled' || status === 'confirmed';
});

const showCancelModal = ref(false);
const cancelReason = ref('');
const cancelSubmitting = ref(false);

const initial = computed(() => {
    const n = props.tenant?.site_name || '';
    return n.trim().charAt(0).toUpperCase() || '?';
});

function showRescheduleForm() {
    window.location.href = editUrl.value;
}

function submitCancel() {
    if (cancelSubmitting.value) {
        return;
    }
    cancelSubmitting.value = true;
    router.post(
        route('public.portal.appointments.cancel', {
            tenant: props.tenant.slug,
            id: props.appointment.id,
        }),
        { reason: cancelReason.value },
        {
            onError: () => {
                cancelSubmitting.value = false;
            },
            onFinish: () => {
                cancelSubmitting.value = false;
            },
        }
    );
}

function formatDateTime(iso) {
    if (!iso) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(isRtl.value ? 'ar-SA' : 'en-US', {
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
        return new Intl.DateTimeFormat(isRtl.value ? 'ar-SA' : 'en-US', {
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(iso));
    } catch (e) {
        return iso;
    }
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
