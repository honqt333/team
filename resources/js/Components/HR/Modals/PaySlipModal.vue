<template>
    <BaseModal
        :show="!!show"
        size="lg"
        :title="$t('hr.financial.payroll.print_slip')"
        @close="$emit('close')"
    >
        <!-- Print Content -->
        <div ref="printContent" class="print:p-4">
            <!-- Standard Report Header (for print) -->
            <StandardReportHeader class="hidden print:block mb-6" :title="$t('hr.payslip_title')" />

            <!-- Employee Info -->
            <div
                class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 mb-6 print:bg-white print:border print:border-gray-300"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">{{ $t('hr.employees.name') }}</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ employee?.name_ar }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">
                            {{ $t('hr.employees.employee_number') }}
                        </p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ employee?.employee_number }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">
                            {{ $t('hr.financial.payroll.period') }}
                        </p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ payrollItem?.period_label }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">
                            {{ $t('hr.employees.department') }}
                        </p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ employee?.department?.name_ar || '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Salary Breakdown -->
            <div class="space-y-4">
                <!-- Base Salary -->
                <div
                    class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <span class="text-gray-700 dark:text-gray-300">
                        {{ $t('hr.financial.payroll.base_salary') }}
                    </span>
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ formatMoney(payrollItem?.base_salary) }}
                    </span>
                </div>

                <!-- GOSI -->
                <div
                    class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <span class="text-gray-700 dark:text-gray-300">
                        {{ $t('hr.financial.payroll.gosi_rate') }} ({{ payrollItem?.gosi_rate }}%)
                    </span>
                    <span class="font-medium text-red-600 dark:text-red-400">
                        -{{ formatMoney(payrollItem?.gosi_amount) }}
                    </span>
                </div>

                <!-- Allowances Section -->
                <div v-if="allowancesBreakdown?.length" class="mt-4">
                    <h4 class="font-semibold text-emerald-700 dark:text-emerald-400 mb-2">
                        {{ $t('hr.settings.allowances.title') }}
                    </h4>
                    <div
                        v-for="item in allowancesBreakdown"
                        :key="item.name"
                        class="flex justify-between items-center py-1 ps-4"
                    >
                        <span class="text-gray-600 dark:text-gray-400 text-sm">
                            {{ item.name }}
                        </span>
                        <span class="text-emerald-600 dark:text-emerald-400 text-sm">
                            +{{ formatMoney(item.amount) }}
                        </span>
                    </div>
                    <div
                        class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-600 mt-2"
                    >
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('hr.employees.total_allowances') }}
                        </span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                            +{{ formatMoney(payrollItem?.total_allowances) }}
                        </span>
                    </div>
                </div>

                <!-- Deductions Section -->
                <div v-if="deductionsBreakdown?.length" class="mt-4">
                    <h4 class="font-semibold text-red-700 dark:text-red-400 mb-2">
                        {{ $t('hr.settings.deductions.title') }}
                    </h4>
                    <div
                        v-for="item in deductionsBreakdown"
                        :key="item.name"
                        class="flex justify-between items-center py-1 ps-4"
                    >
                        <span class="text-gray-600 dark:text-gray-400 text-sm">
                            {{ item.name }}
                        </span>
                        <span class="text-red-600 dark:text-red-400 text-sm">
                            -{{ formatMoney(item.amount) }}
                        </span>
                    </div>
                    <div
                        class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-600 mt-2"
                    >
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('hr.employees.total_deductions') }}
                        </span>
                        <span class="font-semibold text-red-600 dark:text-red-400">
                            -{{ formatMoney(payrollItem?.total_deductions) }}
                        </span>
                    </div>
                </div>

                <!-- Net Salary -->
                <div class="mt-6 pt-4 border-t-2 border-action-primary/25">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('hr.financial.payroll.net_salary') }}
                        </span>
                        <span class="text-2xl font-bold text-action-primary">
                            {{ formatMoney(payrollItem?.net_salary) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Unified Footer Info -->
            <StandardReportFooter class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700" />
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full print:hidden">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.close') }}
                </button>
                <button
                    type="button"
                    @click="printSlip"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all cursor-pointer"
                >
                    🖨️ {{ $t('common.print') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const props = defineProps({
    show: Boolean,
    payrollItem: Object,
    employee: Object,
});

defineEmits(['close']);

const printContent = ref(null);

const page = usePage();
const companyName = computed(() => page.props.tenant?.name || t('common.company'));

const allowancesBreakdown = computed(() => {
    return props.payrollItem?.allowances_breakdown || [];
});

const deductionsBreakdown = computed(() => {
    return props.payrollItem?.deductions_breakdown || [];
});

function formatMoney(value) {
    return parseFloat(value || 0).toLocaleString('ar-SA-u-nu-latn', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
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
    [ref='printContent'],
    [ref='printContent'] * {
        visibility: visible;
    }
    [ref='printContent'] {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
