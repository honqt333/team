<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import OverviewTab from '@/Components/DashboardV2/tabs/OverviewTab.vue';
import FinancialTab from '@/Components/DashboardV2/tabs/FinancialTab.vue';
import OperationsTab from '@/Components/DashboardV2/tabs/OperationsTab.vue';
import AlertsTab from '@/Components/DashboardV2/tabs/AlertsTab.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { activeCurrencyCode, activeCurrencySymbol } = useNumberFormat();

const props = defineProps({
    userContext: {
        type: Object,
        default: () => ({
            canViewFinancials: false,
            canViewInventory: false,
            canCreateWorkOrder: false,
            isTechnician: false,
            isReceptionist: false,
            roleName: 'user',
            roleLabel: '',
        }),
    },
    stats: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    alerts: { type: Object, default: () => ({}) },
    recentWorkOrders: { type: Array, default: () => [] },
    recentInvoices: { type: Array, default: () => [] },
    todaySchedule: { type: Array, default: () => [] },
    todayNewWorkOrders: { type: Array, default: () => [] },
    todayAppointments: { type: Array, default: () => [] },
    readyForDelivery: { type: Array, default: () => [] },
    myAssignedWorkOrders: { type: Array, default: () => [] },
    topServices: { type: Array, default: () => [] },
    technicianPerformance: { type: Array, default: () => [] },
    topCustomers: { type: Array, default: () => [] },
    checkedOutPaymentDue: { type: Object, default: () => null },
    activeWorkshopVehicles: { type: Array, default: () => [] },
    workingTechniciansNow: { type: Array, default: () => [] },
    currency: { type: String, default: '' },
    period: { type: String, default: 'month' },
    lastUpdatedAt: { type: String, default: '' },
});

const page = usePage();
const dashboardCurrency = computed(
    () => props.currency || activeCurrencySymbol.value || activeCurrencyCode.value
);
const userName = computed(() => page.props.auth?.user?.name?.split(' ')[0] || '');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return t('dashboard.greeting_morning');
    if (h < 17) return t('dashboard.greeting_afternoon');
    return t('dashboard.greeting_evening');
});

