<script setup>
import { router } from '@inertiajs/vue3';
import KPICard from '@/Components/DashboardV2/common/KPICard.vue';
import ChartCard from '@/Components/DashboardV2/common/ChartCard.vue';
import RevenueBarChart from '@/Components/DashboardV2/widgets/RevenueBarChart.vue';
import StatusBreakdown from '@/Components/DashboardV2/widgets/StatusBreakdown.vue';
import WeeklyBarChart from '@/Components/DashboardV2/widgets/WeeklyBarChart.vue';
import RecentWorkOrders from '@/Components/DashboardV2/widgets/RecentWorkOrders.vue';
import RecentInvoices from '@/Components/DashboardV2/widgets/RecentInvoices.vue';
import TodaySchedule from '@/Components/DashboardV2/widgets/TodaySchedule.vue';
import QuickActions from '@/Components/DashboardV2/widgets/QuickActions.vue';
import TopServices from '@/Components/DashboardV2/widgets/TopServices.vue';

defineProps({
    stats: { type: Object, required: true },
    charts: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
    recentInvoices: { type: Array, default: () => [] },
    todaySchedule: { type: Array, default: () => [] },
    topServices: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
});

function navigate(url) {
    router.get(url);
}

const ICONS = {
    workOrders:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    revenue:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    outstanding:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    customers:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
};
</script>

<template>
    <div class="space-y-5">
        <!-- KPI Row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <KPICard
                :label="$t('dashboard.work_orders_this_month')"
                :value="stats.workOrders?.thisMonth ?? 0"
                :previousValue="stats.workOrders?.lastMonth ?? null"
                :icon="ICONS.workOrders"
                icon-color="indigo"
                :subtitle="$t('dashboard.active_count', { n: stats.workOrders?.active ?? 0 })"
                href="/app/work-orders"
            />
            <KPICard
                :label="$t('dashboard.revenue_this_month')"
                :value="stats.revenue?.thisMonth ?? 0"
                :previousValue="stats.revenue?.lastMonth ?? null"
                :icon="ICONS.revenue"
                icon-color="emerald"
                format="currency"
                :suffix="currency"
                href="/app/invoices"
            />
            <KPICard
                :label="$t('dashboard.outstanding_balance')"
                :value="stats.revenue?.outstanding ?? 0"
                :icon="ICONS.outstanding"
                icon-color="amber"
                format="currency"
                :suffix="currency"
                :subtitle="
                    $t('dashboard.payments_today', {
                        n: new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(
                            stats.revenue?.paymentsToday ?? 0
                        ),
                    })
                "
                href="/app/invoices"
            />
            <KPICard
                :label="$t('dashboard.total_customers')"
                :value="stats.customers?.total ?? 0"
                :previousValue="stats.customers?.lastMonth ?? null"
                :icon="ICONS.customers"
                icon-color="violet"
                :subtitle="$t('dashboard.new_this_month', { n: stats.customers?.thisMonth ?? 0 })"
                href="/app/customers"
            />
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <ChartCard
                    :title="$t('dashboard.revenue_chart_title')"
                    :subtitle="$t('dashboard.last_30_days')"
                >
                    <RevenueBarChart :data="charts.revenueDaily ?? []" :currency="currency" />
                </ChartCard>
            </div>
            <ChartCard
                :title="$t('dashboard.work_orders_status')"
                :subtitle="$t('dashboard.this_month')"
            >
                <StatusBreakdown :data="charts.workOrdersByStatus ?? {}" />
            </ChartCard>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <ChartCard
                :title="$t('dashboard.weekly_work_orders')"
                :subtitle="$t('dashboard.last_7_days')"
            >
                <WeeklyBarChart :data="charts.weeklyWorkOrders ?? []" />
            </ChartCard>
            <div class="lg:col-span-2">
                <ChartCard
                    :title="$t('dashboard.top_services')"
                    :subtitle="$t('dashboard.last_30_days')"
                >
                    <TopServices :services="topServices" :currency="currency" />
                </ChartCard>
            </div>
        </div>

        <!-- Recent activity + Today schedule + Quick actions -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <ChartCard :title="$t('dashboard.today_schedule')" :subtitle="$t('dashboard.today')">
                <TodaySchedule :items="todaySchedule" />
            </ChartCard>
            <ChartCard
                :title="$t('dashboard.recent_work_orders')"
                :subtitle="$t('dashboard.latest_8')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/work-orders')"
            >
                <RecentWorkOrders :items="recentWorkOrders" :currency="currency" />
            </ChartCard>
            <ChartCard
                :title="$t('dashboard.recent_invoices')"
                :subtitle="$t('dashboard.latest_8')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/invoices')"
            >
                <RecentInvoices :items="recentInvoices" :currency="currency" />
            </ChartCard>
        </div>

        <!-- Quick actions -->
        <ChartCard
            :title="$t('dashboard.quick_actions')"
            :subtitle="$t('dashboard.quick_actions_subtitle')"
        >
            <QuickActions />
        </ChartCard>
    </div>
</template>
