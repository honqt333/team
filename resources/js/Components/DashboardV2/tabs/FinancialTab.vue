<script setup>
import { router } from '@inertiajs/vue3';
import KPICard from '@/Components/DashboardV2/common/KPICard.vue';
import ChartCard from '@/Components/DashboardV2/common/ChartCard.vue';
import RevenueBarChart from '@/Components/DashboardV2/widgets/RevenueBarChart.vue';
import RecentInvoices from '@/Components/DashboardV2/widgets/RecentInvoices.vue';
import TopCustomers from '@/Components/DashboardV2/widgets/TopCustomers.vue';

defineProps({
    stats: { type: Object, required: true },
    charts: { type: Object, required: true },
    recentInvoices: { type: Array, default: () => [] },
    topCustomers: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
});

function navigate(url) {
    router.get(url);
}

const ICONS = {
    revenue:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    outstanding:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    paid: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    avgInvoice:
        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
};
</script>

<template>
    <div class="space-y-5">
        <!-- Financial KPIs -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                :subtitle="$t('dashboard.unpaid_invoices')"
                href="/app/invoices"
            />
            <KPICard
                :label="$t('dashboard.payments_today')"
                :value="stats.revenue?.paymentsToday ?? 0"
                :icon="ICONS.paid"
                icon-color="emerald"
                format="currency"
                :suffix="currency"
                href="/app/invoices"
            />
            <KPICard
                :label="$t('dashboard.avg_invoice_value')"
                :value="stats.revenue?.avgInvoiceValue ?? 0"
                :icon="ICONS.avgInvoice"
                icon-color="sky"
                format="currency"
                :suffix="currency"
                :subtitle="$t('dashboard.this_month')"
            />
        </div>

        <!-- Revenue trend (large chart) -->
        <ChartCard :title="$t('dashboard.revenue_trend')" :subtitle="$t('dashboard.last_30_days')">
            <RevenueBarChart :data="charts.revenueDaily ?? []" :currency="currency" />
        </ChartCard>

        <!-- Top customers + Recent invoices -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartCard
                :title="$t('dashboard.top_customers')"
                :subtitle="$t('dashboard.last_30_days')"
            >
                <TopCustomers :customers="topCustomers" :currency="currency" />
            </ChartCard>
            <ChartCard
                :title="$t('dashboard.recent_invoices')"
                :subtitle="$t('dashboard.latest_8')"
                :action="$t('dashboard.view_all')"
                @action="navigate('/app/invoices')"
            >
                <RecentInvoices :items="recentInvoices" :currency="currency" :limit="6" />
            </ChartCard>
        </div>
    </div>
</template>
