<template>
    <div class="template-customers-report print-sheet">
        <!-- Standard Report Header -->
        <StandardReportHeader
            :title="$t('reports.customers_report.official_report')"
            :centerData="centerData"
        />

        <!-- Financial Totals Summary Box -->
        <div
            v-if="summaryKpis"
            class="grid grid-cols-6 gap-2 p-3 bg-gray-100 rounded-lg text-center mb-4 border border-gray-300 text-xs"
        >
            <div>
                <div class="text-gray-500">
                    {{ $t('reports.customers_report.total_records') }}
                </div>
                <div class="font-bold font-mono">{{ summaryKpis.total_records ?? 0 }}</div>
            </div>
            <div>
                <div class="text-gray-500">
                    {{ $t('reports.customers_report.total_payment_due') }}
                </div>
                <div class="font-bold font-mono">
                    {{ formatMoney(summaryKpis.total_payment_due) }}
                </div>
            </div>
            <div>
                <div class="text-gray-500">
                    {{ $t('reports.customers_report.total_amount_paid') }}
                </div>
                <div class="font-bold font-mono">
                    {{ formatMoney(summaryKpis.total_amount_paid) }}
                </div>
            </div>
            <div>
                <div class="text-gray-500">
                    {{ $t('reports.customers_report.total_amount_adjusted') }}
                </div>
                <div class="font-bold font-mono">
                    {{ formatMoney(summaryKpis.total_amount_adjusted) }}
                </div>
            </div>
            <div>
                <div class="text-gray-500">
                    {{ $t('reports.customers_report.total_payment_remaining') }}
                </div>
                <div class="font-bold font-mono">
                    {{ formatMoney(summaryKpis.total_payment_remaining) }}
                </div>
            </div>
            <div>
                <div class="text-gray-500">
                    {{ $t('reports.customers_report.total_bad_debt') }}
                </div>
                <div class="font-bold font-mono text-rose-700">
                    {{ formatMoney(summaryKpis.total_bad_debt) }}
                </div>
            </div>
        </div>

        <!-- Print Table -->
        <table class="w-full text-[10px] text-start border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100 font-bold border-b border-gray-300">
                    <th class="p-1.5 border-e border-gray-300 text-start">#</th>
                    <th class="p-1.5 border-e border-gray-300 text-start">
                        {{ $t('reports.customers_report.work_order_no') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-start">
                        {{ $t('reports.customers_report.customer_party') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-start">
                        {{ $t('reports.customers_report.vehicle_details') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-start">
                        {{ $t('reports.customers_report.check_in_date') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-end">
                        {{ $t('reports.customers_report.payment_due') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-end">
                        {{ $t('reports.customers_report.amount_paid') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-end">
                        {{ $t('reports.customers_report.amount_adjusted') }}
                    </th>
                    <th class="p-1.5 border-e border-gray-300 text-end">
                        {{ $t('reports.customers_report.payment_remaining') }}
                    </th>
                    <th class="p-1.5 text-center">
                        {{ $t('reports.customers_report.payment_status') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, idx) in tableRows"
                    :key="row.id || idx"
                    class="border-b border-gray-200"
                >
                    <td class="p-1.5 border-e border-gray-300 font-mono">{{ idx + 1 }}</td>
                    <td class="p-1.5 border-e border-gray-300 font-mono font-bold">
                        {{ row.code }}
                    </td>
                    <td class="p-1.5 border-e border-gray-300">
                        {{ row.customer?.name || '-' }} ({{ row.customer?.phone || '-' }})
                    </td>
                    <td class="p-1.5 border-e border-gray-300">
                        {{ row.vehicle?.name || '-' }} [{{ row.vehicle?.plate_number || '-' }}]
                    </td>
                    <td class="p-1.5 border-e border-gray-300 font-mono">
                        {{ row.entry_date }}
                    </td>
                    <td class="p-1.5 border-e border-gray-300 text-end font-mono">
                        {{ formatMoney(row.payment_due) }}
                    </td>
                    <td class="p-1.5 border-e border-gray-300 text-end font-mono">
                        {{ formatMoney(row.amount_paid) }}
                    </td>
                    <td class="p-1.5 border-e border-gray-300 text-end font-mono">
                        {{ formatMoney(row.amount_adjusted) }}
                    </td>
                    <td class="p-1.5 border-e border-gray-300 text-end font-mono font-bold">
                        {{ formatMoney(row.payment_remaining) }}
                    </td>
                    <td class="p-1.5 text-center">
                        {{
                            row.payment_status
                                ? $t(`reports.customers_report.${row.payment_status}`)
                                : '-'
                        }}
                    </td>
                </tr>
                <tr v-if="!tableRows.length">
                    <td colspan="10" class="p-4 text-center text-gray-500">
                        {{ $t('common.no_data') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Standard Report Footer -->
        <StandardReportFooter :centerData="centerData" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    centerData: {
        type: Object,
        default: () => ({}),
    },
    documentSettings: {
        type: Object,
        default: () => ({}),
    },
    visualSettings: {
        type: Object,
        default: () => ({}),
    },
    previewMode: {
        type: Boolean,
        default: false,
    },
});

const summaryKpis = computed(() => props.data?.kpis || {});
const tableRows = computed(() => props.data?.records || []);

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};
</script>
