<template>
    <div class="space-y-6">
        <!-- Salary Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Base Salary -->
            <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-white/80 text-sm">{{ $t('hr.financial.payroll.base_salary') }}</span>
                    <button
                        @click="showEditSalary = true"
                        class="p-1.5 bg-white/20 hover:bg-white/30 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
                <p class="text-3xl font-bold">{{ (employee.base_salary || 0).toLocaleString() }}</p>
                <p class="text-white/60 text-xs mt-1">{{ $t('common.currency') }} / {{ $t('hr.employees.monthly') }}</p>
            </div>

            <!-- Total Allowances -->
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-5 text-white shadow-lg">
                <p class="text-white/80 text-sm mb-2">{{ $t('hr.employees.total_allowances') }}</p>
                <p class="text-3xl font-bold">+{{ totalAllowances.toLocaleString() }}</p>
                <p class="text-white/60 text-xs mt-1">{{ employee.allowances?.length || 0 }} {{ $t('hr.settings.allowances.singular') }}</p>
            </div>

            <!-- Total Deductions -->
            <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl p-5 text-white shadow-lg">
                <p class="text-white/80 text-sm mb-2">{{ $t('hr.employees.total_deductions') }}</p>
                <p class="text-3xl font-bold">-{{ totalDeductions.toLocaleString() }}</p>
                <p class="text-white/60 text-xs mt-1">{{ employee.deductions?.length || 0 }} {{ $t('hr.settings.deductions.singular') }}</p>
            </div>

            <!-- Net Salary -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 text-white shadow-lg">
                <p class="text-white/80 text-sm mb-2">{{ $t('hr.financial.payroll.net_salary') }}</p>
                <p class="text-3xl font-bold">{{ netSalary.toLocaleString() }}</p>
                <p class="text-white/60 text-xs mt-1">{{ $t('common.currency') }}</p>
            </div>
        </div>

        <!-- GOSI Info -->
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-full flex items-center justify-center">
                    <span class="text-lg">🏛️</span>
                </div>
                <div>
                    <p class="font-medium text-amber-900 dark:text-amber-200">{{ $t('hr.financial.payroll.gosi_rate') }}</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ employee.gosi_rate || 9.75 }}% = {{ gosiAmount.toLocaleString() }} {{ $t('common.currency') }}</p>
                </div>
            </div>
        </div>

        <!-- Next Payment Preview -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center">
                    <span class="text-lg">📅</span>
                </div>
                <div>
                    <h4 class="font-semibold text-blue-900 dark:text-blue-200">{{ $t('hr.financial.payroll.next_payment') }}</h4>
                    <p class="text-sm text-blue-600 dark:text-blue-400">{{ nextPaymentDate }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ $t('hr.financial.payroll.expected_amount') }}</span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ netSalary.toLocaleString() }} {{ $t('common.currency') }}</span>
                </div>
            </div>
        </div>

        <!-- Allowances & Deductions Lists -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Allowances -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-emerald-50 dark:bg-emerald-900/20">
                    <h3 class="font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                        💰 {{ $t('hr.settings.allowances.title') }}
                    </h3>
                    <button
                        @click="$emit('manageAllowances')"
                        class="px-3 py-1.5 text-xs font-medium bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
                    >
                        {{ $t('common.manage') }}
                    </button>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div 
                        v-for="allowance in employee.allowances" 
                        :key="allowance.id"
                        class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ allowance.name_ar }}</p>
                            <p class="text-xs text-gray-500">
                                {{ allowance.type === 'percentage' ? `${allowance.pivot?.custom_amount || allowance.amount}% من الراتب` : 'مبلغ ثابت' }}
                            </p>
                        </div>
                        <span class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                            +{{ getAllowanceAmount(allowance).toLocaleString() }}
                        </span>
                    </div>
                    <div v-if="!employee.allowances?.length" class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <span class="text-3xl mb-2 block">💰</span>
                        {{ $t('hr.employees.no_allowances') }}
                    </div>
                </div>
            </div>

            <!-- Deductions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-red-50 dark:bg-red-900/20">
                    <h3 class="font-semibold text-red-800 dark:text-red-300 flex items-center gap-2">
                        📉 {{ $t('hr.settings.deductions.title') }}
                    </h3>
                    <button
                        @click="$emit('manageDeductions')"
                        class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        {{ $t('common.manage') }}
                    </button>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div 
                        v-for="deduction in employee.deductions" 
                        :key="deduction.id"
                        class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ deduction.name_ar }}</p>
                            <p class="text-xs text-gray-500">
                                {{ deduction.type === 'percentage' ? `${deduction.pivot?.custom_amount || deduction.amount}% من الراتب` : 'مبلغ ثابت' }}
                            </p>
                        </div>
                        <span class="text-lg font-semibold text-red-600 dark:text-red-400">
                            -{{ getDeductionAmount(deduction).toLocaleString() }}
                        </span>
                    </div>
                    <div v-if="!employee.deductions?.length" class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <span class="text-3xl mb-2 block">📉</span>
                        {{ $t('hr.employees.no_deductions') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Salary Modal -->
        <Teleport to="body">
            <div v-if="showEditSalary" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showEditSalary = false">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('hr.financial.payroll.base_salary') }}</h3>
                    <form @submit.prevent="submitSalary">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('hr.financial.payroll.base_salary') }} ({{ $t('common.currency') }})
                                </label>
                                <input
                                    v-model="salaryForm.base_salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('hr.financial.payroll.gosi_rate') }} (%)
                                </label>
                                <input
                                    v-model="salaryForm.gosi_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showEditSalary = false" class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="salaryForm.processing"
                                class="px-5 py-2.5 bg-violet-600 text-white rounded-xl font-medium disabled:opacity-50"
                            >
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    employee: Object,
    allAllowances: Array,
    allDeductions: Array,
});

