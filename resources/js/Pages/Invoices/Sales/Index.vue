<template>
    <AppLayout :title="$t('invoices.sales.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('invoices.sales.title')"
                :subtitle="$t('invoices.sales.subtitle')"
                :totalCount="activeData?.total ? toEnglish(activeData.total) : '0'"
                :countLabel="
                    activeTab === 'invoices'
                        ? $t('invoices.sales.tab_invoices')
                        : $t('invoices.sales.tab_returns')
                "
            >
                <template #back>
                    <BackButton :href="route('app.invoices.hub')" />
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2">
                        <!-- View Mode Toggle -->
                        <div
                            class="flex items-center gap-1.5 p-1 bg-surface-muted rounded-xl border border-border"
                        >
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="salesInvoicesViewMode"
                            />
                        </div>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <!-- Search -->
                        <div class="relative group min-w-[200px] flex-1 sm:flex-initial">
                            <div
                                class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-text-muted group-focus-within:text-action-primary transition-colors"
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
                                type="text"
                                v-model="tableFilters.search"
                                :placeholder="$t('common.search')"
                                class="block w-full ps-10 pe-4 py-2.5 text-sm text-text-primary bg-surface border border-border rounded-xl focus:ring-2 focus:ring-focus-ring focus:border-action-primary transition-all outline-none"
                            />
                        </div>

                        <!-- Date From -->
                        <div class="w-full sm:w-36">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('common.date_from') }}
                            </label>
                            <CustomDatePicker
                                v-model="tableFilters.date_from"
                                @update:modelValue="() => applyFilters()"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="w-full sm:w-36">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('common.date_to') }}
                            </label>
                            <CustomDatePicker
                                v-model="tableFilters.date_to"
                                @update:modelValue="() => applyFilters()"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-44">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('common.status') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.payment_status"
                                :options="statusOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('invoices.all_statuses')"
                                @update:modelValue="() => applyFilters()"
                                compact
                            />
                        </div>

                        <!-- Reset Filters Button -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetAllFilters"
                            class="px-3 py-2.5 text-xs font-semibold text-text-secondary hover:text-danger hover:bg-danger-soft rounded-xl border border-border transition-colors flex items-center gap-1.5 cursor-pointer"
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

            <!-- Unified Tabs Section -->
            <div class="flex items-center justify-between">
                <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" @change="onTabChange" />
            </div>

            <!-- Tab Content -->
            <div class="space-y-6">
                <!-- GRID VIEW (Cards) -->
                <div v-if="viewMode === 'grid'">
                    <!-- Empty State -->
                    <AppEmptyState
                        v-if="!activeRows.length"
                        :title="
                            activeTab === 'returns'
                                ? $t('invoices.sales.no_returns')
                                : $t('invoices.sales.title')
                        "
                        :description="
                            activeTab === 'returns'
                                ? $t('common.no_records_found', 'لم يتم العثور على أي سجلات مطابقة')
                                : $t('invoices.sales.empty_hint') ||
                                  $t('common.no_records_found', 'لم يتم العثور على أي سجلات مطابقة')
                        "
                        :action-text="activeTab === 'returns' ? '' : $t('nav.work_orders')"
                        :action-href="activeTab === 'returns' ? '' : route('work-orders.index')"
                    >
                        <template #icon>
                            <svg
                                class="w-8 h-8 text-action-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.75"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </template>
                    </AppEmptyState>

                    <!-- Cards Grid -->
                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                    >
                        <Link
                            v-for="invoice in activeRows"
                            :key="invoice.id"
                            :href="route('app.invoices.show', invoice.id)"
                            class="group relative flex flex-col bg-surface rounded-2xl border border-border hover:border-action-primary hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden"
                        >
                            <!-- Top status stripe -->
                            <div class="h-1 w-full" :class="stripeClass(invoice.payment_status)" />

                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <!-- Number + Status -->
                                    <div class="flex items-start justify-between gap-2 mb-3">
                                        <div>
                                            <span
                                                class="text-xs font-bold text-action-primary font-mono tracking-wide"
                                            >
                                                #{{ invoice.invoice_number }}
                                            </span>
                                            <p
                                                class="mt-1 text-sm font-bold text-text-primary truncate max-w-[150px]"
                                            >
                                                {{
                                                    invoice.customer_name_snapshot ||
                                                    invoice.customer?.name ||
                                                    '—'
                                                }}
                                            </p>
                                        </div>
                                        <span
                                            :class="statusClass(invoice.payment_status)"
                                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold shrink-0"
                                        >
                                            {{ $t(`invoices.status.${invoice.payment_status}`) }}
                                        </span>
                                    </div>

                                    <!-- Original invoice reference if return -->
                                    <div
                                        v-if="invoice.original_invoice"
                                        class="text-xs text-text-muted mb-2 font-mono flex items-center gap-1"
                                    >
                                        <span>{{ $t('invoices.original_invoice') }}:</span>
                                        <span class="text-action-primary font-semibold">
                                            #{{ invoice.original_invoice.invoice_number }}
                                        </span>
                                    </div>

                                    <!-- Amounts Summary -->
                                    <div class="space-y-1.5 py-3 border-y border-border/60 text-xs">
                                        <div class="flex justify-between items-center">
                                            <span class="text-text-secondary">
                                                {{ $t('invoices.subtotal') }}
                                            </span>
                                            <span class="font-mono text-text-primary" dir="ltr">
                                                {{ formatCurrency(invoice.total_excl_tax) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center font-bold">
                                            <span class="text-text-primary">
                                                {{ $t('invoices.grand_total') }}
                                            </span>
                                            <span class="font-mono text-text-primary" dir="ltr">
                                                {{ formatCurrency(invoice.total_incl_tax) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-text-secondary">
                                                {{ $t('invoices.balance') }}
                                            </span>
                                            <span
                                                class="font-mono font-bold"
                                                :class="
                                                    Number(invoice.balance) > 0
                                                        ? 'text-danger'
                                                        : 'text-success'
                                                "
                                                dir="ltr"
                                            >
                                                {{ formatCurrency(invoice.balance) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date & Link footer -->
                                <div
                                    class="mt-4 pt-2 flex items-center justify-between text-xs text-text-muted"
                                >
                                    <span>{{ formatDate(invoice.issue_date) }}</span>
                                    <span
                                        class="text-action-primary font-bold group-hover:translate-x-[-2px] transition-transform"
                                    >
                                        {{ $t('common.view') }} ←
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Pagination for Grid View -->
                    <div v-if="activeData?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="activeData.links"
                            :from="activeData.from"
                            :to="activeData.to"
                            :total="activeData.total"
                        />
                    </div>
                </div>

                <!-- LIST VIEW (DataTable) -->
                <div v-else>
                    <DataTable
                        :columns="activeColumns"
                        :data="activeData"
                        :loading="isLoading"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        @sort="toggleSort"
                        :empty-title="
                            activeTab === 'returns'
                                ? $t('invoices.sales.no_returns')
                                : $t('invoices.sales.title')
                        "
                        :empty-description="
                            activeTab === 'returns'
                                ? $t('common.no_records_found', 'لم يتم العثور على أي سجلات مطابقة')
                                : $t('invoices.sales.empty_hint') ||
                                  $t('common.no_records_found', 'لم يتم العثور على أي سجلات مطابقة')
                        "
                        :empty-action-text="activeTab === 'returns' ? '' : $t('nav.work_orders')"
                        :empty-action-href="
                            activeTab === 'returns' ? '' : route('work-orders.index')
                        "
                    >
                        <!-- Index Column -->
                        <template #cell-index="{ index }">
                            <span class="text-xs text-text-muted font-mono">
                                {{ toEnglish((activeData.from || 1) + index) }}
                            </span>
                        </template>

                        <!-- Invoice Number Column -->
                        <template #cell-invoice_number="{ item }">
                            <Link
                                :href="route('app.invoices.show', item.id)"
                                class="font-bold text-action-primary hover:underline font-mono"
                            >
                                #{{ item.invoice_number }}
                            </Link>
                        </template>

                        <!-- Original Invoice (for returns tab) -->
                        <template #cell-original_invoice="{ item }">
                            <Link
                                v-if="item.original_invoice"
                                :href="route('app.invoices.show', item.original_invoice.id)"
                                class="text-xs text-action-primary hover:underline font-mono font-semibold"
                            >
                                #{{ item.original_invoice.invoice_number }}
                            </Link>
                            <span v-else class="text-text-muted text-xs">—</span>
                        </template>

                        <!-- Issue Date Column -->
                        <template #cell-issue_date="{ item }">
                            <span class="text-xs text-text-secondary font-mono">
                                {{ formatDate(item.issue_date) }}
                            </span>
                        </template>

                        <!-- Customer Column -->
                        <template #cell-customer="{ item }">
                            <p class="font-medium text-text-primary truncate max-w-[200px]">
                                {{ item.customer_name_snapshot || item.customer?.name || '—' }}
                            </p>
                        </template>

                        <!-- Gross Total (for invoices tab) -->
                        <template #cell-gross_total="{ item }">
                            <span
                                class="text-sm font-semibold text-text-primary font-mono"
                                dir="ltr"
                            >
                                {{ formatCurrency(getGrossTotal(item)) }}
                            </span>
                        </template>

                        <!-- Discount (for invoices tab) -->
                        <template #cell-discount="{ item }">
                            <span class="text-sm font-medium text-danger font-mono" dir="ltr">
                                {{ formatCurrency(getDiscount(item)) }}
                            </span>
                        </template>

                        <!-- Subtotal Column -->
                        <template #cell-total_excl_tax="{ item }">
                            <span
                                class="text-sm font-semibold text-text-primary font-mono"
                                dir="ltr"
                            >
                                {{ formatCurrency(item.total_excl_tax) }}
                            </span>
                        </template>

                        <!-- VAT Column -->
                        <template #cell-total_tax="{ item }">
                            <span
                                class="text-sm font-medium text-text-secondary font-mono"
                                dir="ltr"
                            >
                                {{ formatCurrency(item.total_tax) }}
                            </span>
                        </template>

                        <!-- Grand Total Column -->
                        <template #cell-total_incl_tax="{ item }">
                            <span class="text-sm font-bold text-text-primary font-mono" dir="ltr">
                                {{ formatCurrency(item.total_incl_tax) }}
                            </span>
                        </template>

                        <!-- Balance Column -->
                        <template #cell-balance="{ item }">
                            <div class="font-mono text-end" dir="ltr">
                                <span
                                    :class="
                                        Number(item.balance) > 0
                                            ? 'text-danger font-bold'
                                            : 'text-success font-semibold'
                                    "
                                >
                                    {{ formatCurrency(item.balance) }}
                                </span>
                                <div
                                    v-if="(item.bad_debt || 0) > 0"
                                    class="text-[10px] text-warning font-sans font-semibold mt-0.5"
                                >
                                    {{ $t('payments.types.bad_debt') }}:
                                    {{ formatCurrency(item.bad_debt) }}
                                </div>
                            </div>
                        </template>

                        <!-- Payment Status Column -->
                        <template #cell-payment_status="{ item }">
                            <span
                                :class="statusClass(item.payment_status)"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold"
                            >
                                {{ $t(`invoices.status.${item.payment_status}`) }}
                            </span>
                        </template>

                        <!-- Actions Column -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1">
                                <Tooltip :content="$t('common.view')">
                                    <Link
                                        :href="route('app.invoices.show', item.id)"
                                        class="p-2 rounded-lg text-text-muted hover:text-action-primary hover:bg-action-primary-soft transition-colors"
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
                                <Tooltip :content="$t('common.print')">
                                    <Link
                                        :href="route('app.invoices.print', item.id)"
                                        target="_blank"
                                        class="p-2 rounded-lg text-text-muted hover:text-success hover:bg-success-soft transition-colors"
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
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
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
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { toEnglish, formatMoney: formatCurrency } = useNumberFormat();

const props = defineProps({
    invoices: { type: Object, default: () => ({}) },
    returns: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

// View mode state (persisted)
const viewMode = ref(localStorage.getItem('salesInvoicesViewMode') || 'list');
watch(viewMode, (newMode) => {
    localStorage.setItem('salesInvoicesViewMode', newMode);
});

// Status options for dropdown filter
const statusOptions = computed(() => [
    { value: '', label: t('invoices.all_statuses') },
    { value: 'unpaid', label: t('invoices.status.unpaid') },
    { value: 'partial', label: t('invoices.status.partial') },
    { value: 'paid', label: t('invoices.status.paid') },
]);

// Active Tab state
const activeTab = ref(
    props.filters?.tab ||
        (typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('tab')
            : null) ||
        'invoices'
);

// Unified Tabs definition
const tabs = computed(() => [
    {
        id: 'invoices',
        label: t('invoices.sales.tab_invoices'),
        count: props.invoices?.total || 0,
    },
    {
        id: 'returns',
        label: t('invoices.sales.tab_returns'),
        count: props.returns?.total || 0,
    },
]);

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
    routeUrl: '/app/invoices/sales',
    initialFilters: {
        tab: activeTab.value !== 'invoices' ? activeTab.value : '',
        search: props.filters?.search || '',
        payment_status: props.filters?.payment_status || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
    },
    initialSort: {
        column: props.filters?.sort || '',
        direction: props.filters?.direction || 'desc',
    },
    debounceMs: 350,
});

// Synchronize props.filters with tableFilters when Inertia updates
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.tab !== undefined && newFilters.tab !== tableFilters.tab) {
                tableFilters.tab = newFilters.tab || '';
                activeTab.value = newFilters.tab || 'invoices';
            }
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (
                newFilters.payment_status !== undefined &&
                newFilters.payment_status !== tableFilters.payment_status
            ) {
                tableFilters.payment_status = newFilters.payment_status || '';
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
                sortColumn.value = newFilters.sort || '';
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

// Tab Change Handler
const onTabChange = (tabId) => {
    cancelDebouncedSearch?.();
    activeTab.value = tabId;
    tableFilters.tab = tabId !== 'invoices' ? tabId : '';
    applyFilters();
};

// Reset Filters
const hasActiveFilters = computed(() => {
    return Boolean(
        tableFilters.search ||
        tableFilters.payment_status ||
        tableFilters.date_from ||
        tableFilters.date_to ||
        sortColumn.value
    );
});

const resetAllFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.payment_status = '';
    tableFilters.date_from = '';
    tableFilters.date_to = '';
    sortColumn.value = '';
    sortDirection.value = 'desc';
    applyFilters();
};

// Active Data and Rows
const activeData = computed(() => (activeTab.value === 'returns' ? props.returns : props.invoices));
const activeRows = computed(() => activeData.value?.data || []);

// Columns Definitions
const invoiceColumns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: false },
    { key: 'invoice_number', label: t('invoices.invoice_number'), align: 'center', sortable: true },
    { key: 'issue_date', label: t('invoices.issue_date'), align: 'center', sortable: true },
    { key: 'customer', label: t('common.customer'), align: 'start', sortable: false },
    { key: 'gross_total', label: t('invoices.gross_total'), align: 'end', sortable: false },
    { key: 'discount', label: t('invoices.discount'), align: 'end', sortable: false },
    { key: 'total_excl_tax', label: t('invoices.subtotal'), align: 'end', sortable: true },
    { key: 'total_tax', label: t('common.vat'), align: 'end', sortable: false },
    { key: 'total_incl_tax', label: t('invoices.grand_total'), align: 'end', sortable: true },
    { key: 'balance', label: t('invoices.balance'), align: 'end', sortable: true },
    { key: 'payment_status', label: t('common.status'), align: 'center', sortable: false },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-24', sortable: false },
]);

const returnColumns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: false },
    { key: 'invoice_number', label: t('invoices.return_code'), align: 'center', sortable: true },
    {
        key: 'original_invoice',
        label: t('invoices.original_invoice'),
        align: 'center',
        sortable: false,
    },
    { key: 'issue_date', label: t('invoices.issue_date'), align: 'center', sortable: true },
    { key: 'customer', label: t('customers.customer'), align: 'start', sortable: false },
    { key: 'total_excl_tax', label: t('invoices.subtotal'), align: 'end', sortable: true },
    { key: 'total_tax', label: t('invoices.tax'), align: 'end', sortable: false },
    {
        key: 'total_incl_tax',
        label: t('invoices.credit_note.total_credit_note'),
        align: 'end',
        sortable: true,
    },
    { key: 'balance', label: t('invoices.balance'), align: 'end', sortable: true },
    { key: 'payment_status', label: t('common.status'), align: 'center', sortable: false },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-24', sortable: false },
]);

