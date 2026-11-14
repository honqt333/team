<template>
    <AppLayout>
        <Head :title="$t('hr.title')" />

        <div class="space-y-8">
            <!-- Header Section -->
            <PageHeader :title="$t('hr.title')" :subtitle="$t('hr.subtitle')">
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Live KPI Summary Strip -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Active Employees KPI -->
                <div
                    class="bg-surface-primary rounded-2xl p-5 border border-border-subtle shadow-2xs flex items-center justify-between gap-4"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-content-muted">
                            {{ $t('hr.dashboard.active_employees') }}
                        </p>
                        <p class="mt-1.5 text-2xl font-bold text-content-primary font-mono">
                            {{ toEnglish(stats?.employees?.active ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-content-muted">
                            {{ $t('hr.dashboard.of_total') }}
                            <span class="font-semibold font-mono text-content-secondary">
                                {{ toEnglish(stats?.employees?.total ?? 0) }}
                            </span>
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 shrink-0 rounded-xl bg-action-primary/10 text-action-primary flex items-center justify-center"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Attendance Today KPI -->
                <div
                    class="bg-surface-primary rounded-2xl p-5 border border-border-subtle shadow-2xs flex items-center justify-between gap-4"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-content-muted">
                            {{ $t('hr.dashboard.present_today') }}
                        </p>
                        <p class="mt-1.5 text-2xl font-bold text-content-primary font-mono">
                            {{ toEnglish(stats?.attendance?.present_today ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-warning font-medium">
                            <span class="font-mono">
                                {{ toEnglish(stats?.attendance?.late_today ?? 0) }}
                            </span>
                            {{ $t('hr.dashboard.late_today_count') }}
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 shrink-0 rounded-xl bg-warning-soft text-warning flex items-center justify-center"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Pending Leaves KPI -->
                <div
                    class="bg-surface-primary rounded-2xl p-5 border border-border-subtle shadow-2xs flex items-center justify-between gap-4"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-content-muted">
                            {{ $t('hr.dashboard.pending_leaves') }}
                        </p>
                        <p class="mt-1.5 text-2xl font-bold text-content-primary font-mono">
                            {{ toEnglish(stats?.leaves?.pending ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-content-muted">
                            {{ $t('hr.dashboard.compliance_alerts') }}:
                            <span
                                class="font-semibold font-mono"
                                :class="
                                    (stats?.compliance?.total_alerts ?? 0) > 0
                                        ? 'text-danger'
                                        : 'text-content-secondary'
                                "
                            >
                                {{ toEnglish(stats?.compliance?.total_alerts ?? 0) }}
                            </span>
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 shrink-0 rounded-xl bg-info-soft text-info flex items-center justify-center"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Current Month Payroll KPI -->
                <div
                    class="bg-surface-primary rounded-2xl p-5 border border-border-subtle shadow-2xs flex items-center justify-between gap-4"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-content-muted">
                            {{ $t('hr.dashboard.current_payroll_net') }}
                        </p>
                        <p
                            class="mt-1.5 text-2xl font-bold text-content-primary font-mono truncate"
                        >
                            {{
                                stats?.payroll?.status !== 'none'
                                    ? formatMoney(stats?.payroll?.net_total ?? 0)
                                    : '—'
                            }}
                        </p>
                        <p class="mt-1 text-xs text-content-muted">
                            <span v-if="stats?.payroll?.status !== 'none'" class="font-mono">
                                {{ stats?.payroll?.current_period }} ·
                                {{ $t(`hr.payroll.statuses.${stats?.payroll?.status}`) }}
                            </span>
                            <span v-else>{{ $t('hr.dashboard.no_payroll_yet') }}</span>
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 shrink-0 rounded-xl bg-success-soft text-success flex items-center justify-center"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Employees Card -->
                <Link
                    :href="route('app.hr.employees.index')"
                    class="group relative bg-surface-primary rounded-2xl p-6 border border-border-subtle hover:border-action-primary/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-14 h-14 mx-auto rounded-2xl bg-action-primary/10 text-action-primary flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-4 text-base font-bold text-content-primary group-hover:text-action-primary transition-colors"
                    >
                        {{ $t('hr.employees.title') }}
                    </h3>
                    <p class="mt-1.5 text-xs text-content-muted">
                        {{ $t('hr.employees.subtitle') }}
                    </p>
                    <div
                        v-if="stats?.employees?.total > 0"
                        class="absolute top-4 end-4 px-2.5 py-0.5 bg-action-primary text-white text-xs font-bold rounded-full shadow-2xs font-mono"
                    >
                        {{ toEnglish(stats.employees.total) }}
                    </div>
                </Link>

                <!-- Attendance Card -->
                <Link
                    :href="route('app.hr.attendance.index')"
                    class="group relative bg-surface-primary rounded-2xl p-6 border border-border-subtle hover:border-warning/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-14 h-14 mx-auto rounded-2xl bg-warning-soft text-warning flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-4 text-base font-bold text-content-primary group-hover:text-warning transition-colors"
                    >
                        {{ $t('hr.attendance.title') }}
                    </h3>
                    <p class="mt-1.5 text-xs text-content-muted">
                        {{ $t('hr.attendance.subtitle') }}
                    </p>
                </Link>

                <!-- Payroll Card -->
                <Link
                    :href="route('app.hr.payroll.index')"
                    class="group relative bg-surface-primary rounded-2xl p-6 border border-border-subtle hover:border-success/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-14 h-14 mx-auto rounded-2xl bg-success-soft text-success flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-4 text-base font-bold text-content-primary group-hover:text-success transition-colors"
                    >
                        {{ $t('hr.payroll.title') }}
                    </h3>
                    <p class="mt-1.5 text-xs text-content-muted">
                        {{ $t('hr.payroll.subtitle') }}
                    </p>
                </Link>

                <!-- HR Settings Card -->
                <Link
                    :href="route('app.hr.settings.index')"
                    class="group relative bg-surface-primary rounded-2xl p-6 border border-border-subtle hover:border-info/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-14 h-14 mx-auto rounded-2xl bg-info-soft text-info flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-4 text-base font-bold text-content-primary group-hover:text-info transition-colors"
                    >
                        {{ $t('hr.settings.title') }}
                    </h3>
                    <p class="mt-1.5 text-xs text-content-muted">
                        {{ $t('hr.settings.subtitle') }}
                    </p>
                </Link>
            </div>

            <!-- Insights & Alerts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Compliance Alerts (Documents & Contracts) -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-5 shadow-2xs space-y-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-bold text-content-primary">
                            {{ $t('hr.dashboard.compliance_alerts') }}
                        </h3>
                        <span
                            v-if="(stats?.compliance?.total_alerts ?? 0) > 0"
                            class="px-2 py-0.5 rounded-full text-xs font-bold font-mono bg-danger-soft text-danger"
                        >
                            {{ toEnglish(stats.compliance.total_alerts) }}
                        </span>
                    </div>

                    <div v-if="complianceAlerts?.length" class="divide-y divide-border-subtle">
                        <Link
                            v-for="alert in complianceAlerts"
                            :key="`${alert.kind}-${alert.id}`"
                            :href="
                                route('app.hr.employees.show', {
                                    employee: alert.employee_id,
                                    tab: alert.kind === 'contract' ? 'contracts' : 'documents',
                                })
                            "
                            class="py-3 flex items-center justify-between gap-3 hover:bg-surface-secondary/50 -mx-2 px-2 rounded-xl transition-colors"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-content-primary truncate">
                                    {{ alert.employee_name || '—' }}
                                </p>
                                <p class="text-xs text-content-muted truncate">
                                    {{
                                        alert.kind === 'contract'
                                            ? $t('hr.dashboard.contract_badge')
                                            : $t('hr.dashboard.document_badge')
                                    }}
                                    · {{ alert.title }}
                                </p>
                            </div>
                            <div class="shrink-0 text-end">
                                <span
                                    class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="
                                        alert.is_expired
                                            ? 'bg-danger-soft text-danger'
                                            : 'bg-warning-soft text-warning'
                                    "
                                >
                                    {{
                                        alert.is_expired
                                            ? $t('hr.dashboard.expired_badge')
                                            : $t('hr.dashboard.expiring_soon_badge')
                                    }}
                                </span>
                                <p class="mt-1 text-[11px] font-mono text-content-muted">
                                    {{ alert.expiry_date }}
                                </p>
                            </div>
                        </Link>
                    </div>
                    <p v-else class="text-xs text-content-muted py-6 text-center">
                        {{ $t('hr.dashboard.compliance_alerts_empty') }}
                    </p>
                </div>

                <!-- Department Distribution -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-5 shadow-2xs space-y-4"
                >
                    <h3 class="text-sm font-bold text-content-primary">
                        {{ $t('hr.dashboard.departments_distribution') }}
                    </h3>

                    <div v-if="departmentsStats?.length" class="space-y-3">
                        <div v-for="dept in departmentsStats" :key="dept.name" class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-content-primary truncate">
                                    {{ dept.name }}
                                </span>
                                <span class="font-mono font-bold text-content-secondary">
                                    {{ toEnglish(dept.count) }}
                                </span>
                            </div>
                            <div
                                class="w-full h-2 rounded-full bg-surface-secondary overflow-hidden"
                            >
                                <div
                                    class="h-full rounded-full bg-action-primary transition-all duration-300"
                                    :style="{ width: `${departmentPercent(dept.count)}%` }"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-content-muted py-6 text-center">
                        {{ $t('hr.dashboard.departments_empty') }}
                    </p>
                </div>

                <!-- Recent Employees -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-5 shadow-2xs space-y-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-bold text-content-primary">
                            {{ $t('hr.dashboard.recent_employees') }}
                        </h3>
                        <Link
                            :href="route('app.hr.employees.index')"
                            class="text-xs font-semibold text-action-primary hover:underline"
                        >
                            {{ $t('hr.dashboard.view_all') }}
                        </Link>
                    </div>

                    <div v-if="recentEmployees?.length" class="divide-y divide-border-subtle">
                        <Link
                            v-for="emp in recentEmployees"
                            :key="emp.id"
                            :href="route('app.hr.employees.show', emp.id)"
                            class="py-3 flex items-center justify-between gap-3 hover:bg-surface-secondary/50 -mx-2 px-2 rounded-xl transition-colors"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-content-primary truncate">
                                    {{ getName(emp) || emp.display_name || '—' }}
                                </p>
                                <p class="text-xs text-content-muted truncate">
                                    {{
                                        emp.job_title
                                            ? getName(emp.job_title)
                                            : emp.department
                                              ? getName(emp.department)
                                              : '—'
                                    }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-xs font-mono text-content-muted bg-surface-secondary px-2 py-0.5 rounded-lg"
                            >
                                {{ emp.employee_number }}
                            </span>
                        </Link>
                    </div>
                    <p v-else class="text-xs text-content-muted py-6 text-center">
                        {{ $t('hr.dashboard.recent_employees_empty') }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useLocalized } from '@/Composables/useLocalized';

const { toEnglish, formatMoney } = useNumberFormat();
const { getName } = useLocalized();

const props = defineProps({
    stats: Object,
    recentEmployees: {
        type: Array,
        default: () => [],
    },
    departmentsStats: {
        type: Array,
        default: () => [],
    },
    complianceAlerts: {
        type: Array,
        default: () => [],
    },
});

function departmentPercent(count) {
    const total = Number(props.stats?.employees?.total || 0);
    if (!total || !count) return 0;
    return Math.min(100, Math.round((count / total) * 100));
}
</script>
