<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-violet-600 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold">
                        {{ employee?.name_ar?.charAt(0) || '?' }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ $t('employee_portal.welcome') }}، {{ employee?.name_ar }}</h1>
                        <p class="text-violet-200">{{ employee?.job_title?.name_ar || $t('common.employee') }} • {{ employee?.center?.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Attendance Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('employee_portal.present_days') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.attendance?.present || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Late Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('employee_portal.late_days') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.attendance?.late || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Leave Balance -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('employee_portal.leave_balance') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.leaves?.annual_balance || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Hours -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('employee_portal.total_hours') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.attendance?.total_hours || 0 }}h</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $t('employee_portal.quick_actions') }}</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link :href="route('employee.profile')" class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-violet-500 dark:hover:border-violet-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('employee_portal.my_profile') }}</span>
                    </Link>

                    <Link :href="route('employee.attendance')" class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('employee_portal.my_attendance') }}</span>
                    </Link>

                    <Link :href="route('employee.leaves')" class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('employee_portal.my_leaves') }}</span>
                    </Link>

                    <Link :href="route('employee.payslips')" class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('employee_portal.my_payslips') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Recent Payslips -->
            <div v-if="recentPayslips?.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('employee_portal.recent_payslips') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">{{ $t('hr.payroll.period') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">{{ $t('hr.payroll.net_salary') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="payslip in recentPayslips" :key="payslip.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ payslip.payroll_run?.name }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(payslip.net_salary) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full" :class="payslip.payroll_run?.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'">
                                        {{ payslip.payroll_run?.status === 'paid' ? $t('hr.payroll.paid') : $t('hr.payroll.pending') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    employee: Object,
    stats: Object,
    recentPayslips: Array,
});

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
}
</script>
