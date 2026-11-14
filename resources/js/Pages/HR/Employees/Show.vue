<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="getName(employee) || employee.display_name || employee.name_ar"
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
                            {{ (getName(employee) || employee.name_ar)?.charAt(0) }}
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
                            {{
                                employee.job_title
                                    ? getName(employee.job_title)
                                    : $t('hr.employees.no_job_title')
                            }}
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

            <!-- Employee Quick Summary KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div
                    class="bg-surface-primary rounded-2xl p-4 border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs text-content-muted">
                        {{ $t('hr.employees.profile.base_salary') }}
                    </p>
                    <p class="mt-1 text-xl font-bold font-mono text-content-primary">
                        {{ formatMoney(employee.base_salary || 0) }}
                    </p>
                </div>

                <div
                    class="bg-surface-primary rounded-2xl p-4 border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs text-content-muted">
                        {{ $t('hr.employees.profile.attendance_this_month') }}
                    </p>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-xl font-bold font-mono text-success">
                            {{ toEnglish(attendanceSummary?.present || 0) }}
                        </span>
                        <span class="text-xs text-content-muted">
                            {{ $t('hr.employees.profile.present_days') }}
                        </span>
                        <span class="text-xs font-mono text-warning">
                            ({{ toEnglish(attendanceSummary?.late || 0) }}
                            {{ $t('hr.attendance.late') }})
                        </span>
                    </div>
                </div>

                <div
                    class="bg-surface-primary rounded-2xl p-4 border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs text-content-muted">
                        {{ $t('hr.leaves.title') }}
                    </p>
                    <p class="mt-1 text-xl font-bold font-mono text-content-primary">
                        {{ toEnglish((employee.leaves || []).length) }}
                    </p>
                </div>

                <div
                    class="bg-surface-primary rounded-2xl p-4 border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs text-content-muted">
                        {{ $t('hr.employees.profile.labor_hours_month') }}
                    </p>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-xl font-bold font-mono text-action-primary">
                            {{ toEnglish(workOrdersSummary?.labor_hours || 0) }}
                        </span>
                        <span class="text-xs text-content-muted">
                            {{ $t('hr.employees.profile.hours_unit') }}
                        </span>
                        <span class="text-xs font-mono text-content-muted">
                            · {{ toEnglish(workOrdersSummary?.completed_tasks || 0) }}
                            {{ $t('hr.employees.profile.completed_tasks') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url>
                        <template v-for="tab in tabs" :key="tab.id" #[`icon-${tab.id}`]>
                            <span>{{ tab.emoji }}</span>
                        </template>
                    </AppTabs>
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

                    <!-- Asset Custody Tab -->
                    <EmployeeCustodyTab
                        v-show="activeTab === 'custody'"
                        :employee="employee"
                        :custodies="employee.asset_custodies || []"
                    />

                    <!-- Offboarding & EOSB Clearance Tab -->
                    <EmployeeOffboardingTab
                        v-show="activeTab === 'offboarding'"
                        :employee="employee"
                        :latest-offboarding="latestOffboarding"
                        :offboarding-defaults="offboardingDefaults"
                        :custodies="employee.asset_custodies || []"
                    />

                    <!-- Attendance Tab -->
                    <div v-show="activeTab === 'attendance'" class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-success-soft text-success font-semibold"
                                >
                                    {{ $t('hr.employees.profile.present_days') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(attendanceSummary?.present || 0) }}
                                    </span>
                                </span>
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-warning-soft text-warning font-semibold"
                                >
                                    {{ $t('hr.employees.profile.late_days') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(attendanceSummary?.late || 0) }}
                                    </span>
                                </span>
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-danger-soft text-danger font-semibold"
                                >
                                    {{ $t('hr.employees.profile.absent_days') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(attendanceSummary?.absent || 0) }}
                                    </span>
                                </span>
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-info-soft text-info font-semibold"
                                >
                                    {{ $t('hr.employees.profile.overtime_minutes') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(attendanceSummary?.overtime_minutes || 0) }}
                                    </span>
                                    {{ $t('hr.employees.profile.minutes_unit') }}
                                </span>
                            </div>

                            <Link
                                :href="route('app.hr.attendance.index')"
                                class="text-xs font-semibold text-action-primary hover:underline"
                            >
                                {{ $t('hr.employees.profile.open_attendance') }}
                            </Link>
                        </div>

                        <DataTable
                            :columns="attendanceColumns"
                            :items="recentAttendances || []"
                            :empty-title="$t('hr.employees.profile.attendance_empty')"
                            striped
                        >
                            <template #cell-date="{ item }">
                                <span class="font-mono text-xs">{{ item.date }}</span>
                            </template>
                            <template #cell-status="{ item }">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    :class="attendanceStatusClass(item.status)"
                                >
                                    {{ $t(`hr.attendance.${item.status}`) }}
                                </span>
                            </template>
                            <template #cell-check_in="{ item }">
                                <span class="font-mono text-xs">{{ item.check_in || '—' }}</span>
                            </template>
                            <template #cell-check_out="{ item }">
                                <span class="font-mono text-xs">{{ item.check_out || '—' }}</span>
                            </template>
                            <template #cell-late_minutes="{ item }">
                                <span class="font-mono text-xs">
                                    {{ toEnglish(item.late_minutes || 0) }}
                                </span>
                            </template>
                            <template #cell-overtime_minutes="{ item }">
                                <span class="font-mono text-xs">
                                    {{ toEnglish(item.overtime_minutes || 0) }}
                                </span>
                            </template>
                        </DataTable>
                    </div>

                    <!-- Payroll Tab -->
                    <div v-show="activeTab === 'payroll'" class="space-y-4">
                        <DataTable
                            :columns="payrollColumns"
                            :items="payrollItems || []"
                            :empty-title="$t('hr.employees.profile.payroll_empty')"
                            striped
                        >
                            <template #cell-period="{ item }">
                                <Link
                                    v-if="item.payroll_run"
                                    :href="route('app.hr.payroll.show', item.payroll_run.id)"
                                    class="font-mono text-xs font-semibold text-action-primary hover:underline"
                                >
                                    {{ item.payroll_run.period_start }} →
                                    {{ item.payroll_run.period_end }}
                                </Link>
                                <span v-else class="text-xs text-content-muted">—</span>
                            </template>
                            <template #cell-base_salary="{ item }">
                                <span class="font-mono text-xs">
                                    {{ formatMoney(item.base_salary || 0) }}
                                </span>
                            </template>
                            <template #cell-total_allowances="{ item }">
                                <span class="font-mono text-xs text-success">
                                    +{{ formatMoney(item.total_allowances || 0) }}
                                </span>
                            </template>
                            <template #cell-total_deductions="{ item }">
                                <span class="font-mono text-xs text-danger">
                                    -{{ formatMoney(item.total_deductions || 0) }}
                                </span>
                            </template>
                            <template #cell-net_salary="{ item }">
                                <span class="font-mono text-xs font-bold text-content-primary">
                                    {{ formatMoney(item.net_salary || 0) }}
                                </span>
                            </template>
                            <template #cell-status="{ item }">
                                <span
                                    v-if="item.payroll_run?.status"
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-surface-secondary text-content-primary"
                                >
                                    {{ $t(`hr.payroll.statuses.${item.payroll_run.status}`) }}
                                </span>
                            </template>
                        </DataTable>
                    </div>

                    <!-- Work Orders Tab -->
                    <div v-show="activeTab === 'work_orders'" class="space-y-4">
                        <div
                            v-if="!employee.user_id"
                            class="py-10 text-center text-sm text-content-muted"
                        >
                            {{ $t('hr.employees.profile.work_orders_no_user') }}
                        </div>

                        <template v-else>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-warning-soft text-warning font-semibold"
                                >
                                    {{ $t('hr.employees.profile.active_tasks') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(workOrdersSummary?.active_tasks || 0) }}
                                    </span>
                                </span>
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-success-soft text-success font-semibold"
                                >
                                    {{ $t('hr.employees.profile.completed_tasks') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(workOrdersSummary?.completed_tasks || 0) }}
                                    </span>
                                </span>
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-action-primary/10 text-action-primary font-semibold"
                                >
                                    {{ $t('hr.employees.profile.labor_hours_month') }}:
                                    <span class="font-mono">
                                        {{ toEnglish(workOrdersSummary?.labor_hours || 0) }}
                                    </span>
                                    {{ $t('hr.employees.profile.hours_unit') }}
                                </span>
                            </div>

                            <DataTable
                                :columns="workOrderColumns"
                                :items="recentWorkOrders || []"
                                :empty-title="$t('hr.employees.profile.work_orders_empty')"
                                striped
                            >
                                <template #cell-work_order_number="{ item }">
                                    <Link
                                        :href="route('work-orders.show', item.id)"
                                        class="font-mono text-xs font-semibold text-action-primary hover:underline"
                                    >
                                        {{ item.work_order_number || item.code }}
                                    </Link>
                                </template>
                                <template #cell-vehicle="{ item }">
                                    <span class="text-xs">
                                        {{
                                            item.vehicle?.make
                                                ? `${getName(item.vehicle.make)} ${item.vehicle.model ? getName(item.vehicle.model) : ''}`
                                                : '—'
                                        }}
                                    </span>
                                </template>
                                <template #cell-customer="{ item }">
                                    <span class="text-xs">{{ item.customer?.name || '—' }}</span>
                                </template>
                                <template #cell-status="{ item }">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="workOrderStatusClass(item.status)"
                                    >
                                        {{ $t(`work_orders.status.${item.status}`) }}
                                    </span>
                                </template>
                            </DataTable>
                        </template>
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
import AppTabs from '@/Components/Common/AppTabs.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import EmployeeInfoTab from '@/Components/HR/Tabs/EmployeeInfoTab.vue';

import EmployeeFinancialTab from '@/Components/HR/Tabs/EmployeeFinancialTab.vue';
import EmployeeScheduleTab from '@/Components/HR/Tabs/EmployeeScheduleTab.vue';
import LeavesTab from '@/Components/HR/Tabs/LeavesTab.vue';
import PermissionsTab from '@/Components/HR/Tabs/PermissionsTab.vue';
import EmployeeCustodyTab from '@/Components/HR/Tabs/EmployeeCustodyTab.vue';
import EmployeeOffboardingTab from '@/Components/HR/Tabs/EmployeeOffboardingTab.vue';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useLocalized } from '@/Composables/useLocalized';

const { t } = useI18n();
const { success } = useToast();
const { formatMoney, toEnglish } = useNumberFormat();
const { getName } = useLocalized();

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
    recentAttendances: {
        type: Array,
        default: () => [],
    },
    attendanceSummary: {
        type: Object,
        default: () => ({}),
    },
    recentWorkOrders: {
        type: Array,
        default: () => [],
    },
    workOrdersSummary: {
        type: Object,
        default: () => ({}),
    },
    latestOffboarding: {
        type: Object,
        default: null,
    },
    offboardingDefaults: {
        type: Object,
        default: () => ({ allowances_amount: 0, unpaid_advances: 0 }),
    },
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'info');