defineEmits(['manageAllowances', 'manageDeductions']);

const showEditSalary = ref(false);

const salaryForm = useForm({
    base_salary: props.employee.base_salary || 0,
    gosi_rate: props.employee.gosi_rate || 9.75,
});

watch(() => props.employee, (newVal) => {
    salaryForm.base_salary = newVal.base_salary || 0;
    salaryForm.gosi_rate = newVal.gosi_rate || 9.75;
}, { deep: true });

function submitSalary() {
    salaryForm.put(route('app.hr.employees.financial-info.update', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditSalary.value = false;
        },
    });
}

// Calculations
const gosiAmount = computed(() => {
    const rate = parseFloat(props.employee.gosi_rate) || 0;
    const salary = parseFloat(props.employee.base_salary) || 0;
    return Math.round((salary * rate) / 100 * 100) / 100;
});

// Get calculation base for percentage calculations
function getCalculationBase(calculationBase) {
    const baseSalary = parseFloat(props.employee.base_salary) || 0;
    switch (calculationBase) {
        case 'gross_salary':
            return baseSalary + totalAllowances.value;
        case 'monthly_contribution':
        case 'base_salary':
        default:
            return baseSalary;
    }
}

function getAllowanceAmount(allowance) {
    const amount = parseFloat(allowance.pivot?.custom_amount ?? allowance.amount) || 0;
    if (allowance.type === 'percentage') {
        const base = getCalculationBase(allowance.calculation_base);
        return Math.round((base * amount) / 100 * 100) / 100;
    }
    return amount;
}

function getDeductionAmount(deduction) {
    const amount = parseFloat(deduction.pivot?.custom_amount ?? deduction.amount) || 0;
    if (deduction.type === 'percentage') {
        const base = getCalculationBase(deduction.calculation_base);
        return Math.round((base * amount) / 100 * 100) / 100;
    }
    return amount;
}

const totalAllowances = computed(() => {
    if (!props.employee.allowances?.length) return 0;
    return props.employee.allowances.reduce((sum, a) => sum + getAllowanceAmount(a), 0);
});

const totalDeductions = computed(() => {
    if (!props.employee.deductions?.length) return 0;
    return props.employee.deductions.reduce((sum, d) => sum + getDeductionAmount(d), 0);
});

const netSalary = computed(() => {
    const baseSalary = parseFloat(props.employee.base_salary) || 0;
    const gross = baseSalary + totalAllowances.value;
    const deductions = totalDeductions.value + gosiAmount.value;
    return Math.round((gross - deductions) * 100) / 100;
});

const nextPaymentDate = computed(() => {
    const now = new Date();
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    return lastDay.toLocaleDateString('ar-SA');
});
</script>
