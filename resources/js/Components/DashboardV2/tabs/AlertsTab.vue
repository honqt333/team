<script setup>
import { router } from '@inertiajs/vue3';
import ChartCard from '@/Components/DashboardV2/common/ChartCard.vue';
import AlertCard from '@/Components/DashboardV2/widgets/AlertCard.vue';
import RecentInvoices from '@/Components/DashboardV2/widgets/RecentInvoices.vue';

defineProps({
    alerts: { type: Object, required: true },
    recentInvoices: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
});

function navigate(url) {
    router.get(url);
}

const ICONS = {
    overdueWO:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    overdueInvoice:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
    lowStock:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
};
</script>

<template>
    <div class="space-y-5">
        <!-- Alert cards row -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <AlertCard
                :title="$t('dashboard.overdue_work_orders')"
                :count="alerts.overdueWorkOrders ?? 0"
                :description="$t('dashboard.overdue_work_orders_desc')"
                href="/app/work-orders"
                :icon="ICONS.overdueWO"
                color="rose"
            />
            <AlertCard
                :title="$t('dashboard.overdue_invoices')"
                :count="alerts.overdueInvoices ?? 0"
                :description="$t('dashboard.overdue_invoices_desc')"
                href="/app/invoices"
                :icon="ICONS.overdueInvoice"
                color="amber"
            />
            <AlertCard
                :title="$t('dashboard.low_stock_parts')"
                :count="alerts.lowStock ?? 0"
                :description="$t('dashboard.low_stock_parts_desc')"
                href="/app/inventory/parts"
                :icon="ICONS.lowStock"
                color="sky"
            />
        </div>

        <!-- Status summary line -->
        <div
            v-if="
                (alerts.overdueWorkOrders ?? 0) +
                    (alerts.overdueInvoices ?? 0) +
                    (alerts.lowStock ?? 0) ===
                0
            "
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-6 text-center dark:border-emerald-800/50 dark:bg-emerald-900/10"
        >
            <div
                class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>
            <p class="mt-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                {{ $t('dashboard.all_clear') }}
            </p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                {{ $t('dashboard.everything_looks_great') }}
            </p>
        </div>

        <!-- Recent unpaid/partial invoices (helps with collection) -->
        <ChartCard
            :title="$t('dashboard.recent_invoices')"
            :subtitle="$t('dashboard.latest_8')"
            :action="$t('dashboard.view_all')"
            @action="navigate('/app/invoices')"
        >
            <RecentInvoices :items="recentInvoices" :currency="currency" :limit="8" />
        </ChartCard>
    </div>
</template>
