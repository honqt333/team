<template>
    <AppLayout :title="$t('inventory.transfers.title')">
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.transfers.title')"
                :subtitle="$t('inventory.transfers.subtitle')"
                :totalCount="transfers?.total ? toEnglish(transfers.total) : '0'"
                :countLabel="$t('inventory.transfers.title')"
            >
                <template #back>
                    <BackButton :href="route('app.inventory.hub')" />
                </template>
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
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Glass Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppPrintButton
                                @click="handlePrint"
                                variant="icon"
                                :title="$t('common.print')"
                            />
                            <div class="w-px h-5 bg-border-subtle mx-0.5"></div>
                            <AppViewModeToggle v-model="viewMode" />
                        </div>

                        <!-- Add Transfer Button (Outer Left in RTL) -->
                        <button
                            v-if="can('inventory.moves.create') || isAnyAdmin()"
                            @click="showCreateModal = true"
                            type="button"
                            class="btn btn-primary shadow-sm hover:shadow transition-all text-sm font-semibold inline-flex items-center gap-2"
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
                            <span>{{ $t('inventory.transfers.add') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-wrap items-end gap-3 no-print">
                        <!-- Search Box -->
                        <div class="relative group min-w-[200px] flex-1 sm:flex-initial sm:w-64">
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
                                v-model="tableFilters.search"
                                type="text"
                                :placeholder="$t('inventory.transfers.search_placeholder')"
                                class="input-base ps-10 pe-4 py-2.5 text-sm w-full rounded-2xl"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-48">
                            <label
                                class="block text-[11px] font-medium text-content-muted mb-1 px-1"
                            >
                                {{ $t('common.status') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.status"
                                :options="statusOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('inventory.transfers.all_statuses')"
                                class="!rounded-2xl"
                                compact
                                @change="() => applyFilters()"
                            />
                        </div>

                        <!-- Warehouse Filter -->
                        <div class="w-full sm:w-56">
                            <label
                                class="block text-[11px] font-medium text-content-muted mb-1 px-1"
                            >
                                {{ $t('nav.inventory') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.warehouse_id"
                                :options="warehouseOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('common.all')"
                                class="!rounded-2xl"
                                compact
                                @change="() => applyFilters()"
                            />
                        </div>

                        <!-- Reset Button -->
                        <button
                            v-if="hasActiveFilters"
                            @click="resetAllFilters"
                            type="button"
                            class="px-3.5 py-2.5 text-xs font-semibold text-danger hover:bg-danger-soft rounded-2xl border border-danger/20 transition-colors inline-flex items-center gap-1.5 self-end"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                            <span>{{ $t('common.reset_filters') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Transfers Content (Grid or List) -->
            <div class="space-y-6">
                <!-- GRID VIEW -->
                <div v-if="viewMode === 'grid'">
                    <div
                        v-if="!transfers?.data?.length"
                        class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                    >
                        <AppEmptyState
                            :title="$t('inventory.transfers.title')"
                            :description="
                                $t('inventory.transfers.empty_hint') ||
                                $t('common.no_records_found')
                            "
                            :action-text="
                                can('inventory.moves.create') || isAnyAdmin()
                                    ? $t('inventory.transfers.add')
                                    : ''
                            "
                            @action="showCreateModal = true"
                        />
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5"
                    >
                        <Link
                            v-for="transfer in transfers.data"
                            :key="transfer.id"
                            :href="route('app.inventory.transfers.show', transfer.id)"
                            class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden p-5"
                        >
                            <!-- Card Header -->
                            <div
                                class="flex items-center justify-between gap-2 pb-3 border-b border-border-subtle"
                            >
                                <span
                                    class="font-bold font-mono text-sm text-action-primary group-hover:underline"
                                >
                                    #{{ transfer.code }}
                                </span>
                                <span :class="statusClass(transfer.status)">
                                    {{ $t('inventory.transfers.statuses.' + transfer.status) }}
                                </span>
                            </div>

                            <!-- Warehouse Routing -->
                            <div class="py-4 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-content-muted">
                                        {{ $t('inventory.transfers.from') }}
                                    </span>
                                    <span
                                        class="font-semibold text-content-primary truncate max-w-[150px]"
                                    >
                                        {{ transfer.from_warehouse?.name || '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-center text-content-muted">
                                    <svg
                                        class="w-4 h-4 rtl:rotate-180"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                                        />
                                    </svg>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-content-muted">
                                        {{ $t('inventory.transfers.to') }}
                                    </span>
                                    <span
                                        class="font-semibold text-content-primary truncate max-w-[150px]"
                                    >
                                        {{ transfer.to_warehouse?.name || '—' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Footer Info -->
                            <div
                                class="pt-3 border-t border-border-subtle mt-auto flex items-center justify-between text-[11px] text-content-secondary"
                            >
                                <span class="font-mono">
                                    {{ transfer.items_count || 0 }}
                                    {{ $t('inventory.transfers.items') }}
                                </span>
                                <span class="font-mono">
                                    {{ formatDate(transfer.created_at) }}
                                </span>
                            </div>
                        </Link>
                    </div>

                    <!-- Pagination for Grid View -->
                    <div v-if="transfers?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="transfers.links"
                            :from="transfers.from"
                            :to="transfers.to"
                            :total="transfers.total"
                        />
                    </div>
                </div>

                <!-- LIST VIEW (DataTable) -->
                <div v-else>
                    <DataTable
                        :columns="columns"
                        :data="transfers"
                        :loading="isLoading"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        @sort="toggleSort"
                        :empty-title="$t('inventory.transfers.title')"
                        :empty-description="
                            $t('inventory.transfers.empty_hint') || $t('common.no_records_found')
                        "
                        :empty-action-text="
                            can('inventory.moves.create') || isAnyAdmin()
                                ? $t('inventory.transfers.add')
                                : ''
                        "
                        @empty-action="showCreateModal = true"
                    >
                        <!-- Index Column -->
                        <template #cell-index="{ index }">
                            <span class="text-xs text-content-muted font-mono">
                                {{ toEnglish((transfers.from || 1) + index) }}
                            </span>
                        </template>

                        <!-- Code Column -->
                        <template #cell-code="{ item }">
                            <Link
                                :href="route('app.inventory.transfers.show', item.id)"
                                class="font-bold text-action-primary hover:underline font-mono"
                            >
                                #{{ item.code }}
                            </Link>
                        </template>

                        <!-- From Warehouse Column -->
                        <template #cell-from_warehouse="{ item }">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-danger/80"></span>
                                <span class="text-sm font-medium text-content-primary">
                                    {{ item.from_warehouse?.name || '—' }}
                                </span>
                            </div>
                        </template>

                        <!-- To Warehouse Column -->
                        <template #cell-to_warehouse="{ item }">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-success/80"></span>
                                <span class="text-sm font-medium text-content-primary">
                                    {{ item.to_warehouse?.name || '—' }}
                                </span>
                            </div>
                        </template>

                        <!-- Items Count Column -->
                        <template #cell-items_count="{ item }">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-surface-secondary text-content-secondary font-mono"
                            >
                                {{ toEnglish(item.items_count || 0) }}
                            </span>
                        </template>

                        <!-- Status Column -->
                        <template #cell-status="{ item }">
                            <span :class="statusClass(item.status)">
                                {{ $t('inventory.transfers.statuses.' + item.status) }}
                            </span>
                        </template>

                        <!-- Created At Column -->
                        <template #cell-created_at="{ item }">
                            <span class="text-xs text-content-secondary font-mono" dir="ltr">
                                {{ formatDate(item.created_at) }}
                            </span>
                        </template>

                        <!-- Actions Column -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center">
                                <Tooltip :content="$t('common.view')">
                                    <Link
                                        :href="route('app.inventory.transfers.show', item.id)"
                                        class="p-2 rounded-lg text-content-muted hover:text-action-primary hover:bg-action-primary-soft transition-colors"
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
        </PrintEngine>

        <!-- Create Modal -->
        <CreateModal
            :show="showCreateModal"
            :warehouses="warehouses"
            @close="showCreateModal = false"
        />
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import CreateModal from './CreateModal.vue';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    transfers: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    warehouses: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);

// View mode state (persisted)
const viewMode = ref(localStorage.getItem('inventoryTransfersViewMode') || 'list');
watch(viewMode, (newMode) => {
    localStorage.setItem('inventoryTransfersViewMode', newMode);
});

// Status options for dropdown filter
const statusOptions = computed(() => [
    { value: '', label: t('inventory.transfers.all_statuses') },
    { value: 'draft', label: t('inventory.transfers.statuses.draft') },
    { value: 'sent', label: t('inventory.transfers.statuses.sent') },
    { value: 'received', label: t('inventory.transfers.statuses.received') },
    { value: 'cancelled', label: t('inventory.transfers.statuses.cancelled') },
]);

// Warehouse options
const warehouseOptions = computed(() => {
    const allOption = { value: '', label: t('common.all') };
    const list = (props.warehouses || []).map((w) => ({
        value: w.id,
        label: w.name,
    }));
    return [allOption, ...list];
});

// Server Table Composable
const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
    cancelDebouncedSearch,
} = useServerTable({
    routeUrl: '/app/inventory/transfers',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        warehouse_id: props.filters?.warehouse_id || '',
    },
    initialSort: {
        column: props.filters?.sort || 'created_at',
        direction: props.filters?.direction || 'desc',
    },
    debounceMs: 350,
});

// Synchronize props.filters with tableFilters when Inertia updates
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (newFilters.status !== undefined && newFilters.status !== tableFilters.status) {
                tableFilters.status = newFilters.status || '';
            }
            if (
                newFilters.warehouse_id !== undefined &&
                newFilters.warehouse_id !== tableFilters.warehouse_id
            ) {
                tableFilters.warehouse_id = newFilters.warehouse_id || '';
            }
            if (newFilters.sort !== undefined && newFilters.sort !== sortColumn.value) {
                sortColumn.value = newFilters.sort || 'created_at';
            }
            if (
                newFilters.direction !== undefined &&
                newFilters.direction !== sortDirection.value
            ) {
                sortDirection.value = newFilters.direction || 'desc';
            }
        }
    },
    { deep: true }
);

// Reset Filters
const hasActiveFilters = computed(() => {
    return Boolean(
        tableFilters.search ||
        tableFilters.status ||
        tableFilters.warehouse_id ||
        (sortColumn.value && sortColumn.value !== 'created_at')
    );
});

const resetAllFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.status = '';
    tableFilters.warehouse_id = '';
    sortColumn.value = 'created_at';
    sortDirection.value = 'desc';
    applyFilters();
};

// Columns Definitions
const columns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: false },
    { key: 'code', label: t('inventory.transfers.code'), align: 'center', sortable: true },
    { key: 'from_warehouse', label: t('inventory.transfers.from'), align: 'start', sortable: true },
    { key: 'to_warehouse', label: t('inventory.transfers.to'), align: 'start', sortable: true },
    { key: 'items_count', label: t('inventory.transfers.items'), align: 'center', sortable: true },
    { key: 'status', label: t('common.status'), align: 'center', sortable: true },
    { key: 'created_at', label: t('common.created_at'), align: 'center', sortable: true },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-20', sortable: false },
]);

const statusClass = (status) => {
    const map = {
        draft: 'bg-surface-secondary text-content-secondary border border-border-subtle',
        sent: 'bg-action-primary-soft text-action-primary border border-action-primary/20',
        received: 'bg-success-soft text-success border border-success/20',
        cancelled: 'bg-danger-soft text-danger border border-danger/20',
    };
    return `inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold ${map[status] || map.draft}`;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const handlePrint = () => {
    printDocument();
};
</script>
