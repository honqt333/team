<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/Dashboard/StatsCard.vue';
import RevenueChart from '@/Components/Dashboard/RevenueChart.vue';
import WorkOrdersStatusChart from '@/Components/Dashboard/WorkOrdersStatusChart.vue';
import WeeklyWorkOrdersChart from '@/Components/Dashboard/WeeklyWorkOrdersChart.vue';
import RecentWorkOrders from '@/Components/Dashboard/RecentWorkOrders.vue';
import RecentInvoices from '@/Components/Dashboard/RecentInvoices.vue';
import AlertsWidget from '@/Components/Dashboard/AlertsWidget.vue';
import QuickActions from '@/Components/Dashboard/QuickActions.vue';

const { t } = useI18n();

const props = defineProps({
    stats:             { type: Object, default: () => ({}) },
    charts:            { type: Object, default: () => ({}) },
    alerts:            { type: Object, default: () => ({}) },
    recentWorkOrders:  { type: Array,  default: () => [] },
    recentInvoices:    { type: Array,  default: () => [] },
    currency:          { type: String, default: 'SAR' },
});

// Icons
const icons = {
    workOrders: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>`,
    active:     `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`,
    revenue:    `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    outstanding:`<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    customers:  `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    vehicles:   `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>`,
};

const userName = computed(() => usePage().props.auth?.user?.name?.split(' ')[0] || '');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return t('dashboard.greeting_morning');
    if (h < 17) return t('dashboard.greeting_afternoon');
    return t('dashboard.greeting_evening');
});
</script>

<template>
    <Head :title="$t('dashboard.title')" />
    <AppLayout>
        <div class="space-y-6 px-1">

            <!-- Greeting Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ greeting }}،
                        <span class="text-indigo-600 dark:text-indigo-400">{{ userName }}</span>
                        👋
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('dashboard.subtitle') }}
                    </p>
                </div>
                <div class="text-sm text-gray-400 dark:text-gray-500 font-mono bg-white dark:bg-gray-800 px-3 py-1.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    {{ new Date().toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatsCard
                    :label="$t('dashboard.work_orders_this_month')"
                    :value="stats.workOrders?.thisMonth ?? 0"
                    :previousValue="stats.workOrders?.lastMonth ?? null"
                    :icon="icons.workOrders"
                    color="indigo"
                    :subtitle="$t('dashboard.active_count', { n: stats.workOrders?.active ?? 0 })"
                    link="/app/work-orders"
                />
                <StatsCard
                    :label="$t('dashboard.revenue_this_month')"
                    :value="stats.revenue?.thisMonth ?? 0"
                    :previousValue="stats.revenue?.lastMonth ?? null"
                    :icon="icons.revenue"
                    color="emerald"
                    format="currency"
                    :suffix="currency"
                    link="/app/invoices"
                />
                <StatsCard
                    :label="$t('dashboard.outstanding_balance')"
                    :value="stats.revenue?.outstanding ?? 0"
                    :icon="icons.outstanding"
                    color="amber"
                    format="currency"
                    :suffix="currency"
                    :subtitle="$t('dashboard.payments_today', { n: stats.revenue?.paymentsToday ?? 0 })"
                />
                <StatsCard
                    :label="$t('dashboard.total_customers')"
                    :value="stats.customers?.total ?? 0"
                    :previousValue="stats.customers?.lastMonth ?? null"
                    :icon="icons.customers"
                    color="violet"
                    :subtitle="$t('dashboard.new_this_month', { n: stats.customers?.thisMonth ?? 0 })"
                    link="/app/customers"
                />
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Revenue chart (takes 2/3) -->
                <div class="lg:col-span-2">
                    <RevenueChart
                        :data="charts.revenueDaily ?? []"
                        :currency="currency"
                    />
                </div>
                <!-- Status donut (takes 1/3) -->
                <div>
                    <WorkOrdersStatusChart :data="charts.workOrdersByStatus ?? {}" />
                </div>
            </div>

            <!-- Weekly + Alerts + Quick Actions Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <WeeklyWorkOrdersChart :data="charts.weeklyWorkOrders ?? []" />
                </div>
                <div>
                    <AlertsWidget
                        :overdueWorkOrders="alerts.overdueWorkOrders ?? 0"
                        :overdueInvoices="alerts.overdueInvoices ?? 0"
                        :lowStock="alerts.lowStock ?? 0"
                    />
                </div>
                <div>
                    <QuickActions />
                </div>
            </div>

            <!-- Recent Work Orders & Invoices -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <RecentWorkOrders :workOrders="recentWorkOrders" />
                <RecentInvoices :invoices="recentInvoices" :currency="currency" />
            </div>

        </div>
    </AppLayout>
</template>
