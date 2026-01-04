<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 print:bg-white print:static" @click.self="$emit('close')">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto print:max-w-none print:max-h-none print:shadow-none print:rounded-none">
                <!-- Header (Hidden in print - use PrintHeader) -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between print:hidden">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('hr.financial.payroll.print_slip') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button @click="printSlip" class="px-4 py-2 bg-violet-600 text-white rounded-lg text-sm font-medium hover:bg-violet-700">
                            🖨️ {{ $t('common.print') }}
                        </button>
                        <button @click="$emit('close')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Print Content -->
                <div ref="printContent" class="p-6 print:p-4">
                    <!-- Logo & Company Info (for print) -->
                    <div class="hidden print:block text-center mb-6 border-b pb-4">
                        <h1 class="text-xl font-bold">{{ companyName }}</h1>
                        <p class="text-gray-600">كشف راتب</p>
                    </div>

                    <!-- Employee Info -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 mb-6 print:bg-white print:border print:border-gray-300">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">{{ $t('hr.employees.name') }}</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ employee?.name_ar }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">{{ $t('hr.employees.employee_number') }}</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ employee?.employee_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">{{ $t('hr.financial.payroll.period') }}</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ payrollItem?.period_label }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">{{ $t('hr.employees.department') }}</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ employee?.department?.name_ar || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Breakdown -->
                    <div class="space-y-4">
                        <!-- Base Salary -->
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">{{ $t('hr.financial.payroll.base_salary') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ formatMoney(payrollItem?.base_salary) }}</span>
                        </div>

                        <!-- GOSI -->
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $t('hr.financial.payroll.gosi_rate') }} ({{ payrollItem?.gosi_rate }}%)
                            </span>
                            <span class="font-medium text-red-600 dark:text-red-400">-{{ formatMoney(payrollItem?.gosi_amount) }}</span>
                        </div>

                        <!-- Allowances Section -->
                        <div v-if="allowancesBreakdown?.length" class="mt-4">
                            <h4 class="font-semibold text-emerald-700 dark:text-emerald-400 mb-2">{{ $t('hr.settings.allowances.title') }}</h4>
                            <div v-for="item in allowancesBreakdown" :key="item.name" class="flex justify-between items-center py-1 ps-4">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">{{ item.name }}</span>
                                <span class="text-emerald-600 dark:text-emerald-400 text-sm">+{{ formatMoney(item.amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-600 mt-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $t('hr.employees.total_allowances') }}</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">+{{ formatMoney(payrollItem?.total_allowances) }}</span>
                            </div>
                        </div>

                        <!-- Deductions Section -->
                        <div v-if="deductionsBreakdown?.length" class="mt-4">
                            <h4 class="font-semibold text-red-700 dark:text-red-400 mb-2">{{ $t('hr.settings.deductions.title') }}</h4>
                            <div v-for="item in deductionsBreakdown" :key="item.name" class="flex justify-between items-center py-1 ps-4">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">{{ item.name }}</span>
                                <span class="text-red-600 dark:text-red-400 text-sm">-{{ formatMoney(item.amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-600 mt-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $t('hr.employees.total_deductions') }}</span>
                                <span class="font-semibold text-red-600 dark:text-red-400">-{{ formatMoney(payrollItem?.total_deductions) }}</span>
                            </div>
                        </div>

                        <!-- Net Salary -->
                        <div class="mt-6 pt-4 border-t-2 border-violet-200 dark:border-violet-800">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('hr.financial.payroll.net_salary') }}</span>
                                <span class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ formatMoney(payrollItem?.net_salary) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500">
                        <div class="flex justify-between">
                            <span>{{ $t('hr.financial.payroll.created_by') }}: {{ payrollItem?.created_by?.name || '-' }}</span>
                            <span>{{ $t('common.date') }}: {{ payrollItem?.created_at ? new Date(payrollItem.created_at).toLocaleDateString('ar-SA') : '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    payrollItem: Object,
    employee: Object,
});

defineEmits(['close']);

const printContent = ref(null);

const page = usePage();
const companyName = computed(() => page.props.tenant?.name || 'الشركة');

const allowancesBreakdown = computed(() => {
    return props.payrollItem?.allowances_breakdown || [];
});

const deductionsBreakdown = computed(() => {
    return props.payrollItem?.deductions_breakdown || [];
});

function formatMoney(value) {
    return parseFloat(value || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function printSlip() {
    window.print();
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print\:block {
        display: block !important;
    }
    [ref="printContent"], [ref="printContent"] * {
        visibility: visible;
    }
    [ref="printContent"] {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
