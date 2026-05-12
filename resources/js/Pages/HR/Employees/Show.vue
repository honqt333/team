<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('app.hr.employees.index')"
                            class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div
                            class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden shadow-sm border border-gray-200 dark:border-gray-600">
                            <img v-if="employee.photo_path" :src="`/storage/${employee.photo_path}`"
                                class="w-full h-full object-cover" />
                            <div v-else
                                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-violet-500 to-purple-600 text-white text-xl font-bold">
                                {{ employee.name_ar?.charAt(0) }}
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ employee.name_ar }}</h1>
                                <span :class="[
                                    'px-2 py-0.5 rounded-full text-xs font-medium',
                                    employee.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : employee.status === 'inactive'
                                            ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                ]">
                                    {{ $t(`hr.employees.status.${employee.status}`) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ employee.employee_number }} | {{ employee.job_title?.name_ar ||
                                    $t('hr.employees.no_job_title') }}
                                <span v-if="employee.center" class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                                <span v-if="employee.center" class="text-violet-600 dark:text-violet-400 font-medium">{{
                                    employee.center?.name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="() => window.open(route('app.hr.employees.print', { id: employee.id }), '_blank')"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                            :title="$t('common.print')"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span class="hidden sm:inline">{{ $t('common.print') }}</span>
                        </button>

                        <button v-if="employee.status === 'active'" @click="toggleStatus('inactive')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            {{ $t('hr.employees.deactivate') }}
                        </button>
                        <button v-else @click="toggleStatus('active')"
                            class="px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-100 rounded-lg hover:bg-emerald-200">
                            {{ $t('hr.employees.activate') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 px-4">
                    <nav class="flex gap-6 overflow-x-auto">
                        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                            'py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                            activeTab === tab.key
                                ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        ]">
                            {{ tab.icon }} {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Info Tab -->
                    <EmployeeInfoTab v-show="activeTab === 'info'" :employee="employee" :job-titles="jobTitles"
                        :employee-types="employeeTypes" :departments="departments" :nationalities="nationalities"
                        :centers="centers" :users="users" />

                    <!-- Financial Tab -->
                    <EmployeeFinancialTab v-show="activeTab === 'financial'" :employee="employee"
                        :all-allowances="allAllowances" :all-deductions="allDeductions"
                        :payroll-items="payrollItems || []" :other-payments="otherPayments || []" />

                    <!-- Schedule Tab -->
                    <EmployeeScheduleTab v-show="activeTab === 'schedule'" :employee="employee" :shifts="shifts"
                        :weekly-schedule="weeklySchedule" />

                    <!-- Leaves Tab -->
                    <LeavesTab v-show="activeTab === 'leaves'" :employee="employee" :leaves="employee.leaves || []" />

                    <!-- Permissions Tab -->
                    <PermissionsTab v-show="activeTab === 'permissions'" :employee="employee" />

                    <!-- Attendance Tab (Coming Soon) -->
                    <div v-show="activeTab === 'attendance'" class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">📅</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>

                    <!-- Payroll Tab (Coming Soon) -->
                    <div v-show="activeTab === 'payroll'" class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">💰</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>

                    <!-- Work Orders Tab (Coming Soon) -->
                    <div v-show="activeTab === 'work_orders'" class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">🔧</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmployeeInfoTab from '@/Components/HR/Tabs/EmployeeInfoTab.vue';

import EmployeeFinancialTab from '@/Components/HR/Tabs/EmployeeFinancialTab.vue';
import EmployeeScheduleTab from '@/Components/HR/Tabs/EmployeeScheduleTab.vue';
import LeavesTab from '@/Components/HR/Tabs/LeavesTab.vue';
import PermissionsTab from '@/Components/HR/Tabs/PermissionsTab.vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    employee: Object,
    jobTitles: Array,
    employeeTypes: Array,
    departments: Array,
    allAllowances: Array,
    allDeductions: Array,
    users: Array,
    nationalities: Array,
    centers: Array,
    shifts: Array,
    weeklySchedule: Object,
    payrollItems: Array,
    otherPayments: Array,
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'info');

const tabs = computed(() => [
    { key: 'info', label: t('hr.employees.tabs.info'), icon: '📋' },
    { key: 'financial', label: t('hr.employees.tabs.financial'), icon: '💵' },
    { key: 'schedule', label: t('hr.employees.tabs.schedule'), icon: '⏰' },
    { key: 'leaves', label: t('hr.leaves.title'), icon: '🏖️' },
    { key: 'permissions', label: t('hr.employees.tabs.permissions'), icon: '🔐' },
    { key: 'attendance', label: t('hr.employees.tabs.attendance'), icon: '📅' },
    { key: 'payroll', label: t('hr.employees.tabs.payroll'), icon: '💰' },
    { key: 'work_orders', label: t('hr.employees.tabs.work_orders'), icon: '🔧' },
]);

watch(activeTab, (newTab) => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', newTab);
    window.history.replaceState({}, '', url);
});

function toggleStatus(newStatus) {
    router.put(route('app.hr.employees.update', props.employee.id), {
        ...props.employee,
        status: newStatus,
    }, {
        onSuccess: () => success(t('common.saved_success')),
    });
}
</script>
