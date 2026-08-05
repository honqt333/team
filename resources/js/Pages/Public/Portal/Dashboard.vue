<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.dashboard.title')"
        :subtitle="t('customer_portal.dashboard.greeting_subtitle')"
        show-logout
        show-back-to-site
        show-bottom-nav
        :current-route="'public.portal.dashboard'"
    >
        <!--
            Dashboard.vue — customer portal home.

            The first thing the customer sees is the
            PRIMARY CARD. It carries the single most
            important interaction the customer can take
            right now:
              - upcoming appointment, or
              - active work order, or
              - empty state with a "book now" CTA.

            Below the primary card: the customer's
            vehicles (small chips, not a flood of cards),
            and a compact recent-activity list.
        -->

        <!--
            Greeting line. Sits at the top of the page,
            not inside the primary card, because the
            greeting is the page header. The card
            carries the action, not the welcome.
        -->
        <p class="text-base sm:text-lg text-slate-700 dark:text-slate-200 mb-4">
            {{ t('customer_portal.dashboard.greeting', { name: customer.name }) }}
        </p>

        <!--
            Primary card. The component picks one of three
            shapes:
              1. Upcoming appointment (highest priority)
              2. Active work order (fallback if no
                 upcoming appointment)
              3. Empty state (no appointment, no work
                 order)
        -->
        <PrimaryCard
            v-if="primaryCard.kind === 'appointment'"
            kind="appointment"
            :kicker="t('customer_portal.dashboard.primary_kicker_appointment')"
            :title="primaryCard.title"
            :subtitle="t('customer_portal.dashboard.primary_subtitle_appointment')"
            :when-label="t('customer_portal.dashboard.cta_view')"
            :when-value="primaryCard.when"
            :branch-label="t('customer_portal.booking.review_branch')"
            :branch-value="primaryCard.branch"
            :cta-primary="{
                label: t('customer_portal.dashboard.cta_view'),
                href: primaryCard.viewHref,
            }"
            :cta-secondary="
                canManageAppointment
                    ? {
                          label: t('customer_portal.dashboard.cta_edit'),
                          href: primaryCard.editHref,
                      }
                    : null
            "
        />

        <PrimaryCard
            v-else-if="primaryCard.kind === 'work_order'"
            kind="work_order"
            :kicker="t('customer_portal.dashboard.primary_kicker_workorder')"
            :title="t('customer_portal.dashboard.primary_title_workorder')"
            :subtitle="t('customer_portal.dashboard.primary_subtitle_workorder')"
            :progress-label="t('customer_portal.dashboard.progress_label')"
            :progress-caption="
                t('customer_portal.dashboard.progress_caption', { status: primaryCard.statusLabel })
            "
            :progress-value="primaryCard.progress"
            :cta-primary="
                canBook
                    ? {
                          label: t('customer_portal.dashboard.cta_book'),
                          href: bookUrl,
                      }
                    : null
            "
        />

        <PrimaryCard
            v-else
            kind="empty"
            :kicker="t('customer_portal.dashboard.primary_kicker_empty')"
            :title="t('customer_portal.dashboard.primary_title_empty')"
            :subtitle="t('customer_portal.dashboard.primary_subtitle_empty')"
            :cta-primary="
                canBook
                    ? {
                          label: t('customer_portal.dashboard.cta_book'),
                          href: bookUrl,
                      }
                    : null
            "
        />

        <!--
            Vehicles. The user spec asks for a small
            chips/cards list — NOT the previous big
            grid that ate the whole page. We render
            a horizontal scroller on mobile and a small
            grid on larger screens. Empty state is calm.
        -->
        <section class="mt-8">
            <div class="flex items-baseline justify-between mb-3">
                <h2 class="text-base sm:text-lg font-bold">
                    {{ t('customer_portal.dashboard.section_vehicles') }}
                </h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ vehicles.length }}
                </span>
            </div>
            <div
                v-if="vehicles.length === 0"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-center text-sm text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800"
            >
                {{ t('customer_portal.dashboard.no_vehicles') }}
            </div>
            <div
                v-else
                class="flex gap-3 overflow-x-auto sm:grid sm:grid-cols-2 sm:overflow-visible -mx-4 px-4 sm:mx-0 sm:px-0"
            >
                <article
                    v-for="v in vehicles"
                    :key="v.id"
                    class="flex-shrink-0 w-44 sm:w-auto bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800"
                >
                    <div class="flex items-center gap-2 mb-1">
                        <svg
                            class="w-4 h-4 text-slate-400 dark:text-slate-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 13l1.06-3.18a2 2 0 011.9-1.32h12.08a2 2 0 011.9 1.32L21 13M5 17v2a1 1 0 001 1h2a1 1 0 001-1v-2m6 0v2a1 1 0 001 1h2a1 1 0 001-1v-2M5 17h14a2 2 0 002-2v-2H3v2a2 2 0 002 2z"
                            />
                        </svg>
                        <span class="font-bold text-sm" dir="ltr">
                            {{ v.plate_number }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        {{ [v.make, v.model, v.year].filter(Boolean).join(' · ') }}
                    </div>
                </article>
            </div>
        </section>

        <!--
            Recent activity. A compact list of past
            work orders. We map the raw status keys to
            friendly labels here, so the customer never
            sees "ready_for_qc" in their UI.
        -->
        <section class="mt-8">
            <h2 class="text-base sm:text-lg font-bold mb-3">
                {{ t('customer_portal.dashboard.section_recent') }}
            </h2>
            <div
                v-if="recentWorkOrders.length === 0"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-center text-sm text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800"
            >
                {{ t('customer_portal.dashboard.no_recent') }}
            </div>
            <div v-else class="space-y-2">
                <article
                    v-for="w in recentWorkOrders"
                    :key="w.id"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{
                                    t('customer_portal.dashboard.work_order_code', { code: w.code })
                                }}
                            </div>
                            <div
                                v-if="w.expected_end_date"
                                class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                            >
                                {{
                                    t('customer_portal.dashboard.expected_on', {
                                        date: formatDateOnly(w.expected_end_date),
                                    })
                                }}
                            </div>
                        </div>
                        <StatusBadge :status="w.status" :label="workOrderStatusLabel(w.status)" />
                    </div>
                </article>
            </div>
        </section>
    </CustomerPortalLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import PrimaryCard from '@/Components/CustomerPortal/PrimaryCard.vue';
