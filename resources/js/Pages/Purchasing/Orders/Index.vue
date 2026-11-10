<template>
    <AppLayout :title="$t('purchasing.orders.title')">
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('purchasing.orders.title')"
                :subtitle="$t('purchasing.orders.subtitle')"
                :totalCount="orders?.total ? toEnglish(orders.total) : '0'"
                :countLabel="$t('purchasing.orders.title')"
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
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
                                @click="printOrders"
                                :title="$t('common.print')"
                            />

                            <!-- Divider -->
                            <div class="w-px h-6 bg-border-subtle mx-1"></div>

                            <!-- View Toggle Group -->
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="purchasing_orders_view_mode"
                            />
                        </div>

                        <!-- Create Order Button (Anchored Outer Left in RTL) -->
                        <button
                            v-if="can('purchasing.pos.create') || isAnyAdmin()"
                            @click="openCreateModal"
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
                                {{ $t('purchasing.orders.add') }}
                            </span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-3 no-print">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div
                                class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-content-muted group-focus-within:text-action-primary transition-colors"
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
                                v-model="tableFilters.search"
                                type="text"
                                :placeholder="$t('purchasing.orders.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-2.5 text-sm text-content-primary bg-surface-primary border border-border-subtle rounded-xl focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all outline-none placeholder-content-muted shadow-xs"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <!-- Supplier Filter -->
                            <div v-if="suppliers && suppliers.length > 0" class="w-full sm:w-48">
                                <SearchableSelect
                                    v-model="tableFilters.supplier_id"
                                    :options="[
                                        { id: '', name: $t('purchasing.orders.select_supplier') },
                                        ...suppliers,
                                    ]"
                                    option-label="name"
                                    option-value="id"
                                    :placeholder="$t('purchasing.orders.select_supplier')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Status Filter -->
                            <div class="w-full sm:w-44">
                                <SearchableSelect
                                    v-model="tableFilters.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('purchasing.orders.all_statuses')"
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
            <AppTableSkeleton v-if="!orders" :rows="6" :cols="6" />

            <!-- Main Content -->
            <div v-else>
                <!-- Empty State -->
                <div
                    v-if="!orders.data || orders.data.length === 0"
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                >
                    <AppEmptyState
                        :title="$t('purchasing.orders.empty')"
                        :description="$t('purchasing.orders.empty_hint')"
                        :action-text="
                            can('purchasing.pos.create') || isAnyAdmin()
                                ? $t('purchasing.orders.add')
                                : ''
                        "
                        @action="openCreateModal"
                    />
                </div>

                <!-- GRID VIEW -->
                <div v-else-if="viewMode === 'grid'" class="space-y-6 no-print">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                    >
                        <Link
                            v-for="order in orders.data"
                            :key="order.id"
                            :href="route('app.purchasing.orders.show', order.id)"
                            class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden p-5"
                        >
                            <!-- Card Header -->
                            <div
                                class="flex items-center justify-between gap-2 pb-3 border-b border-border-subtle"
                            >
                                <span
                                    class="font-bold font-mono text-sm text-action-primary group-hover:underline"
                                >
                                    #{{ toEnglish(order.code) }}
                                </span>
                                <span :class="getStatusBadgeClass(order.status)">
                                    {{ getStatusLabel(order.status) }}
                                </span>
                            </div>

                            <!-- Supplier & Warehouse -->
                            <div class="py-4 space-y-2">
                                <div
                                    class="text-sm font-semibold text-content-primary line-clamp-1"
                                >
                                    {{ order.supplier?.name || '-' }}
                                </div>
                                <div
                                    v-if="order.warehouse"
                                    class="text-xs text-content-muted flex items-center gap-1"
                                >
                                    <span>{{ $t('purchasing.orders.warehouse') }}:</span>
                                    <span class="text-content-secondary font-medium">
                                        {{ order.warehouse.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Bottom Info -->
                            <div class="pt-3 border-t border-border-subtle mt-auto space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-content-muted">
                                        {{ $t('purchasing.orders.items') }}:
                                    </span>
                                    <span class="font-mono text-content-secondary font-medium">
                                        {{ toEnglish(order.items_count || 0) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-content-muted">
                                        {{ $t('purchasing.orders.date') }}:
                                    </span>
                                    <span class="font-mono text-content-secondary">
                                        {{ formatDate(order.order_date) }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between pt-1 text-sm font-bold border-t border-border-subtle/60"
                                >
                                    <span class="text-content-muted text-xs">
                                        {{ $t('purchasing.orders.total') }}:
                                    </span>
                                    <span class="font-mono text-content-primary">
                                        {{ formatCurrency(order.total) }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Pagination for Grid -->
                    <div v-if="orders?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="orders.links"
                            :from="orders.from"
                            :to="orders.to"
                            :total="orders.total"
                        />
                    </div>
                </div>

                <!-- LIST VIEW (DataTable) -->
                <div v-else class="space-y-4 no-print">
                    <DataTable
                        :data="orders"
                        :columns="columns"
                        :loading="isLoading"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        @sort="toggleSort"
                        @row-click="navigateToOrder"
                        :empty-title="$t('purchasing.orders.empty')"
                        :empty-description="$t('purchasing.orders.empty_hint')"
                    >
                        <template #cell-code="{ item }">
                            <Link
                                :href="route('app.purchasing.orders.show', item.id)"
                                class="font-bold text-action-primary hover:underline font-mono"
                            >
                                #{{ toEnglish(item.code) }}
                            </Link>
                        </template>

                        <template #cell-supplier="{ item }">
                            <span class="text-sm font-medium text-content-primary">
                                {{ item.supplier?.name || '-' }}
                            </span>
                        </template>

                        <template #cell-warehouse="{ item }">
                            <span class="text-xs text-content-secondary">
                                {{ item.warehouse?.name || '-' }}
                            </span>
                        </template>

                        <template #cell-order_date="{ item }">
                            <span class="text-xs text-content-secondary font-mono">
                                {{ formatDate(item.order_date) }}
                            </span>
                        </template>

                        <template #cell-items_count="{ item }">
                            <span class="text-xs font-mono text-content-secondary">
                                {{ toEnglish(item.items_count || 0) }}
                            </span>
                        </template>

                        <template #cell-total="{ item }">
                            <span class="text-sm font-bold text-content-primary font-mono">
                                {{ formatCurrency(item.total) }}
                            </span>
                        </template>

                        <template #cell-status="{ item }">
                            <span :class="getStatusBadgeClass(item.status)">
                                {{ getStatusLabel(item.status) }}
                            </span>
                        </template>

                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1" @click.stop>
                                <Tooltip :text="$t('common.view')">
                                    <Link
                                        :href="route('app.purchasing.orders.show', item.id)"
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

        <!-- Create/Edit Modal -->
        <PurchaseOrderFormModal
            :show="showCreateModal"
            :suppliers="suppliers"
            :warehouses="warehouses"
            :units="units"
            :default-warehouse="defaultWarehouse"
            @close="showCreateModal = false"
            @saved="onSaved"
        />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader :title="$t('purchasing.orders.title')" />

                <table class="w-full mt-6">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('purchasing.orders.code') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('purchasing.orders.supplier') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('purchasing.orders.date') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('purchasing.orders.total') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('common.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders?.data || []" :key="order.id" class="border-b">
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ toEnglish(order.code) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm align-middle">
                                {{ order.supplier?.name }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ formatDate(order.order_date) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ formatCurrency(order.total) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-bold align-middle">
                                {{ getStatusLabel(order.status) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer -->
                <StandardReportFooter class="mt-8 border-t pt-4" />
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';
import PageHeader from '@/Components/PageHeader.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import PurchaseOrderFormModal from '@/Components/Purchasing/PurchaseOrderFormModal.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const { t } = useI18n();
const { toEnglish, formatCurrency } = useNumberFormat();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    orders: Object,
    suppliers: Array,
    statuses: Array,
    filters: Object,
    warehouses: Array,
    units: Array,
    defaultWarehouse: Object,
});

// View mode state
const viewMode = ref(localStorage.getItem('purchasing_orders_view_mode') || 'list');
watch(viewMode, (mode) => {
    localStorage.setItem('purchasing_orders_view_mode', mode);
});

const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
    cancelDebouncedSearch,
} = useServerTable({
    routeUrl: '/app/purchasing/orders',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        supplier_id: props.filters?.supplier_id || '',
    },
    initialSort: {
        column: props.filters?.sort || 'created_at',
        direction: props.filters?.direction || 'desc',
    },
    debounceMs: 350,
});

const hasActiveFilters = computed(() => {
    return Boolean(tableFilters.search || tableFilters.status || tableFilters.supplier_id);
});

const handleResetFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.status = '';
    tableFilters.supplier_id = '';
    applyFilters();
};

watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
            tableFilters.search = newFilters.search || '';
        }
        if (newFilters.status !== undefined && newFilters.status !== tableFilters.status) {
            tableFilters.status = newFilters.status || '';
        }
        if (
            newFilters.supplier_id !== undefined &&
            newFilters.supplier_id !== tableFilters.supplier_id
        ) {
            tableFilters.supplier_id = newFilters.supplier_id || '';
        }
        if (newFilters.sort !== undefined && newFilters.sort !== sortColumn.value) {
            sortColumn.value = newFilters.sort || 'created_at';
        }
        if (newFilters.direction !== undefined && newFilters.direction !== sortDirection.value) {
            sortDirection.value = newFilters.direction || 'desc';
        }
    },
    { deep: true }
);

const columns = computed(() => [
    { key: 'code', label: t('purchasing.orders.code'), sortable: true, align: 'start' },
    { key: 'supplier', label: t('purchasing.orders.supplier'), sortable: true, align: 'start' },
    { key: 'warehouse', label: t('purchasing.orders.warehouse'), sortable: false, align: 'start' },
    { key: 'order_date', label: t('purchasing.orders.date'), sortable: true, align: 'start' },
    { key: 'items_count', label: t('purchasing.orders.items'), sortable: false, align: 'start' },
    { key: 'total', label: t('purchasing.orders.total'), sortable: true, align: 'start' },
    { key: 'status', label: t('common.status'), sortable: true, align: 'start' },
    { key: 'actions', label: t('common.actions'), sortable: false, align: 'center' },
]);

const navigateToOrder = (order) => {
    if (order?.id) {
        router.visit(route('app.purchasing.orders.show', order.id));
    }
};

const printOrders = () => {
    window.print();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const getStatusLabel = (status) => {
    const labels = {
        draft: t('purchasing.orders.statuses.draft'),
        sent: t('purchasing.orders.statuses.sent'),
        partial: t('purchasing.orders.statuses.partial'),
        received: t('purchasing.orders.statuses.received'),
        cancelled: t('purchasing.orders.statuses.cancelled'),
    };
    return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-surface-secondary text-content-secondary border-border-default',
        sent: 'bg-info-soft text-info border-info/20',
        partial: 'bg-warning-soft text-warning border-warning/20',
        received: 'bg-success-soft text-success border-success/20',
        cancelled: 'bg-danger-soft text-danger border-danger/20',
    };
    return `inline-flex px-2.5 py-1 rounded-full text-xs font-bold border shadow-xs ${classes[status] || 'bg-surface-secondary text-content-secondary'}`;
};

const statusOptions = computed(() => {
    const allOption = { value: '', label: t('purchasing.orders.all_statuses') };
    const mappedStatuses = (props.statuses || []).map((status) => ({
        value: status,
        label: getStatusLabel(status),
    }));
    return [allOption, ...mappedStatuses];
});

// Modal Logic
const showCreateModal = ref(false);

const openCreateModal = () => {
    showCreateModal.value = true;
};

const onSaved = () => {
    router.reload({ only: ['orders'] });
    showCreateModal.value = false;
};
</script>
