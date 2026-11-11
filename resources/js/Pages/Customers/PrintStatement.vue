<template>
    <StandardReportLayout
        :title="$t('customers.statement.title')"
        :subtitle="customerSummary"
        back-route="app.customers.show"
        :back-route-params="customer?.id ? [customer.id] : []"
    >
        <template #header-extra>
            <div v-if="customer?.tax_number" class="text-xs text-gray-600">
                {{ $t('common.tax_number_label') }}
                <span class="font-mono">{{ customer.tax_number }}</span>
            </div>
        </template>

        <!-- Financial Summary Banner -->
        <div
            class="grid grid-cols-3 gap-3 mb-6 p-3 bg-gray-50 rounded-xl border border-gray-200 text-center text-xs"
        >
            <div class="p-2 bg-white rounded-lg border border-gray-100">
                <span class="text-gray-500 block mb-1">
                    {{ $t('customers.statement.total_invoices') }}
                </span>
                <strong class="font-mono text-sm text-gray-900">
                    {{ formatMoney(financialSummary?.total_invoiced) }}
                </strong>
            </div>
            <div class="p-2 bg-white rounded-lg border border-gray-100">
                <span class="text-gray-500 block mb-1">
                    {{ $t('customers.statement.total_paid') }}
                </span>
                <strong class="font-mono text-sm text-emerald-700">
                    {{ formatMoney(financialSummary?.total_paid) }}
                </strong>
            </div>
            <div class="p-2 bg-white rounded-lg border border-gray-100">
                <span class="text-gray-500 block mb-1">
                    {{ $t('customers.statement.remaining_balance') }}
                </span>
                <strong class="font-mono text-sm text-rose-700">
                    {{ formatMoney(financialSummary?.total_due) }}
                </strong>
            </div>
        </div>

        <!-- Detailed Ledger Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-center border border-gray-300 border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-300 text-gray-800 font-bold">
                        <th class="py-2.5 px-2 text-center w-8">#</th>
                        <th class="py-2.5 px-3 text-center">{{ $t('common.date') }}</th>
                        <th class="py-2.5 px-3 text-center">
                            {{ $t('customers.statement.type') }}
                        </th>
                        <th class="py-2.5 px-3 text-center">
                            {{ $t('common.reference_document_number') }}
                        </th>
                        <th class="py-2.5 px-3 text-center">
                            {{ $t('customers.statement.work_order_vehicle') }}
                        </th>
                        <th class="py-2.5 px-3 text-center">
                            {{ $t('customers.statement.debtor_invoices') }}
                        </th>
                        <th class="py-2.5 px-3 text-center">
                            {{ $t('customers.statement.credit') }}
                        </th>
                        <th class="py-2.5 px-3 text-center">
                            {{ $t('customers.statement.balance') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="(entry, idx) in ledger" :key="entry.id" class="text-center">
                        <td class="py-2 px-2 font-mono text-gray-500 text-[11px]">
                            {{ idx + 1 }}
                        </td>
                        <td class="py-2 px-3 font-mono text-gray-600 text-[11px]">
                            {{ entry.date }}
                        </td>
                        <td class="py-2 px-3">
                            <span
                                v-if="entry.type === 'invoice'"
                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"
                            >
                                {{ $t('invoices.invoice') }}
                            </span>
                            <span
                                v-else-if="entry.type === 'refund'"
                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                            >
                                {{ $t('payments.types.refund') }}
                            </span>
                            <span
                                v-else
                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                {{ $t('print_settings.receipt') }}
                            </span>
                        </td>
                        <td class="py-2 px-3 font-mono font-bold text-gray-900">
                            {{ entry.reference }}
                        </td>
                        <td class="py-2 px-3 text-xs text-gray-600">
                            <span
                                v-if="entry.work_order_number"
                                class="font-mono text-selection-text"
                            >
                                {{ entry.work_order_number }}
                            </span>
                            <span v-if="entry.vehicle_plate" class="text-gray-500 ms-1 font-mono">
                                ({{ entry.vehicle_plate }})
                            </span>
                            <span v-if="!entry.work_order_number && !entry.vehicle_plate">—</span>
                        </td>
                        <td class="py-2 px-3 font-mono text-gray-900">
                            {{ entry.debit > 0 ? formatMoney(entry.debit) : '—' }}
                        </td>
                        <td class="py-2 px-3 font-mono text-emerald-700">
                            {{ entry.credit > 0 ? formatMoney(entry.credit) : '—' }}
                        </td>
                        <td
                            class="py-2 px-3 font-mono font-black"
                            :class="entry.balance > 0 ? 'text-rose-700' : 'text-gray-900'"
                        >
                            {{ formatMoney(entry.balance) }}
                        </td>
                    </tr>

                    <tr v-if="!ledger || ledger.length === 0">
                        <td colspan="8" class="py-8 text-center text-gray-400 text-xs">
                            {{ $t('customers.statement.no_transactions') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </StandardReportLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const props = defineProps({
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
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            branch: '',
            vat_number: '',
            cr_number: '',
            logo_url: '',
            phone: '',
        }),
    },
});

const { t } = useI18n();

const customerSummary = computed(() => {
    if (!props.customer?.name) return '';
    if (props.customer?.phone) {
        return `${props.customer.name} (${props.customer.phone})`;
    }
    return props.customer.name;
});

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' SAR'
    );
};
</script>

<style>
@page {
    size: A4 portrait;
    margin: 15mm 10mm 12mm 10mm;
}

@media print {
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }

    th,
    td {
        border: 1px solid #9ca3af !important;
        padding: 5px 6px !important;
        text-align: center !important;
    }

    th {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