const activeColumns = computed(() =>
    activeTab.value === 'returns' ? returnColumns.value : invoiceColumns.value
);

// Formatting Helpers
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

// formatCurrency provided by useNumberFormat()

const getDiscount = (invoice) => {
    if (!invoice.work_order) return 0;
    const itemsDiscount =
        invoice.work_order.items?.reduce(
            (sum, item) => sum + Number(item.discount_amount || 0),
            0
        ) || 0;
    const partsDiscount =
        invoice.work_order.parts?.reduce((sum, part) => sum + Number(part.discount || 0), 0) || 0;
    return itemsDiscount + partsDiscount;
};

const getGrossTotal = (invoice) => {
    const exclTax = Number(invoice.total_excl_tax || 0);
    return exclTax + getDiscount(invoice);
};

const statusClass = (status) => {
    const map = {
        paid: 'bg-success-soft text-success border border-success/20',
        partial: 'bg-warning-soft text-warning border border-warning/20',
        unpaid: 'bg-danger-soft text-danger border border-danger/20',
    };
    return map[status] || 'bg-surface-muted text-text-secondary border border-border';
};

const stripeClass = (status) => {
    const map = {
        paid: 'bg-success',
        partial: 'bg-warning',
        unpaid: 'bg-danger',
    };
    return map[status] || 'bg-border';
};
</script>
