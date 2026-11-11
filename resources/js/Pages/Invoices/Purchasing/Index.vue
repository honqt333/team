<template>
    <Head :title="$t('invoices.purchases.title')" />
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('invoices.purchases.title')"
                :subtitle="$t('invoices.purchases.subtitle')"
                :totalCount="activeData?.total ? toEnglish(activeData.total) : '0'"
                :countLabel="
                    activeTab === 'returns'
                        ? $t('invoices.purchases.tab_returns')
                        : $t('invoices.purchases.tab_invoices')
                "
                gradientFrom="from-amber-600"
                gradientTo="to-orange-600"
                glowFrom="from-amber-500"
                glowTo="to-orange-600"
                badgeBg="bg-amber-50/70 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-200/60 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
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
                            stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
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
                                :url="
                                    route('app.invoices.purchases.index', {
                                        ...tableFilters,
                                        print: 1,
                                    })
                                "
                                variant="icon"
                                :title="$t('common.print')"
                            />
                            <div class="w-px h-5 bg-border-subtle mx-0.5"></div>
                            <AppViewModeToggle v-model="viewMode" />
                        </div>

                        <!-- Add Direct Purchase Invoice Button (Outer Left in RTL) -->
                        <button
                            v-if="can('purchasing.invoices.create') || isAnyAdmin()"
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
                            <span>{{ $t('invoices.purchases.add') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <!-- Search -->
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
                                type="text"
                                v-model="tableFilters.search"
                                :placeholder="$t('common.search')"
                                class="input-base ps-10 pe-4 py-2.5 text-sm w-full rounded-2xl"
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

                        <!-- Status Filter (Only relevant for invoices tab) -->
                        <div v-if="activeTab === 'invoices'" class="w-full sm:w-44">
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
                                :placeholder="$t('invoices.all_statuses')"
                                class="!rounded-2xl"
                                compact
                                @change="() => applyFilters()"
                            />
                        </div>

                        <!-- Reset Filters Button -->
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

            <!-- Standard AppTabs Component -->
            <AppTabs
                :model-value="activeTab"
                :tabs="tabs"
                variant="pills"
                size="md"
                @change="onTabChange"
            />

            <!-- Tab Content -->
            <div class="space-y-6">
                <!-- GRID VIEW -->
                <div v-if="viewMode === 'grid'">
                    <div
                        v-if="!activeRows.length"
                        class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                    >
                        <AppEmptyState
                            :title="
                                activeTab === 'returns'
                                    ? $t('invoices.purchases.no_returns')
                                    : $t('invoices.purchases.title')
                            "
                            :description="
                                activeTab === 'returns'
                                    ? $t('invoices.purchases.returns_empty_hint') ||
                                      $t('common.no_records_found')
                                    : $t('invoices.purchases.empty_hint') ||
                                      $t('common.no_records_found')
                            "
                            :action-text="
                                activeTab === 'returns'
                                    ? ''
                                    : can('purchasing.invoices.create') || isAnyAdmin()
                                      ? $t('invoices.purchases.add')
                                      : ''
                            "
                            @action="showCreateModal = true"
                        />
                    </div>

                    <!-- Invoices Cards Grid -->
                    <div
                        v-else-if="activeTab === 'invoices'"
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5"
                    >
                        <Link
                            v-for="invoice in activeRows"
                            :key="invoice.id"
                            :href="route('app.invoices.purchases.show', invoice.id)"
                            class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden"
                        >
                            <!-- Header Bar -->
                            <div
                                class="bg-surface-secondary/50 p-4 pe-5 flex justify-between items-start border-b border-border-subtle"
                            >
                                <span
                                    class="text-[11px] font-semibold text-content-secondary font-mono bg-surface-secondary px-2 py-0.5 rounded-lg border border-border-subtle"
                                >
                                    {{ formatDate(invoice.issue_date) }}
                                </span>
                                <div class="text-end">
                                    <span
                                        class="text-sm font-bold font-mono tracking-tight text-content-primary group-hover:text-action-primary transition-colors"
                                    >
                                        #{{ invoice.code }}
                                    </span>
                                    <p
                                        v-if="invoice.invoice_number"
                                        class="text-[10px] text-content-muted font-mono truncate max-w-[120px]"
                                    >
                                        {{ invoice.invoice_number }}
                                    </p>
                                </div>
                            </div>

                            <!-- Body Content -->
                            <div class="p-4 flex-1 flex flex-col justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <p
                                            class="text-xs font-semibold text-content-primary truncate"
                                        >
                                            {{ invoice.supplier?.name || '—' }}
                                        </p>
                                        <span
                                            :class="invoiceStatusClass(invoice.status)"
                                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold"
                                        >
                                            {{
                                                $t(`invoices.purchases.statuses.${invoice.status}`)
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="invoice.purchase_order"
                                        class="text-[10px] text-content-muted font-mono"
                                    >
                                        {{ $t('purchasing.order') }}: #{{
                                            invoice.purchase_order.code
                                        }}
                                    </div>
                                </div>

                                <!-- Financial Info -->
                                <div class="space-y-2 pt-2 border-t border-border-subtle">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-content-muted">
                                            {{ $t('common.total') }}
                                        </span>
                                        <span
                                            class="font-bold text-content-primary font-mono"
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(invoice.total) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="Number(invoice.balance) > 0"
                                        class="flex justify-between items-center text-xs"
                                    >
                                        <span class="text-danger font-medium">
                                            {{ $t('invoices.purchases.remaining') }}
                                        </span>
                                        <span class="font-bold text-danger font-mono" dir="ltr">
                                            {{ formatCurrency(invoice.balance) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Returns Cards Grid -->
                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5"
                    >
                        <Link
                            v-for="ret in activeRows"
                            :key="ret.id"
                            :href="route('app.invoices.purchases.returns.show', ret.id)"
                            class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden"
                        >
                            <!-- Header Bar -->
                            <div
                                class="bg-surface-secondary/50 p-4 pe-5 flex justify-between items-start border-b border-border-subtle"
                            >
                                <span
                                    class="text-[11px] font-semibold text-content-secondary font-mono bg-surface-secondary px-2 py-0.5 rounded-lg border border-border-subtle"
                                >
                                    {{ formatDate(ret.return_date) }}
                                </span>
                                <div class="text-end">
                                    <span
                                        class="text-sm font-bold font-mono tracking-tight text-content-primary group-hover:text-action-primary transition-colors"
                                    >
                                        #{{ ret.code }}
                                    </span>
                                    <p
                                        v-if="ret.purchase_invoice"
                                        class="text-[10px] text-action-primary font-mono truncate max-w-[120px]"
                                    >
                                        #{{ ret.purchase_invoice.code }}
                                    </p>
                                </div>
                            </div>

                            <!-- Body Content -->
                            <div class="p-4 flex-1 flex flex-col justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold text-content-primary truncate">
                                        {{ ret.purchase_invoice?.supplier?.name || '—' }}
                                    </p>
                                    <p class="text-[10px] text-content-muted">
                                        {{ $t('invoices.original_invoice') }}: #{{
                                            ret.purchase_invoice?.code || '—'
                                        }}
                                    </p>
                                </div>

                                <!-- Financial Info -->
                                <div class="space-y-2 pt-2 border-t border-border-subtle">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-content-muted">
                                            {{ $t('common.total') }}
                                        </span>
                                        <span class="font-bold text-danger font-mono" dir="ltr">
                                            {{ formatCurrency(ret.total) }}
                                        </span>
                                    </div>
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
                                ? $t('invoices.purchases.no_returns')
                                : $t('invoices.purchases.title')
                        "
                        :empty-description="
                            activeTab === 'returns'
                                ? $t('invoices.purchases.returns_empty_hint') ||
                                  $t('common.no_records_found')
                                : $t('invoices.purchases.empty_hint') ||
                                  $t('common.no_records_found')
                        "
                        :empty-action-text="
                            activeTab === 'returns'
                                ? ''
                                : can('purchasing.invoices.create') || isAnyAdmin()
                                  ? $t('invoices.purchases.add')
                                  : ''
                        "
                        @empty-action="showCreateModal = true"
                    >
                        <!-- Index Column -->
                        <template #cell-index="{ index }">
                            <span class="text-xs text-content-muted font-mono">
                                {{ toEnglish((activeData.from || 1) + index) }}
                            </span>
                        </template>

                        <!-- Code Column -->
                        <template #cell-code="{ item }">
                            <Link
                                v-if="activeTab === 'invoices'"
                                :href="route('app.invoices.purchases.show', item.id)"
                                class="font-bold text-action-primary hover:underline font-mono"
                            >
                                #{{ item.code }}
                            </Link>
                            <Link
                                v-else
                                :href="route('app.invoices.purchases.returns.show', item.id)"
                                class="font-bold text-action-primary hover:underline font-mono"
                            >
                                #{{ item.code }}
                            </Link>
                        </template>

                        <!-- Invoice Number Column (for invoices) -->
                        <template #cell-invoice_number="{ item }">
                            <span class="text-xs text-content-secondary font-mono">
                                {{ item.invoice_number ? '#' + item.invoice_number : '—' }}
                            </span>
                        </template>

                        <!-- Original Invoice Column (for returns) -->
                        <template #cell-original_invoice="{ item }">
                            <Link
                                v-if="item.purchase_invoice"
                                :href="
                                    route('app.invoices.purchases.show', item.purchase_invoice_id)
                                "
                                class="text-xs font-semibold text-action-primary hover:underline font-mono"
                            >
                                #{{ item.purchase_invoice.code }}
                            </Link>
                            <span v-else class="text-content-muted text-xs">—</span>
                        </template>

                        <!-- Issue Date / Return Date Column -->
                        <template #cell-issue_date="{ item }">
                            <span class="text-xs text-content-secondary font-mono">
                                {{ formatDate(item.issue_date) }}
                            </span>
                        </template>

                        <template #cell-return_date="{ item }">
                            <span class="text-xs text-content-secondary font-mono">
                                {{ formatDate(item.return_date) }}
                            </span>
                        </template>

                        <!-- Supplier Column -->
                        <template #cell-supplier="{ item }">
                            <p class="font-medium text-content-primary truncate max-w-[200px]">
                                {{
                                    (activeTab === 'invoices'
                                        ? item.supplier?.name
                                        : item.purchase_invoice?.supplier?.name) || '—'
                                }}
                            </p>
                        </template>

                        <!-- Subtotal Column -->
                        <template #cell-subtotal="{ item }">
                            <span
                                class="text-sm font-semibold text-content-primary font-mono"
                                dir="ltr"
                            >
                                {{ formatCurrency(item.subtotal) }}
                            </span>
                        </template>

                        <!-- Tax Amount Column -->
                        <template #cell-tax_amount="{ item }">
                            <span
                                class="text-sm font-medium text-content-muted font-mono"
                                dir="ltr"
                            >
                                {{ formatCurrency(item.tax_amount) }}
                            </span>
                        </template>

                        <!-- Total Column -->
                        <template #cell-total="{ item }">
                            <span
                                :class="
                                    activeTab === 'returns' ? 'text-danger' : 'text-content-primary'
                                "
                                class="text-sm font-bold font-mono"
                                dir="ltr"
                            >
                                {{ formatCurrency(item.total) }}
                            </span>
                        </template>

                        <!-- Balance Column (for invoices) -->
                        <template #cell-balance="{ item }">
                            <div class="font-mono text-end" dir="ltr">
                                <span
                                    :class="
                                        Number(item.balance) > 0
                                            ? 'text-danger font-bold'
                                            : 'text-success font-semibold'
                                    "
                                >
                                    {{ formatCurrency(item.balance || 0) }}
                                </span>
                                <div
                                    v-if="Number(item.balance) > 0 && item.due_date"
                                    class="text-[10px] text-danger/80 font-sans font-semibold mt-0.5"
                                >
                                    {{ getOverdueLabel(item.due_date) }}
                                </div>
                            </div>
                        </template>

                        <!-- Status Column (for invoices) -->
                        <template #cell-status="{ item }">
                            <span
                                :class="invoiceStatusClass(item.status)"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold"
                            >
                                {{ $t(`invoices.purchases.statuses.${item.status}`) }}
                            </span>
                        </template>

                        <!-- Actions Column -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1">
                                <Tooltip :content="$t('common.view')">
                                    <Link
                                        v-if="activeTab === 'invoices'"
                                        :href="route('app.invoices.purchases.show', item.id)"
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
                                    <Link
                                        v-else
                                        :href="
                                            route('app.invoices.purchases.returns.show', item.id)
                                        "
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
                                <Tooltip
                                    v-if="activeTab === 'invoices'"
                                    :content="$t('common.print')"
                                >
                                    <Link
                                        :href="route('app.invoices.purchases.print', item.id)"
                                        target="_blank"
                                        class="p-2 rounded-lg text-content-muted hover:text-success hover:bg-success-soft transition-colors"
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

        <!-- Direct Purchase Invoice Form Modal -->
        <PurchaseInvoiceFormModal
            v-if="showCreateModal"
            :show="showCreateModal"
            :suppliers="suppliers"
            :warehouses="warehouses"
            :units="units"
            :defaultWarehouse="defaultWarehouse"
            :work-orders="workOrders"
            @close="showCreateModal = false"
            @saved="onInvoiceSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import Tooltip from '@/Components/Tooltip.vue';
import PurchaseInvoiceFormModal from '@/Components/Purchasing/PurchaseInvoiceFormModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { toEnglish, formatMoney: formatCurrency } = useNumberFormat();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    invoices: { type: Object, default: () => ({}) },
    returns: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    defaultWarehouse: { type: Object, default: () => null },
    warehouses: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    workOrders: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);

const onInvoiceSaved = () => {
    router.reload({ only: ['invoices'] });
    showCreateModal.value = false;
};

// View mode state (persisted)
const viewMode = ref(localStorage.getItem('purchaseInvoicesViewMode') || 'list');
watch(viewMode, (newMode) => {
    localStorage.setItem('purchaseInvoicesViewMode', newMode);
});

// Status options for dropdown filter
const statusOptions = computed(() => [
    { value: '', label: t('invoices.all_statuses') },
    { value: 'draft', label: t('invoices.purchases.statuses.draft') },
    { value: 'open', label: t('invoices.purchases.statuses.open') },
    { value: 'paid', label: t('invoices.purchases.statuses.paid') },
    { value: 'cancelled', label: t('invoices.purchases.statuses.cancelled') },
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
        label: t('invoices.purchases.tab_invoices'),
        count: props.invoices?.total || 0,
    },
    {
        id: 'returns',
        label: t('invoices.purchases.tab_returns'),
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
    routeUrl: '/app/invoices/purchases',
    initialFilters: {
        tab: activeTab.value !== 'invoices' ? activeTab.value : '',
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
    },
    initialSort: {
        column: props.filters?.sort || '',
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
            if (newFilters.tab !== undefined && newFilters.tab !== tableFilters.tab) {
                tableFilters.tab = newFilters.tab || '';
                activeTab.value = newFilters.tab || 'invoices';
            }
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (newFilters.status !== undefined && newFilters.status !== tableFilters.status) {
                tableFilters.status = newFilters.status || '';
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
        tableFilters.status ||
        tableFilters.date_from ||
        tableFilters.date_to ||
        sortColumn.value
    );
});

const resetAllFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.status = '';
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
    { key: 'code', label: t('invoices.purchases.invoice'), align: 'center', sortable: true },
    {
        key: 'invoice_number',
        label: t('invoices.purchases.invoice_number'),
        align: 'center',
        sortable: true,
    },
    { key: 'issue_date', label: t('invoices.issue_date'), align: 'center', sortable: true },
    { key: 'supplier', label: t('common.supplier'), align: 'start', sortable: true },
    { key: 'subtotal', label: t('invoices.subtotal'), align: 'end', sortable: true },
    { key: 'tax_amount', label: t('invoices.tax'), align: 'end', sortable: false },
    { key: 'total', label: t('invoices.purchases.total'), align: 'end', sortable: true },
    { key: 'balance', label: t('invoices.purchases.remaining'), align: 'end', sortable: true },
    { key: 'status', label: t('common.status'), align: 'center', sortable: true },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-24', sortable: false },
]);

