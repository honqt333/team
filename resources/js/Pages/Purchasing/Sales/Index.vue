<template>
    <AppLayout :title="$t('nav.sales') + ' - ' + $t('purchasing.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('nav.sales') + ' (' + $t('inventory.title') + ')'"
                :subtitle="$t('invoices.sales.subtitle')"
                :totalCount="
                    activeTab === 'invoices'
                        ? invoices?.total
                            ? toEnglish(invoices.total)
                            : '0'
                        : returns?.total
                          ? toEnglish(returns.total)
                          : '0'
                "
                :countLabel="
                    activeTab === 'invoices'
                        ? $t('invoices.sales.tab_invoices')
                        : $t('invoices.sales.tab_returns')
                "
            >
                <template #back>
                    <BackButton :href="route('app.purchasing.hub')" />
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
                                storage-key="sales_invoices_view_mode"
                            />
                        </div>

                        <!-- Create Invoice Button (Outer Left in RTL) -->
                        <button
                            v-if="can('crm.work_orders.create') || isAnyAdmin()"
                            @click="showCreateModal = true"
                            type="button"
                            class="flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer"
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
                                {{ $t('invoices.sales.create_invoice') }}
                            </span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-3 no-print">
                        <!-- Search -->
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
                                :placeholder="$t('common.search')"
                                class="block w-full ps-11 pe-4 py-2.5 text-sm text-content-primary bg-surface-primary border border-border-subtle rounded-xl focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all outline-none placeholder-content-muted shadow-xs"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <!-- Date From -->
                            <div class="w-full sm:w-36">
                                <CustomDatePicker
                                    v-model="tableFilters.date_from"
                                    :placeholder="$t('common.date_from')"
                                    @update:model-value="applyFilters"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="w-full sm:w-36">
                                <CustomDatePicker
                                    v-model="tableFilters.date_to"
                                    :placeholder="$t('common.date_to')"
                                    @update:model-value="applyFilters"
                                />
                            </div>

                            <!-- Status Filter -->
                            <div class="w-full sm:w-40">
                                <SearchableSelect
                                    v-model="tableFilters.payment_status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('invoices.all_statuses')"
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
                <!-- INVOICES TAB -->
                <div v-if="activeTab === 'invoices'">
                    <!-- Empty State -->
                    <div
                        v-if="!invoices?.data || invoices.data.length === 0"
                        class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                    >
                        <AppEmptyState
                            :title="$t('invoices.sales.title')"
                            :description="$t('invoices.sales.empty_hint')"
                            :action-text="
                                can('crm.work_orders.create') || isAnyAdmin()
                                    ? $t('invoices.sales.create_invoice')
                                    : ''
                            "
                            @action="showCreateModal = true"
                        />
                    </div>

                    <!-- GRID VIEW -->
                    <div v-else-if="viewMode === 'grid'" class="space-y-6 no-print">
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                        >
                            <Link
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                                :href="route('app.invoices.show', invoice.id)"
                                class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden p-5"
                            >
                                <!-- Top status badge & number -->
                                <div
                                    class="flex items-center justify-between gap-2 pb-3 border-b border-border-subtle"
                                >
                                    <span
                                        class="text-sm font-bold font-mono text-action-primary group-hover:underline"
                                    >
                                        #{{ invoice.invoice_number }}
                                    </span>
                                    <span :class="getStatusBadgeClass(invoice.payment_status)">
                                        {{ getStatusLabel(invoice.payment_status) }}
                                    </span>
                                </div>

                                <!-- Customer -->
                                <div class="py-4 space-y-1">
                                    <div
                                        class="text-sm font-semibold text-content-primary truncate"
                                    >
                                        {{
                                            invoice.customer_name_snapshot ||
                                            invoice.customer?.name ||
                                            '—'
                                        }}
                                    </div>
                                    <div class="text-xs text-content-secondary font-mono">
                                        {{ formatDate(invoice.issue_date) }}
                                    </div>
                                </div>

                                <!-- Details -->
                                <div
                                    class="pt-3 border-t border-border-subtle mt-auto space-y-2 text-xs"
                                >
                                    <div class="flex justify-between items-center">
                                        <span class="text-content-muted">
                                            {{ $t('invoices.subtotal') }}:
                                        </span>
                                        <span class="font-mono text-content-secondary">
                                            {{ formatCurrency(invoice.total_excl_tax) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-content-muted">
                                            {{ $t('invoices.balance') }}:
                                        </span>
                                        <span
                                            :class="
                                                invoice.balance > 0
                                                    ? 'text-danger font-bold'
                                                    : 'text-success font-medium'
                                            "
                                            class="font-mono"
                                        >
                                            {{ formatCurrency(invoice.balance) }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex justify-between items-center pt-1 border-t border-border-subtle/60 text-sm font-bold"
                                    >
                                        <span class="text-content-muted text-xs">
                                            {{ $t('invoices.grand_total') }}:
                                        </span>
                                        <span class="font-mono text-content-primary">
                                            {{ formatCurrency(invoice.total_incl_tax) }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Pagination for Grid -->
                        <div v-if="invoices?.links?.length >= 3" class="mt-6">
                            <AppPagination
                                :links="invoices.links"
                                :from="invoices.from"
                                :to="invoices.to"
                                :total="invoices.total"
                            />
                        </div>
                    </div>

                    <!-- LIST VIEW (DataTable) -->
                    <div v-else class="space-y-4 no-print">
                        <DataTable
                            :data="invoices"
                            :columns="invoiceColumns"
                            :loading="isLoading"
                            :sort-column="sortColumn"
                            :sort-direction="sortDirection"
                            @sort="toggleSort"
                            :empty-title="$t('invoices.sales.title')"
                            :empty-description="$t('invoices.sales.empty_hint')"
                        >
                            <template #cell-index="{ index }">
                                <span class="text-xs text-content-muted font-mono">
                                    {{ toEnglish((invoices.from || 1) + index) }}
                                </span>
                            </template>

                            <template #cell-invoice_number="{ item }">
                                <Link
                                    :href="route('app.invoices.show', item.id)"
                                    class="font-bold text-action-primary hover:underline font-mono"
                                >
                                    {{ item.invoice_number }}
                                </Link>
                            </template>

                            <template #cell-issue_date="{ item }">
                                <span class="text-xs text-content-secondary font-mono">
                                    {{ formatDate(item.issue_date) }}
                                </span>
                            </template>

                            <template #cell-customer="{ item }">
                                <span class="text-sm font-medium text-content-primary">
                                    {{ item.customer_name_snapshot || item.customer?.name || '—' }}
                                </span>
                            </template>

                            <template #cell-total_excl_tax="{ item }">
                                <span class="text-sm font-mono text-content-secondary">
                                    {{ formatCurrency(item.total_excl_tax) }}
                                </span>
                            </template>

                            <template #cell-total_tax="{ item }">
                                <span class="text-sm font-mono text-content-muted">
                                    {{ formatCurrency(item.total_tax) }}
                                </span>
                            </template>

                            <template #cell-total_incl_tax="{ item }">
                                <span class="text-sm font-bold font-mono text-content-primary">
                                    {{ formatCurrency(item.total_incl_tax) }}
                                </span>
                            </template>

                            <template #cell-balance="{ item }">
                                <span
                                    :class="
                                        item.balance > 0
                                            ? 'text-danger font-bold'
                                            : 'text-success font-medium'
                                    "
                                    class="text-sm font-mono"
                                >
                                    {{ formatCurrency(item.balance) }}
                                </span>
                            </template>

                            <template #cell-payment_status="{ item }">
                                <span :class="getStatusBadgeClass(item.payment_status)">
                                    {{ getStatusLabel(item.payment_status) }}
                                </span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <Tooltip :text="$t('common.view')">
                                        <Link
                                            :href="route('app.invoices.show', item.id)"
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

                <!-- RETURNS TAB -->
                <div v-else-if="activeTab === 'returns'">
                    <!-- Empty State -->
                    <div
                        v-if="!returns?.data || returns.data.length === 0"
                        class="bg-surface-primary rounded-2xl border border-border-subtle p-12"
                    >
                        <AppEmptyState
                            :title="$t('invoices.sales.tab_returns')"
                            :description="$t('invoices.sales.no_returns')"
                        />
                    </div>

                    <!-- GRID VIEW -->
                    <div v-else-if="viewMode === 'grid'" class="space-y-6 no-print">
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                        >
                            <Link
                                v-for="item in returns.data"
                                :key="item.id"
                                :href="route('app.invoices.show', item.id)"
                                class="group relative flex flex-col bg-surface-primary rounded-2xl border border-border-subtle hover:border-border-strong hover:shadow-md transition-all duration-300 overflow-hidden p-5"
                            >
                                <div
                                    class="flex items-center justify-between gap-2 pb-3 border-b border-border-subtle"
                                >
                                    <span
                                        class="text-sm font-bold font-mono text-danger group-hover:underline"
                                    >
                                        #{{ item.invoice_number }}
                                    </span>
                                    <span :class="getStatusBadgeClass(item.payment_status)">
                                        {{ getStatusLabel(item.payment_status) }}
                                    </span>
                                </div>

                                <div class="py-4 space-y-1">
                                    <div
                                        class="text-sm font-semibold text-content-primary truncate"
                                    >
                                        {{
                                            item.customer_name_snapshot ||
                                            item.customer?.name ||
                                            '—'
                                        }}
                                    </div>
                                    <div class="text-xs text-content-secondary font-mono">
                                        {{ formatDate(item.issue_date) }}
                                    </div>
                                    <div
                                        v-if="item.original_invoice"
                                        class="text-xs text-content-muted flex items-center gap-1"
                                    >
                                        <span>{{ $t('invoices.original_invoice') }}:</span>
                                        <span class="font-mono text-content-secondary">
                                            #{{ item.original_invoice.invoice_number }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="pt-3 border-t border-border-subtle mt-auto flex justify-between items-center text-sm font-bold"
                                >
                                    <span class="text-content-muted text-xs">
                                        {{ $t('invoices.grand_total') }}:
                                    </span>
                                    <span class="font-mono text-danger">
                                        -{{ formatCurrency(item.total_incl_tax) }}
                                    </span>
                                </div>
                            </Link>
                        </div>

                        <!-- Pagination for Grid -->
                        <div v-if="returns?.links?.length >= 3" class="mt-6">
                            <AppPagination
                                :links="returns.links"
                                :from="returns.from"
                                :to="returns.to"
                                :total="returns.total"
                            />
                        </div>
                    </div>

                    <!-- LIST VIEW (DataTable) -->
                    <div v-else class="space-y-4 no-print">
                        <DataTable
                            :data="returns"
                            :columns="returnColumns"
                            :loading="isLoading"
                            :sort-column="sortColumn"
                            :sort-direction="sortDirection"
                            @sort="toggleSort"
                            :empty-title="$t('invoices.sales.tab_returns')"
                            :empty-description="$t('invoices.sales.no_returns')"
                        >
                            <template #cell-index="{ index }">
                                <span class="text-xs text-content-muted font-mono">
                                    {{ toEnglish((returns.from || 1) + index) }}
                                </span>
                            </template>

                            <template #cell-invoice_number="{ item }">
                                <Link
                                    :href="route('app.invoices.show', item.id)"
                                    class="font-bold text-danger hover:underline font-mono"
                                >
                                    {{ item.invoice_number }}
                                </Link>
                            </template>

                            <template #cell-original_invoice="{ item }">
                                <span
                                    v-if="item.original_invoice"
                                    class="text-xs font-mono text-content-secondary"
                                >
                                    #{{ item.original_invoice.invoice_number }}
                                </span>
                                <span v-else class="text-xs text-content-muted">—</span>
                            </template>

                            <template #cell-issue_date="{ item }">
                                <span class="text-xs text-content-secondary font-mono">
                                    {{ formatDate(item.issue_date) }}
                                </span>
                            </template>

                            <template #cell-customer="{ item }">
                                <span class="text-sm font-medium text-content-primary">
                                    {{ item.customer_name_snapshot || item.customer?.name || '—' }}
                                </span>
                            </template>

                            <template #cell-total_incl_tax="{ item }">
                                <span class="text-sm font-bold font-mono text-danger">
                                    -{{ formatCurrency(item.total_incl_tax) }}
                                </span>
                            </template>

                            <template #cell-payment_status="{ item }">
                                <span :class="getStatusBadgeClass(item.payment_status)">
                                    {{ getStatusLabel(item.payment_status) }}
                                </span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <Tooltip :text="$t('common.view')">
                                        <Link
                                            :href="route('app.invoices.show', item.id)"
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
        </div>

        <!-- Create Modal -->
        <SalesInvoiceFormModal
            v-if="showCreateModal"
            :show="showCreateModal"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            :warehouses="warehouses"
            :units="units"
            @close="showCreateModal = false"
            @saved="handleSaved"
        />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('nav.sales')"
                    :subtitle="
                        activeTab === 'invoices'
                            ? $t('invoices.sales.tab_invoices')
                            : $t('invoices.sales.tab_returns')
                    "
                />

                <DataTable structured class="w-full mt-6">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('invoices.invoice_number') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('common.customer') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('invoices.issue_date') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('invoices.grand_total') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase border-b-2 align-middle"
                            >
                                {{ $t('common.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="invoice in (activeTab === 'invoices'
                                ? invoices?.data
                                : returns?.data) || []"
                            :key="invoice.id"
                            class="border-b"
                        >
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ invoice.invoice_number }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm align-middle">
                                {{
                                    invoice.customer_name_snapshot || invoice.customer?.name || '—'
                                }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ formatDate(invoice.issue_date) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-mono align-middle">
                                {{ formatCurrency(invoice.total_incl_tax) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-bold align-middle">
                                {{ getStatusLabel(invoice.payment_status) }}
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
import { useAutoOpenCreate } from '@/Composables/useAutoOpenCreate';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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
import SalesInvoiceFormModal from '@/Components/Purchasing/SalesInvoiceFormModal.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { toEnglish, formatCurrency } = useNumberFormat();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    invoices: { type: Object, default: () => ({}) },
    returns: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

const activeTab = ref(props.filters?.tab === 'returns' ? 'returns' : 'invoices');
const showCreateModal = ref(false);
useAutoOpenCreate(() => {
    showCreateModal.value = true;
});

const viewMode = ref(localStorage.getItem('sales_invoices_view_mode') || 'list');

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

const statusOptions = [
    { value: '', label: t('invoices.all_statuses') },
    { value: 'unpaid', label: t('invoices.status.unpaid') },
    { value: 'partial', label: t('invoices.status.partial') },
    { value: 'paid', label: t('invoices.status.paid') },
];

const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
} = useServerTable({
    routeUrl: () => route('app.purchasing.sales.index'),
    initialFilters: {
        search: props.filters?.search || '',
        payment_status: props.filters?.payment_status || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
        tab: activeTab.value,
    },
    initialSort: {
        column: props.filters?.sort || 'issue_date',
        direction: props.filters?.direction || 'desc',
    },
});

const hasActiveFilters = computed(() => {
    return Boolean(
        tableFilters.search ||
        tableFilters.payment_status ||
        tableFilters.date_from ||
        tableFilters.date_to
    );
});

const handleResetFilters = () => {
    tableFilters.search = '';
    tableFilters.payment_status = '';
    tableFilters.date_from = '';
    tableFilters.date_to = '';
    applyFilters();
};

const onTabChange = (tabId) => {
    activeTab.value = tabId;
    tableFilters.tab = tabId;
    applyFilters();
};

const handleSaved = () => {
    showCreateModal.value = false;
    router.reload({ only: ['invoices', 'returns'] });
};

const handlePrint = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.delete('invoices_page');
    url.searchParams.delete('returns_page');
    window.open(url.toString(), '_blank');
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const invoiceColumns = computed(() => [
    { key: 'index', label: '#', sortable: false, width: '50px', align: 'center' },
    { key: 'invoice_number', label: t('invoices.invoice_number'), sortable: true, align: 'start' },
    { key: 'issue_date', label: t('invoices.issue_date'), sortable: true, align: 'start' },
    { key: 'customer', label: t('common.customer'), sortable: true, align: 'start' },
    { key: 'total_excl_tax', label: t('invoices.subtotal'), sortable: true, align: 'start' },
    { key: 'total_tax', label: t('common.vat'), sortable: false, align: 'start' },
    { key: 'total_incl_tax', label: t('invoices.grand_total'), sortable: true, align: 'start' },
    { key: 'balance', label: t('invoices.balance'), sortable: true, align: 'start' },
    { key: 'payment_status', label: t('common.status'), sortable: true, align: 'start' },
    { key: 'actions', label: t('common.actions'), sortable: false, align: 'center' },
]);

const returnColumns = computed(() => [
    { key: 'index', label: '#', sortable: false, width: '50px', align: 'center' },
    { key: 'invoice_number', label: t('invoices.invoice_number'), sortable: true, align: 'start' },
    {
        key: 'original_invoice',
        label: t('invoices.original_invoice'),
        sortable: false,
        align: 'start',
    },
    { key: 'issue_date', label: t('invoices.issue_date'), sortable: true, align: 'start' },
    { key: 'customer', label: t('common.customer'), sortable: true, align: 'start' },
    { key: 'total_incl_tax', label: t('invoices.grand_total'), sortable: true, align: 'start' },
    { key: 'payment_status', label: t('common.status'), sortable: true, align: 'start' },
    { key: 'actions', label: t('common.actions'), sortable: false, align: 'center' },
]);

const getStatusLabel = (status) => {
    const map = {
        paid: t('invoices.status.paid'),
        partial: t('invoices.status.partial'),
        unpaid: t('invoices.status.unpaid'),
    };
    return map[status] || status;
};

const getStatusBadgeClass = (status) => {
    const map = {
        paid: 'bg-success-soft text-success border-success-subtle',
        partial: 'bg-warning-soft text-warning border-warning-subtle',
        unpaid: 'bg-danger-soft text-danger border-danger-subtle',
    };
    return `inline-flex px-2.5 py-1 rounded-full text-xs font-bold border shadow-xs ${map[status] || 'bg-surface-secondary text-content-secondary'}`;
};
</script>
