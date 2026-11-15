<template>
    <Head :title="`${$t('purchasing.grn.title')} #${grn?.code}`" />
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="`${$t('purchasing.grn.title')} #${grn?.code}`"
                :subtitle="grn?.purchase_order?.supplier?.name || grn?.warehouse?.name || ''"
                :total-count="formatCurrency(totalAmount)"
                :count-label="$t('common.total')"
            >
                <template v-if="grn?.purchase_order_id" #back>
                    <BackButton :href="route('app.purchasing.orders.show', grn.purchase_order_id)" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                </template>

                <template #identity>
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge
                            :label="$t(`purchasing.grn.statuses.${grn?.status}`)"
                            :class="statusBadgeClass"
                        />
                        <span v-if="grn?.warehouse?.name" class="text-xs text-text-muted">
                            {{ grn.warehouse.name }}
                        </span>
                    </div>
                </template>

                <template #actions>
                    <AppPrintButton variant="icon" @click="printGrn" />

                    <AppButton
                        v-if="grn?.status === 'draft'"
                        variant="success"
                        size="md"
                        @click="postGrn"
                    >
                        <template #iconLeft>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </template>
                        {{ $t('purchasing.grn.post_voucher') }}
                    </AppButton>

                    <AppButton
                        v-if="grn?.status === 'draft'"
                        variant="danger"
                        size="sm"
                        @click="cancelGrn"
                    >
                        {{ $t('common.cancel') }}
                    </AppButton>
                </template>
            </PageHeader>

            <!-- Unified Linked Documents Bar (Purchase Order & Purchase Invoice) -->
            <div
                v-if="grn?.purchase_order_id || grn?.purchase_invoice"
                class="flex flex-wrap items-center justify-between gap-3 bg-surface px-4 py-3 rounded-2xl border border-border shadow-xs"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-success-soft text-success border border-success/20"
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
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                        <span>{{ $t('purchasing.grn.title') }}</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <LinkedInvoiceBadge
                        v-if="grn?.purchase_order_id"
                        :href="route('app.purchasing.orders.show', grn.purchase_order_id)"
                        kind="work_order"
                        :number="grn.purchase_order?.code || String(grn.purchase_order_id)"
                        :tooltip="`${$t('purchasing.orders.title')} #${grn.purchase_order?.code || grn.purchase_order_id}`"
                    />

                    <LinkedInvoiceBadge
                        v-if="grn?.purchase_invoice"
                        :href="route('app.invoices.purchases.show', grn.purchase_invoice.id)"
                        :kind="grn.purchase_invoice.is_deferred ? 'deferred' : 'paid'"
                        :number="grn.purchase_invoice.code || String(grn.purchase_invoice.id)"
                        :tooltip="`${$t('invoices.purchases.invoice')} #${grn.purchase_invoice.code}`"
                    />
                </div>
            </div>

            <!-- Top Cards Section: Supplier Info & Financial Cost Card -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- 1. Supplier Card -->
                <WorkOrderCustomerCard
                    :customer="grn?.purchase_order?.supplier"
                    :customer-name="grn?.purchase_order?.supplier?.name || grn?.warehouse?.name || ''"
                    :vat-number="grn?.purchase_order?.supplier?.tax_number || ''"
                    :address="supplierAddress"
                    party-type="supplier"
                    :party-href="
                        grn?.purchase_order?.supplier?.id
                            ? route('app.purchasing.suppliers.show', grn.purchase_order.supplier.id)
                            : ''
                    "
                    :party-badge="$t('purchasing.orders.supplier')"
                >
                    <template #header>
                        <div
                            class="flex items-center justify-between bg-surface border-b border-border px-4 py-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-full border border-border bg-selection-bg text-action-primary flex items-center justify-center shrink-0 shadow-inner"
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
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                        />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-text-muted"
                                    >
                                        {{ $t('inventory.warehouses.title') }}
                                    </p>
                                    <h4 class="text-sm font-bold text-text-primary truncate">
                                        {{ grn?.warehouse?.name || '-' }}
                                    </h4>
                                </div>
                            </div>
                            <div class="text-end shrink-0">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wider text-text-muted"
                                >
                                    {{ $t('purchasing.grn.received_date') }}
                                </p>
                                <p class="text-xs font-bold font-mono text-text-primary">
                                    {{ formatDate(grn?.received_date) }}
                                </p>
                            </div>
                        </div>
                    </template>
                </WorkOrderCustomerCard>

                <!-- 2. Financial Cost Card -->
                <WorkOrderFinancialSummary
                    :totals="financialTotals"
                    :has-tax="false"
                    :show-payments="false"
                    :hide-zero-rows="true"
                    :parts-label="$t('purchasing.orders.items')"
                    :grand-label="$t('common.total')"
                >
                    <template #footer>
                        <div class="mt-4 pt-3 border-t border-border grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <Link
                                v-if="grn?.purchase_order_id"
                                :href="route('app.purchasing.orders.show', grn.purchase_order_id)"
                                class="p-2.5 rounded-xl bg-selection-bg border border-action-primary/25 hover:border-action-primary/50 transition-colors"
                            >
                                <div
                                    class="text-[10px] font-black text-action-primary uppercase tracking-widest mb-0.5"
                                >
                                    {{ $t('purchasing.grn.for_po') }}
                                </div>
                                <div class="text-xs font-bold text-selection-text font-mono">
                                    {{ grn.purchase_order?.code }}
                                </div>
                            </Link>

                            <div class="p-2.5 rounded-xl bg-surface-muted border border-border">
                                <div
                                    class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-0.5"
                                >
                                    {{ $t('purchasing.orders.qty_received') }}
                                </div>
                                <div class="text-xs font-bold text-text-primary font-mono">
                                    {{ toEnglish(totalQtyReceived) }}
                                </div>
                            </div>

                            <div
                                v-if="grn?.delivery_note"
                                class="p-2.5 rounded-xl bg-surface-muted border border-border"
                            >
                                <div
                                    class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-0.5"
                                >
                                    {{ $t('purchasing.grn.delivery_note') }}
                                </div>
                                <div class="text-xs font-bold text-text-primary font-mono truncate">
                                    {{ grn.delivery_note }}
                                </div>
                            </div>
                        </div>
                    </template>
                </WorkOrderFinancialSummary>
            </div>

            <!-- Create Invoice Prompt (Only when posted without an invoice yet) -->
            <div
                v-if="!grn?.purchase_invoice && grn?.status === 'posted'"
                class="p-5 rounded-2xl border transition-all bg-warning-soft border-warning/25"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-warning text-text-inverse"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-widest text-warning mb-0.5"
                            >
                                {{ $t('invoices.title') }}
                            </p>
                            <h4 class="text-lg font-black text-text-primary">
                                {{ $t('purchasing.grn.no_invoice_yet') }}
                            </h4>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <AppButton
                            variant="warning"
                            size="sm"
                            @click="createInvoice"
                        >
                            {{ $t('purchasing.grn.create_invoice_now') }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <!-- Notes Section (If Notes are present) -->
            <div
                v-if="grn?.notes"
                class="bg-surface rounded-2xl shadow-sm border border-border p-5"
            >
                <div class="flex items-center gap-3 text-text-muted mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                        />
                    </svg>
                    <h3 class="text-xs font-black uppercase tracking-[0.15em]">
                        {{ $t('common.notes') }}
                    </h3>
                </div>
                <p class="text-sm text-text-secondary leading-relaxed font-semibold italic">
                    {{ grn.notes }}
                </p>
            </div>

            <!-- Tabs Section Selector -->
            <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url>
                <template #icon-items>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </template>
                <template #icon-details>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>
            </AppTabs>

            <!-- Items Tab -->
            <div v-show="activeTab === 'items'">
                <DataTable
                    :columns="itemColumns"
                    :data="grn?.items || []"
                    :show-pagination="false"
                    :empty-title="$t('purchasing.orders.no_items')"
                >
                    <template #header>
                        <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-text-muted"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                            {{ $t('purchasing.orders.items') }}
                            <span
                                class="px-2 py-0.5 text-xs rounded-full bg-surface-raised text-text-secondary border border-border font-mono"
                            >
                                {{ toEnglish(grn?.items?.length || 0) }}
                            </span>
                        </h3>
                    </template>

                    <template #cell-part="{ item }">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-surface-muted flex items-center justify-center shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 text-text-muted"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-text-primary">
                                    {{ item.part?.name_ar || item.part?.name_en }}
                                </p>
                                <p v-if="item.part?.sku" class="text-xs text-text-muted font-mono">
                                    {{ item.part.sku }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <template #cell-qty_received="{ item }">
                        {{ toEnglish(item.qty_received) }}
                    </template>

                    <template #cell-unit_cost="{ item }">
                        {{ formatCurrency(item.unit_cost) }}
                    </template>

                    <template #cell-line_total="{ item }">
                        {{ formatCurrency(item.qty_received * item.unit_cost) }}
                    </template>
                </DataTable>
            </div>

            <!-- Details & Posting Info Tab -->
            <div
                v-show="activeTab === 'details'"
                class="bg-surface rounded-2xl shadow-sm border border-border p-6 space-y-6"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-3 rounded-2xl bg-surface-muted border border-border">
                        <div
                            class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.grn.received_date') }}
                        </div>
                        <div class="text-sm font-bold text-text-primary font-mono">
                            {{ formatDate(grn?.received_date) }}
                        </div>
                    </div>

                    <Link
                        v-if="grn?.purchase_order_id"
                        :href="route('app.purchasing.orders.show', grn.purchase_order_id)"
                        class="p-3 rounded-2xl bg-selection-bg border border-action-primary/25 hover:border-action-primary/50 transition-colors"
                    >
                        <div
                            class="text-[10px] font-black text-action-primary uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.grn.for_po') }}
                        </div>
                        <div class="text-sm font-bold text-selection-text font-mono">
                            {{ grn.purchase_order?.code }}
                        </div>
                    </Link>

                    <div
                        v-if="grn?.delivery_note"
                        class="p-3 rounded-2xl bg-surface-muted border border-border"
                    >
                        <div
                            class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.grn.delivery_note') }}
                        </div>
                        <div class="text-sm font-bold text-text-primary font-mono">
                            {{ grn.delivery_note }}
                        </div>
                    </div>

                    <div
                        v-if="grn?.status === 'posted'"
                        class="p-3 rounded-2xl bg-success-soft border border-success/20"
                    >
                        <div
                            class="text-[10px] font-black text-success uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.grn.posted_at') }}
                        </div>
                        <div class="text-sm font-bold text-success truncate">
                            {{ formatDateTime(grn.posted_at) }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="grn?.status === 'posted' && grn?.posted_by_user"
                    class="p-4 rounded-2xl bg-success-soft border border-success/20 flex items-center gap-3"
                >
                    <span class="text-xs font-bold text-success">{{ $t('common.by') }}:</span>
                    <span class="text-sm font-bold text-text-primary">
                        {{ grn.posted_by_user.name }}
                    </span>
                </div>
            </div>
        </PrintEngine>

        <!-- Print Version -->
        <Teleport to="body">
            <div class="print-section hidden p-8 bg-white text-black" id="print-area">
                <StandardReportHeader :title="$t('purchasing.grn.title')">
                    <template #extra>
                        <div class="text-xs font-mono font-bold">{{ grn.code }}</div>
                    </template>
                </StandardReportHeader>

                <!-- Logistics Grid -->
                <div
                    class="grid grid-cols-3 gap-4 mb-6 text-xs bg-gray-50 p-4 rounded-xl border border-gray-200"
                >
                    <div>
                        <span class="text-gray-500 block mb-1">
                            {{ $t('inventory.warehouses.title') }}
                        </span>
                        <strong class="text-sm font-bold text-gray-900">
                            {{ grn.warehouse?.name }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block mb-1">
                            {{ $t('purchasing.grn.for_po') }}
                        </span>
                        <strong class="text-sm font-bold font-mono text-gray-900">
                            {{ grn.purchase_order?.code }}
                            <span
                                v-if="grn.purchase_order?.supplier?.name"
                                class="font-sans text-xs text-gray-600 block"
                            >
                                ({{ grn.purchase_order?.supplier?.name }})
                            </span>
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block mb-1">
                            {{ $t('purchasing.grn.received_date') }}
                        </span>
                        <strong class="text-sm font-bold font-mono text-gray-900">
                            {{ formatDate(grn.received_date) }}
                        </strong>
                    </div>
                </div>

                <!-- Items Table -->
                <DataTable
                    structured
                    class="w-full text-xs text-center border border-gray-300 border-collapse mb-6"
                >
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-300 font-bold text-gray-800">
                            <th class="py-2.5 px-3 text-start">
                                {{ $t('inventory.parts.title') }}
                            </th>
                            <th class="py-2.5 px-3 text-center w-28">
                                {{ $t('common.quantity') }}
                            </th>
                            <th class="py-2.5 px-3 text-end w-32">
                                {{ $t('inventory.parts.unit_price') }}
                            </th>
                            <th class="py-2.5 px-3 text-end w-32">{{ $t('common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="item in grn.items" :key="item.id">
                            <td class="py-2.5 px-3 text-start">
                                <div class="font-bold text-gray-900">
                                    {{ item.part?.name_ar || item.part?.name_en }}
                                </div>
                                <div class="text-[11px] text-gray-500 font-mono">
                                    {{ item.part?.sku }}
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold">
                                {{ toEnglish(item.qty_received) }}
                            </td>
                            <td class="py-2.5 px-3 text-end font-mono">
                                {{ formatCurrency(item.unit_cost) }}
                            </td>
                            <td class="py-2.5 px-3 text-end font-mono font-bold">
                                {{ formatCurrency(item.qty_received * item.unit_cost) }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <!-- Total -->
                <div class="flex justify-end mb-6">
                    <div class="w-64 bg-gray-50 p-3 rounded-lg border border-gray-200 text-xs">
                        <div class="flex justify-between items-center font-bold">
                            <span>{{ $t('common.total') }}</span>
                            <span class="text-base font-mono text-selection-text">
                                {{ formatCurrency(totalAmount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Notes if any -->
                <div
                    v-if="grn?.notes"
                    class="mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs"
                >
                    <strong class="block mb-1 text-gray-700">{{ $t('common.notes') }}</strong>
                    <p class="text-gray-600">{{ grn.notes }}</p>
                </div>

                <!-- Unified Footer -->
                <StandardReportFooter class="mt-8 pt-4 border-t border-gray-300" />
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import { printDocument } from '@/Composables/usePrint';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import BackButton from '@/Components/BackButton.vue';
import LinkedInvoiceBadge from '@/Components/Common/LinkedInvoiceBadge.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import WorkOrderCustomerCard from '@/Components/WorkOrders/WorkOrderCustomerCard.vue';
import WorkOrderFinancialSummary from '@/Components/WorkOrders/WorkOrderFinancialSummary.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const props = defineProps({
    grn: Object,
});

const activeTab = ref('items');
const { formatCurrency, toEnglish } = useNumberFormat();
const { confirm } = useConfirm();
const { success } = useToast();

const tabs = computed(() => [
    {
        id: 'items',
        label: t('purchasing.orders.items'),
        count: props.grn?.items?.length || 0,
    },
    {
        id: 'details',
        label: t('common.details'),
    },
]);

const itemColumns = computed(() => [
    { key: 'part', label: t('inventory.parts.title'), align: 'start' },
    {
        key: 'qty_received',
        label: t('common.quantity'),
        align: 'center',
        class: 'font-mono font-medium text-text-primary',
    },
    {
        key: 'unit_cost',
        label: t('inventory.parts.unit_price'),
        align: 'end',
        class: 'font-mono text-text-secondary',
    },
    {
        key: 'line_total',
        label: t('common.total'),
        align: 'end',
        class: 'font-mono font-medium text-text-primary',
    },
]);

const supplierAddress = computed(() => {
    const supplier = props.grn?.purchase_order?.supplier;
    if (!supplier) return '';
    if (typeof supplier.address === 'string' && supplier.address.trim()) {
        return supplier.address.trim();
    }
    return [supplier.city, supplier.country].filter(Boolean).join(', ');
});

const financialTotals = computed(() => {
    const total = Number(totalAmount.value || 0);
    return {
        services: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        parts: { price: total, discount: 0, amount: total, tax: 0, total },
        grand: { price: total, discount: 0, amount: total, tax: 0, total },
    };
});

const totalAmount = computed(() => {
    return (props.grn.items || []).reduce(
        (sum, item) => sum + parseFloat(item.qty_received) * parseFloat(item.unit_cost),
        0
    );
});

const totalQtyReceived = computed(() => {
    return (props.grn.items || []).reduce(
        (sum, item) => sum + (parseFloat(item.qty_received) || 0),
        0
    );
});

const statusBadgeClass = computed(() => {
    const classes = {
        draft: 'bg-surface-muted text-text-secondary border-border',
        posted: 'bg-success-soft text-success border-success/20',
        cancelled: 'bg-danger-soft text-danger border-danger/20',
    };
    return `${classes[props.grn.status] || classes.draft} gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border`;
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const postGrn = async () => {
    const confirmed = await confirm({
        title: t('purchasing.grn.post_receipt'),
        message: t('purchasing.grn.post_confirm'),
        type: 'success',
    });
    if (!confirmed) return;

    router.post(
        route('app.purchasing.grn.post', props.grn.id),
        {},
        {
            onSuccess: () => success(t('purchasing.grn.posted_success')),
        }
    );
};

const cancelGrn = async () => {
    const confirmed = await confirm({
        title: t('purchasing.grn.cancel_receipt'),
        message: t('purchasing.grn.cancel_confirm'),
        type: 'danger',
    });
    if (!confirmed) return;

    router.post(
        route('app.purchasing.grn.cancel', props.grn.id),
        {},
        {
            onSuccess: () => success(t('purchasing.grn.cancelled')),
        }
    );
};

const createInvoice = async () => {
    const confirmed = await confirm({
        title: t('purchasing.grn.create_purchase_invoice'),
        message: t('purchasing.grn.create_invoice_confirm'),
        type: 'info',
    });
    if (!confirmed) return;

    router.post(
        route('app.purchasing.grn.create-invoice', props.grn.id),
        {},
        {
            onSuccess: () => success(t('invoices.created')),
        }
    );
};

const printGrn = () => {
    printDocument();
};
</script>

<style scoped>
@media print {
    @page {
        size: portrait;
        margin: 10mm;
    }
    body * {
        visibility: hidden;
    }
    .print-section,
    .print-section * {
        visibility: visible;
    }
    .print-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
    }
}
</style>
