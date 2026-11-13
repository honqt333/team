<template>
    <AppLayout :title="$t('hr.employees.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('hr.employees.title')"
                :subtitle="$t('hr.employees.subtitle')"
                :totalCount="toEnglish((counts?.active || 0) + (counts?.inactive || 0))"
                :countLabel="$t('hr.employees.title')"
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
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppPrintButton variant="icon" @click="printEmployees" />

                            <!-- Divider -->
                            <div class="w-px h-5 bg-border-subtle mx-0.5"></div>

                            <!-- View Mode Toggle -->
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="employees_view_mode"
                            />
                        </div>

                        <!-- Add Employee CTA (Anchored Outer Left in RTL) -->
                        <button
                            v-if="can('hr.employees.create') || isAnyAdmin()"
                            @click="showAddModal = true"
                            type="button"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span class="text-sm">
                                {{ $t('hr.employees.add') }}
                            </span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-3 no-print">
                        <!-- Status Tabs -->
                        <div class="w-full md:w-auto">
                            <AppTabs
                                :model-value="tableFilters.status"
                                :tabs="statusTabs"
                                variant="pills"
                                size="sm"
                                @update:model-value="handleStatusChange"
                            />
                        </div>

                        <!-- Center Filter (if more than 1 center) -->
                        <div class="w-full md:w-56" v-if="centers && centers.length > 1">
                            <SearchableSelect
                                v-model="tableFilters.center_id"
                                :options="[{ id: '', name: $t('common.all_centers') }, ...centers]"
                                option-label="name"
                                option-value="id"
                                :placeholder="$t('common.all_centers')"
                                :label="''"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div
                                class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none text-content-muted group-focus-within:text-action-primary transition-colors"
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
                                v-model="tableFilters.search"
                                type="text"
                                :placeholder="$t('common.search')"
                                class="w-full ps-11 pe-10 py-2.5 bg-surface-primary dark:bg-surface-secondary/50 border border-border-subtle rounded-xl text-sm text-content-primary placeholder-content-muted focus:outline-hidden focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all shadow-2xs"
                                @keyup.enter="applyFilters"
                            />
                            <button
                                v-if="tableFilters.search"
                                @click="
                                    tableFilters.search = '';
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

                        <!-- Reset Button -->
                        <button
                            v-if="hasActiveFilters"
                            @click="handleResetFilters"
                            type="button"
                            class="p-2.5 text-content-secondary hover:text-action-primary bg-surface-primary dark:bg-surface-secondary/50 hover:bg-surface-secondary border border-border-subtle rounded-xl transition-colors cursor-pointer shadow-2xs shrink-0"
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

            <!-- Main Content Area -->
            <div>
                <!-- Empty State -->
                <div v-if="!employees.data || employees.data.length === 0" class="py-8">
                    <AppEmptyState
                        :title="$t('hr.employees.no_employees')"
                        :description="$t('hr.employees.empty_hint')"
                        :action-text="
                            can('hr.employees.create') || isAnyAdmin() ? $t('hr.employees.add') : ''
                        "
                        @action="showAddModal = true"
                    />
                </div>

                <!-- GRID VIEW -->
                <div v-else-if="viewMode === 'grid'" class="space-y-6">
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"
                    >
                        <div
                            v-for="employee in employees.data"
                            :key="employee.id"
                            class="bg-surface-primary dark:bg-surface-secondary/40 rounded-2xl border border-border-subtle p-5 shadow-2xs hover:shadow-md hover:border-action-primary/30 transition-all flex flex-col justify-between group relative overflow-hidden"
                        >
                            <!-- Top Status Badge -->
                            <div class="absolute top-4 end-4">
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[11px] font-bold border shadow-2xs',
                                        employee.status === 'active'
                                            ? 'bg-success-soft text-success border-success/20'
                                            : 'bg-surface-secondary text-content-muted border-border-subtle',
                                    ]"
                                >
                                    {{ $t(`common.${employee.status}`) }}
                                </span>
                            </div>

                            <!-- Avatar & Names -->
                            <div class="flex flex-col items-center text-center pt-2">
                                <div
                                    class="w-16 h-16 mb-3 rounded-full bg-surface-secondary border-2 border-surface-primary shadow-xs flex items-center justify-center overflow-hidden"
                                >
                                    <img
                                        v-if="employee.photo_path"
                                        :src="`/storage/${employee.photo_path}`"
                                        :alt="getName(employee)"
                                        class="w-full h-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex items-center justify-center bg-action-primary/10 text-action-primary text-xl font-bold font-heading"
                                    >
                                        {{
                                            employee.name_ar?.charAt(0) ||
                                            employee.name_en?.charAt(0) ||
                                            '؟'
                                        }}
                                    </div>
                                </div>

                                <h3
                                    class="text-base font-bold text-content-primary mb-0.5 line-clamp-1 group-hover:text-action-primary transition-colors"
                                >
                                    {{ getName(employee) }}
                                </h3>
                                <p class="text-xs text-content-muted mb-1 line-clamp-1">
                                    {{
                                        employee.job_title
                                            ? getName(employee.job_title)
                                            : $t('hr.employees.no_job_title')
                                    }}
                                </p>
                                <span
                                    class="px-2 py-0.5 rounded-md font-mono text-[11px] text-content-muted bg-surface-secondary border border-border-subtle"
                                >
                                    {{ employee.employee_number }}
                                </span>
                            </div>

                            <!-- Card Details -->
                            <div
                                class="w-full pt-4 mt-4 border-t border-border-subtle space-y-2 text-xs"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-content-muted">
                                        {{ $t('common.department') }}
                                    </span>
                                    <span class="font-medium text-content-primary">
                                        {{
                                            employee.department ? getName(employee.department) : '—'
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="employee.center"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-content-muted">
                                        {{ $t('hr.employees.branch') }}
                                    </span>
                                    <span class="font-medium text-action-primary">
                                        {{ employee.center.name }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-content-muted">
                                        {{ $t('hr.employees.base_salary') }}
                                    </span>
                                    <span class="font-mono font-medium text-content-primary">
                                        {{ currency(employee.base_salary || 0) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-content-muted">{{ $t('common.phone') }}</span>
                                    <span class="font-mono text-content-secondary" dir="ltr">
                                        {{ employee.phone || '—' }}
                                    </span>
                                </div>

                                <!-- ID / Iqama Expiry Date Badge & Unassigned Permissions Indicator -->
                                <div
                                    v-if="
                                        getEmployeeIdExpiryDate(employee) ||
                                        employee.no_permissions_set ||
                                        employee.has_permissions_configured === false
                                    "
                                    class="pt-2 border-t border-border-subtle/60 flex flex-col gap-1.5"
                                >
                                    <div
                                        v-if="getEmployeeIdExpiryDate(employee)"
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            :class="[
                                                'w-full inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border',
                                                getIdExpiryBadgeClass(employee),
                                            ]"
                                            :title="getIdExpiryTitle(employee)"
                                        >
                                            <span class="font-mono" dir="ltr">
                                                {{ getEmployeeIdExpiryDate(employee) }}
                                            </span>
                                            <span>({{ $t('hr.employees.national_id') }})</span>
                                            <span
                                                v-if="getIdExpiryStatusText(employee)"
                                                class="text-[10px] font-bold"
                                            >
                                                • {{ getIdExpiryStatusText(employee) }}
                                            </span>
                                        </span>
                                    </div>

                                    <div
                                        v-if="
                                            employee.no_permissions_set ||
                                            employee.has_permissions_configured === false
                                        "
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold border bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800"
                                        >
                                            <span class="font-black">!</span>
                                            <span>{{ $t('hr.employees.no_permissions_set') }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Action Link -->
                            <div class="pt-4 mt-2">
                                <Link
                                    :href="route('app.hr.employees.show', employee.id)"
                                    class="w-full flex items-center justify-center gap-1.5 py-2 px-3 bg-surface-secondary hover:bg-action-primary hover:text-white text-content-primary rounded-xl text-xs font-bold transition-colors"
                                >
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
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <span>{{ $t('common.view') }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Pagination -->
                    <AppPagination
                        :links="employees.links"
                        :from="employees.from"
                        :to="employees.to"
                        :total="employees.total"
                    />
                </div>

                <!-- LIST / TABLE VIEW -->
                <div v-else class="space-y-4">
                    <DataTable
                        :data="employees"
                        :columns="columns"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        :loading="isLoading"
                        @sort="toggleSort"
                    >
                        <!-- Number Slot -->
                        <template #cell-employee_number="{ item }">
                            <span class="font-mono text-xs font-semibold text-content-secondary">
                                {{ item.employee_number }}
                            </span>
                        </template>

                        <!-- Name & Avatar Slot -->
                        <template #cell-name="{ item }">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-surface-secondary border border-border-subtle flex items-center justify-center overflow-hidden shrink-0"
                                >
                                    <img
                                        v-if="item.photo_path"
                                        :src="`/storage/${item.photo_path}`"
                                        :alt="getName(item)"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else class="text-xs font-bold text-action-primary">
                                        {{
                                            item.name_ar?.charAt(0) ||
                                            item.name_en?.charAt(0) ||
                                            '؟'
                                        }}
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <Link
                                        :href="route('app.hr.employees.show', item.id)"
                                        class="font-semibold text-content-primary hover:text-action-primary transition-colors line-clamp-1"
                                    >
                                        {{ getName(item) }}
                                    </Link>
                                    <p
                                        v-if="item.name_en && item.name_ar !== item.name_en"
                                        class="text-xs text-content-muted line-clamp-1"
                                    >
                                        {{ item.name_en }}
                                    </p>
                                    <div
                                        v-if="
                                            getEmployeeIdExpiryDate(item) ||
                                            item.no_permissions_set ||
                                            item.has_permissions_configured === false
                                        "
                                        class="flex flex-wrap items-center gap-1 mt-1"
                                    >
                                        <span
                                            v-if="getEmployeeIdExpiryDate(item)"
                                            :class="[
                                                'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold border',
                                                getIdExpiryBadgeClass(item),
                                            ]"
                                            :title="getIdExpiryTitle(item)"
                                        >
                                            <span class="font-mono" dir="ltr">
                                                {{ getEmployeeIdExpiryDate(item) }}
                                            </span>
                                            <span>({{ $t('hr.employees.national_id') }})</span>
                                            <span
                                                v-if="getIdExpiryStatusText(item)"
                                                class="text-[9px] font-bold"
                                            >
                                                • {{ getIdExpiryStatusText(item) }}
                                            </span>
                                        </span>
                                        <span
                                            v-if="
                                                item.no_permissions_set ||
                                                item.has_permissions_configured === false
                                            "
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800"
                                        >
                                            <span class="font-black">!</span>
                                            <span>{{ $t('hr.employees.no_permissions_set') }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Job Title Slot -->
                        <template #cell-job_title="{ item }">
                            <span class="text-xs text-content-secondary">
                                {{ item.job_title ? getName(item.job_title) : '—' }}
                            </span>
                        </template>

                        <!-- Branch Slot -->
                        <template #cell-center="{ item }">
                            <span class="text-xs text-content-secondary">
                                {{ item.center?.name || '—' }}
                            </span>
                        </template>

                        <!-- Department Slot -->
                        <template #cell-department="{ item }">
                            <span class="text-xs text-content-secondary">
                                {{ item.department ? getName(item.department) : '—' }}
                            </span>
                        </template>

                        <!-- Salary Slot -->
                        <template #cell-base_salary="{ item }">
                            <span class="font-mono text-xs font-bold text-content-primary">
                                {{ currency(item.base_salary || 0) }}
                            </span>
                        </template>

                        <!-- Phone Slot -->
                        <template #cell-phone="{ item }">
                            <span class="font-mono text-xs text-content-secondary" dir="ltr">
                                {{ item.phone || '—' }}
                            </span>
                        </template>

                        <!-- Status Slot -->
                        <template #cell-status="{ item }">
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-xs font-bold border shadow-2xs',
                                    item.status === 'active'
                                        ? 'bg-success-soft text-success border-success/20'
                                        : 'bg-surface-secondary text-content-muted border-border-subtle',
                                ]"
                            >
                                {{ $t(`common.${item.status}`) }}
                            </span>
                        </template>

                        <!-- Actions Slot -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center" @click.stop>
                                <Tooltip :text="$t('common.view')">
                                    <Link
                                        :href="route('app.hr.employees.show', item.id)"
                                        class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-xl transition-all"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </Link>
                                </Tooltip>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Add Employee Modal -->
        <EmployeeFormModal
            :show="showAddModal"
            :job-titles="jobTitles"
            :departments="departments"
            :nationalities="nationalities"
            :centers="centers"
            :shifts="shifts"
            @close="showAddModal = false"
            @saved="showAddModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BackButton from '@/Components/BackButton.vue';
import Tooltip from '@/Components/Tooltip.vue';
import EmployeeFormModal from '@/Components/HR/EmployeeFormModal.vue';
import { usePermission } from '@/Composables/usePermission';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { getName } = useLocalized();
const { toEnglish, formatCurrency } = useNumberFormat();
const currency = (val) => formatCurrency(val);

const props = defineProps({
    employees: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    counts: {
        type: Object,
        default: () => ({ active: 0, inactive: 0 }),
    },
    jobTitles: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    nationalities: {
        type: Array,
        default: () => [],
    },
    centers: {
        type: Array,
        default: () => [],
    },
    shifts: {
        type: Array,
        default: () => [],
    },
});

const showAddModal = ref(false);
const viewMode = ref(localStorage.getItem('employees_view_mode') || 'grid');

const statusTabs = computed(() => [
    {
        id: 'active',
        label: t('common.active'),
        count: toEnglish(props.counts?.active || 0),
    },
    {
        id: 'inactive',
        label: t('common.inactive'),
        count: toEnglish(props.counts?.inactive || 0),
    },
]);

const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
} = useServerTable({
    routeUrl: () => route('app.hr.employees.index'),
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || 'active',
        center_id: props.filters?.center_id || '',
    },
    initialSort: {
        column: props.filters?.sort || 'name_ar',
        direction: props.filters?.direction || 'asc',
    },
});

const hasActiveFilters = computed(() => {
    return Boolean(
        tableFilters.search ||
        tableFilters.center_id ||
        (tableFilters.status && tableFilters.status !== 'active')
    );
});

const handleStatusChange = (status) => {
    tableFilters.status = status;
    applyFilters();
};

const handleResetFilters = () => {
    tableFilters.search = '';
    tableFilters.center_id = '';
    tableFilters.status = 'active';
    applyFilters();
};

const columns = computed(() => [
    {
        key: 'employee_number',
        label: t('hr.employees.number'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'name',
        label: t('common.name'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'job_title',
        label: t('hr.employees.job_title'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'center',
        label: t('hr.employees.branch'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'department',
        label: t('common.department'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'base_salary',
        label: t('hr.employees.base_salary'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'phone',
        label: t('common.phone'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'status',
        label: t('common.status'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'actions',
        label: t('common.actions'),
        sortable: false,
        align: 'center',
    },
]);

const printEmployees = () => {
    window.open(route('app.hr.employees.print'), '_blank');
};

const getEmployeeIdExpiryDate = (employee) => {
    return employee?.id_expiry_date || employee?.national_id_expiry || null;
};

const getEmployeeIdExpiryStatus = (employee) => {
    if (employee?.id_expiry_status) {
        return employee.id_expiry_status;
    }
    const dateStr = getEmployeeIdExpiryDate(employee);
    if (!dateStr) return null;
    const expiry = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (expiry < today) return 'expired';
    const in30Days = new Date(today);
    in30Days.setDate(in30Days.getDate() + 30);
    if (expiry <= in30Days) return 'expiring_soon';
    return 'valid';
};

const getIdExpiryBadgeClass = (employee) => {
    const status = getEmployeeIdExpiryStatus(employee);
    if (status === 'expired') {
        return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800';
    }
    if (status === 'expiring_soon') {
        return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800';
    }
    return 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-800';
};

const getIdExpiryStatusText = (employee) => {
    const status = getEmployeeIdExpiryStatus(employee);
    if (!status) return '';
    return t(`hr.employees.id_expiry_statuses.${status}`);
};

const getIdExpiryTitle = (employee) => {
    const date = getEmployeeIdExpiryDate(employee);
    const text = getIdExpiryStatusText(employee);
    return date && text ? `${date} (${t('hr.employees.national_id')} - ${text})` : '';
};
</script>
