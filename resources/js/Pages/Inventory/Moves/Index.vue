<template>
    <AppLayout :title="$t('inventory.moves.title')">
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.moves.title')"
                :subtitle="
                    $t('inventory.moves.subtitle') + (warehouse ? ` - ${warehouse.name}` : '')
                "
                :totalCount="moves?.total ? toEnglish(moves.total) : '0'"
                :countLabel="$t('inventory.moves.title')"
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
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
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
                        </div>

                        <!-- Primary Action Buttons (Outer Left in RTL) -->
                        <div
                            v-if="can('inventory.moves.create') || isAnyAdmin()"
                            class="flex items-center gap-2"
                        >
                            <!-- Receipt Button -->
                            <button
                                @click="showReceiptModal = true"
                                type="button"
                                class="btn btn-secondary shadow-sm hover:shadow transition-all text-sm font-semibold inline-flex items-center gap-2"
                            >
                                <svg
                                    class="w-4 h-4 text-success"
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
                                <span>{{ $t('inventory.moves.receipt') }}</span>
                            </button>

                            <!-- Adjustment Button -->
                            <button
                                @click="showAdjustmentModal = true"
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
                                        stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                    />
                                </svg>
                                <span>{{ $t('inventory.moves.adjust') }}</span>
                            </button>
                        </div>
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
                                :placeholder="$t('inventory.moves.search_placeholder')"
                                class="input-base ps-10 pe-4 py-2.5 text-sm w-full rounded-2xl"
                            />
                        </div>

                        <!-- Type Filter -->
                        <div class="w-full sm:w-52">
                            <label
                                class="block text-[11px] font-medium text-content-muted mb-1 px-1"
                            >
                                {{ $t('inventory.moves.type') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.type"
                                :options="moveTypeOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('inventory.moves.all_types')"
                                class="!rounded-2xl"
                                compact
                                @change="() => applyFilters()"
                            />
                        </div>

                        <!-- Date From -->
                        <div class="w-full sm:w-40">
                            <label
                                class="block text-[11px] font-medium text-content-muted mb-1 px-1"
                            >
                                {{ $t('common.date_from') }}
                            </label>
                            <CustomDatePicker
                                v-model="tableFilters.date_from"
                                @change="() => applyFilters()"
                                class="!rounded-2xl"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="w-full sm:w-40">
                            <label
                                class="block text-[11px] font-medium text-content-muted mb-1 px-1"
                            >
                                {{ $t('common.date_to') }}
                            </label>
                            <CustomDatePicker
                                v-model="tableFilters.date_to"
                                @change="() => applyFilters()"
                                class="!rounded-2xl"
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

            <!-- Moves DataTable -->
            <DataTable
                :columns="columns"
                :data="moves"
                :loading="isLoading"
                :sort-column="sortColumn"
                :sort-direction="sortDirection"
                @sort="toggleSort"
                :empty-title="$t('inventory.moves.title')"
                :empty-description="
                    $t('inventory.moves.empty_hint') || $t('common.no_records_found')
                "
            >
                <!-- Row Number Column -->
                <template #cell-index="{ index }">
                    <span class="text-xs text-content-muted font-mono">
                        {{ toEnglish((moves.from || 1) + index) }}
                    </span>
                </template>

                <!-- Part Column -->
                <template #cell-part="{ item }">
                    <Link
                        :href="route('app.inventory.parts.show', item.part_id)"
                        class="group/part block"
                    >
                        <div
                            class="text-sm font-bold text-action-primary group-hover/part:underline transition-all"
                        >
                            {{ item.part?.name_ar || item.part?.name_en || '—' }}
                        </div>
                        <div class="text-xs text-content-muted font-mono" dir="ltr">
                            {{ item.part?.sku || '—' }}
                        </div>
                    </Link>
                </template>

                <!-- Action/Type Column -->
                <template #cell-action="{ item }">
                    <span :class="getMoveTypeBadgeClass(item.move_type)">
                        {{ getMoveTypeLabel(item.move_type) }}
                    </span>
                </template>

                <!-- Details/Notes Column -->
                <template #cell-details="{ item }">
                    <span
                        class="text-xs text-content-secondary max-w-[200px] truncate block"
                        :title="item.notes || '-'"
                    >
                        {{ item.notes || '—' }}
                    </span>
                </template>

                <!-- Stock Before Column -->
                <template #cell-stock_before="{ item }">
                    <span class="text-xs font-mono text-content-secondary" dir="ltr">
                        {{ calculateStockBefore(item) }}
                    </span>
                </template>

                <!-- Quantity Column -->
                <template #cell-qty="{ item }">
                    <span
                        :class="item.qty > 0 ? 'text-success' : 'text-danger'"
                        class="font-mono text-sm font-bold"
                        dir="ltr"
                    >
                        {{ item.qty > 0 ? '+' : '' }}{{ formatQuantity(item.qty) }}
                    </span>
                </template>

                <!-- Stock After Column -->
                <template #cell-stock_after="{ item }">
                    <span class="text-xs font-mono font-semibold text-content-primary" dir="ltr">
                        {{ formatQuantity(item.balance_after) }}
                    </span>
                </template>

                <!-- Cost Price (Unit Cost) Column -->
                <template #cell-unit_cost="{ item }">
                    <span class="text-xs font-mono text-content-secondary" dir="ltr">
                        {{ formatCurrency(item.unit_cost) }}
                    </span>
                </template>

                <!-- WAC Column -->
                <template #cell-wac="{ item }">
                    <span class="text-xs font-mono text-content-muted" dir="ltr">
                        {{ formatCurrency(item.wac_after) }}
                    </span>
                </template>

                <!-- Cost Amount (Total Cost) Column -->
                <template #cell-total_cost="{ item }">
                    <span class="text-xs font-mono font-bold text-content-primary" dir="ltr">
                        {{ formatCurrency(item.total_cost) }}
                    </span>
                </template>

                <!-- Updated At Column -->
                <template #cell-posted_at="{ item }">
                    <span class="text-xs font-mono text-content-secondary" dir="ltr">
                        {{ formatDate(item.posted_at) }}
                    </span>
                </template>

                <!-- Actions Column -->
                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-center">
                        <Tooltip v-if="canReverse(item)" :content="$t('inventory.moves.reverse')">
                            <button
                                @click="reverseMove(item)"
                                type="button"
                                class="p-1.5 rounded-lg text-content-muted hover:text-danger hover:bg-danger-soft transition-colors"
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
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                                    />
                                </svg>
                            </button>
                        </Tooltip>
                        <span
                            v-else-if="item.reversed_at"
                            class="text-[11px] font-semibold text-danger"
                        >
                            {{ $t('inventory.moves.reversed') }}
                        </span>
                        <span v-else class="text-content-muted text-xs">—</span>
                    </div>
                </template>
            </DataTable>
        </PrintEngine>

        <!-- Modals -->
        <ReceiptModal
            :show="showReceiptModal"
            :warehouse-id="warehouse?.id"
            @close="showReceiptModal = false"
            @saved="() => router.reload({ only: ['moves'] })"
        />
        <AdjustmentModal
            :show="showAdjustmentModal"
            :warehouse-id="warehouse?.id"
            @close="showAdjustmentModal = false"
            @saved="() => router.reload({ only: ['moves'] })"
        />
        <ConfirmModal />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('inventory.moves.title')"
                    :subtitle="warehouse ? warehouse.name : ''"
                />

                <DataTable structured>
                    <thead>
                        <tr>
                            <th style="width: 40px" class="align-middle text-center">#</th>
                            <th class="align-middle text-center">
                                {{ $t('inventory.moves.columns.part') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('inventory.moves.columns.action') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('inventory.moves.columns.qty') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('inventory.moves.columns.stock_after') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('inventory.moves.columns.cost_price') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('inventory.moves.columns.updated_at') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(move, index) in moves?.data || []" :key="move.id">
                            <td class="align-middle">{{ index + 1 }}</td>
                            <td class="font-bold align-middle">
                                {{ move.part?.name_ar }}
                                <br />
                                <small class="text-gray-500">{{ move.part?.sku }}</small>
                            </td>
                            <td class="align-middle">{{ getMoveTypeLabel(move.move_type) }}</td>
                            <td
                                dir="ltr"
                                :class="move.qty > 0 ? 'text-green-600' : 'text-red-600'"
                                class="align-middle"
                            >
                                {{ move.qty > 0 ? '+' : '' }}{{ formatQuantity(move.qty) }}
                            </td>
                            <td class="align-middle">{{ formatQuantity(move.balance_after) }}</td>
                            <td class="align-middle">{{ formatCurrency(move.unit_cost) }}</td>
                            <td dir="ltr" class="text-[10px] align-middle">
                                {{ formatDate(move.posted_at) }}
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
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import BackButton from '@/Components/BackButton.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import ReceiptModal from '@/Components/Inventory/ReceiptModal.vue';
import AdjustmentModal from '@/Components/Inventory/AdjustmentModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { formatQuantity, formatCurrency, toEnglish } = useNumberFormat();
const { confirm } = useConfirm();

const props = defineProps({
    moves: { type: Object, default: () => ({}) },
    warehouse: { type: Object, default: () => null },
    moveTypes: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const showReceiptModal = ref(false);
const showAdjustmentModal = ref(false);

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
    routeUrl: '/app/inventory/moves',
    initialFilters: {
        search: props.filters?.search || '',
        type: props.filters?.type || '',
        part_id: props.filters?.part_id || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
    },
    initialSort: {
        column: props.filters?.sort || 'posted_at',
        direction: props.filters?.direction || 'desc',
    },
    debounceMs: 350,
});

// Watch dates for immediate filtering
watch([() => tableFilters.date_from, () => tableFilters.date_to], () => {
    applyFilters();
});

// Synchronize props.filters with tableFilters when Inertia updates
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (newFilters.type !== undefined && newFilters.type !== tableFilters.type) {
                tableFilters.type = newFilters.type || '';
            }
            if (newFilters.part_id !== undefined && newFilters.part_id !== tableFilters.part_id) {
                tableFilters.part_id = newFilters.part_id || '';
            }
            if (
                newFilters.date_from !== undefined &&
                newFilters.date_from !== tableFilters.date_from
            ) {
                tableFilters.date_from = newFilters.date_from || '';
            }
            if (newFilters.date_to !== undefined && newFilters.date_to !== tableFilters.date_to) {
                tableFilters.date_to = newFilters.date_to || '';
            }
            if (newFilters.sort !== undefined && newFilters.sort !== sortColumn.value) {
                sortColumn.value = newFilters.sort || 'posted_at';
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
        tableFilters.type ||
        tableFilters.part_id ||
        tableFilters.date_from ||
        tableFilters.date_to ||
        (sortColumn.value && sortColumn.value !== 'posted_at')
    );
});

const resetAllFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.type = '';
    tableFilters.part_id = '';
    tableFilters.date_from = '';
    tableFilters.date_to = '';
    sortColumn.value = 'posted_at';
    sortDirection.value = 'desc';
    applyFilters();
};

// Columns Definitions
const columns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: true },
    { key: 'part', label: t('inventory.moves.columns.part'), align: 'start', sortable: true },
    { key: 'action', label: t('inventory.moves.columns.action'), align: 'center', sortable: false },
    {
        key: 'details',
        label: t('inventory.moves.columns.details'),
        align: 'start',
        sortable: false,
    },
    {
        key: 'stock_before',
        label: t('inventory.moves.columns.stock_before'),
        align: 'center',
        sortable: false,
    },
    { key: 'qty', label: t('inventory.moves.columns.qty'), align: 'center', sortable: true },
    {
        key: 'stock_after',
        label: t('inventory.moves.columns.stock_after'),
        align: 'center',
        sortable: true,
    },
    {
        key: 'unit_cost',
        label: t('inventory.moves.columns.cost_price'),
        align: 'center',
        sortable: true,
    },
    { key: 'wac', label: t('inventory.moves.columns.wac'), align: 'center', sortable: false },
    {
        key: 'total_cost',
        label: t('inventory.moves.columns.cost_amount'),
        align: 'center',
        sortable: true,
    },
    {
        key: 'posted_at',
        label: t('inventory.moves.columns.updated_at'),
        align: 'center',
        sortable: true,
    },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-20', sortable: false },
]);

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

