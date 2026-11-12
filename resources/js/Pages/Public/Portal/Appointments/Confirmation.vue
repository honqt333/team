<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.booking.confirmed_title')"
        :subtitle="t('customer_portal.booking.confirmed_subtitle')"
        show-logout
        show-bottom-nav
        :current-route="'public.portal.appointments.new'"
        :language-base-path="`/${tenant.slug}/portal/appointments/new`"
    >
        <!--
            Confirmation.vue — the success page.

            A clear "yes, your booking is confirmed" hero
            (no more plain check icon alone), the booking
            summary, and the next actions:
              - back to the dashboard
              - book another
              - cancel or reschedule (if eligible)

            The cancel and reschedule actions are only
            shown if the appointment is at least 2 hours
            away and the status is scheduled/confirmed.
            The server re-checks before persisting.
        -->

        <section
            class="rounded-2xl p-6 sm:p-8 text-white relative overflow-hidden"
            :style="{
                background:
                    'linear-gradient(135deg, color-mix(in srgb, var(--tenant-primary, #2563eb) 80%, var(--tenant-secondary, #0ea5e9)) 0%, var(--tenant-primary, #2563eb) 100%)',
            }"
        >
            <div
                class="w-14 h-14 rounded-full flex items-center justify-center mb-4 bg-white/15 backdrop-blur"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
            </div>

            <h2 class="text-2xl sm:text-3xl font-extrabold leading-tight">
                {{ t('customer_portal.booking.confirmed_title') }}
            </h2>
            <p class="mt-2 text-sm sm:text-base opacity-90">
                {{ t('customer_portal.booking.confirmed_subtitle') }}
            </p>

            <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="opacity-70 text-[10px] uppercase tracking-wider">
                        {{ t('customer_portal.booking.confirmed_when') }}
                    </p>
                    <p class="font-semibold mt-0.5" dir="ltr">
                        {{ formatDateTime(appointment.scheduled_at) }}
                    </p>
                </div>
                <div>
                    <p class="opacity-70 text-[10px] uppercase tracking-wider">
                        {{ t('customer_portal.booking.confirmed_branch') }}
                    </p>
                    <p class="font-semibold mt-0.5">
                        {{ appointment.center_name }}
                    </p>
                </div>
            </div>
        </section>

        <section
            class="mt-6 bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
        >
            <h3
                class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3"
            >
                {{ t('customer_portal.booking.review_title') }}
            </h3>
            <dl class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-slate-500 dark:text-slate-400 w-1/3">
                        {{ t('customer_portal.booking.confirmed_code') }}
                    </dt>
                    <dd class="font-mono font-bold text-end" dir="ltr">#{{ appointment.id }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-slate-500 dark:text-slate-400 w-1/3">
                        {{ t('customer_portal.booking.confirmed_vehicle') }}
                    </dt>
                    <dd class="font-semibold text-end flex flex-col items-end gap-1.5" dir="ltr">
                        <span>{{ appointment.vehicle_label }}</span>
                        <SaudiPlateDisplay
                            v-if="appointment.vehicle_plate"
                            :plate-number="appointment.vehicle_plate"
                            size="xs"
                        />
                    </dd>
                </div>
                <div
                    v-if="appointment.estimated_end_at"
                    class="flex items-start justify-between gap-3 py-3"
                >
                    <dt class="text-slate-500 dark:text-slate-400 w-1/3">
                        {{ t('customer_portal.booking.confirmed_until') }}
                    </dt>
                    <dd class="font-semibold text-end" dir="ltr">
                        {{ formatTime(appointment.estimated_end_at) }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-slate-500 dark:text-slate-400 w-1/3">
                        {{ t('customer_portal.booking.confirmed_services') }}
                    </dt>
                    <dd class="font-semibold text-end">
                        <ul
                            v-if="appointment.services && appointment.services.length"
                            class="space-y-0.5"
                        >
                            <li v-for="s in appointment.services" :key="s.id">
                                {{ s.name }}
                            </li>
                        </ul>
                        <span v-else class="text-slate-400">—</span>
                    </dd>
                </div>
                <div v-if="appointment.notes" class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-slate-500 dark:text-slate-400 w-1/3">
                        {{ t('customer_portal.portal_appointments.notes_label') }}
                    </dt>
                    <dd class="font-semibold text-end">
                        {{ appointment.notes }}
                    </dd>
                </div>
            </dl>
        </section>

        <!--
            Primary actions. Two CTAs side-by-side on
            larger screens, stacked on mobile. The
            "back to dashboard" is the low-emphasis
            outline button; "book another" is the
            filled primary.
        -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a
                :href="dashboardUrl"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
                {{ t('customer_portal.booking.back_to_dashboard_cta') }}
            </a>
            <a
                :href="bookAnotherUrl"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold text-white"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                {{ t('customer_portal.booking.book_another') }}
            </a>
        </div>

        <!--
            Self-service cancel + reschedule. Shown
            only when the appointment is at least 2
            hours away and in scheduled/confirmed
            status. The server re-checks the same
            window before persisting, so this is a
            hint, not a security gate.
        -->
        <div v-if="canManage" class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                    type="button"
                    @click="showRescheduleForm"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    {{ t('customer_portal.actions.reschedule_button') }}
                </button>
                <button
                    type="button"
                    @click="showCancelModal = true"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold text-rose-600 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                >
                    {{ t('customer_portal.actions.cancel_button') }}
                </button>
            </div>
        </div>

        <!--
            Cancel confirmation modal. A lightweight
            overlay: a small dialog, optional reason,
            and two buttons. The reason is plain text
            only; we never echo it back to anyone but
            the audit log.
        -->
        <BaseModal
            :show="showCancelModal"
            :title="t('customer_portal.actions.cancel_title')"
            size="sm"
            @close="showCancelModal = false"
        >
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
        </BaseModal>
    </CustomerPortalLayout>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    appointment: { type: Object, required: true },
});

const { t: tRaw, locale: i18nLocale } = useI18n();
const page = usePage();
const isRtl = computed(
    () => props.locale?.is_rtl ?? (page.props.locale || i18nLocale.value || 'ar') === 'ar'
);
const t = (path, params) => {
    const v = tRaw(path, params || {});
    return v === path ? path.split('.').pop() : v;
};

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
</script>
