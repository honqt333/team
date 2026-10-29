<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('hr.payroll.details')"
                :subtitle="formatPeriod(payrollRun.period_start)"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-600"
                glowFrom="from-emerald-500"
                badgeBg="bg-emerald-50/50 dark:bg-emerald-900/30"
                badgeText="text-emerald-600 dark:text-emerald-400"
                badgeBorder="border-emerald-100/50 dark:border-emerald-800/30"
                badgeDot="bg-emerald-500"
            >
                <template #back>
                    <BackButton :href="route('app.hr.payroll.index')" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <!-- Other Payments Link -->
                    <Link
                        :href="route('app.hr.employees.index')"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors flex items-center gap-1.5"
                    >
                        <span>{{ $t('hr.financial.other_payments.title') }}</span>
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                    </Link>

                    <!-- Status Badge -->
                    <span
                        :class="getStatusClass(payrollRun.status)"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold border shadow-sm"
                    >
                        {{ $t(`hr.payroll.statuses.${payrollRun.status}`) }}
                    </span>

                    <!-- Actions -->
                    <div v-if="payrollRun.status === 'draft'" class="flex items-center gap-2">
                        <button
                            @click="regeneratePayroll"
                            :disabled="processing"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            <span>{{ $t('hr.payroll.regenerate') }}</span>
                        </button>
                        <button
                            @click="approvePayroll"
                            :disabled="processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span>{{ $t('hr.payroll.approve') }}</span>
                        </button>
                    </div>
                    <button
                        v-if="payrollRun.status === 'approved'"
                        @click="markAsPaid"
                        :disabled="processing"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <span>{{ $t('hr.payroll.mark_paid') }}</span>
                    </button>
                    <a
                        :href="route('app.hr.payroll.print', payrollRun.id)"
                        target="_blank"
                        class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                        <span>{{ $t('common.print') }}</span>
                    </a>
                </template>
            </PageHeader>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <div class="text-sm text-gray-500 mb-1">
                        {{ $t('hr.payroll.employees_count') }}
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ payrollRun.items?.length || 0 }}
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <div class="text-sm text-gray-500 mb-1">
                        {{ $t('hr.payroll.total_salaries') }}
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ formatCurrency(totalSalaries) }}
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <div class="text-sm text-gray-500 mb-1">
                        {{ $t('hr.payroll.total_deductions') }}
                    </div>
                    <div class="text-2xl font-bold text-red-600">
                        {{ formatCurrency(totalDeductions) }}
                    </div>
                </div>
                <div
                    class="bg-emerald-50 dark:bg-emerald-900/30 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800"
                >
                    <div class="text-sm text-emerald-600 dark:text-emerald-400 mb-1">
                        {{ $t('hr.payroll.total_net') }}
                    </div>
                    <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                        {{ formatCurrency(totalNet) }}
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white">
                        {{ $t('hr.payroll.items') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-start font-medium text-gray-600 dark:text-gray-300 align-middle text-center"
                                >
                                    {{ $t('common.employee') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300 align-middle"
                                >
                                    {{ $t('hr.payroll.base_salary') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300 align-middle"
                                >
                                    {{ $t('hr.payroll.allowances') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300 align-middle"
                                >
                                    {{ $t('hr.payroll.deductions') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300 align-middle"
                                >
                                    {{ $t('hr.payroll.gosi') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-medium text-emerald-600 dark:text-emerald-400 align-middle"
                                >
                                    {{ $t('hr.payroll.net_salary') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300 w-12 align-middle"
                                ></th>
                                <th
                                    class="px-4 py-3 text-end font-medium text-gray-600 dark:text-gray-300 w-16 align-middle text-center"
                                    v-if="payrollRun.status === 'draft'"
                                ></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="item in payrollRun.items"
                                :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 group"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ item.employee?.name_ar }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ item.employee?.job_title?.name_ar }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono align-middle">
                                    {{ formatCurrency(item.base_salary) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-mono text-emerald-600 align-middle"
                                >
                                    +{{ formatCurrency(item.total_allowances) }}
                                    <div
                                        v-if="item.allowances_breakdown?.other > 0"
                                        class="text-[10px] text-gray-400"
                                    >
                                        {{ $t('hr.financial.other_payments.title') }}:
                                        {{ formatCurrency(item.allowances_breakdown.other) }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-mono text-red-600 align-middle"
                                >
                                    -{{ formatCurrency(item.total_deductions) }}
                                    <div
                                        v-if="item.deductions_breakdown?.other > 0"
                                        class="text-[10px] text-gray-400"
                                    >
                                        {{ $t('hr.financial.other_payments.title') }}:
                                        {{ formatCurrency(item.deductions_breakdown.other) }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-mono text-orange-600 align-middle"
                                >
                                    -{{ formatCurrency(item.gosi_amount) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-bold text-emerald-700 dark:text-emerald-400 align-middle"
                                >
                                    {{ formatCurrency(item.net_salary) }}
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <a
                                        :href="route('app.hr.payroll.payslip', item.id)"
                                        target="_blank"
                                        class="text-gray-400 hover:text-emerald-500 opacity-0 group-hover:opacity-100 transition-all p-1 inline-block"
                                        :title="$t('hr.payroll.print_payslip')"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                            />
                                        </svg>
                                    </a>
                                </td>
                                <td
                                    class="px-4 py-3 text-end align-middle"
                                    v-if="payrollRun.status === 'draft'"
                                >
                                    <button
                                        @click="deleteItem(item)"
                                        class="text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all p-1"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import { useToast } from '@/Composables/useToast';

const { success, error } = useToast();
const processing = ref(false);

const props = defineProps({
    payrollRun: Object,
});

const totalSalaries = computed(
    () => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.base_salary || 0), 0) || 0
);
const totalDeductions = computed(
    () =>
        props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.total_deductions || 0), 0) ||
        0
);
const totalNet = computed(
    () => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.net_salary || 0), 0) || 0
);

function regeneratePayroll() {
    if (!confirm(t('hr.payroll.recreate_confirm'))) return;

    processing.value = true;
    router.put(
        route('app.hr.payroll.regenerate', props.payrollRun.id),
        {},
        {
            onSuccess: () => success(t('hr.payroll.recreated')),
            onFinish: () => (processing.value = false),
        }
    );
}

function approvePayroll() {
    processing.value = true;
    router.put(
        route('app.hr.payroll.approve', props.payrollRun.id),
        {},
        {
            onSuccess: () => success(t('hr.payroll.approved_successfully')),
            onFinish: () => (processing.value = false),
        }
    );
}

function deleteItem(item) {
    if (!confirm(t('hr.payroll.exclude_employee_confirm'))) return;

    router.delete(route('app.hr.payroll.items.destroy', [props.payrollRun.id, item.id]), {
        onSuccess: () => success(t('hr.payroll.employee_excluded')),
    });
}

function markAsPaid() {
    processing.value = true;
    router.put(
        route('app.hr.payroll.mark-paid', props.payrollRun.id),
        {},
        {
            onSuccess: () => success(t('hr.payroll.marked_paid')),
            onFinish: () => (processing.value = false),
        }
    );
}

function formatPeriod(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('ar-SA-u-nu-latn', {
        month: 'long',
        year: 'numeric',
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency: 'SAR' }).format(
        amount || 0
    );
}

function getStatusClass(status) {
    switch (status) {
        case 'draft':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'approved':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'paid':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        default:
            return 'bg-gray-100 text-gray-600';
    }
}
</script>
