<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.portal_appointments.title')"
        :subtitle="t('customer_portal.portal_appointments.subtitle')"
        show-logout
        show-bottom-nav
        current-route="public.portal.appointments.index"
        :language-base-path="`/${tenant.slug}/portal/appointments`"
    >
        <!-- Flash (e.g. after cancel / reschedule redirects) -->
        <div
            v-if="flashSuccess"
            class="mb-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 p-4 text-sm font-bold text-emerald-800 dark:text-emerald-200"
        >
            {{ flashSuccess }}
        </div>

        <!-- Toolbar first, primary CTA at the outer edge (constitution §3.2) -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" size="sm" />
            <a
                :href="newBookingUrl"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-xs font-extrabold shadow-sm transition hover:opacity-90"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                {{ t('customer_portal.portal_appointments.book_new_cta') }}
            </a>
        </div>

        <PortalEmptyState
            v-if="currentList.length === 0"
            :message="
                activeTab === 'upcoming'
                    ? t('customer_portal.portal_appointments.empty_upcoming')
                    : t('customer_portal.portal_appointments.empty_past')
            "
            :hint="
                activeTab === 'upcoming'
                    ? t('customer_portal.portal_appointments.empty_upcoming_hint')
                    : ''
            "
        >
            <a
                v-if="activeTab === 'upcoming'"
                :href="newBookingUrl"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-xs font-extrabold shadow-sm"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                {{ t('customer_portal.portal_appointments.book_new_cta') }}
            </a>
        </PortalEmptyState>

        <div v-else class="space-y-4">
            <article
                v-for="appt in currentList"
                :key="appt.id"
                class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden"
            >
                <div class="flex items-stretch">
                    <!-- Date tile -->
                    <div
                        class="w-20 shrink-0 flex flex-col items-center justify-center text-white py-4"
                        :style="{
                            backgroundColor:
                                activeTab === 'upcoming'
                                    ? 'var(--tenant-primary, #2563eb)'
                                    : '#94a3b8',
                        }"
                    >
                        <span class="text-2xl font-extrabold leading-none" dir="ltr">
                            {{ dayOf(appt.scheduled_at) }}
                        </span>
                        <span class="text-[11px] font-bold mt-1 opacity-90">
                            {{ monthOf(appt.scheduled_at) }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0 p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-sm font-extrabold text-slate-900 dark:text-white"
                                    >
                                        {{ formatDateTime(appt.scheduled_at) }}
                                    </span>
                                    <StatusBadge
                                        :status="appt.status"
                                        :label="
                                            t(`customer_portal.appointment_statuses.${appt.status}`)
                                        "
                                        show-dot
                                    />
                                </div>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ appt.center_name }}
                                </p>
                                <p v-if="appt.service_bay_name" class="text-xs text-slate-500">
                                    {{
                                        t('customer_portal.portal_appointments.bay_label', {
                                            name: appt.service_bay_name,
                                        })
                                    }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ appt.vehicle_label }}
                                </p>
                            </div>
                            <SaudiPlateDisplay
                                v-if="appt.vehicle_plate"
                                :plate-number="appt.vehicle_plate"
                                size="xs"
                            />
                        </div>

                        <div v-if="appt.services?.length" class="mt-3 flex flex-wrap gap-1.5">
                            <span
                                v-for="s in appt.services"
                                :key="s.id"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-semibold text-slate-700 dark:text-slate-300"
                            >
                                {{ s.name }}
                            </span>
                        </div>

                        <p
                            v-if="appt.notes"
                            class="mt-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-xs text-slate-600 dark:text-slate-300 whitespace-pre-line"
                        >
                            {{ appt.notes }}
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <a
                                :href="`/${tenant.slug}/portal/appointment/${appt.id}`"
                                class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                            >
                                {{ t('customer_portal.portal_appointments.view_details') }}
                            </a>
                            <a
                                v-if="appt.can_manage"
                                :href="`/${tenant.slug}/portal/appointment/${appt.id}/edit`"
                                class="inline-flex items-center px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                            >
                                {{ t('customer_portal.portal_appointments.reschedule_cta') }}
                            </a>
                            <a
                                v-if="appt.work_order_id"
                                :href="`/${tenant.slug}/portal/work-order/${appt.work_order_id}`"
                                class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold text-white transition hover:opacity-90"
                                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
                            >
                                {{ t('customer_portal.portal_appointments.view_work_order') }}
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import StatusBadge from '@/Components/CustomerPortal/StatusBadge.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import PortalEmptyState from '@/Components/CustomerPortal/PortalEmptyState.vue';
import { usePortalFormat } from '@/Composables/usePortalFormat';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    upcomingAppointments: { type: Array, default: () => [] },
    pastAppointments: { type: Array, default: () => [] },
});

const { t, isRtl, formatDateTime } = usePortalFormat(props);
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const newBookingUrl = computed(() => `/${props.tenant.slug}/portal/appointments/new`);

const activeTab = ref('upcoming');
const tabs = computed(() => [
    {
        id: 'upcoming',
        label: t('customer_portal.portal_appointments.upcoming_tab'),
        count: props.upcomingAppointments.length,
    },
    {
        id: 'past',
        label: t('customer_portal.portal_appointments.past_tab'),
        count: props.pastAppointments.length,
    },
]);
const currentList = computed(() =>
    activeTab.value === 'upcoming' ? props.upcomingAppointments : props.pastAppointments
);

function dayOf(iso) {
    return iso ? new Date(iso).getDate() : '—';
}
function monthOf(iso) {
    if (!iso) return '';
    try {
        return new Intl.DateTimeFormat(isRtl.value ? 'ar-SA-u-nu-latn' : 'en-US', {
            month: 'short',
        }).format(new Date(iso));
    } catch (e) {
        return '';
    }
}
</script>
