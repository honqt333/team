<template>
    <StandardReportLayout
        :title="$t('hr.payroll.print_payslip')"
        :subtitle="payrollPeriod"
        back-route="app.hr.payroll.index"
    >
        <template #header-extra>
            <div v-if="payrollItem.payroll_run?.name" class="text-xs font-bold text-gray-700">
                {{ payrollItem.payroll_run?.name }}
            </div>
        </template>

        <!-- Employee Info -->
        <div
            class="grid grid-cols-2 gap-4 mb-6 text-sm bg-gray-50 p-4 rounded-lg border border-gray-200"
        >
            <div class="flex justify-between border-b border-gray-200 pb-2">
                <span class="text-gray-500">{{ $t('hr.employees.employee') }}</span>
                <span class="font-bold">{{ payrollItem.employee?.name }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-200 pb-2">
                <span class="text-gray-500">{{ $t('hr.employees.employee_number') }}</span>
                <span class="font-bold">{{ payrollItem.employee?.employee_number }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-200 pb-2">
                <span class="text-gray-500">{{ $t('hr.employees.job_title') }}</span>
                <span class="font-bold">{{ payrollItem.employee?.job_title?.name }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-200 pb-2">
                <span class="text-gray-500">{{ $t('hr.employees.department') }}</span>
                <span class="font-bold">{{ payrollItem.employee?.department?.name }}</span>
            </div>
        </div>

        <!-- Earnings -->
        <table class="w-full text-sm mb-6 border border-gray-300">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-300">
                    <th class="text-right py-2 px-3 font-bold">{{ $t('hr.payroll.earnings') }}</th>
                    <th class="text-left py-2 px-3 font-bold w-40">
                        {{ $t('hr.payroll.amount') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200">
                    <td class="py-2 px-3">{{ $t('hr.payroll.base_salary') }}</td>
                    <td class="py-2 px-3 text-left font-medium">
                        {{ formatCurrency(payrollItem.base_salary) }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in allowanceRows"
                    :key="index"
                    class="border-b border-gray-200"
                >
                    <td class="py-2 px-3">{{ row.label }}</td>
                    <td class="py-2 px-3 text-left font-medium">
                        {{ formatCurrency(row.amount) }}
                    </td>
                </tr>
                <tr class="bg-gray-50 font-bold border-t border-gray-300">
                    <td class="py-2 px-3">{{ $t('hr.payroll.total_earnings') }}</td>
                    <td class="py-2 px-3 text-left text-emerald-700">
                        {{ formatCurrency(totalEarnings) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Deductions -->
        <table class="w-full text-sm mb-6 border border-gray-300">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-300">
                    <th class="text-right py-2 px-3 font-bold">
                        {{ $t('hr.payroll.deductions') }}
                    </th>
                    <th class="text-left py-2 px-3 font-bold w-40">
                        {{ $t('hr.payroll.amount') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200">
                    <td class="py-2 px-3">{{ $t('hr.payroll.gosi') }}</td>
                    <td class="py-2 px-3 text-left font-medium">
                        {{ formatCurrency(payrollItem.gosi_amount) }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in deductionRows"
                    :key="index"
                    class="border-b border-gray-200"
                >
                    <td class="py-2 px-3">{{ row.label }}</td>
                    <td class="py-2 px-3 text-left font-medium">
                        {{ formatCurrency(row.amount) }}
                    </td>
                </tr>
                <tr class="bg-gray-50 font-bold border-t border-gray-300">
                    <td class="py-2 px-3">{{ $t('hr.payroll.total_deductions') }}</td>
                    <td class="py-2 px-3 text-left text-rose-700">
                        {{ formatCurrency(totalDeductions) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Net Salary Box -->
        <div
            class="flex justify-between items-center bg-gray-50 border border-gray-300 px-6 py-4 rounded-lg"
        >
            <span class="text-base font-bold text-gray-900">{{ $t('hr.payroll.net_salary') }}</span>
            <span class="text-2xl font-black text-emerald-700 font-mono">
                {{ formatCurrency(payrollItem.net_salary) }}
            </span>
        </div>

        <!-- Notes -->
        <div
            v-if="payrollItem.notes"
            class="mt-6 text-sm bg-amber-50/50 border border-amber-200 p-4 rounded-lg"
        >
            <h3 class="font-bold mb-1 text-gray-800">{{ $t('common.notes') }}</h3>
            <p class="text-gray-700 whitespace-pre-wrap">{{ payrollItem.notes }}</p>
        </div>
    </StandardReportLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';
import { usePrint } from '@/Composables/usePrint';

const props = defineProps({
    payrollItem: {
        type: Object,
        required: true,
    },
});

const { t } = useI18n();

const payrollPeriod = computed(() => {
    const start = props.payrollItem.payroll_run?.period_start;
    const end = props.payrollItem.payroll_run?.period_end;
    if (start && end) {
        return `${t('hr.payroll.period')}: ${start} — ${end}`;
    }
    return '';
});

const allowanceRows = computed(() => {
    const breakdown = props.payrollItem?.allowances_breakdown || {};
    return Object.entries(breakdown).map(([key, value]) => ({
        label: key,
        amount: Number(value) || 0,
    }));
});

const deductionRows = computed(() => {
    const breakdown = props.payrollItem?.deductions_breakdown || {};
    return Object.entries(breakdown).map(([key, value]) => ({
        label: key,
        amount: Number(value) || 0,
    }));
});

const totalEarnings = computed(() => {
    const base = Number(props.payrollItem?.base_salary) || 0;
    return base + (Number(props.payrollItem?.total_allowances) || 0);
});

const totalDeductions = computed(() => {
    const gosi = Number(props.payrollItem?.gosi_amount) || 0;
    return gosi + (Number(props.payrollItem?.total_deductions) || 0);
});

const { formatCurrency } = usePrint();
</script>