import StatusBadge from '@/Components/CustomerPortal/StatusBadge.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    customer: { type: Object, required: true },
    vehicles: { type: Array, default: () => [] },
    upcomingAppointments: { type: Array, default: () => [] },
    recentWorkOrders: { type: Array, default: () => [] },
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

// Booking is only offered to customers with at least
// one vehicle — the booking form rejects requests
// without a vehicle and there's no self-service way
// to add one in the portal. Hiding the CTA on empty
// states keeps the UI honest.
const canBook = computed(() => (props.vehicles?.length ?? 0) > 0);
const bookUrl = computed(() => `/${props.tenant.slug}/portal/appointments/new`);

// Date / time formatters in the active locale.
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

function formatDateOnly(iso) {
    if (!iso) {
        return '';
    }
    try {
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

// Friendly label for an internal appointment status.
// The customer-facing dictionary lives in
// `customer_portal.appointment_statuses` so the
// dashboard never leaks raw keys like "checked_in".
function appointmentStatusLabel(status) {
    return t('customer_portal.appointment_statuses.' + (status || 'scheduled'));
}

// Same idea for work order statuses.
function workOrderStatusLabel(status) {
    return t('customer_portal.work_order_statuses.' + (status || 'open'));
}

// The PRIMARY CARD is the single most important
// block on the page. We pick its shape here so the
// template can render exactly one of three
// variants. The shape decision is local to the
// dashboard — the PrimaryCard component does not
// decide between appointment vs work order.
const primaryCard = computed(() => {
    const upcoming = props.upcomingAppointments?.[0];
    if (upcoming) {
        const start = upcoming.scheduled_at ? new Date(upcoming.scheduled_at) : null;
        const now = new Date();
        const isToday = start && sameDay(start, now);
        const isTomorrow =
            start && sameDay(start, new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1));

        const titleKey = isToday
            ? 'primary_title_appointment_today'
            : isTomorrow
              ? 'primary_title_appointment_tomorrow'
              : 'primary_title_appointment';

        return {
            kind: 'appointment',
            title: t('customer_portal.dashboard.' + titleKey),
            when: formatDate(upcoming.scheduled_at),
            branch: upcoming.center_name || t('customer_portal.booking.review_branch'),
            viewHref: `/${props.tenant.slug}/portal/appointment/${upcoming.id}`,
            editHref: `/${props.tenant.slug}/portal/appointment/${upcoming.id}/edit`,
        };
    }

    const activeWo = (props.recentWorkOrders || []).find(
        (w) => w.status && !['done', 'cancelled'].includes(w.status)
    );
    if (activeWo) {
        return {
            kind: 'work_order',
            statusLabel: workOrderStatusLabel(activeWo.status),
            progress: workOrderProgress(activeWo.status),
        };
    }

    return { kind: 'empty' };
});

// The customer can manage (reschedule / cancel) an
// upcoming appointment only if it is at least 2 hours
// away and the appointment is in scheduled/confirmed
// status. We mirror the server's rule on the client so
// the button is honest. The server re-checks before
// persisting.
const MIN_HOURS_BEFORE = 2;
const canManageAppointment = computed(() => {
    const a = props.upcomingAppointments?.[0];
    if (!a?.scheduled_at) {
        return false;
    }
    const start = new Date(a.scheduled_at);
    const earliest = new Date();
    earliest.setHours(earliest.getHours() + MIN_HOURS_BEFORE);
    if (start < earliest) {
        return false;
    }
    return a.status === 'scheduled' || a.status === 'confirmed';
});

function sameDay(a, b) {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

// Progress for the active work order. We expose a
// 0..100 estimate so the progress bar is meaningful
// without leaking the workflow internals. The
// mapping is conservative — the customer should not
// see a 100% bar on something that is still in
// progress.
function workOrderProgress(status) {
    switch (status) {
        case 'draft':
            return 10;
        case 'open':
            return 25;
        case 'in_progress':
            return 55;
        case 'ready_for_qc':
            return 80;
        case 'on_hold':
            return 55;
        case 'done':
            return 100;
        case 'cancelled':
            return 0;
        default:
            return 30;
    }
}
</script>
