<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="employee.name_ar"
                gradientFrom="from-action-primary"
                gradientTo="to-purple-700"
                glowFrom="from-action-primary"
                badgeBg="bg-selection-bg"
                badgeText="text-action-primary"
                badgeBorder="border-action-primary/25"
                badgeDot="bg-action-primary"
            >
                <template #back>
                    <BackButton :href="route('app.hr.employees.index')" />
                </template>

                <template #icon>
                    <div
                        class="w-full h-full rounded-2xl overflow-hidden flex items-center justify-center"
                    >
                        <img
                            v-if="employee.photo_path"
                            :src="`/storage/${employee.photo_path}`"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-white text-2xl font-black">
                            {{ employee.name_ar?.charAt(0) }}
                        </span>
                    </div>
                </template>

                <template #identity>
                    <div
                        class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-medium mt-1"
                    >
                        <span class="font-mono font-bold text-gray-700 dark:text-gray-300">
                            {{ employee.employee_number }}
                        </span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>
                            {{ employee.job_title?.name_ar || $t('hr.employees.no_job_title') }}
                        </span>
                        <template v-if="employee.center">
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <span class="text-action-primary font-semibold">
                                {{ employee.center?.name }}
                            </span>
                        </template>
                    </div>
                </template>

                <template #actions>
                    <span
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold border shadow-sm',
                            employee.status === 'active'
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800'
                                : employee.status === 'inactive'
                                  ? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'
                                  : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
                        ]"
                    >
                        {{ $t(`hr.employees.status.${employee.status}`) }}
                    </span>

                    <button
                        v-if="employee.status === 'active'"
                        @click="toggleStatus('inactive')"
                        class="px-4 py-2 text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/40 shadow-sm transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                            />
                        </svg>
                        <span>{{ $t('hr.employees.deactivate') }}</span>
                    </button>
                    <button
                        v-else
                        @click="toggleStatus('active')"
                        class="px-4 py-2 text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl hover:from-emerald-700 hover:to-teal-700 shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <span>{{ $t('hr.employees.activate') }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Tabs -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="border-b border-gray-200 dark:border-gray-700 px-4">
                    <nav class="flex gap-6 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === tab.key
                                    ? 'border-action-primary text-action-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                            ]"
                        >
                            {{ tab.icon }} {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Info Tab -->
                    <EmployeeInfoTab
                        v-show="activeTab === 'info'"
                        :employee="employee"
                        :job-titles="jobTitles"
                        :employee-types="employeeTypes"
                        :departments="departments"
                        :nationalities="nationalities"
                        :centers="centers"
                        :users="users"
                    />

                    <!-- Financial Tab -->
                    <EmployeeFinancialTab
                        v-show="activeTab === 'financial'"
                        :employee="employee"
                        :all-allowances="allAllowances"
                        :all-deductions="allDeductions"
                        :payroll-items="payrollItems || []"
                        :other-payments="otherPayments || []"
                    />

                    <!-- Schedule Tab -->
                    <EmployeeScheduleTab
                        v-show="activeTab === 'schedule'"
                        :employee="employee"
                        :shifts="shifts"
                        :weekly-schedule="weeklySchedule"
                    />

                    <!-- Leaves Tab -->
                    <LeavesTab
                        v-show="activeTab === 'leaves'"
                        :employee="employee"
                        :leaves="employee.leaves || []"
                    />

                    <!-- Permissions Tab -->
                    <PermissionsTab v-show="activeTab === 'permissions'" :employee="employee" />

                    <!-- Attendance Tab (Coming Soon) -->
                    <div v-show="activeTab === 'attendance'" class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4"
                        >
                            <span class="text-2xl">📅</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $t('common.coming_soon') }}
                        </p>
                    </div>

                    <!-- Payroll Tab (Coming Soon) -->
                    <div v-show="activeTab === 'payroll'" class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4"
                        >
                            <span class="text-2xl">💰</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $t('common.coming_soon') }}
                        </p>
                    </div>

                    <!-- Work Orders Tab (Coming Soon) -->
                    <div v-show="activeTab === 'work_orders'" class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4"
                        >
                            <span class="text-2xl">🔧</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $t('common.coming_soon') }}
                        </p>
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
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
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
    router.put(
        route('app.hr.employees.update', props.employee.id),
        {
            ...props.employee,
            status: newStatus,
        },
        {
            onSuccess: () => success(t('common.saved_success')),
        }
    );
}
</script>
