<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-600 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $t('hr.payroll.payslip_details') }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ payslip?.payroll_run?.name }}
                            </p>
                        </div>
                    </div>

                    <button
                        @click="print"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                        {{ $t('common.print') }}
                    </button>
                </div>
            </div>

            <!-- Period -->
            <div
                class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 text-sm"
            >
                <span class="text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.period') }}:</span>
                <span class="font-bold text-gray-900 dark:text-white" dir="ltr">
                    {{ payslip?.payroll_run?.period_start }} —
                    {{ payslip?.payroll_run?.period_end }}
                </span>
            </div>

            <!-- Earnings -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700"
                >
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        {{ $t('hr.payroll.earnings') }}
                    </h2>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="flex justify-between items-center px-6 py-3">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('hr.payroll.base_salary') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ formatCurrency(payslip?.base_salary) }}
                        </span>
                    </div>

                    <div
                        v-for="(row, index) in allowanceRows"
                        :key="index"
                        class="flex justify-between items-center px-6 py-3"
                    >
                        <span class="text-gray-600 dark:text-gray-400">{{ row.label }}</span>
                        <span class="font-medium text-green-600 dark:text-green-400">
                            +{{ formatCurrency(row.amount) }}
                        </span>
                    </div>

                    <div
                        class="flex justify-between items-center px-6 py-3 bg-gray-50 dark:bg-gray-700/30"
                    >
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ $t('hr.payroll.total_earnings') }}
                        </span>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(totalEarnings) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Deductions -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700"
                >
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        {{ $t('hr.payroll.deductions') }}
                    </h2>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="flex justify-between items-center px-6 py-3">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('hr.payroll.gosi') }}
                        </span>
                        <span class="font-medium text-red-600 dark:text-red-400">
                            -{{ formatCurrency(payslip?.gosi_amount) }}
                        </span>
                    </div>

                    <div
                        v-for="(row, index) in deductionRows"
                        :key="index"
                        class="flex justify-between items-center px-6 py-3"
                    >
                        <span class="text-gray-600 dark:text-gray-400">{{ row.label }}</span>
                        <span class="font-medium text-red-600 dark:text-red-400">
                            -{{ formatCurrency(row.amount) }}
                        </span>
                    </div>

                    <div
                        class="flex justify-between items-center px-6 py-3 bg-gray-50 dark:bg-gray-700/30"
                    >
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ $t('hr.payroll.total_deductions') }}
                        </span>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(totalDeductions) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Net -->
            <div
                class="bg-gradient-to-r from-amber-600 to-orange-600 rounded-2xl p-6 shadow-lg shadow-amber-500/20"
            >
                <div class="flex justify-between items-center">
                    <span class="text-white font-bold text-lg">
                        {{ $t('hr.payroll.net_salary') }}
                    </span>
                    <span class="text-white font-black text-3xl">
                        {{ formatCurrency(payslip?.net_salary) }}
                    </span>
                </div>
            </div>

            <!-- Notes -->
            <div
                v-if="payslip?.notes"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">
                    {{ $t('common.notes') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ payslip.notes }}</p>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';

const props = defineProps({
    payslip: Object,
});

const allowanceRows = computed(() => {
    const breakdown = props.payslip?.allowances_breakdown || {};
    return Object.entries(breakdown).map(([key, value]) => ({
        label: key,
        amount: Number(value) || 0,
    }));
});

const deductionRows = computed(() => {
    const breakdown = props.payslip?.deductions_breakdown || {};
    return Object.entries(breakdown).map(([key, value]) => ({
        label: key,
        amount: Number(value) || 0,
    }));
});

const totalEarnings = computed(() => {
    const base = Number(props.payslip?.base_salary) || 0;
    return base + (Number(props.payslip?.total_allowances) || 0);
});

const totalDeductions = computed(() => {
    const gosi = Number(props.payslip?.gosi_amount) || 0;
    return gosi + (Number(props.payslip?.total_deductions) || 0);
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
</script>