const today = computed(() => {
    return new Date().toLocaleDateString(page.props.locale === 'en' ? 'en-US' : 'ar-SA', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const tabs = computed(() => {
    const list = [
        {
            id: 'overview',
            label: t('dashboard.tab_overview'),
        },
    ];

    if (props.userContext?.canViewFinancials) {
        list.push({
            id: 'financial',
            label: t('dashboard.tab_financial'),
        });
    }

    list.push({
        id: 'operations',
        label: t('dashboard.tab_operations'),
        count: props.stats?.workOrders?.active || null,
        badge: props.stats?.workOrders?.active || null,
    });

    const alertsTotal =
        (props.alerts?.overdueWorkOrders ?? 0) +
        (props.userContext?.canViewFinancials ? (props.alerts?.overdueInvoices ?? 0) : 0) +
        (props.userContext?.canViewInventory ? (props.alerts?.lowStock ?? 0) : 0);

    list.push({
        id: 'alerts',
        label: t('dashboard.tab_alerts'),
        count: alertsTotal || null,
        badge: alertsTotal || null,
    });

    return list;
});

const initialTab =
    typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('tab') : null;

const activeTab = ref(
    initialTab && initialTab !== 'financial'
        ? initialTab
        : initialTab === 'financial' && props.userContext?.canViewFinancials
          ? 'financial'
          : 'overview'
);

watch(activeTab, (newTab) => {
    if (newTab === 'financial' && !props.userContext?.canViewFinancials) {
        activeTab.value = 'overview';
    }
});

const isRefreshing = ref(false);
const selectedPeriod = ref(props.period || 'month');
const autoRefresh = ref(
    typeof window !== 'undefined' &&
        window.localStorage?.getItem('khidmh_dashboard_autorefresh') === '1'
);
const showCustomizeMenu = ref(false);

const defaultWidgetPrefs = {
    kpis: true,
    operations: true,
    charts: true,
    services: true,
};

const loadWidgetPrefs = () => {
    if (typeof window === 'undefined') return { ...defaultWidgetPrefs };
    try {
        const raw = window.localStorage?.getItem('khidmh_dashboard_widgets');
        return raw ? { ...defaultWidgetPrefs, ...JSON.parse(raw) } : { ...defaultWidgetPrefs };
    } catch {
        return { ...defaultWidgetPrefs };
    }
};

const widgetPrefs = ref(loadWidgetPrefs());

watch(
    widgetPrefs,
    (val) => {
        if (typeof window !== 'undefined') {
            try {
                window.localStorage?.setItem('khidmh_dashboard_widgets', JSON.stringify(val));
            } catch {
                // ignore storage errors
            }
        }
    },
    { deep: true }
);

watch(
    () => props.period,
    (val) => {
        if (val) selectedPeriod.value = val;
    }
);

const periodOptions = computed(() => [
    { id: 'today', label: t('dashboard.period_today') },
    { id: '7d', label: t('dashboard.last_7_days') },
    { id: 'month', label: t('dashboard.this_month') },
    { id: '30d', label: t('dashboard.last_30_days') },
]);

const RELOAD_ONLY = [
    'period',
    'lastUpdatedAt',
    'userContext',
    'stats',
    'charts',
    'alerts',
    'recentWorkOrders',
    'recentInvoices',
    'todaySchedule',
    'todayNewWorkOrders',
    'todayAppointments',
    'readyForDelivery',
    'myAssignedWorkOrders',
    'topServices',
    'technicianPerformance',
    'topCustomers',
    'checkedOutPaymentDue',
    'activeWorkshopVehicles',
    'workingTechniciansNow',
];

function selectPeriod(periodId) {
    if (selectedPeriod.value === periodId && !isRefreshing.value) return;
    selectedPeriod.value = periodId;
    isRefreshing.value = true;
    router.reload({
        data: { period: periodId },
        only: RELOAD_ONLY,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function refresh(force = true) {
    if (isRefreshing.value) return;
    isRefreshing.value = true;
    router.reload({
        data: {
            period: selectedPeriod.value,
            ...(force ? { refresh: 1 } : {}),
        },
        only: RELOAD_ONLY,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

let autoRefreshTimer = null;

function setupAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
    if (autoRefresh.value && typeof window !== 'undefined') {
        autoRefreshTimer = setInterval(() => {
            if (!document.hidden) {
                refresh(true);
            }
        }, 60000);
    }
}

watch(autoRefresh, (val) => {
    if (typeof window !== 'undefined') {
        try {
            window.localStorage?.setItem('khidmh_dashboard_autorefresh', val ? '1' : '0');
        } catch {
            // ignore
        }
    }
    setupAutoRefresh();
});

const closeCustomizeOnOutsideClick = (event) => {
    if (showCustomizeMenu.value && !event.target.closest('.dashboard-customize-root')) {
        showCustomizeMenu.value = false;
    }
};

onMounted(() => {
    setupAutoRefresh();
    if (typeof document !== 'undefined') {
        document.addEventListener('click', closeCustomizeOnOutsideClick);
    }
});

onBeforeUnmount(() => {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (typeof document !== 'undefined') {
        document.removeEventListener('click', closeCustomizeOnOutsideClick);
    }
});
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Approved Master PageHeader -->
            <PageHeader
                :title="$t('dashboard.title')"
                :subtitle="`${greeting}، ${userName}`"
                gradient-from="from-action-primary"
                gradient-to="to-action-primary"
                glow-from="from-action-primary"
                glow-to="to-action-primary"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <!-- Role badge -->
                    <div
                        v-if="userContext?.roleLabel"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700/60 text-xs font-semibold text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-600/60"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ userContext.roleLabel }}</span>
                    </div>

                    <!-- Date display -->
                    <div
                        class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-800/80 text-xs font-medium text-slate-600 dark:text-slate-300"
                    >
                        <svg
                            class="h-3.5 w-3.5 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span>{{ today }}</span>
                    </div>

                    <!-- Period Switcher -->
                    <div
                        class="hidden lg:inline-flex items-center gap-0.5 rounded-xl border border-slate-200/80 bg-slate-100/80 p-1 dark:border-slate-700 dark:bg-slate-800/80"
                    >
                        <button
                            v-for="opt in periodOptions"
                            :key="opt.id"
                            type="button"
                            @click="selectPeriod(opt.id)"
                            :class="[
                                'rounded-lg px-2.5 py-1 text-xs font-semibold transition-all cursor-pointer',
                                selectedPeriod === opt.id
                                    ? 'bg-white text-action-primary shadow-2xs dark:bg-slate-700 dark:text-white'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                            ]"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <!-- Customize Widgets -->
                    <div class="relative dashboard-customize-root">
                        <button
                            type="button"
                            @click="showCustomizeMenu = !showCustomizeMenu"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition-all hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
                            :title="$t('dashboard.customize')"
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
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                />
                            </svg>
                            <span class="hidden xl:inline">{{ $t('dashboard.customize') }}</span>
                        </button>
                        <div
                            v-if="showCustomizeMenu"
                            class="absolute end-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl z-40 dark:border-slate-700 dark:bg-slate-800 space-y-2"
                        >
                            <label
                                v-for="(val, key) in widgetPrefs"
                                :key="key"
                                class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer"
                            >
                                <span>{{ $t(`dashboard.widget_${key}`) }}</span>
                                <input
                                    type="checkbox"
                                    v-model="widgetPrefs[key]"
                                    class="rounded border-slate-300 text-action-primary focus:ring-action-primary"
                                />
                            </label>
                            <div class="border-t border-slate-100 dark:border-slate-700 pt-2">
                                <label
                                    class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer"
                                >
                                    <span>{{ $t('dashboard.auto_refresh') }} (60s)</span>
                                    <input
                                        type="checkbox"
                                        v-model="autoRefresh"
                                        class="rounded border-slate-300 text-action-primary focus:ring-action-primary"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Refresh button -->
                    <button
                        type="button"
                        @click="refresh(true)"
                        :disabled="isRefreshing"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition-all hover:bg-slate-50 hover:text-slate-900 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
                        :title="$t('dashboard.refresh')"
                    >
                        <svg
                            :class="['h-3.5 w-3.5', { 'animate-spin': isRefreshing }]"
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
                        <span class="hidden sm:inline">{{ $t('dashboard.refresh') }}</span>
                    </button>
                </template>

                <template #primary-action v-if="userContext?.canCreateWorkOrder">
                    <Link
                        href="/app/work-orders?create=1"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-action-primary hover:bg-action-primary-hover px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-focus-ring-soft transition-all active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('dashboard.new_work_order') }}
                    </Link>
                </template>
            </PageHeader>

            <!-- Quick Attention / Action Required Strip -->
            <div
                v-if="
                    (alerts?.overdueWorkOrders ?? 0) > 0 ||
                    (readyForDelivery?.length ?? 0) > 0 ||
                    (todayAppointments?.length ?? 0) > 0 ||
                    (userContext?.canViewFinancials && (checkedOutPaymentDue?.count ?? 0) > 0) ||
                    (userContext?.canViewInventory && (alerts?.lowStock ?? 0) > 0)
                "
                class="flex flex-wrap items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white/70 backdrop-blur-sm p-3.5 dark:border-slate-800 dark:bg-slate-800/60 shadow-2xs"
            >
                <div
                    class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 ml-1"
                >
                    <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                    <span>{{ $t('dashboard.alerts_attention', { n: '' }) }}</span>
                </div>

                <!-- Overdue Work Orders -->
                <Link
                    v-if="(alerts?.overdueWorkOrders ?? 0) > 0"
                    href="/app/work-orders"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/60"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                    <span>
                        {{ $t('dashboard.overdue_work_orders') }}: {{ alerts.overdueWorkOrders }}
                    </span>
                </Link>

                <!-- Ready for QC / Delivery -->
                <Link
                    v-if="(readyForDelivery?.length ?? 0) > 0"
                    href="/app/workshop"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <span>
                        {{ $t('work_orders.work_order_statuses.ready_for_qc') || 'جاهزة للتسليم' }}:
                        {{ readyForDelivery.length }}
                    </span>
                </Link>

                <!-- Today's Appointments -->
                <Link
                    v-if="(todayAppointments?.length ?? 0) > 0"
                    href="/app/appointments"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-selection-bg px-3 py-1.5 text-xs font-semibold text-selection-text transition hover:bg-selection-bg"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-action-primary"></span>
                    <span>
                        {{ $t('appointments.title') || 'مواعيد اليوم' }}:
                        {{ todayAppointments.length }}
                    </span>
                </Link>

                <!-- Low Stock Parts -->
                <Link
                    v-if="userContext?.canViewInventory && (alerts?.lowStock ?? 0) > 0"
                    href="/app/inventory/stock?stock_status=low_stock"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-300 dark:hover:bg-amber-900/60"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    <span>{{ $t('dashboard.low_stock_parts') }}: {{ alerts.lowStock }}</span>
                </Link>

                <!-- Checked-Out Payment Due -->
                <Link
                    v-if="userContext?.canViewFinancials && (checkedOutPaymentDue?.count ?? 0) > 0"
                    href="/app/work-orders?status=done"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100 dark:bg-orange-950/50 dark:text-orange-300 dark:hover:bg-orange-900/60"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                    <span>
                        {{ $t('dashboard.checked_out_payment_due') }}:
                        {{ checkedOutPaymentDue.count }} ({{ checkedOutPaymentDue.amount }}
                        {{ dashboardCurrency }})
                    </span>
                </Link>
            </div>

            <!-- Approved Standard AppTabs (Pills Variant) -->
            <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url url-param="tab" />

            <!-- Tab Content View -->
            <div class="min-h-[500px]">
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    mode="out-in"
                >
                    <OverviewTab
                        v-if="activeTab === 'overview'"
                        :user-context="userContext"
                        :stats="stats"
                        :charts="charts"
                        :recent-work-orders="recentWorkOrders"
                        :recent-invoices="recentInvoices"
                        :today-schedule="todaySchedule"
                        :today-appointments="todayAppointments"
                        :ready-for-delivery="readyForDelivery"
                        :my-assigned-work-orders="myAssignedWorkOrders"
                        :top-services="topServices"
                        :currency="dashboardCurrency"
                        :is-loading="isRefreshing"
                        :visible-widgets="widgetPrefs"
                    />

                    <FinancialTab
                        v-else-if="activeTab === 'financial' && userContext?.canViewFinancials"
                        :stats="stats"
                        :charts="charts"
                        :recent-invoices="recentInvoices"
                        :top-customers="topCustomers"
                        :currency="dashboardCurrency"
                    />

                    <OperationsTab
                        v-else-if="activeTab === 'operations'"
                        :user-context="userContext"
                        :stats="stats"
                        :charts="charts"
                        :recent-work-orders="recentWorkOrders"
                        :today-schedule="todaySchedule"
                        :today-new-work-orders="todayNewWorkOrders"
                        :top-services="topServices"
                        :technician-performance="technicianPerformance"
                        :active-workshop-vehicles="activeWorkshopVehicles"
                        :working-technicians-now="workingTechniciansNow"
                        :currency="dashboardCurrency"
                    />

                    <AlertsTab
                        v-else-if="activeTab === 'alerts'"
                        :user-context="userContext"
                        :alerts="alerts"
                        :recent-invoices="recentInvoices"
                        :currency="dashboardCurrency"
                    />
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>
