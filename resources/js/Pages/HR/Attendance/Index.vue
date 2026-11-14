<template>
    <AppLayout :title="$t('hr.attendance.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('hr.attendance.title')"
                :subtitle="$t('hr.attendance.subtitle')"
                :totalCount="toEnglish(stats?.total || 0)"
                :countLabel="$t('common.employee')"
            >
                <template #back>
                    <BackButton :href="route('app.hr.index')" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppPrintButton variant="icon" @click="printAttendance" />
                        </div>
                    </div>
                </template>

                <template #filters>
                    <div
                        class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3 no-print"
                    >
                        <!-- Date Navigation -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-primary dark:bg-surface-secondary/50 rounded-2xl border border-border-subtle shadow-2xs shrink-0"
                        >
                            <button
                                @click="changeDate(-1)"
                                type="button"
                                class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-xl transition-colors cursor-pointer"
                                :title="$t('common.previous')"
                            >
                                <svg
                                    class="w-4 h-4 rtl:rotate-180"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M15 19l-7-7 7-7"
                                    />
                                </svg>
                            </button>

                            <div class="w-40 sm:w-44">
                                <CustomDatePicker v-model="selectedDate" class="!rounded-xl" />
                            </div>

                            <button
                                @click="changeDate(1)"
                                type="button"
                                class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-xl transition-colors cursor-pointer"
                                :title="$t('common.next')"
                            >
                                <svg
                                    class="w-4 h-4 rtl:rotate-180"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Status Filter Tabs -->
                        <div class="w-full lg:w-auto overflow-x-auto">
                            <AppTabs
                                :model-value="statusFilter"
                                :tabs="statusTabs"
                                variant="pills"
                                size="sm"
                                @update:model-value="handleStatusChange"
                            />
                        </div>

                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full min-w-[200px]">
                            <div
                                class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-content-muted group-focus-within:text-action-primary transition-colors"
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
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="$t('common.search')"
                                class="w-full ps-10 pe-9 py-2 bg-surface-primary dark:bg-surface-secondary/50 border border-border-subtle rounded-xl text-sm text-content-primary placeholder-content-muted focus:outline-hidden focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all shadow-2xs"
                                @keyup.enter="applyFilters"
                            />
                            <button
                                v-if="searchQuery"
                                @click="
                                    searchQuery = '';
                                    applyFilters();
                                "
                                type="button"
                                class="absolute inset-y-0 end-0 pe-3 flex items-center text-content-muted hover:text-content-primary transition-colors cursor-pointer"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Reset Filters -->
                        <button
                            v-if="hasActiveFilters"
                            @click="handleResetFilters"
                            type="button"
                            class="p-2 text-content-secondary hover:text-action-primary bg-surface-primary dark:bg-surface-secondary/50 hover:bg-surface-secondary border border-border-subtle rounded-xl transition-colors cursor-pointer shadow-2xs shrink-0"
                            :title="$t('common.reset_filter')"
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
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <!-- Total -->
                <div
                    class="bg-surface-primary dark:bg-surface-secondary/40 p-4 rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs font-medium text-content-muted mb-1">
                        {{ $t('common.total') }}
                    </p>
                    <span class="text-2xl font-bold font-mono text-content-primary tracking-tight">
                        {{ toEnglish(stats.total || 0) }}
                    </span>
                </div>

                <!-- Present -->
                <div
                    class="bg-surface-primary dark:bg-surface-secondary/40 p-4 rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs font-medium text-success mb-1">
                        {{ $t('hr.attendance.present') }}
                    </p>
                    <span class="text-2xl font-bold font-mono text-success tracking-tight">
                        {{ toEnglish(stats.present || 0) }}
                    </span>
                </div>

                <!-- Absent -->
                <div
                    class="bg-surface-primary dark:bg-surface-secondary/40 p-4 rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs font-medium text-danger mb-1">
                        {{ $t('hr.attendance.absent') }}
                    </p>
                    <span class="text-2xl font-bold font-mono text-danger tracking-tight">
                        {{ toEnglish(stats.absent || 0) }}
                    </span>
                </div>

                <!-- Leave -->
                <div
                    class="bg-surface-primary dark:bg-surface-secondary/40 p-4 rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <p class="text-xs font-medium text-info mb-1">
                        {{ $t('hr.attendance.leave') }}
                    </p>
                    <span class="text-2xl font-bold font-mono text-info tracking-tight">
                        {{ toEnglish(stats.leave || 0) }}
                    </span>
                </div>

                <!-- Late -->
                <div
                    class="bg-surface-primary dark:bg-surface-secondary/40 p-4 rounded-2xl border border-border-subtle shadow-2xs col-span-2 sm:col-span-1"
                >
                    <p class="text-xs font-medium text-warning mb-1">
                        {{ $t('hr.attendance.late') }}
                    </p>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl font-bold font-mono text-warning tracking-tight">
                            {{ toEnglish(stats.late_count || 0) }}
                        </span>
                        <span
                            v-if="stats.total_late_minutes"
                            class="text-xs font-mono text-content-muted"
                        >
                            ({{ toEnglish(stats.total_late_minutes) }} {{ $t('common.minutes') }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attendance Data Table -->
            <div class="space-y-4">
                <DataTable
                    :data="employees"
                    :columns="columns"
                    :empty-title="$t('hr.attendance.not_marked')"
                    :empty-description="$t('hr.attendance.empty_hint')"
                >
                    <!-- Employee Column Slot -->
                    <template #cell-employee="{ item }">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full bg-surface-secondary border border-border-subtle flex items-center justify-center overflow-hidden shrink-0"
                            >
                                <img
                                    v-if="item.photo_url"
                                    :src="item.photo_url"
                                    :alt="getName(item)"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-xs font-bold text-action-primary">
                                    {{ item.name_ar?.charAt(0) || item.name_en?.charAt(0) || '؟' }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-content-primary line-clamp-1">
                                    {{ getName(item) }}
                                </div>
                                <div class="font-mono text-xs text-content-muted">
                                    {{ item.employee_number }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Status Column Slot -->
                    <template #cell-status="{ item }">
                        <span
                            v-if="item.attendance"
                            :class="[
                                'px-2.5 py-1 rounded-full text-xs font-bold border shadow-2xs',
                                getStatusClass(item.attendance.status),
                            ]"
                        >
                            {{ $t(`hr.attendance.${item.attendance.status}`) }}
                        </span>
                        <span
                            v-else-if="item.is_on_leave"
                            class="px-2.5 py-1 rounded-full text-xs font-bold border shadow-2xs bg-info-soft text-info border-info/20"
                        >
                            {{ $t('hr.attendance.leave') }}
                        </span>
                        <span
                            v-else
                            class="px-2 py-0.5 rounded-full text-xs font-medium bg-surface-secondary text-content-muted border border-border-subtle"
                        >
                            {{ $t('hr.attendance.not_marked') }}
                        </span>
                    </template>

                    <!-- Shift Column Slot -->
                    <template #cell-shift="{ item }">
                        <div v-if="item.shift_name || item.shift_start" class="text-xs">
                            <span class="font-medium text-content-primary">
                                {{ item.shift_name || '—' }}
                            </span>
                            <span
                                v-if="item.shift_start && item.shift_end"
                                class="block font-mono text-[11px] text-content-muted"
                                dir="ltr"
                            >
                                {{ item.shift_start }} - {{ item.shift_end }}
                            </span>
                        </div>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <!-- Check In Slot -->
                    <template #cell-check_in="{ item }">
                        <span class="font-mono text-xs font-medium text-content-primary">
                            {{ item.attendance?.check_in || '—' }}
                        </span>
                    </template>

                    <!-- Check Out Slot -->
                    <template #cell-check_out="{ item }">
                        <span class="font-mono text-xs font-medium text-content-primary">
                            {{ item.attendance?.check_out || '—' }}
                        </span>
                    </template>

                    <!-- Late or Overtime Slot -->
                    <template #cell-timing="{ item }">
                        <template v-if="item.attendance?.late_minutes > 0">
                            <span
                                class="px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-warning-soft text-warning border border-warning/20"
                            >
                                {{ toEnglish(item.attendance.late_minutes) }}
                                {{ $t('common.minutes') }}
                            </span>
                        </template>
                        <template v-else-if="item.attendance?.overtime_minutes > 0">
                            <span
                                class="px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-success-soft text-success border border-success/20"
                            >
                                +{{ toEnglish(item.attendance.overtime_minutes) }}
                                {{ $t('common.minutes') }}
                            </span>
                        </template>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <!-- Actions Column Slot -->
                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-center gap-1.5" @click.stop>
                            <!-- Quick Check In -->
                            <button
                                v-if="
                                    !item.attendance &&
                                    (can('hr.attendance.manage') || isAnyAdmin())
                                "
                                @click="checkIn(item)"
                                type="button"
                                class="px-2.5 py-1 text-xs font-semibold bg-success-soft text-success hover:bg-success hover:text-white rounded-lg border border-success/20 transition-colors cursor-pointer"
                            >
                                {{ $t('hr.attendance.check_in') }}
                            </button>

                            <!-- Quick Check Out -->
                            <button
                                v-if="
                                    item.attendance &&
                                    !item.attendance.check_out &&
                                    item.attendance.status === 'present' &&
                                    (can('hr.attendance.manage') || isAnyAdmin())
                                "
                                @click="checkOut(item)"
                                type="button"
                                class="px-2.5 py-1 text-xs font-semibold bg-warning-soft text-warning hover:bg-warning hover:text-white rounded-lg border border-warning/20 transition-colors cursor-pointer"
                            >
                                {{ $t('hr.attendance.check_out') }}
                            </button>

                            <!-- Quick Absent -->
                            <button
                                v-if="
                                    !item.attendance &&
                                    (can('hr.attendance.manage') || isAnyAdmin())
                                "
                                @click="markAbsent(item)"
                                type="button"
                                class="px-2.5 py-1 text-xs font-semibold bg-danger-soft text-danger hover:bg-danger hover:text-white rounded-lg border border-danger/20 transition-colors cursor-pointer"
                            >
                                {{ $t('hr.attendance.absent') }}
                            </button>

                            <!-- Edit Attendance Modal Button -->
                            <Tooltip
                                v-if="
                                    item.attendance && (can('hr.attendance.manage') || isAnyAdmin())
                                "
                                :text="$t('common.edit')"
                            >
                                <button
                                    @click="editAttendance(item)"
                                    type="button"
                                    class="p-1.5 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
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
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                        />
                                    </svg>
                                </button>
                            </Tooltip>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Edit Attendance Modal -->
        <BaseModal :show="editingAttendance !== null" @close="editingAttendance = null" size="md">
            <template #title>
                {{ $t('hr.attendance.update_status') }}
            </template>

            <div v-if="editingAttendance" class="space-y-4">
                <div>
                    <SearchableSelect
                        v-model="editForm.status"
                        :options="attendanceStatusOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('hr.attendance.status')"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <AppInput
                        v-model="editForm.check_in"
                        type="time"
                        :label="$t('hr.attendance.check_in')"
                    />
                    <AppInput
                        v-model="editForm.check_out"
                        type="time"
                        :label="$t('hr.attendance.check_out')"
                    />
                </div>

                <AppTextarea
                    v-model="editForm.notes"
                    :label="$t('hr.attendance.notes')"
                    :rows="3"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-subtle">
                    <AppButton type="button" variant="secondary" @click="editingAttendance = null">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton type="button" variant="primary" @click="updateAttendance">
                        {{ $t('common.save') }}
                    </AppButton>
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const { success, error } = useToast();
const { getName } = useLocalized();
const { toEnglish } = useNumberFormat();
const { can, isAnyAdmin } = usePermission();

const selectedDate = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const attendanceStatusOptions = computed(() => [
    { value: 'present', label: t('hr.attendance.present') },
    { value: 'absent', label: t('hr.attendance.absent') },
    { value: 'late', label: t('hr.attendance.late') },
    { value: 'leave', label: t('hr.attendance.leave') },
    { value: 'holiday', label: t('hr.attendance.holiday') },
]);

const statusTabs = computed(() => [
    { id: '', label: t('common.all'), count: toEnglish(props.stats?.total || 0) },
    {
        id: 'present',
        label: t('hr.attendance.present'),
        count: toEnglish(props.stats?.present || 0),
    },
    { id: 'absent', label: t('hr.attendance.absent'), count: toEnglish(props.stats?.absent || 0) },
    { id: 'late', label: t('hr.attendance.late'), count: toEnglish(props.stats?.late_count || 0) },
    { id: 'leave', label: t('hr.attendance.leave'), count: toEnglish(props.stats?.leave || 0) },
]);

const hasActiveFilters = computed(() => {
    return Boolean(searchQuery.value || statusFilter.value);
});

const editingAttendance = ref(null);
const editForm = reactive({
    id: null,
    status: 'present',
    check_in: null,
    check_out: null,
    notes: '',
});

const columns = computed(() => [
    {
        key: 'employee',
        label: t('common.employee'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'status',
        label: t('hr.attendance.status'),
        sortable: false,
        align: 'center',
    },
    {
        key: 'shift',
        label: t('hr.employees.shift'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'check_in',
        label: t('hr.attendance.check_in'),
        sortable: false,
        align: 'center',
    },
    {
        key: 'check_out',
        label: t('hr.attendance.check_out'),
        sortable: false,
        align: 'center',
    },
    {
        key: 'timing',
        label: t('hr.attendance.late'),
        sortable: false,
        align: 'center',
    },
    {
        key: 'actions',
        label: t('common.actions'),
        sortable: false,
        align: 'center',
    },
]);

watch(selectedDate, () => {
    applyFilters();
});

function applyFilters() {
    router.get(
        route('app.hr.attendance.index'),
        {
            date: selectedDate.value,
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
}

function handleStatusChange(status) {
    statusFilter.value = status;
    applyFilters();
}

function handleResetFilters() {
    searchQuery.value = '';
    statusFilter.value = '';
    applyFilters();
}

function changeDate(days) {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() + days);
    selectedDate.value = date.toISOString().split('T')[0];
}

function getStatusClass(status) {
    switch (status) {
        case 'present':
            return 'bg-success-soft text-success border-success/20';
        case 'absent':
            return 'bg-danger-soft text-danger border-danger/20';
        case 'late':
            return 'bg-warning-soft text-warning border-warning/20';
        case 'leave':
            return 'bg-info-soft text-info border-info/20';
        default:
            return 'bg-surface-secondary text-content-secondary border-border-subtle';
    }
}

function checkIn(employee) {
    const time = new Date().toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
    });

    router.post(
        route('app.hr.attendance.store'),
        {
            employee_id: employee.id,
            date: selectedDate.value,
            status: 'present',
            check_in: time,
        },
        {
            preserveScroll: true,
            onError: () => error(t('common.error_occurred')),
        }
    );
}

function checkOut(employee) {
    const time = new Date().toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
    });

    router.put(
        route('app.hr.attendance.update', employee.attendance.id),
        {
            status: 'present',
            check_out: time,
        },
        {
            preserveScroll: true,
            onError: () => error(t('common.error_occurred')),
        }
    );
}

function markAbsent(employee) {
    router.post(
        route('app.hr.attendance.store'),
        {
            employee_id: employee.id,
            date: selectedDate.value,
            status: 'absent',
        },
        {
            preserveScroll: true,
            onError: () => error(t('common.error_occurred')),
        }
    );
}

function editAttendance(employee) {
    editingAttendance.value = employee;
    editForm.id = employee.attendance.id;
    editForm.status = employee.attendance.status;
    editForm.check_in = employee.attendance.check_in;
    editForm.check_out = employee.attendance.check_out;
    editForm.notes = employee.attendance.notes;
}

function updateAttendance() {
    router.put(
        route('app.hr.attendance.update', editForm.id),
        {
            status: editForm.status,
            check_in: editForm.check_in,
            check_out: editForm.check_out,
            notes: editForm.notes,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingAttendance.value = null;
                success(t('common.saved_success'));
            },
            onError: () => error(t('common.error_occurred')),
        }
    );
}

function printAttendance() {
    window.open(route('app.hr.attendance.print', { date: selectedDate.value }), '_blank');
}
</script>