const getMoveTypeLabel = (type) => {
    const labels = {
        receipt: t('inventory.moves.types.receipt'),
        issue_to_workorder: t('inventory.moves.types.issue'),
        sale: t('inventory.moves.types.sale'),
        adjustment_in: t('inventory.moves.types.adjustment_in'),
        adjustment_out: t('inventory.moves.types.adjustment_out'),
        transfer_in: t('inventory.moves.types.transfer_in'),
        transfer_out: t('inventory.moves.types.transfer_out'),
        reversal: t('inventory.moves.types.reversal'),
        purchase_return: t('inventory.moves.types.purchase_return'),
    };
    return labels[type] || type;
};

const moveTypeOptions = computed(() => {
    const allOption = { value: '', label: t('inventory.moves.all_types') };
    const types = (props.moveTypes || []).map((type) => ({
        value: type,
        label: getMoveTypeLabel(type),
    }));
    return [allOption, ...types];
});

const getMoveTypeBadgeClass = (type) => {
    const classes = {
        receipt: 'bg-success-soft text-success border border-success/20',
        issue_to_workorder: 'bg-danger-soft text-danger border border-danger/20',
        sale: 'bg-danger-soft text-danger border border-danger/20',
        adjustment_in: 'bg-action-primary-soft text-action-primary border border-action-primary/20',
        adjustment_out: 'bg-danger-soft text-danger border border-danger/20',
        transfer_in: 'bg-surface-secondary text-content-primary border border-border-subtle',
        transfer_out: 'bg-surface-secondary text-content-secondary border border-border-subtle',
        reversal: 'bg-surface-secondary text-content-secondary border border-border-subtle',
        purchase_return: 'bg-danger-soft text-danger border border-danger/20',
    };
    return `inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold shadow-sm ${classes[type] || 'bg-surface-secondary text-content-secondary'}`;
};

const calculateStockBefore = (move) => {
    const balanceAfter = parseFloat(move.balance_after) || 0;
    const qty = parseFloat(move.qty) || 0;
    const before = balanceAfter - qty;
    return before.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
        useGrouping: false,
    });
};

const canReverse = (move) => {
    if (move.reversed_at) return false;

    const restrictedTypes = [
        'issue_to_workorder',
        'sale',
        'transfer_in',
        'transfer_out',
        'purchase_return',
    ];
    if (restrictedTypes.includes(move.move_type)) return false;

    if (move.move_type === 'receipt') return can('inventory.receipts.cancel');
    if (move.move_type?.startsWith('adjustment')) return can('inventory.adjustments.cancel');

    return false;
};

const reverseMove = async (move) => {
    const confirmed = await confirm({
        title: t('inventory.moves.reverse'),
        message: t('inventory.moves.confirm_reverse'),
        type: 'danger',
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
    });

    if (!confirmed) return;

    router.post(
        route('app.inventory.moves.reverse', move.id),
        {},
        {
            preserveScroll: true,
        }
    );
};

const handlePrint = () => {
    printDocument();
};
</script>
