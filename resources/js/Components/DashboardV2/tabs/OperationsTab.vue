<script setup>
import { Link, router } from '@inertiajs/vue3';
import KPICard from '@/Components/DashboardV2/common/KPICard.vue';
import ChartCard from '@/Components/DashboardV2/common/ChartCard.vue';
import StatusBreakdown from '@/Components/DashboardV2/widgets/StatusBreakdown.vue';
import WeeklyBarChart from '@/Components/DashboardV2/widgets/WeeklyBarChart.vue';
import RecentWorkOrders from '@/Components/DashboardV2/widgets/RecentWorkOrders.vue';
import TodaySchedule from '@/Components/DashboardV2/widgets/TodaySchedule.vue';
import TopServices from '@/Components/DashboardV2/widgets/TopServices.vue';
import TechnicianPerformance from '@/Components/DashboardV2/widgets/TechnicianPerformance.vue';
import CustomerContactActions from '@/Components/DashboardV2/common/CustomerContactActions.vue';

defineProps({
    userContext: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    charts: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
    todaySchedule: { type: Array, default: () => [] },
    todayNewWorkOrders: { type: Array, default: () => [] },
    topServices: { type: Array, default: () => [] },
    technicianPerformance: { type: Array, default: () => [] },
    activeWorkshopVehicles: { type: Array, default: () => [] },
    workingTechniciansNow: { type: Array, default: () => [] },
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

        <!-- Workshop Intelligence KPIs -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <KPICard
                :label="$t('dashboard.bays_utilization')"
                :value="
                    stats.workshop?.baysTotal
                        ? Math.round(
                              ((stats.workshop?.baysBookedToday ?? 0) / stats.workshop.baysTotal) *
                                  100
                          )
                        : 0
                "
                suffix="%"
                :icon="ICONS.vehicles"
                icon-color="indigo"
                :subtitle="
                    stats.workshop?.baysTotal
                        ? $t('dashboard.bays_booked_today', {
                              booked: stats.workshop?.baysBookedToday ?? 0,
                              total: stats.workshop?.baysTotal ?? 0,
                          })
                        : $t('dashboard.no_bays_configured')
                "
                href="/app/workshop"
            />
            <KPICard
                :label="$t('dashboard.on_time_delivery')"
                :value="stats.workshop?.onTimeRate ?? 0"
                suffix="%"
                :icon="ICONS.completed"
                icon-color="emerald"
                href="/app/work-orders"
            />
            <KPICard
                :label="$t('dashboard.avg_cycle_time')"
                :value="stats.workshop?.avgCycleHours ?? 0"
                :suffix="` ${$t('dashboard.hours_unit')}`"
                :icon="ICONS.active"
                icon-color="amber"
                href="/app/work-orders"
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
                <RecentWorkOrders
                    :items="recentWorkOrders"
                    :currency="currency"
                    :limit="6"
                    :show-price="userContext?.canViewFinancials"
                />
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

        <!-- Live Workshop Floor: Active Vehicles & Working Technicians Now -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartCard
                :title="$t('dashboard.active_workshop_vehicles')"
                :subtitle="$t('dashboard.active_workshop_vehicles_subtitle')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/workshop')"
            >
                <div
                    v-if="activeWorkshopVehicles?.length"
                    class="divide-y divide-slate-100 dark:divide-slate-800"
                >
                    <div
                        v-for="item in activeWorkshopVehicles"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="`/app/work-orders/${item.id}`"
                                    class="font-mono text-xs font-bold text-action-primary hover:underline"
                                >
                                    {{ item.code }}
                                </Link>
                                <span
                                    class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200"
                                >
                                    {{ item.vehicle_label || item.plate_number }}
                                </span>
                                <span
                                    class="rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-slate-600 dark:text-slate-300"
                                >
                                    {{ item.plate_number }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ item.customer_name }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <div
                                    class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                >
                                    <div
                                        class="h-full rounded-full bg-emerald-500 transition-all"
                                        :style="{ width: `${item.progress_percent}%` }"
                                    ></div>
                                </div>
                                <span
                                    class="font-mono text-[11px] font-bold text-slate-600 dark:text-slate-300"
                                >
                                    {{ item.progress_percent }}%
                                </span>
                            </div>
                        </div>

                        <CustomerContactActions
                            :phone="item.customer_phone"
                            :whatsapp="item.whatsapp_phone"
                            :work-order-code="item.code"
                            :plate-number="item.plate_number"
                            :compact="false"
                        />
                    </div>
                </div>
                <div v-else class="py-8 text-center text-xs text-slate-400">
                    {{ $t('dashboard.no_active_vehicles') }}
                </div>
            </ChartCard>

            <ChartCard
                :title="$t('dashboard.working_technicians_now')"
                :subtitle="$t('dashboard.working_technicians_now_subtitle')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/workshop')"
            >
                <div
                    v-if="workingTechniciansNow?.length"
                    class="divide-y divide-slate-100 dark:divide-slate-800"
                >
                    <div
                        v-for="(tech, idx) in workingTechniciansNow"
                        :key="`${tech.user_id}-${tech.work_order_id}-${idx}`"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-bold text-slate-800 dark:text-slate-200"
                            >
                                {{ tech.technician_name }}
                            </p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ tech.service_title }}
                            </p>
                        </div>
                        <div class="text-end shrink-0">
                            <Link
                                :href="`/app/work-orders/${tech.work_order_id}`"
                                class="font-mono text-xs font-bold text-action-primary hover:underline block"
                            >
                                {{ tech.work_order_code }}
                            </Link>
                            <span class="font-mono text-[11px] text-slate-400">
                                {{ tech.plate_number }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-xs text-slate-400">
                    {{ $t('dashboard.no_working_technicians') }}
                </div>
            </ChartCard>
        </div>
    </div>
</template>
