<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.portal_invoices.title')"
        :subtitle="t('customer_portal.portal_invoices.subtitle')"
        show-logout
        show-bottom-nav
        current-route="public.portal.invoices.index"
        :language-base-path="`/${tenant.slug}/portal/invoices`"
    >
        <!-- Financial summary -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-6">
            <div
                v-for="card in summaryCards"
                :key="card.key"
                class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border p-4 shadow-sm"
                :class="card.border"
            >
                <span class="absolute inset-y-0 start-0 w-1" :class="card.accent" />
                <p class="text-xs font-bold text-slate-400">{{ card.label }}</p>
                <p class="text-lg font-extrabold mt-1" :class="card.text" dir="ltr">
                    {{ formatMoney(card.value) }}
                    <span class="text-xs font-semibold text-slate-500">{{ currency }}</span>
                </p>
            </div>
        </section>

        <div class="mb-5">
            <AppTabs v-model="activeFilter" :tabs="filterTabs" variant="pills" size="sm" />
        </div>

        <PortalEmptyState
            v-if="filteredInvoices.length === 0"
            :message="t('customer_portal.portal_invoices.empty')"
        />

        <div v-else class="space-y-3.5">
            <a
                v-for="inv in filteredInvoices"
                :key="inv.id"
                :href="`/${tenant.slug}/portal/invoice/${inv.id}`"
                class="group block rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-sm font-mono font-extrabold text-slate-900 dark:text-white"
                                dir="ltr"
                            >
                                {{ inv.invoice_number }}
                            </span>
                            <span
                                v-if="isNote(inv)"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200"
                            >
                                {{ t(`customer_portal.portal_invoices.${inv.type}`) }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                :class="paymentStatusClass(inv.payment_status)"
                            >
                                {{
                                    t(
                                        `customer_portal.portal_invoices.status_${inv.payment_status}`
                                    )
                                }}
                            </span>
                        </div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                            {{ inv.vehicle_label || inv.center_name || '—' }}
                        </p>
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400"
                        >
                            <span v-if="inv.issue_date">{{ formatDate(inv.issue_date) }}</span>
                            <span v-if="inv.center_name">{{ inv.center_name }}</span>
                            <span v-if="inv.work_order_code">
                                {{
                                    t('customer_portal.portal_invoices.view_work_order', {
                                        code: inv.work_order_code,
                                    })
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <SaudiPlateDisplay
                            v-if="inv.vehicle_plate"
                            :plate-number="inv.vehicle_plate"
                            size="xs"
                        />
                        <p
                            class="text-base font-extrabold text-slate-900 dark:text-white"
                            dir="ltr"
                        >
                            {{ formatMoney(inv.total_incl_tax) }}
                            <span class="text-xs font-semibold text-slate-500">
                                {{ inv.currency_code }}
                            </span>
                        </p>
                        <p
                            v-if="inv.balance > 0 && !isNote(inv)"
                            class="text-xs font-bold text-rose-600 dark:text-rose-400"
                        >
                            {{ t('customer_portal.portal_invoices.balance_label') }}:
                            <span dir="ltr">{{ formatMoney(inv.balance) }}</span>
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import PortalEmptyState from '@/Components/CustomerPortal/PortalEmptyState.vue';
import { usePortalFormat, paymentStatusClass } from '@/Composables/usePortalFormat';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    invoices: { type: Array, default: () => [] },
    financialSummary: { type: Object, default: () => ({}) },
});

const { t, formatDate, formatMoney } = usePortalFormat(props);

const currency = computed(() => props.financialSummary?.currency || props.tenant.currency || 'SAR');
const isNote = (inv) => inv.type === 'credit_note' || inv.type === 'debit_note';
const isUnpaid = (inv) => !isNote(inv) && Number(inv.balance) > 0.009;

const summaryCards = computed(() => {
    const balance = Number(props.financialSummary?.total_balance || 0);
    return [
        {
            key: 'invoiced',
            label: t('customer_portal.portal_invoices.summary_invoiced'),
            value: props.financialSummary?.total_invoiced,
            text: 'text-slate-900 dark:text-white',
            border: 'border-slate-200/80 dark:border-slate-800',
            accent: 'bg-blue-500',
        },
        {
            key: 'paid',
            label: t('customer_portal.portal_invoices.summary_paid'),
            value: props.financialSummary?.total_paid,
            text: 'text-emerald-600 dark:text-emerald-400',
            border: 'border-slate-200/80 dark:border-slate-800',
            accent: 'bg-emerald-500',
        },
        {
            key: 'balance',
            label: t('customer_portal.portal_invoices.summary_balance'),
            value: balance,
            text:
                balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white',
            border:
                balance > 0
                    ? 'border-rose-200/80 dark:border-rose-900/50'
                    : 'border-slate-200/80 dark:border-slate-800',
            accent: balance > 0 ? 'bg-rose-500' : 'bg-slate-300',
        },
    ];
});

const activeFilter = ref('all');
const filterTabs = computed(() => [
    {
        id: 'all',
        label: t('customer_portal.portal_invoices.filter_all'),
        count: props.invoices.length,
    },
    {
        id: 'unpaid',
        label: t('customer_portal.portal_invoices.filter_unpaid'),
        count: props.invoices.filter(isUnpaid).length,
    },
    {
        id: 'paid',
        label: t('customer_portal.portal_invoices.filter_paid'),
        count: props.invoices.filter((i) => i.payment_status === 'paid').length,
    },
]);

const filteredInvoices = computed(() => {
    if (activeFilter.value === 'unpaid') return props.invoices.filter(isUnpaid);
    if (activeFilter.value === 'paid')
        return props.invoices.filter((i) => i.payment_status === 'paid');
    return props.invoices;
});
</script>