const tabs = computed(() => [
    { id: 'info', label: t('hr.employees.tabs.info'), emoji: '📋' },
    { id: 'financial', label: t('hr.employees.tabs.financial'), emoji: '💵' },
    { id: 'schedule', label: t('hr.employees.tabs.schedule'), emoji: '⏰' },
    { id: 'leaves', label: t('hr.leaves.title'), emoji: '🏖️' },
    { id: 'custody', label: t('hr.custody.tab_title'), emoji: '🧰' },
    { id: 'permissions', label: t('hr.employees.tabs.permissions'), emoji: '🔐' },
    { id: 'attendance', label: t('hr.employees.tabs.attendance'), emoji: '📅' },
    { id: 'payroll', label: t('hr.employees.tabs.payroll'), emoji: '💰' },
    { id: 'work_orders', label: t('hr.employees.tabs.work_orders'), emoji: '🔧' },
    { id: 'offboarding', label: t('hr.offboarding.tab_title'), emoji: '📄' },
]);

watch(activeTab, (newTab) => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', newTab);
    window.history.replaceState({}, '', url);
});

const attendanceColumns = computed(() => [
    { key: 'date', label: t('hr.attendance.date') },
    { key: 'status', label: t('hr.attendance.status') },
    { key: 'check_in', label: t('hr.attendance.check_in') },
    { key: 'check_out', label: t('hr.attendance.check_out') },
    { key: 'late_minutes', label: t('hr.attendance.late') },
    { key: 'overtime_minutes', label: t('hr.employees.profile.overtime_minutes') },
    { key: 'notes', label: t('hr.attendance.notes') },
]);

