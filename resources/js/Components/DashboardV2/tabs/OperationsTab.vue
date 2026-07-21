<script setup>
import { router } from '@inertiajs/vue3';
import KPICard from '@/Components/DashboardV2/common/KPICard.vue';
import ChartCard from '@/Components/DashboardV2/common/ChartCard.vue';
import StatusBreakdown from '@/Components/DashboardV2/widgets/StatusBreakdown.vue';
import WeeklyBarChart from '@/Components/DashboardV2/widgets/WeeklyBarChart.vue';
import RecentWorkOrders from '@/Components/DashboardV2/widgets/RecentWorkOrders.vue';
import TodaySchedule from '@/Components/DashboardV2/widgets/TodaySchedule.vue';
import TopServices from '@/Components/DashboardV2/widgets/TopServices.vue';
import TechnicianPerformance from '@/Components/DashboardV2/widgets/TechnicianPerformance.vue';

defineProps({
    stats: { type: Object, required: true },
    charts: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
    todaySchedule: { type: Array, default: () => [] },
    todayNewWorkOrders: { type: Array, default: () => [] },
    topServices: { type: Array, default: () => [] },
    technicianPerformance: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
});

function navigate(url) {
    router.get(url);
}

const ICONS = {
    workOrders:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    active: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
    completed:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    vehicles:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 5h2m4 0h4M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
};
</script>

<template>
    <div class="space-y-5">
        <!-- Operational KPIs -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <KPICard
                :label="$t('dashboard.work_orders_this_month')"
                :value="stats.workOrders?.thisMonth ?? 0"
                :previousValue="stats.workOrders?.lastMonth ?? null"
                :icon="ICONS.workOrders"
                icon-color="indigo"
                href="/app/work-orders"
            />
            <KPICard
                :label="$t('dashboard.active_work_orders')"
                :value="stats.workOrders?.active ?? 0"
                :icon="ICONS.active"
                icon-color="sky"
                :subtitle="$t('dashboard.open_or_in_progress')"
                href="/app/work-orders"
            />
            <KPICard
                :label="$t('dashboard.completed_today')"
                :value="stats.workOrders?.completedToday ?? 0"
                :icon="ICONS.completed"
                icon-color="emerald"
                :subtitle="$t('dashboard.closed_today')"
                href="/app/work-orders"
            />
            <KPICard
                :label="$t('dashboard.total_vehicles')"
                :value="stats.vehicles?.total ?? 0"
                :icon="ICONS.vehicles"
                icon-color="violet"
                :subtitle="$t('dashboard.in_database')"
                href="/app/vehicles"
            />
        </div>

        <!-- Status + Weekly -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <ChartCard
                :title="$t('dashboard.work_orders_status')"
                :subtitle="$t('dashboard.all_time')"
            >
                <StatusBreakdown
                    :data="charts.workOrdersByStatusAll ?? charts.workOrdersByStatus ?? {}"
                />
            </ChartCard>
            <div class="lg:col-span-2">
                <ChartCard
                    :title="$t('dashboard.weekly_work_orders')"
                    :subtitle="$t('dashboard.last_7_days')"
                >
                    <WeeklyBarChart :data="charts.weeklyWorkOrders ?? []" />
                </ChartCard>
            </div>
        </div>

        <!-- Today schedule + Recent WO -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartCard
                :title="$t('dashboard.today_schedule')"
                :subtitle="$t('dashboard.expected_today')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/work-orders')"
            >
                <TodaySchedule :items="todaySchedule" />
            </ChartCard>
            <ChartCard
                :title="$t('dashboard.recent_work_orders')"
                :subtitle="$t('dashboard.latest_8')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/work-orders')"
            >
                <RecentWorkOrders :items="recentWorkOrders" :currency="currency" :limit="6" />
            </ChartCard>
        </div>

        <!-- Top services + Technicians -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartCard
                :title="$t('dashboard.top_services')"
                :subtitle="$t('dashboard.last_30_days')"
            >
                <TopServices :services="topServices" :currency="currency" />
            </ChartCard>
            <ChartCard
                :title="$t('dashboard.technician_performance')"
                :subtitle="$t('dashboard.last_30_days')"
            >
                <TechnicianPerformance :technicians="technicianPerformance" />
            </ChartCard>
        </div>
    </div>
</template>
