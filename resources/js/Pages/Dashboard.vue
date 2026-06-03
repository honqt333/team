<script setup>
import { computed, ref } from 'vue';
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
import DashboardCustomizer from '@/Components/Dashboard/DashboardCustomizer.vue';

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
    revenue:    `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    outstanding:`<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    customers:  `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
};

const userName = computed(() => usePage().props.auth?.user?.name?.split(' ')[0] || '');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return t('dashboard.greeting_morning');
    if (h < 17) return t('dashboard.greeting_afternoon');
    return t('dashboard.greeting_evening');
});

const now = computed(() =>
    new Date().toLocaleDateString('en-GB', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    })
);

// ── Customizer ──────────────────────────────────────────────
const customizerOpen = ref(false);

const defaultWidgets = () => [
    {
        id: 'revenue_chart',
        label: t('dashboard.revenue_chart_title'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
    },
    {
        id: 'status_chart',
        label: t('dashboard.work_orders_status'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>`,
    },
    {
        id: 'weekly_chart',
        label: t('dashboard.weekly_work_orders'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
    },
    {
        id: 'alerts',
        label: t('dashboard.alerts_title'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>`,
    },
    {
        id: 'quick_actions',
        label: t('dashboard.quick_actions'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`,
    },
    {
        id: 'recent_work_orders',
        label: t('dashboard.recent_work_orders'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>`,
    },
    {
        id: 'recent_invoices',
        label: t('dashboard.recent_invoices'),
        visible: true,
        icon: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
    },
];

const STORAGE_KEY = 'dashboard_widgets_v1';
function loadWidgets() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            // Merge saved order/visibility with fresh defaults (in case new widgets added)
            const defaults = defaultWidgets();
            return defaults.map(d => {
                const found = parsed.find(p => p.id === d.id);
                return found ? { ...d, visible: found.visible } : d;
            }).sort((a, b) => {
                const ai = parsed.findIndex(p => p.id === a.id);
                const bi = parsed.findIndex(p => p.id === b.id);
                return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
            });
        }
    } catch {}
    return defaultWidgets();
}

const widgets = ref(loadWidgets());

function isVisible(id) {
    return widgets.value.find(w => w.id === id)?.visible ?? true;
}

function onSaveWidgets(updated) {
    widgets.value = updated;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(updated.map(w => ({ id: w.id, visible: w.visible }))));
}

function onResetDefaults() {
    widgets.value = defaultWidgets();
    localStorage.removeItem(STORAGE_KEY);
    customizerOpen.value = false;
}
</script>

<template>
    <Head :title="$t('dashboard.title')" />
    <AppLayout>
        <div class="space-y-5">

            <!-- ══════════════════════════════════════
                 HEADER — Premium Gradient Banner
            ══════════════════════════════════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 shadow-xl shadow-indigo-500/20">

                <!-- Decorative blobs -->
                <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/5 rounded-full blur-2xl pointer-events-none" />
                <div class="absolute -bottom-8 -left-8 w-40 h-40 bg-violet-400/10 rounded-full blur-2xl pointer-events-none" />
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-24 bg-white/3 rounded-full blur-3xl pointer-events-none" />

                <!-- Grid pattern overlay -->
                <div class="absolute inset-0 opacity-5 pointer-events-none"
                     style="background-image: linear-gradient(rgba(255,255,255,.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.3) 1px, transparent 1px); background-size: 32px 32px;" />

                <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-6 py-5">
                    <!-- Left: Greeting -->
                    <div class="flex items-center gap-4">
                        <!-- Avatar ring -->
                        <div class="hidden sm:flex w-12 h-12 rounded-2xl bg-white/15 ring-2 ring-white/30 items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-indigo-200 text-sm font-medium">{{ greeting }}</p>
                            <h1 class="text-white text-xl font-bold tracking-tight">
                                {{ userName }} 👋
                            </h1>
                            <p class="text-indigo-200/70 text-xs mt-0.5">{{ $t('dashboard.subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Right: Date + Customize button -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Date badge -->
                        <div class="hidden sm:flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white/80 text-xs font-medium px-3 py-2 rounded-xl border border-white/20">
                            <svg class="w-3.5 h-3.5 text-indigo-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ now }}
                        </div>

                        <!-- Customize button -->
                        <button
                            @click="customizerOpen = true"
                            class="flex items-center gap-2 bg-white/15 hover:bg-white/25 active:bg-white/30 backdrop-blur-sm text-white text-sm font-semibold px-4 py-2 rounded-xl border border-white/25 transition-all duration-200 shadow-sm hover:shadow-md group"
                        >
                            <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $t('dashboard.customize') }}
                        </button>
                    </div>
                </div>

                <!-- Quick stats strip inside header -->
                <div class="relative grid grid-cols-2 sm:grid-cols-4 gap-px bg-white/10 border-t border-white/10">
                    <div class="bg-indigo-700/40 px-5 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white text-base font-bold leading-none">{{ stats.workOrders?.active ?? 0 }}</p>
                            <p class="text-indigo-200/80 text-xs mt-0.5">{{ $t('dashboard.work_orders_this_month') }}</p>
                        </div>
                    </div>
                    <div class="bg-indigo-700/40 px-5 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white text-base font-bold leading-none">
                                {{ new Intl.NumberFormat('en-SA', { maximumFractionDigits: 0 }).format(stats.revenue?.thisMonth ?? 0) }}
                            </p>
                            <p class="text-indigo-200/80 text-xs mt-0.5">{{ $t('dashboard.revenue_this_month') }} ({{ currency }})</p>
                        </div>
                    </div>
                    <div class="bg-indigo-700/40 px-5 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white text-base font-bold leading-none">{{ stats.customers?.total ?? 0 }}</p>
                            <p class="text-indigo-200/80 text-xs mt-0.5">{{ $t('dashboard.total_customers') }}</p>
                        </div>
                    </div>
                    <div class="bg-indigo-700/40 px-5 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-400/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white text-base font-bold leading-none">
                                {{ new Intl.NumberFormat('en-SA', { maximumFractionDigits: 0 }).format(stats.revenue?.outstanding ?? 0) }}
                            </p>
                            <p class="text-indigo-200/80 text-xs mt-0.5">{{ $t('dashboard.outstanding_balance') }} ({{ currency }})</p>
                        </div>
                    </div>
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
                    :subtitle="$t('dashboard.payments_today', { n: new Intl.NumberFormat('en-SA', { maximumFractionDigits: 0 }).format(stats.revenue?.paymentsToday ?? 0) })"
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
            <div v-if="isVisible('revenue_chart') || isVisible('status_chart')" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div v-if="isVisible('revenue_chart')" class="lg:col-span-2">
                    <RevenueChart :data="charts.revenueDaily ?? []" :currency="currency" />
                </div>
                <div v-if="isVisible('status_chart')">
                    <WorkOrdersStatusChart :data="charts.workOrdersByStatus ?? {}" />
                </div>
            </div>

            <!-- Weekly + Alerts + Quick Actions -->
            <div v-if="isVisible('weekly_chart') || isVisible('alerts') || isVisible('quick_actions')"
                 class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div v-if="isVisible('weekly_chart')">
                    <WeeklyWorkOrdersChart :data="charts.weeklyWorkOrders ?? []" />
                </div>
                <div v-if="isVisible('alerts')">
                    <AlertsWidget
                        :overdueWorkOrders="alerts.overdueWorkOrders ?? 0"
                        :overdueInvoices="alerts.overdueInvoices ?? 0"
                        :lowStock="alerts.lowStock ?? 0"
                    />
                </div>
                <div v-if="isVisible('quick_actions')">
                    <QuickActions />
                </div>
            </div>

            <!-- Recent Lists -->
            <div v-if="isVisible('recent_work_orders') || isVisible('recent_invoices')"
                 class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-if="isVisible('recent_work_orders')">
                    <RecentWorkOrders :workOrders="recentWorkOrders" />
                </div>
                <div v-if="isVisible('recent_invoices')">
                    <RecentInvoices :invoices="recentInvoices" :currency="currency" />
                </div>
            </div>

        </div>

        <!-- Dashboard Customizer Drawer -->
        <DashboardCustomizer
            v-model="customizerOpen"
            :widgets="widgets"
            @update:widgets="onSaveWidgets"
            @reset-defaults="onResetDefaults"
        />

    </AppLayout>
</template>
