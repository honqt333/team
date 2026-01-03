<template>
    <div class="space-y-6">
        <!-- Base Salary Card -->
        <div class="bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-violet-200 dark:border-violet-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.employees.base_salary') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.monthly') }}</p>
                </div>
                <div class="text-end">
                    <p class="text-3xl font-bold text-violet-600 dark:text-violet-400">
                        {{ employee.base_salary?.toLocaleString() || '0' }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('common.currency') }}</p>
                </div>
            </div>
        </div>

        <!-- Salary Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
                <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ $t('hr.employees.total_allowances') }}</p>
                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">+{{ totalAllowances.toLocaleString() }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
                <p class="text-sm text-red-600 dark:text-red-400">{{ $t('hr.employees.total_deductions') }}</p>
                <p class="text-2xl font-bold text-red-700 dark:text-red-300">-{{ totalDeductions.toLocaleString() }}</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                <p class="text-sm text-blue-600 dark:text-blue-400">{{ $t('hr.employees.net_salary') }}</p>
                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ netSalary.toLocaleString() }}</p>
            </div>
        </div>

        <!-- Allowances -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    💰 {{ $t('hr.settings.allowances.title') }}
                </h3>
                <button
                    @click="showAllowanceModal = true"
                    class="text-sm text-violet-600 dark:text-violet-400 hover:underline"
                >
                    {{ $t('common.manage') }}
                </button>
            </div>
            <div class="p-4">
                <div v-if="employee.allowances?.length" class="space-y-2">
                    <div 
                        v-for="allowance in employee.allowances" 
                        :key="allowance.id"
                        class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    >
                        <span class="text-gray-700 dark:text-gray-300">{{ allowance.name_ar }}</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">
                            +{{ getAllowanceAmount(allowance).toLocaleString() }}
                            {{ allowance.type === 'percentage' ? `(${allowance.pivot?.custom_amount || allowance.amount}%)` : '' }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                    {{ $t('hr.employees.no_allowances') }}
                </p>
            </div>
        </div>

        <!-- Deductions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    📉 {{ $t('hr.settings.deductions.title') }}
                </h3>
                <button
                    @click="showDeductionModal = true"
                    class="text-sm text-violet-600 dark:text-violet-400 hover:underline"
                >
                    {{ $t('common.manage') }}
                </button>
            </div>
            <div class="p-4">
                <div v-if="employee.deductions?.length" class="space-y-2">
                    <div 
                        v-for="deduction in employee.deductions" 
                        :key="deduction.id"
                        class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    >
                        <span class="text-gray-700 dark:text-gray-300">{{ deduction.name_ar }}</span>
                        <span class="font-medium text-red-600 dark:text-red-400">
                            -{{ getDeductionAmount(deduction).toLocaleString() }}
                            {{ deduction.type === 'percentage' ? `(${deduction.pivot?.custom_amount || deduction.amount}%)` : '' }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                    {{ $t('hr.employees.no_deductions') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    employee: Object,
    allAllowances: Array,
    allDeductions: Array,
});

const showAllowanceModal = ref(false);
const showDeductionModal = ref(false);

function getAllowanceAmount(allowance) {
    const amount = allowance.pivot?.custom_amount ?? allowance.amount;
    if (allowance.type === 'percentage') {
        return (props.employee.base_salary * amount) / 100;
    }
    return amount;
}

function getDeductionAmount(deduction) {
    const amount = deduction.pivot?.custom_amount ?? deduction.amount;
    if (deduction.type === 'percentage') {
        return (props.employee.base_salary * amount) / 100;
    }
    return amount;
}

const totalAllowances = computed(() => {
    return props.employee.allowances?.reduce((sum, a) => sum + getAllowanceAmount(a), 0) || 0;
});

const totalDeductions = computed(() => {
    return props.employee.deductions?.reduce((sum, d) => sum + getDeductionAmount(d), 0) || 0;
});

const netSalary = computed(() => {
    return (props.employee.base_salary || 0) + totalAllowances.value - totalDeductions.value;
});
</script>