const payrollColumns = computed(() => [
    { key: 'period', label: t('hr.payroll.period') },
    { key: 'base_salary', label: t('hr.employees.profile.base_salary') },
    { key: 'total_allowances', label: t('hr.payroll.total_allowances') },
    { key: 'total_deductions', label: t('hr.payroll.total_deductions') },
    { key: 'net_salary', label: t('hr.payroll.total_net') },
    { key: 'status', label: t('common.status') },
]);

const workOrderColumns = computed(() => [
    { key: 'work_order_number', label: '#' },
    { key: 'vehicle', label: t('common.vehicle') },
    { key: 'customer', label: t('common.customer') },
    { key: 'status', label: t('common.status') },
]);

function attendanceStatusClass(status) {
    switch (status) {
        case 'present':
            return 'bg-success-soft text-success';
        case 'late':
            return 'bg-warning-soft text-warning';
        case 'absent':
            return 'bg-danger-soft text-danger';
        default:
            return 'bg-surface-secondary text-content-muted';
    }
}

function workOrderStatusClass(status) {
    const classes = {
        draft: 'bg-surface-secondary text-content-muted border border-border-subtle',
        open: 'bg-info-soft text-info border border-info/20',
        in_progress: 'bg-action-primary/10 text-action-primary border border-action-primary/20',
        on_hold: 'bg-warning-soft text-warning border border-warning/20',
        ready_for_qc: 'bg-warning-soft text-warning border border-warning/20',
        done: 'bg-success-soft text-success border border-success/20',
        cancelled: 'bg-danger-soft text-danger border border-danger/20',
    };
    return classes[status] || classes.draft;
}

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
