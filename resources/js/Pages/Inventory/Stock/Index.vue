<template>
    <AppLayout :title="$t('inventory.stock.title')">
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.stock.title')"
                :subtitle="
                    $t('inventory.stock.subtitle') + (warehouse ? ` - ${warehouse.name}` : '')
                "
                :totalCount="balances ? toEnglish(balances.total) : '0'"
                :countLabel="$t('inventory.stock.title')"
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
                            stroke-width="2.2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
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

                            <!-- Divider -->
                            <div class="w-px h-6 bg-border-subtle mx-1"></div>

                            <!-- View Toggle Group -->
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="inventory_stock_view_mode"
                            />
                        </div>
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
                                v-model="filters.search"
                                type="text"
                                :placeholder="$t('inventory.stock.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-2.5 text-sm text-content-primary bg-surface-primary border border-border-subtle rounded-xl focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all outline-none placeholder-content-muted shadow-xs"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <!-- Warehouse Filter -->
                            <div class="w-full sm:w-48" v-if="warehouses && warehouses.length > 1">
                                <SearchableSelect
                                    v-model="filters.warehouse_id"
                                    :options="warehouses"
                                    option-label="name"
                                    option-value="id"
                                    :placeholder="$t('inventory.stock.select_warehouse')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Category Filter -->
                            <div class="w-full sm:w-44">
                                <SearchableSelect
                                    v-model="filters.category"
                                    :options="[
                                        { id: '', name: $t('common.all_categories') },
                                        ...(categories || []),
                                    ]"
                                    option-label="name"
                                    option-value="id"
                                    :placeholder="$t('common.all_categories')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Stock Status Filter -->
                            <div class="w-full sm:w-36">
                                <SearchableSelect
                                    v-model="filters.stock_status"
                                    :options="[
                                        { value: '', label: $t('common.all') },
                                        {
                                            value: 'in_stock',
                                            label: $t('inventory.stock.in_stock'),
                                        },
                                        {
                                            value: 'low_stock',
                                            label: $t('inventory.stock.low_stock'),
                                        },
                                        {
                                            value: 'out_of_stock',
                                            label: $t('inventory.stock.out_of_stock'),
                                        },
                                    ]"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('common.all')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Reset Button -->
                            <button
                                v-if="hasActiveFilters"
                                @click="handleReset"
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

            <!-- KPI Stats Summary Cards -->
            <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 no-print">
                <!-- Total Items -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-4 shadow-xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-content-secondary">
                            {{ $t('inventory.stock.total_items') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-action-primary/10 text-action-primary flex items-center justify-center"
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
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold font-mono text-content-primary">
                        {{ toEnglish(stats.total_items ?? 0) }}
                    </div>
                </div>

                <!-- In Stock -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-4 shadow-xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-content-secondary">
                            {{ $t('inventory.stock.in_stock') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-success-soft text-success flex items-center justify-center"
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
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold font-mono text-success">
                        {{ toEnglish(stats.in_stock ?? 0) }}
                    </div>
                </div>

                <!-- Low Stock -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-4 shadow-xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-content-secondary">
                            {{ $t('inventory.stock.low_stock') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-warning-soft text-warning flex items-center justify-center"
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
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold font-mono text-warning">
                        {{ toEnglish(stats.low_stock ?? 0) }}
                    </div>
                </div>

                <!-- Total Stock Value -->
                <div
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-4 shadow-xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-content-secondary">
                            {{ $t('inventory.stock.total_value') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-action-primary/10 text-action-primary flex items-center justify-center"
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
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold font-mono text-content-primary">
                        {{ formatCurrency(stats.total_value ?? 0) }}
                    </div>
                </div>
            </div>

            <!-- View Content -->
            <!-- GRID VIEW -->
            <div v-if="viewMode === 'grid'" class="no-print">
                <div
                    v-if="!balances.data || balances.data.length === 0"
                    class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                >
                    <AppEmptyState
                        :title="$t('inventory.stock.empty')"
                        :description="$t('inventory.stock.empty_hint')"
                    />
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                >
                    <div
                        v-for="balance in balances.data"
                        :key="balance.id"
                        class="bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col group p-4"
                    >
                        <!-- Header -->
                        <div
                            class="flex items-center justify-between gap-2 pb-3 border-b border-border-subtle"
                        >
                            <div
                                class="flex items-center gap-1.5 font-mono text-xs font-semibold text-action-primary"
                                dir="ltr"
                            >
                                <span>{{ balance.part?.sku }}</span>
                                <span v-if="balance.part?.unit" class="text-content-muted">·</span>
                                <span v-if="balance.part?.unit" class="text-content-secondary">
                                    {{ balance.part?.unit?.name_ar || balance.part?.unit }}
                                </span>
                            </div>
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold font-mono border',
                                    getStockStatusBadgeClass(balance),
                                ]"
                                dir="ltr"
                            >
                                {{ formatQuantity(balance.qty_on_hand) }}
                            </span>
                        </div>

                        <!-- Body -->
                        <div class="py-3 flex-1 space-y-3">
                            <!-- Part Name -->
                            <div>
                                <Link
                                    :href="route('app.inventory.parts.show', balance.part_id)"
                                    class="text-sm font-bold text-content-primary hover:text-action-primary transition-colors line-clamp-2"
                                >
                                    {{ balance.part?.name_ar }}
                                </Link>
                                <div
                                    v-if="balance.part?.name_en"
                                    class="text-xs text-content-muted mt-0.5 truncate"
                                >
                                    {{ balance.part?.name_en }}
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="space-y-1.5 pt-2 border-t border-border-subtle text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-content-muted">
                                        {{ $t('inventory.stock.wac') }}:
                                    </span>
                                    <span
                                        class="font-mono font-medium text-content-primary"
                                        dir="ltr"
                                    >
                                        {{ formatCurrency(balance.wac_cost) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-content-muted">
                                        {{ $t('inventory.parts.price') }}:
                                    </span>
                                    <span
                                        class="font-mono font-medium text-content-primary"
                                        dir="ltr"
                                    >
                                        {{ formatCurrency(balance.sale_price) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-content-muted">
                                        {{ $t('inventory.stock.min_qty') }}:
                                    </span>
                                    <span
                                        class="font-mono font-medium text-content-secondary"
                                        dir="ltr"
                                    >
                                        {{ formatQuantity(balance.min_stock) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Min Sale Price Notice -->
                            <div v-if="balance.min_sale_price > 0" class="pt-1">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono bg-danger-soft text-danger border border-danger-subtle"
                                >
                                    <span>{{ $t('inventory.parts.min_sale_price') }}:</span>
                                    <span class="font-bold" dir="ltr">
                                        {{ formatCurrency(balance.min_sale_price) }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div
                            class="pt-2.5 border-t border-border-subtle mt-auto flex items-center justify-between text-[11px] text-content-secondary"
                        >
                            <span>
                                {{
                                    balance.part?.category?.name_ar ||
                                    balance.part?.category?.name_en ||
                                    '-'
                                }}
                            </span>
                            <Link
                                :href="route('app.inventory.parts.show', balance.part_id)"
                                class="text-action-primary hover:underline font-medium inline-flex items-center gap-1"
                            >
                                <span>{{ $t('common.view') }}</span>
                                <svg
                                    class="w-3.5 h-3.5 rtl:rotate-180"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pagination for Grid -->
                <div v-if="balances?.links?.length >= 3" class="mt-6">
                    <AppPagination
                        :links="balances.links"
                        :from="balances.from"
                        :to="balances.to"
                        :total="balances.total"
                    />
                </div>
            </div>

            <!-- LIST VIEW (DataTable) -->
            <div v-else class="no-print">
                <DataTable
                    :columns="columns"
                    :data="balances"
                    :loading="isLoading"
                    :sort-column="sortColumn"
                    :sort-direction="sortDirection"
                    @sort="toggleSort"
                    :empty-title="$t('inventory.stock.empty')"
                    :empty-description="$t('inventory.stock.empty_hint')"
                >
                    <!-- Index Column -->
                    <template #cell-index="{ index }">
                        <span class="text-xs text-content-muted font-mono">
                            {{ toEnglish((balances.from || 1) + index) }}
                        </span>
                    </template>

                    <!-- SKU Column -->
                    <template #cell-sku="{ item }">
                        <span class="font-mono font-medium text-xs text-content-primary" dir="ltr">
                            {{ item.part?.sku }}
                        </span>
                    </template>

                    <!-- Name Column -->
                    <template #cell-name="{ item }">
                        <div class="flex flex-col">
                            <Link
                                :href="route('app.inventory.parts.show', item.part_id)"
                                class="text-sm font-bold text-action-primary hover:underline transition-colors"
                            >
                                {{ item.part?.name_ar }}
                            </Link>
                            <span
                                v-if="item.part?.name_en"
                                class="text-xs text-content-muted mt-0.5"
                            >
                                {{ item.part?.name_en }}
                            </span>
                        </div>
                    </template>

                    <!-- Category Column -->
                    <template #cell-category="{ item }">
                        <span class="text-xs text-content-secondary">
                            {{
                                item.part?.category?.name_ar || item.part?.category?.name_en || '-'
                            }}
                        </span>
                    </template>

                    <!-- Available Qty Column -->
                    <template #cell-qty_on_hand="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold font-mono border',
                                getStockStatusBadgeClass(item),
                            ]"
                            dir="ltr"
                        >
                            <span>{{ formatQuantity(item.qty_on_hand) }}</span>
                            <span v-if="item.part?.unit" class="text-[11px] font-normal">
                                {{ item.part?.unit?.name_ar || item.part?.unit }}
                            </span>
                        </span>
                    </template>

                    <!-- Min Stock Column -->
                    <template #cell-min_stock="{ item }">
                        <span class="font-mono text-xs text-content-secondary" dir="ltr">
                            {{ formatQuantity(item.min_stock || 0) }}
                        </span>
                    </template>

                    <!-- WAC Cost Column -->
                    <template #cell-wac_cost="{ item }">
                        <span class="font-mono text-xs font-medium text-content-primary" dir="ltr">
                            {{ formatCurrency(item.wac_cost) }}
                        </span>
                    </template>

                    <!-- Sale Price Column -->
                    <template #cell-sale_price="{ item }">
                        <span
                            class="font-mono text-xs font-semibold text-content-primary"
                            dir="ltr"
                        >
                            {{ formatCurrency(item.sale_price) }}
                        </span>
                    </template>

                    <!-- Min Sale Price Column -->
                    <template #cell-min_sale_price="{ item }">
                        <span
                            v-if="item.min_sale_price > 0"
                            class="font-mono text-xs text-danger font-medium"
                            dir="ltr"
                        >
                            {{ formatCurrency(item.min_sale_price) }}
                        </span>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>

    <!-- Print Section -->
    <Teleport to="body">
        <div class="print-section hidden">
            <!-- Header -->
            <StandardReportHeader
                :title="$t('inventory.stock.title')"
                :subtitle="warehouse ? warehouse.name : ''"
            />

            <!-- Table -->
            <DataTable structured>
                <thead>
                    <tr>
                        <th style="width: 40px" class="align-middle text-center">#</th>
                        <th class="align-middle text-center">
                            {{ $t('inventory.parts.part_number') }}
                        </th>
                        <th class="align-middle text-center">{{ $t('inventory.parts.name') }}</th>
                        <th class="align-middle text-center">
                            {{ $t('inventory.parts.category') }}
                        </th>
                        <th class="align-middle text-center">
                            {{ $t('inventory.stock.available') }}
                        </th>
                        <th class="align-middle text-center">
                            {{ $t('inventory.stock.avg_cost') }}
                        </th>
                        <th class="align-middle text-center">{{ $t('inventory.parts.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(balance, index) in balances.data" :key="balance.id">
                        <td class="align-middle">{{ toEnglish(index + 1) }}</td>
                        <td class="font-bold align-middle">{{ balance.part?.sku }}</td>
                        <td class="align-middle">{{ balance.part?.name_ar }}</td>
                        <td class="align-middle">{{ balance.part?.category?.name_ar || '-' }}</td>
                        <td class="align-middle">
                            {{ formatQuantity(balance.qty_on_hand) }}
                            {{ balance.part?.unit?.name_ar || '' }}
                        </td>
                        <td class="align-middle">{{ formatCurrency(balance.wac_cost) }}</td>
                        <td class="align-middle">
                            {{ formatCurrency(balance.sale_price) }}
                        </td>
                    </tr>
                </tbody>
            </DataTable>

            <!-- Footer -->
            <StandardReportFooter class="mt-8 border-t pt-4" />
        </div>
    </Teleport>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { useServerTable } from '@/Composables/useServerTable';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { formatQuantity, formatCurrency, toEnglish } = useNumberFormat();

const props = defineProps({
    balances: Object,
    warehouse: Object,
    warehouses: Array,
    categories: Array,
    filters: Object,
    stats: Object,
});

// View mode state
const viewMode = ref(localStorage.getItem('inventory_stock_view_mode') || 'list');
watch(viewMode, (mode) => {
    localStorage.setItem('inventory_stock_view_mode', mode);
});

// useServerTable setup
const { filters, sortColumn, sortDirection, isLoading, applyFilters, resetFilters, toggleSort } =
    useServerTable({
        routeUrl: () => route('app.inventory.stock.index'),
        initialFilters: {
            search: props.filters?.search || '',
            stock_status: props.filters?.stock_status || '',
            category: props.filters?.category || '',
            warehouse_id: props.filters?.warehouse_id || props.warehouse?.id || '',
        },
        initialSort: {
            column: props.filters?.sort || 'qty_on_hand',
            direction: props.filters?.direction || props.filters?.order || 'desc',
        },
    });

const hasActiveFilters = computed(() => {
    return Boolean(
        filters.search ||
        filters.stock_status ||
        filters.category ||
        (props.warehouses?.length > 1 && filters.warehouse_id !== (props.warehouse?.id || ''))
    );
});

const handleReset = () => {
    filters.search = '';
    filters.stock_status = '';
    filters.category = '';
    filters.warehouse_id = props.warehouse?.id || '';
    applyFilters();
};

// Table Columns configuration
const columns = computed(() => [
    {
        key: 'index',
        label: '#',
        width: '50px',
        align: 'start',
    },
    {
        key: 'sku',
        label: t('inventory.parts.part_number'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'name',
        label: t('inventory.parts.name'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'category',
        label: t('inventory.parts.category'),
        sortable: false,
        align: 'start',
    },
    {
        key: 'qty_on_hand',
        label: t('inventory.stock.available'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'min_stock',
        label: t('inventory.stock.min_qty'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'wac_cost',
        label: t('inventory.stock.avg_cost'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'sale_price',
        label: t('inventory.parts.price'),
        sortable: true,
        align: 'start',
    },
    {
        key: 'min_sale_price',
        label: t('inventory.parts.min_sale_price'),
        sortable: true,
        align: 'start',
    },
]);

// Stock status badge classes using semantic design tokens
const getStockStatusBadgeClass = (balance) => {
    const qty = Number(balance.qty_on_hand) || 0;
    const minQty = Number(balance.min_stock) || 0;

    if (qty <= 0) {
        return 'bg-danger-soft text-danger border-danger-subtle';
    } else if (qty <= minQty) {
        return 'bg-warning-soft text-warning border-warning-subtle';
    }
    return 'bg-success-soft text-success border-success-subtle';
};

const handlePrint = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.delete('page');
    window.open(url.toString(), '_blank');
};
</script>
