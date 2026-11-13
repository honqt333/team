<template>
    <BaseModal :show="show" @close="$emit('close')" size="4xl" scroll-entire>
        <template #title>
            <div class="flex items-center justify-between w-full pe-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white flex items-center justify-center font-bold shadow-md shadow-focus-ring-soft"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">
                            {{ $t('customers.statement.title') }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ customer?.name }} ({{ customer?.phone }})
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="handlePrint"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft transition-all hover:-translate-y-0.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    <span>{{ $t('common.print') }}</span>
                </button>
            </div>
        </template>

        <div class="space-y-6 statement-content print-container">
            <!-- Screen Summary Cards (Hidden during Print) -->
            <div class="screen-only grid grid-cols-1 sm:grid-cols-3 gap-3 print:hidden">
                <div
                    class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40"
                >
                    <span
                        class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block"
                    >
                        {{ $t('customers.statement.total_invoiced') }}
                    </span>
                    <span
                        class="text-xl font-black text-gray-900 dark:text-white font-mono mt-1 block"
                    >
                        {{ formatMoney(financialSummary?.total_invoiced) }}
                    </span>
                </div>

                <div
                    class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40"
                >
                    <span
                        class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block"
                    >
                        {{ $t('customers.statement.total_paid') }}
                    </span>
                    <span
                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1 block"
                    >
                        {{ formatMoney(financialSummary?.total_paid) }}
                    </span>
                </div>

                <div
                    class="p-4 rounded-2xl border"
                    :class="
                        financialSummary?.total_due > 0
                            ? 'bg-rose-50/50 dark:bg-rose-900/20 border-rose-100 dark:border-rose-800/40'
                            : 'bg-gray-50/50 dark:bg-gray-900/20 border-gray-100 dark:border-gray-800'
                    "
                >
                    <span
                        class="text-[11px] font-bold uppercase tracking-wider block"
                        :class="
                            financialSummary?.total_due > 0
                                ? 'text-rose-600 dark:text-rose-400'
                                : 'text-gray-500'
                        "
                    >
                        {{ $t('customers.statement.total_due') }}
                    </span>
                    <span
                        class="text-xl font-black font-mono mt-1 block"
                        :class="
                            financialSummary?.total_due > 0
                                ? 'text-rose-600 dark:text-rose-400'
                                : 'text-gray-900 dark:text-white'
                        "
                    >
                        {{ formatMoney(financialSummary?.total_due) }}
                    </span>
                </div>
            </div>

            <!-- AUDIT_STANDARDS.md Section 5: Standard Official Print Header (Visible ONLY during Print) -->
            <div
                class="print-header-area print-only hidden print:block border-b-2 border-gray-800 pb-4 mb-4"
            >
                <div class="grid grid-cols-3 items-center text-xs">
                    <!-- Column 1: Workshop / Center Info -->
                    <div class="text-start space-y-1">
                        <h2 class="text-base font-black text-black">
                            {{ companyInfo.name }}
                        </h2>
                        <p v-if="companyInfo.branch" class="font-bold text-gray-700">
                            {{ $t('common.branch_label') }} {{ companyInfo.branch }}
                        </p>
                        <p v-if="companyInfo.tax_number" class="text-gray-600">
                            {{ $t('common.tax_number_label') }}
                            <span class="font-mono font-bold">
                                {{ companyInfo.tax_number }}
                            </span>
                        </p>
                        <p v-if="companyInfo.cr_number" class="text-gray-600">
                            {{ $t('common.commercial_registration_label') }}
                            <span class="font-mono">{{ companyInfo.cr_number }}</span>
                        </p>
                    </div>

                    <!-- Column 2 (Center): Logo & Statement Title -->
                    <div class="text-center flex flex-col items-center justify-center">
                        <img
                            v-if="companyInfo.logo"
                            :src="companyInfo.logo"
                            alt="Logo"
                            class="h-14 w-auto object-contain mx-auto mb-1"
                        />
                        <div v-else class="text-center py-1">
                            <span class="text-base font-black tracking-wide text-black">
                                {{ companyInfo.name }}
                            </span>
                        </div>
                        <span class="text-sm font-black text-black uppercase tracking-wider">
                            {{ $t('customers.statement.title') }}
                        </span>
                    </div>

                    <!-- Column 3: Customer Info & Print Date -->
                    <div class="text-end space-y-1">
                        <p class="font-black text-black text-sm">{{ customer?.name }}</p>
                        <p class="font-mono" dir="ltr">{{ customer?.phone }}</p>
                        <p v-if="customer?.tax_number" class="text-gray-600">
                            {{ $t('common.tax_number_label') }}
                            <span class="font-mono">{{ customer?.tax_number }}</span>
                        </p>
                        <p class="text-gray-500">
                            {{ $t('common.print_date_label') }}
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                    </div>
                </div>

                <!-- Print Financial Banner -->
                <div
                    class="grid grid-cols-3 gap-2 mt-4 p-2.5 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('customers.statement.total_invoices') }}
                        </span>
                        <strong class="font-mono">
                            {{ formatMoney(financialSummary?.total_invoiced) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('customers.statement.total_paid') }}
                        </span>
                        <strong class="font-mono text-emerald-700">
                            {{ formatMoney(financialSummary?.total_paid) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('customers.statement.remaining_balance') }}
                        </span>
                        <strong class="font-mono text-red-700">
                            {{ formatMoney(financialSummary?.total_due) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-gray-700 dark:text-gray-300">
                        <thead
                            class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold border-b border-gray-200 dark:border-gray-700"
                        >
                            <tr>
                                <th class="px-3 py-3 text-center">#</th>
                                <th class="px-3 py-3 text-center">{{ $t('common.date') }}</th>
                                <th class="px-3 py-3 text-center">
                                    {{ $t('customers.statement.type') }}
                                </th>
                                <th class="px-3 py-3 text-center">
                                    {{ $t('common.reference_document_number') }}
                                </th>
                                <th class="px-3 py-3 text-center">
                                    {{ $t('customers.statement.work_order_vehicle') }}
                                </th>
                                <th class="px-3 py-3 text-center">
                                    {{ $t('customers.statement.debtor_invoices') }}
                                </th>
                                <th class="px-3 py-3 text-center">
                                    {{ $t('customers.statement.credit') }}
                                </th>
                                <th class="px-3 py-3 text-center">
                                    {{ $t('customers.statement.balance') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                            <tr
                                v-for="(entry, idx) in ledger"
                                :key="entry.id"
                                class="hover:bg-gray-50/60 dark:hover:bg-gray-800/60 text-center"
                            >
                                <td class="px-3 py-2.5 font-mono text-gray-400 text-[11px]">
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-3 py-2.5 font-mono text-gray-500 text-[11px]">
                                    {{ entry.date }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        v-if="entry.type === 'invoice'"
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                    >
                                        {{ $t('invoices.invoice') }}
                                    </span>
                                    <span
                                        v-else-if="entry.type === 'refund'"
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                    >
                                        {{ $t('payments.types.refund') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                    >
                                        {{ $t('print_settings.receipt') }}
                                    </span>
                                </td>
                                <td
                                    class="px-3 py-2.5 font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ entry.reference }}
                                </td>
                                <td class="px-3 py-2.5 text-xs text-gray-500">
                                    <span
                                        v-if="entry.work_order_number"
                                        class="font-mono text-action-primary"
                                    >
                                        {{ entry.work_order_number }}
                                    </span>
                                    <span
                                        v-if="entry.vehicle_plate"
                                        class="text-gray-400 ms-1 font-mono"
                                    >
                                        ({{ entry.vehicle_plate }})
                                    </span>
                                    <span v-if="!entry.work_order_number && !entry.vehicle_plate">
                                        —
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-gray-900 dark:text-white">
                                    {{ entry.debit > 0 ? formatMoney(entry.debit) : '—' }}
                                </td>
                                <td
                                    class="px-3 py-2.5 font-mono text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ entry.credit > 0 ? formatMoney(entry.credit) : '—' }}
                                </td>
                                <td
                                    class="px-3 py-2.5 font-mono font-black"
                                    :class="
                                        entry.balance > 0
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-gray-900 dark:text-white'
                                    "
                                >
                                    {{ formatMoney(entry.balance) }}
                                </td>
                            </tr>

                            <tr v-if="!ledger || ledger.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-xs">
                                    {{ $t('customers.statement.no_transactions') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Print Footer -->
            <div
                class="print-footer-area print-only hidden print:block pt-6 mt-8 border-t border-gray-300 text-xs text-gray-600"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p>
                            {{ $t('common.prepared_by_label') }}
                            <span class="font-bold text-black">
                                {{ $page.props.auth?.user?.name || 'Administrator' }}
                            </span>
                        </p>
                        <p>
                            {{ $t('common.print_date_time_label') }}
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                    </div>
                    <div class="text-center w-48 border-t border-dashed border-gray-400 pt-1">
                        <span class="text-[11px] font-bold text-black block">
                            {{ $t('reports.payments_report.official_seal') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { printDocument } from '@/Composables/usePrint';
const { t } = useI18n();

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { formatMoney } = useNumberFormat();
const page = usePage();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    customer: {
        type: Object,
        default: null,
    },
    financialSummary: {
        type: Object,
        default: () => ({
            total_invoiced: 0,
            total_paid: 0,
            total_due: 0,
        }),
    },
    ledger: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['close']);

const companyInfo = computed(() => {
    const center = page.props.center || {};
    const tenant = page.props.tenant || {};
    return {
        name:
            center.name_ar ||
            center.name ||
            tenant.trade_name ||
            tenant.legal_name ||
            tenant.name ||
            t('common.app_name'),
        branch: center.name || center.name_ar || '',
        tax_number: tenant.vat_number || tenant.tax_number || center.vat_number || '',
        cr_number: tenant.cr_number || center.cr_number || '',
        logo:
            center.logo_invoice_url ||
            center.logo_light_url ||
            tenant.logo_url ||
            (tenant.logo_path ? '/storage/' + tenant.logo_path : ''),
    };
});

// Currency formatting now uses tenant-aware formatMoney from useNumberFormat()

const printDateFormatted = computed(() => {
    return new Date().toLocaleString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const handlePrint = () => {
    if (props.customer?.id) {
        window.open(route('customers.statement.print', props.customer.id), '_blank');
    } else {
        printDocument();
    }
};
</script>

<style scoped>
@page {
    size: A4 portrait;
    margin: 10mm;
}

@media print {
    :global(body) {
        background-color: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    :global(.fixed.inset-0) {
        position: static !important;
        display: block !important;
        overflow: visible !important;
        background: transparent !important;
        min-height: auto !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    :global(.flex.min-h-full) {
        display: block !important;
        min-height: auto !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    :global(.modal-container),
    :global(.relative.bg-white) {
        position: static !important;
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 1. Hide entire page background */
    body * {
        visibility: hidden !important;
    }

    /* 2. Show only statement container */
    .statement-content,
    .statement-content * {
        visibility: visible !important;
    }

    /* 3. Strictly hide screen summary cards so they never duplicate with print banner */
    .screen-only,
    .screen-only * {
        display: none !important;
        visibility: hidden !important;
    }

    /* 4. Strictly show print header and banner */
    .print-only {
        display: block !important;
        visibility: visible !important;
    }

    .statement-content {
        position: static !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        color: black !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }

    th,
    td {
        border: 1px solid #d1d5db !important;
        padding: 6px !important;
        text-align: center !important;
    }

    th {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: bold !important;
    }

    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
