<template>
    <div class="p-8 max-w-3xl mx-auto bg-white min-h-screen text-gray-900" dir="rtl">
        <Head :title="$t('hr.payroll.print_payslip')" />

        <!-- Print Controls -->
        <div class="print:hidden mb-8 flex justify-between items-center">
            <button
                @click="print"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                    />
                </svg>
                {{ $t('common.print') }}
            </button>
            <button
                @click="close"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium"
            >
                {{ $t('common.close') }}
            </button>
        </div>

        <!-- Header -->
        <div class="mb-8 border-b-2 border-gray-900 pb-6">
            <div class="grid grid-cols-3 items-start">
                <div class="text-right">
                    <h1 class="text-xl font-bold">{{ tenant?.name }}</h1>
                    <p class="text-sm text-gray-500">{{ payrollItem.employee?.center?.name }}</p>
                </div>

                <div class="flex justify-center">
                    <div v-if="tenant?.logo_url" class="w-20 h-20">
                        <img :src="tenant.logo_url" class="w-full h-full object-contain" />
                    </div>
                </div>

                <div class="text-left">
                    <p class="text-xs text-gray-400 font-bold" dir="ltr">
                        {{ new Date().toLocaleDateString('en-GB').split('/').reverse().join('/') }}
                    </p>
                    <div class="mt-1 text-sm font-bold text-gray-900">
                        {{ payrollItem.payroll_run?.name }}
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <h2 class="text-xl font-bold uppercase tracking-widest">
                    {{ $t('hr.payroll.print_payslip') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $t('hr.payroll.period') }}: {{ payrollItem.payroll_run?.period_start }} —
                    {{ payrollItem.payroll_run?.period_end }}
                </p>
            </div>
        </div>

        <!-- Employee -->
        <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
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
        <table class="w-full text-sm mb-6">
            <thead>
                <tr class="border-b-2 border-gray-900">
                    <th class="text-right py-2 font-bold">{{ $t('hr.payroll.earnings') }}</th>
                    <th class="text-left py-2 font-bold w-32">{{ $t('hr.payroll.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200">
                    <td class="py-2">{{ $t('hr.payroll.base_salary') }}</td>
                    <td class="py-2 text-left font-medium">
                        {{ formatCurrency(payrollItem.base_salary) }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in allowanceRows"
                    :key="index"
                    class="border-b border-gray-200"
                >
                    <td class="py-2">{{ row.label }}</td>
                    <td class="py-2 text-left font-medium">{{ formatCurrency(row.amount) }}</td>
                </tr>
                <tr class="border-b-2 border-gray-900">
                    <td class="py-2 font-bold">{{ $t('hr.payroll.total_earnings') }}</td>
                    <td class="py-2 text-left font-bold">
                        {{ formatCurrency(totalEarnings) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Deductions -->
        <table class="w-full text-sm mb-8">
            <thead>
                <tr class="border-b-2 border-gray-900">
                    <th class="text-right py-2 font-bold">{{ $t('hr.payroll.deductions') }}</th>
                    <th class="text-left py-2 font-bold w-32">{{ $t('hr.payroll.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200">
                    <td class="py-2">{{ $t('hr.payroll.gosi') }}</td>
                    <td class="py-2 text-left font-medium">
                        {{ formatCurrency(payrollItem.gosi_amount) }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in deductionRows"
                    :key="index"
                    class="border-b border-gray-200"
                >
                    <td class="py-2">{{ row.label }}</td>
                    <td class="py-2 text-left font-medium">{{ formatCurrency(row.amount) }}</td>
                </tr>
                <tr class="border-b-2 border-gray-900">
                    <td class="py-2 font-bold">{{ $t('hr.payroll.total_deductions') }}</td>
                    <td class="py-2 text-left font-bold">
                        {{ formatCurrency(totalDeductions) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Net -->
        <div class="flex justify-between items-center bg-gray-100 px-6 py-4 rounded-lg">
            <span class="text-lg font-bold">{{ $t('hr.payroll.net_salary') }}</span>
            <span class="text-2xl font-black text-emerald-700">
                {{ formatCurrency(payrollItem.net_salary) }}
            </span>
        </div>

        <!-- Notes -->
        <div v-if="payrollItem.notes" class="mt-8 text-sm">
            <h3 class="font-bold mb-1">{{ $t('common.notes') }}</h3>
            <p class="text-gray-600">{{ payrollItem.notes }}</p>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-4 border-t border-gray-300 text-center text-xs text-gray-500">
            <p>{{ $t('hr.payroll.print_footer') }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    payrollItem: Object,
});

const { t } = useI18n();
const page = usePage();

const tenant = computed(() => page.props.tenant);

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

function formatCurrency(value) {
    return new Intl.NumberFormat('en-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function print() {
    window.print();
}

function close() {
    router.get(route('app.hr.payroll.index'));
}
</script>
