<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/DashboardV2/common/PageHeader.vue';
import DashboardTabs from '@/Components/DashboardV2/common/DashboardTabs.vue';
import OverviewTab from '@/Components/DashboardV2/tabs/OverviewTab.vue';
import FinancialTab from '@/Components/DashboardV2/tabs/FinancialTab.vue';
import OperationsTab from '@/Components/DashboardV2/tabs/OperationsTab.vue';
import AlertsTab from '@/Components/DashboardV2/tabs/AlertsTab.vue';

const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    alerts: { type: Object, default: () => ({}) },
    recentWorkOrders: { type: Array, default: () => [] },
    recentInvoices: { type: Array, default: () => [] },
    todaySchedule: { type: Array, default: () => [] },
    todayNewWorkOrders: { type: Array, default: () => [] },
    topServices: { type: Array, default: () => [] },
    technicianPerformance: { type: Array, default: () => [] },
    topCustomers: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
});

const activeTab = ref('overview');

const tabs = computed(() => [
    {
        id: 'overview',
        label: t('dashboard.tab_overview'),
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>',
        badge: null,
    },
    {
        id: 'financial',
        label: t('dashboard.tab_financial'),
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        badge: null,
    },
    {
        id: 'operations',
        label: t('dashboard.tab_operations'),
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
        badge: props.stats?.workOrders?.active || null,
    },
    {
        id: 'alerts',
        label: t('dashboard.tab_alerts'),
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
        badge:
            (props.alerts?.overdueWorkOrders ?? 0) +
                (props.alerts?.overdueInvoices ?? 0) +
                (props.alerts?.lowStock ?? 0) || null,
    },
]);

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name?.split(' ')[0] || '');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return t('dashboard.greeting_morning');
    if (h < 17) return t('dashboard.greeting_afternoon');
    return t('dashboard.greeting_evening');
});

const today = computed(() => {
    return new Date().toLocaleDateString(page.props.locale || 'en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

function refresh() {
    router.reload({
        only: [
            'stats',
            'charts',
            'alerts',
            'recentWorkOrders',
            'recentInvoices',
            'todaySchedule',
            'topServices',
            'technicianPerformance',
            'topCustomers',
        ],
    });
}
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AppLayout>
        <div class="flex flex-col">
            <PageHeader
                :title="$t('dashboard.title')"
                :subtitle="$t('dashboard.subtitle')"
                :user-name="userName"
                :date="today"
                :greeting="greeting"
            />

            <DashboardTabs v-model="activeTab" :tabs="tabs" />

            <div class="flex-1 bg-slate-50 px-4 py-5 sm:px-6 dark:bg-slate-900">
                <!-- Refresh button -->
                <div class="mb-4 flex items-center justify-end">
                    <button
                        @click="refresh"
                        class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
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
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        {{ $t('dashboard.refresh') }}
                    </button>
                </div>

                <Transition
                    enter-active-class="transition-opacity duration-150"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    mode="out-in"
                >
                    <OverviewTab
                        v-if="activeTab === 'overview'"
                        :stats="stats"
                        :charts="charts"
                        :recent-work-orders="recentWorkOrders"
                        :recent-invoices="recentInvoices"
                        :today-schedule="todaySchedule"
                        :top-services="topServices"
                        :currency="currency"
                    />
                    <FinancialTab
                        v-else-if="activeTab === 'financial'"
                        :stats="stats"
                        :charts="charts"
                        :recent-invoices="recentInvoices"
                        :top-customers="topCustomers"
                        :currency="currency"
                    />
                    <OperationsTab
                        v-else-if="activeTab === 'operations'"
                        :stats="stats"
                        :charts="charts"
                        :recent-work-orders="recentWorkOrders"
                        :today-schedule="todaySchedule"
                        :top-services="topServices"
                        :technician-performance="technicianPerformance"
                        :currency="currency"
                    />
                    <AlertsTab
                        v-else
                        :alerts="alerts"
                        :recent-invoices="recentInvoices"
                        :currency="currency"
                    />
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>