const returnColumns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: false },
    { key: 'code', label: t('invoices.return_code'), align: 'center', sortable: true },
    {
        key: 'original_invoice',
        label: t('invoices.original_invoice'),
        align: 'center',
        sortable: false,
    },
    { key: 'return_date', label: t('invoices.return_date'), align: 'center', sortable: true },
    { key: 'supplier', label: t('common.supplier'), align: 'start', sortable: false },
    { key: 'subtotal', label: t('invoices.subtotal'), align: 'end', sortable: true },
    { key: 'tax_amount', label: t('invoices.tax'), align: 'end', sortable: false },
    { key: 'total', label: t('common.total'), align: 'end', sortable: true },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-20', sortable: false },
]);

const activeColumns = computed(() =>
    activeTab.value === 'returns' ? returnColumns.value : invoiceColumns.value
);

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

// formatCurrency provided by useNumberFormat()

const invoiceStatusClass = (status) => {
    const map = {
        paid: 'bg-success-soft text-success border border-success/20',
        open: 'bg-action-primary-soft text-action-primary border border-action-primary/20',
        draft: 'bg-surface-secondary text-content-secondary border border-border-subtle',
        cancelled: 'bg-danger-soft text-danger border border-danger/20',
    };
    return map[status] || 'bg-surface-secondary text-content-secondary border border-border-subtle';
};

const getOverdueLabel = (dueDate) => {
    if (!dueDate) return t('invoices.due_amount');
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = today - due;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 0) {
        return toEnglish(diffDays) + ' ' + t('purchasing.days_overdue');
    }
    return t('invoices.purchases.amount_due');
};
</script>
