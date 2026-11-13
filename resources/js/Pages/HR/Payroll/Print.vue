<template>
    <Head :title="$t('hr.payroll.print_report')" />

    <StandardReportLayout
        :title="$t('hr.payroll.print_title')"
        :subtitle="formatPeriod(payrollRun.period_start)"
        :total-count="payrollRun.items?.length"
        :total-count-label="$t('hr.employees.employee')"
        :custom-company="tenant"
        :on-print="print"
        default-orientation="landscape"
        back-route="app.hr.payroll.index"
    >
        <!-- Table -->
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-900">
                    <th
                        class="py-2.5 px-2 text-start font-bold uppercase w-8 border border-gray-300"
                    >
                        #
                    </th>
                    <th class="py-2.5 px-2 text-start font-bold uppercase border border-gray-300">
                        {{ $t('common.employee') }}
                    </th>
                    <th class="py-2.5 px-2 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.payroll.base_salary') }}
                    </th>
                    <th class="py-2.5 px-2 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.payroll.allowances') }}
                    </th>
                    <th class="py-2.5 px-2 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.payroll.deductions') }}
                    </th>
                    <th class="py-2.5 px-2 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.payroll.net_salary') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr
                    v-for="(item, index) in payrollRun.items"
                    :key="item.id"
                    class="break-inside-avoid hover:bg-gray-50"
                >
                    <td
                        class="py-2.5 px-2 text-gray-500 font-mono text-center border border-gray-200"
                    >
                        {{ index + 1 }}
                    </td>
                    <td class="py-2.5 px-2 border border-gray-200">
                        <div class="font-bold">{{ item.employee?.name_ar }}</div>
                        <div class="text-xs text-gray-500 font-mono">
                            {{ item.employee?.job_title?.name_ar }}
                        </div>
                    </td>
                    <td class="py-2.5 px-2 text-center font-mono border border-gray-200">
                        {{ formatAmount(item.base_salary) }}
                    </td>
                    <td
                        class="py-2.5 px-2 text-center font-mono text-emerald-600 border border-gray-200"
                    >
                        {{ formatAmount(item.total_allowances) }}
                    </td>
                    <td
                        class="py-2.5 px-2 text-center font-mono text-red-600 border border-gray-200"
                    >
                        {{ formatAmount(item.total_deductions) }}
                    </td>
                    <td class="py-2.5 px-2 text-center font-bold font-mono border border-gray-200">
                        {{ formatAmount(item.net_salary) }}
                    </td>
                </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-900 font-bold bg-gray-50">
                <tr>
                    <td colspan="2" class="py-2.5 px-2 border border-gray-300">
                        {{ $t('common.total') }}
                    </td>
                    <td class="py-2.5 px-2 text-center font-mono border border-gray-300">
                        {{ formatAmount(totalSalaries) }}
                    </td>
                    <td
                        class="py-2.5 px-2 text-center text-emerald-600 font-mono border border-gray-300"
                    >
                        {{ formatAmount(totalAllowances) }}
                    </td>
                    <td
                        class="py-2.5 px-2 text-center text-red-600 font-mono border border-gray-300"
                    >
                        {{ formatAmount(totalDeductions) }}
                    </td>
                    <td class="py-2.5 px-2 text-center text-base font-mono border border-gray-300">
                        {{ formatAmount(totalNet) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </StandardReportLayout>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const props = defineProps({
    payrollRun: Object,
    tenant: Object,
});

const totalSalaries = computed(
    () => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.base_salary || 0), 0) || 0
);
const totalAllowances = computed(
    () =>
        props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.total_allowances || 0), 0) ||
        0
);
const totalDeductions = computed(
    () =>
        props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.total_deductions || 0), 0) ||
        0
);
const totalNet = computed(
    () => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.net_salary || 0), 0) || 0
);

function print() {
    printDocument();
}

function formatPeriod(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('ar-SA-u-nu-latn', {
        month: 'long',
        year: 'numeric',
    });
}

function formatAmount(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
}
</script>
