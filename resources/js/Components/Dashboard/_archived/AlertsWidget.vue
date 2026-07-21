<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    overdueWorkOrders: { type: Number, default: 0 },
    overdueInvoices: { type: Number, default: 0 },
    lowStock: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
});

const alerts = computed(() =>
    [
        {
            id: 'overdue_wo',
            show: props.overdueWorkOrders > 0,
            count: props.overdueWorkOrders,
            label: t('dashboard.overdue_work_orders'),
            description: t('dashboard.overdue_work_orders_desc'),
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
            color: 'rose',
            bg: 'bg-rose-50 dark:bg-rose-900/20',
            border: 'border-rose-200 dark:border-rose-800/60',
            iconBg: 'bg-rose-100 dark:bg-rose-900/40',
            iconColor: 'text-rose-600 dark:text-rose-400',
            badgeBg: 'bg-rose-600',
            link: '/app/work-orders',
        },
        {
            id: 'overdue_inv',
            show: props.overdueInvoices > 0,
            count: props.overdueInvoices,
            label: t('dashboard.overdue_invoices'),
            description: t('dashboard.overdue_invoices_desc'),
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
            color: 'amber',
            bg: 'bg-amber-50 dark:bg-amber-900/20',
            border: 'border-amber-200 dark:border-amber-800/60',
            iconBg: 'bg-amber-100 dark:bg-amber-900/40',
            iconColor: 'text-amber-600 dark:text-amber-400',
            badgeBg: 'bg-amber-600',
            link: '/app/invoices',
        },
        {
            id: 'low_stock',
            show: props.lowStock > 0,
            count: props.lowStock,
            label: t('dashboard.low_stock_parts'),
            description: t('dashboard.low_stock_parts_desc'),
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>`,
            color: 'violet',
            bg: 'bg-violet-50 dark:bg-violet-900/20',
            border: 'border-violet-200 dark:border-violet-800/60',
            iconBg: 'bg-violet-100 dark:bg-violet-900/40',
            iconColor: 'text-violet-600 dark:text-violet-400',
            badgeBg: 'bg-violet-600',
            link: '/app/inventory/stock',
        },
    ].filter((a) => a.show)
);

const hasAlerts = computed(() => alerts.value.length > 0);
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
    >
        <!-- Header -->
        <div
            class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700/60"
        >
            <div
                class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center"
            >
                <svg
                    class="w-4 h-4 text-rose-600 dark:text-rose-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ $t('dashboard.alerts_title') }}
                </h3>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{
                        hasAlerts
                            ? $t('dashboard.alerts_attention', { n: alerts.length })
                            : $t('dashboard.all_clear')
                    }}
                </p>
            </div>
            <div v-if="hasAlerts" class="ms-auto">
                <span
                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-rose-500 text-white text-xs font-bold animate-pulse"
                >
                    {{ alerts.length }}
                </span>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-4 space-y-3">
            <div
                v-for="i in 2"
                :key="i"
                class="h-16 bg-gray-100 dark:bg-gray-700/50 rounded-xl animate-pulse"
            />
        </div>

        <!-- Alerts -->
        <div v-else-if="hasAlerts" class="p-4 space-y-3">
            <a
                v-for="alert in alerts"
                :key="alert.id"
                :href="alert.link"
                :class="[
                    'flex items-center gap-3 p-3 rounded-xl border transition-all hover:shadow-sm hover:-translate-y-0.5',
                    alert.bg,
                    alert.border,
                ]"
            >
                <div
                    :class="[
                        'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
                        alert.iconBg,
                        alert.iconColor,
                    ]"
                >
                    <span v-safe-html="alert.icon" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        {{ alert.label }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ alert.description }}</p>
                </div>
                <div
                    :class="[
                        'px-2.5 py-1 rounded-full text-white text-xs font-bold flex-shrink-0',
                        alert.badgeBg,
                    ]"
                >
                    {{ alert.count }}
                </div>
            </a>
        </div>

        <!-- All good state -->
        <div v-else class="py-8 px-5 text-center">
            <div
                class="w-14 h-14 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center mx-auto mb-3"
            >
                <svg
                    class="w-7 h-7 text-emerald-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ $t('dashboard.everything_looks_great') }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ $t('dashboard.no_alerts_attention') }}
            </p>
        </div>
    </div>
</template>
