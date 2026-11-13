<template>
    <AppLayout :title="$t('departments.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('departments.title')"
                :subtitle="$t('departments.subtitle')"
                :totalCount="departments ? toEnglish(departments.total) : '0'"
                :countLabel="$t('departments.title')"
            >
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
                            stroke-width="2.2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppPrintButton
                                variant="icon"
                                @click="handlePrint"
                                :title="$t('common.print')"
                            />

                            <div class="w-px h-6 bg-border-subtle mx-1"></div>

                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="departments_view_mode"
                            />
                        </div>

                        <!-- Add Button (Anchored Outer Left in RTL) -->
                        <button
                            v-if="canManage"
                            @click="openCreateModal"
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
                                {{ $t('departments.add') }}
                            </span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-3 no-print">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div
                                class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none text-content-muted group-focus-within:text-action-primary transition-colors"
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
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="tableFilters.search"
                                :placeholder="$t('departments.search')"
                                class="block w-full ps-11 pe-4 py-2.5 text-sm text-content-primary bg-surface-primary border border-border-subtle rounded-xl focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all outline-none placeholder-content-muted shadow-xs"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <!-- Status Filter -->
                            <div class="w-full sm:w-44">
                                <SearchableSelect
                                    v-model="tableFilters.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('common.status')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Reset Button -->
                            <button
                                v-if="hasActiveFilters"
                                @click="handleResetFilters"
                                type="button"
                                class="p-2.5 text-content-muted hover:text-danger hover:bg-danger-soft rounded-xl border border-border-subtle bg-surface-primary transition-colors cursor-pointer shadow-xs"
                                :title="$t('common.reset_filter') || $t('common.reset')"
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
                    </div>
                </template>
            </PageHeader>

            <!-- Loading State -->
            <AppTableSkeleton v-if="!departments" :rows="5" :cols="5" />

            <!-- Main Content -->
            <div v-else>
                <!-- Empty State -->
                <div
                    v-if="!departments.data || departments.data.length === 0"
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                >
                    <AppEmptyState
                        :title="$t('departments.empty')"
                        :description="$t('departments.empty_hint')"
                        :action-text="canManage ? $t('departments.add') : ''"
                        @action="openCreateModal"
                    />
                </div>

                <!-- GRID VIEW -->
                <div v-else-if="viewMode === 'grid'" class="space-y-6 no-print">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                    >
                        <div
                            v-for="dept in departments.data"
                            :key="dept.id"
                            class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden p-5"
                        >
                            <!-- Card Header -->
                            <div
                                class="flex items-center justify-between gap-3 pb-3 border-b border-border-subtle"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold text-white flex-shrink-0 bg-action-primary"
                                    >
                                        {{ getName(dept).charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="truncate">
                                        <h3 class="font-bold text-sm text-content-primary truncate">
                                            {{ getName(dept) }}
                                        </h3>
                                        <p
                                            v-if="dept.name_en && $i18n.locale === 'ar'"
                                            class="text-xs text-content-muted truncate"
                                        >
                                            {{ dept.name_en }}
                                        </p>
                                    </div>
                                </div>

                                <AppToggle
                                    :model-value="dept.is_active"
                                    @update:model-value="toggleActive(dept)"
                                    :disabled="togglingId === dept.id || !canManage"
                                    size="sm"
                                    color="success"
                                />
                            </div>

                            <!-- Body -->
                            <div class="py-4 space-y-2 flex-1">
                                <p class="text-xs text-content-secondary line-clamp-2">
                                    {{ dept.description || '—' }}
                                </p>
                            </div>

                            <!-- Card Footer -->
                            <div
                                class="pt-3 border-t border-border-subtle mt-auto flex items-center justify-between text-xs"
                            >
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-medium font-mono bg-surface-secondary text-content-secondary border border-border-subtle"
                                >
                                    {{ toEnglish(dept.services_count || 0) }}
                                    {{ $t('departments.services') }}
                                </span>

                                <div class="flex items-center gap-1">
                                    <Tooltip :text="$t('common.edit')">
                                        <button
                                            v-if="canManage"
                                            @click="openEditModal(dept)"
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
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                    </Tooltip>

                                    <Tooltip :text="$t('common.delete')">
                                        <button
                                            v-if="canManage"
                                            @click="handleDelete(dept)"
                                            type="button"
                                            class="p-1.5 text-content-secondary hover:text-danger hover:bg-danger-soft rounded-lg transition-colors cursor-pointer"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </Tooltip>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination for Grid -->
                    <div v-if="departments?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="departments.links"
                            :from="departments.from"
                            :to="departments.to"
                            :total="departments.total"
                        />
                    </div>
                </div>

                <!-- LIST VIEW (DataTable) -->
                <div v-else class="space-y-4 no-print">
                    <DataTable
                        :data="departments"
                        :columns="columns"
                        :loading="isLoading"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        @sort="toggleSort"
                        :empty-title="$t('departments.empty')"
                        :empty-description="$t('departments.empty_hint')"
                    >
                        <template #cell-name="{ item }">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0 bg-action-primary"
                                >
                                    {{ getName(item).charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-sm text-content-primary">
                                        {{ getName(item) }}
                                    </span>
                                    <span
                                        v-if="item.name_en && $i18n.locale === 'ar'"
                                        class="text-xs text-content-muted"
                                    >
                                        {{ item.name_en }}
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template #cell-description="{ item }">
                            <span class="text-xs text-content-secondary line-clamp-1 max-w-xs">
                                {{ item.description || '—' }}
                            </span>
                        </template>

                        <template #cell-services_count="{ item }">
                            <span
                                class="px-2.5 py-1 text-xs font-medium font-mono rounded-full bg-surface-secondary text-content-secondary border border-border-subtle"
                            >
                                {{ toEnglish(item.services_count || 0) }}
                                {{ $t('departments.services') }}
                            </span>
                        </template>

                        <template #cell-is_active="{ item }">
                            <AppToggle
                                :model-value="item.is_active"
                                @update:model-value="toggleActive(item)"
                                :disabled="togglingId === item.id || !canManage"
                                size="sm"
                                color="success"
                            />
                        </template>

                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1" @click.stop>
                                <Tooltip :text="$t('common.edit')">
                                    <button
                                        v-if="canManage"
                                        @click="openEditModal(item)"
                                        type="button"
                                        class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-xl transition-all cursor-pointer"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                </Tooltip>

                                <Tooltip :text="$t('common.delete')">
                                    <button
                                        v-if="canManage"
                                        @click="handleDelete(item)"
                                        type="button"
                                        class="p-2 text-content-secondary hover:text-danger hover:bg-danger-soft rounded-xl transition-all cursor-pointer"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </Tooltip>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Department Form Modal -->
        <DepartmentFormModal
            :show="showModal"
            :department="selectedDepartment"
            :existing-departments="departments?.data || []"
            @close="closeModal"
            @saved="handleSaved"
        />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('departments.title')"
                    :subtitle="$t('departments.subtitle')"
                />

                <DataTable structured class="w-full mt-6">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('departments.columns.name') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('departments.columns.description') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('departments.columns.services_count') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('departments.columns.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="dept in departments?.data || []" :key="dept.id" class="border-b">
                            <td class="px-4 py-3 text-center text-sm font-bold align-middle">
                                {{ getName(dept) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm align-middle">
                                {{ dept.description || '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ toEnglish(dept.services_count || 0) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm align-middle">
                                {{ dept.is_active ? $t('common.active') : $t('common.inactive') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <StandardReportFooter class="mt-8 border-t pt-4" />
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import DepartmentFormModal from '@/Components/DepartmentFormModal.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { confirm } = useConfirm();
const { success } = useToast();
const { getName } = useLocalized();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    departments: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const showModal = ref(false);
const selectedDepartment = ref(null);
const togglingId = ref(null);

const viewMode = ref(localStorage.getItem('departments_view_mode') || 'list');

const statusOptions = [
    { value: '', label: t('common.all') },
    { value: 'active', label: t('common.active') },
    { value: 'inactive', label: t('common.inactive') },
];

const canManage = computed(() => can('services.departments.manage') || isAnyAdmin());

const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
} = useServerTable({
    routeUrl: () => route('departments.index'),
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
    },
    initialSort: {
        column: props.filters?.sort || 'sort_order',
        direction: props.filters?.direction || 'asc',
    },
});

const hasActiveFilters = computed(() => {
    return Boolean(tableFilters.search || tableFilters.status);
});

const handleResetFilters = () => {
    tableFilters.search = '';
    tableFilters.status = '';
    applyFilters();
};

const columns = computed(() => [
    {
        key: 'name',
        label: t('departments.columns.name'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'description',
        label: t('departments.columns.description'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'services_count',
        label: t('departments.columns.services_count'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'is_active',
        label: t('departments.columns.status'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'actions',
        label: t('common.actions'),
        sortable: false,
        align: 'center',
    },
]);

function openCreateModal() {
    selectedDepartment.value = null;
    showModal.value = true;
}

function openEditModal(dept) {
    selectedDepartment.value = dept;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedDepartment.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments'] });
}

function toggleActive(dept) {
    togglingId.value = dept.id;
    router.patch(
        `/app/departments/${dept.id}/toggle-active`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingId.value = null;
            },
        }
    );
}

async function handleDelete(dept) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(dept)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    router.delete(`/app/departments/${dept.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            console.error('Failed to delete:', errors);
        },
    });
}

function handlePrint() {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.delete('page');
    window.open(url.toString(), '_blank');
}
</script>
