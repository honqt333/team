<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-600 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('employee_portal.my_payslips') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ payslips?.total || 0 }} {{ $t('common.records') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payslips Grid -->
            <div v-if="payslips?.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                    v-for="payslip in payslips.data" 
                    :key="payslip.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:border-amber-500 dark:hover:border-amber-500 transition-all hover:-translate-y-1"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ payslip.payroll_run?.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ payslip.payroll_run?.period_start }} - {{ payslip.payroll_run?.period_end }}
                            </p>
                        </div>
                        <span 
                            class="px-3 py-1 text-xs font-bold rounded-full"
                            :class="payslip.payroll_run?.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'"
                        >
                            {{ payslip.payroll_run?.status === 'paid' ? $t('hr.payroll.paid') : $t('hr.payroll.pending') }}
                        </span>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">{{ $t('hr.payroll.base_salary') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(payslip.base_salary) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-green-600 dark:text-green-400">{{ $t('hr.payroll.total_allowances') }}</span>
                            <span class="font-medium text-green-600 dark:text-green-400">+{{ formatCurrency(payslip.total_allowances) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-red-600 dark:text-red-400">{{ $t('hr.payroll.total_deductions') }}</span>
                            <span class="font-medium text-red-600 dark:text-red-400">-{{ formatCurrency(payslip.total_deductions) }}</span>
                        </div>
                        <div class="flex justify-between pt-3">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('hr.payroll.net_salary') }}</span>
                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ formatCurrency(payslip.net_salary) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">{{ $t('common.no_data') }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="payslips?.links?.length > 3" class="flex justify-center">
                <nav class="flex gap-1">
                    <template v-for="link in payslips.links" :key="link.label">
                        <Link 
                            v-if="link.url"
                            :href="link.url"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
                            :class="link.active ? 'bg-amber-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            v-html="link.label"
                        />
                        <span 
                            v-else
                            class="px-4 py-2 rounded-xl text-sm bg-gray-100 dark:bg-gray-900 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-not-allowed"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    payslips: Object,
    employee: Object,
});

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
}
</script>
