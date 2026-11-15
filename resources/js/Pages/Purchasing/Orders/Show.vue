<template>
    <Head :title="`${$t('purchasing.orders.title')} #${order.code}`" />
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="`${$t('purchasing.orders.title')} #${order.code}`"
                :subtitle="order.supplier?.name || ''"
                :total-count="formatCurrency(order.total)"
                :count-label="$t('common.total')"
            >
                <template #back>
                    <BackButton :href="route('app.purchasing.orders.index')" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </template>

                <template #identity>
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge
                            :label="$t(`purchasing.orders.statuses.${order.status}`)"
                            :class="statusBadgeClass"
                        />
                        <span v-if="order.warehouse?.name" class="text-xs text-text-muted">
                            {{ order.warehouse.name }}
                        </span>
                    </div>
                </template>

                <template #actions>
                    <AppPrintButton variant="icon" @click="printOrder" />

                    <AppButton
                        v-if="canCancel"
                        variant="danger"
                        size="sm"
                        @click="cancelOrder"
                    >
                        <template #iconLeft>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </template>
                        {{ $t('common.cancel') }}
                    </AppButton>

                    <AppButton
                        v-if="order.status === 'draft' && order.items?.length > 0"
                        variant="primary"
                        size="md"
                        @click="sendOrder"
                    >
                        <template #iconLeft>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                />
                            </svg>
                        </template>
                        {{ $t('purchasing.orders.actions.send') }}
                    </AppButton>

                    <AppButton
                        v-if="canReceive"
                        variant="success"
                        size="md"
                        @click="showGrnModal = true"
                    >
                        <template #iconLeft>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </template>
                        {{ $t('purchasing.orders.actions.receive') }}
                    </AppButton>
                </template>
            </PageHeader>

            <!-- Unified Linked Documents Bar (GRNs & Linked Purchase Invoices) -->
            <div
                v-if="order.goods_received_notes && order.goods_received_notes.length > 0"
                class="flex flex-wrap items-center justify-between gap-3 bg-surface px-4 py-3 rounded-2xl border border-border shadow-xs"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-info-soft text-info border border-info/20"
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
                    <template v-for="grn in order.goods_received_notes" :key="`grn-${grn.id}`">
                        <LinkedInvoiceBadge
                            :href="route('app.purchasing.grn.show', grn.id)"
                            :kind="grn.status === 'posted' ? 'paid' : 'deferred'"
                            :number="grn.code || String(grn.id)"
                            :tooltip="`${$t('purchasing.grn.title')} #${grn.code} (${formatDate(grn.received_at || grn.received_date)})`"
                        />
                        <LinkedInvoiceBadge
                            v-if="grn.purchase_invoice"
                            :href="route('app.invoices.purchases.show', grn.purchase_invoice.id)"
                            kind="standard"
                            :number="grn.purchase_invoice.code || String(grn.purchase_invoice.id)"
                            :tooltip="`${$t('invoices.purchases.invoice')} #${grn.purchase_invoice.code}`"
                        />
                    </template>
                </div>
            </div>

            <!-- Top Cards Section: Supplier Info & Financial Cost Card -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- 1. Supplier Card -->
                <WorkOrderCustomerCard
                    :customer="order.supplier"
                    :customer-name="order.supplier?.name || ''"
                    :vat-number="order.supplier?.tax_number || ''"
                    :address="supplierAddress"
                    party-type="supplier"
                    :party-href="
                        order.supplier?.id
                            ? route('app.purchasing.suppliers.show', order.supplier.id)
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
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
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
                                        {{ order.warehouse?.name || '-' }}
                                    </h4>
                                </div>
                            </div>
                            <div class="text-end shrink-0">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wider text-text-muted"
                                >
                                    {{ $t('purchasing.orders.date') }}
                                </p>
                                <p class="text-xs font-bold font-mono text-text-primary">
                                    {{ formatDate(order.order_date) }}
                                </p>
                            </div>
                        </div>
                    </template>
                </WorkOrderCustomerCard>

                <!-- 2. Financial Cost Card -->
                <WorkOrderFinancialSummary
                    :totals="financialTotals"
                    :has-tax="Number(order.tax_amount || 0) > 0"
                    :tax-rate="15"
                    :show-payments="false"
                    :hide-zero-rows="true"
                    :parts-label="$t('purchasing.orders.items')"
                    :grand-label="$t('common.total')"
                >
                    <template #footer>
                        <div
                            v-if="order.expected_date || order.create_credit_invoice || order.sent_at"
                            class="mt-4 pt-3 border-t border-border grid grid-cols-1 sm:grid-cols-3 gap-3"
                        >
                            <div
                                v-if="order.expected_date"
                                class="p-2.5 rounded-xl bg-surface-muted border border-border"
                            >
                                <div
                                    class="text-[10px] font-black text-warning uppercase tracking-widest mb-0.5"
                                >
                                    {{ $t('purchasing.orders.expected_date') }}
                                </div>
                                <div class="text-xs font-bold text-text-primary font-mono">
                                    {{ formatDate(order.expected_date) }}
                                </div>
                            </div>

                            <div
                                v-if="order.create_credit_invoice"
                                class="p-2.5 rounded-xl bg-selection-bg border border-action-primary/25"
                            >
                                <div
                                    class="text-[10px] font-black text-action-primary uppercase tracking-widest mb-0.5"
                                >
                                    {{ $t('purchasing.orders.create_credit_invoice') }}
                                </div>
                                <div class="text-xs font-bold text-selection-text font-mono">
                                    {{
                                        order.due_date
                                            ? formatDate(order.due_date)
                                            : $t('common.active')
                                    }}
                                </div>
                            </div>

                            <div
                                v-if="order.sent_at"
                                class="p-2.5 rounded-xl bg-info-soft border border-info/20"
                            >
                                <div
                                    class="text-[10px] font-black text-info uppercase tracking-widest mb-0.5"
                                >
                                    {{ $t('purchasing.orders.sent_at') }}
                                </div>
                                <div
                                    class="text-xs font-bold text-info truncate"
                                    :title="order.sent_by_user?.name"
                                >
                                    {{ formatDateTime(order.sent_at) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </WorkOrderFinancialSummary>
            </div>

            <!-- Notes Section (If Notes are present) -->
            <div
                v-if="order.notes"
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
                    {{ order.notes }}
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
                <template #icon-grns>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
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
                    :data="order.items || []"
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
                                {{ toEnglish(order.items?.length || 0) }}
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

                    <template #cell-qty_ordered="{ item }">
                        {{ toEnglish(item.qty_ordered) }}
                    </template>

                    <template #cell-qty_received="{ item }">
                        <span :class="getReceivedClass(item)">
                            {{ toEnglish(item.qty_received || 0) }}
                        </span>
                    </template>

                    <template #cell-unit_cost="{ item }">
                        {{ formatCurrency(item.unit_cost) }}
                    </template>

                    <template #cell-line_total="{ item }">
                        {{ formatCurrency(item.line_total) }}
                    </template>
                </DataTable>
            </div>

            <!-- Goods Received Notes Tab (if any) -->
            <div v-if="order.goods_received_notes?.length > 0" v-show="activeTab === 'grns'">
                <DataTable
                    :columns="grnColumns"
                    :data="order.goods_received_notes || []"
                    :show-pagination="false"
                >
                    <template #header>
                        <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-success"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            {{ $t('purchasing.grn.title') }}
                            <span
                                class="px-2 py-0.5 text-xs rounded-full bg-success-soft text-success font-mono"
                            >
                                {{ toEnglish(order.goods_received_notes?.length || 0) }}
                            </span>
                        </h3>
                    </template>

                    <template #cell-received_at="{ item }">
                        {{ formatDate(item.received_at || item.received_date) }}
                    </template>

                    <template #cell-status="{ item }">
                        <StatusBadge
                            :label="$t(`purchasing.grn.statuses.${item.status}`)"
                            :class="
                                item.status === 'posted'
                                    ? 'bg-success-soft text-success border-success/20'
                                    : item.status === 'cancelled'
                                      ? 'bg-danger-soft text-danger border-danger/20'
                                      : 'bg-surface-muted text-text-secondary border-border'
                            "
                        />
                    </template>

                    <template #cell-actions="{ item }">
                        <Link
                            :href="route('app.purchasing.grn.show', item.id)"
                            class="text-action-primary hover:text-action-primary-hover text-sm font-medium"
                        >
                            {{ $t('common.view') }}
                        </Link>
                    </template>
                </DataTable>
            </div>

            <!-- Details & Terms Tab -->
            <div
                v-show="activeTab === 'details'"
                class="bg-surface rounded-2xl shadow-sm border border-border p-6 space-y-6"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-3 rounded-2xl bg-surface-muted border border-border">
                        <div
                            class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.orders.date') }}
                        </div>
                        <div class="text-sm font-bold text-text-primary font-mono">
                            {{ formatDate(order.order_date) }}
                        </div>
                    </div>

                    <div
                        v-if="order.expected_date"
                        class="p-3 rounded-2xl bg-surface-muted border border-border"
                    >
                        <div
                            class="text-[10px] font-black text-warning uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.orders.expected_date') }}
                        </div>
                        <div class="text-sm font-bold text-text-primary font-mono">
                            {{ formatDate(order.expected_date) }}
                        </div>
                    </div>

                    <div
                        v-if="order.create_credit_invoice"
                        class="p-3 rounded-2xl bg-selection-bg border border-action-primary/25"
                    >
                        <div
                            class="text-[10px] font-black text-action-primary uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.orders.create_credit_invoice') }}
                        </div>
                        <div class="text-sm font-bold text-selection-text font-mono">
                            {{ order.due_date ? formatDate(order.due_date) : $t('common.active') }}
                        </div>
                    </div>

                    <div
                        v-if="order.sent_at"
                        class="p-3 rounded-2xl bg-info-soft border border-info/20"
                    >
                        <div
                            class="text-[10px] font-black text-info uppercase tracking-widest mb-1"
                        >
                            {{ $t('purchasing.orders.sent_at') }}
                        </div>
                        <div class="text-sm font-bold text-info truncate">
                            {{ formatDateTime(order.sent_at) }}
                        </div>
                    </div>
                </div>

                <div v-if="order.terms" class="pt-4 border-t border-border">
                    <h3 class="text-sm font-bold text-text-primary mb-2">
                        {{ $t('purchasing.orders.terms') }}
                    </h3>
                    <p class="text-sm text-text-secondary leading-relaxed whitespace-pre-wrap">
                        {{ order.terms }}
                    </p>
                </div>
            </div>
        </PrintEngine>

        <ConfirmModal />
        <GrnModal :show="showGrnModal" :purchase-order="order" @close="showGrnModal = false" />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader :title="$t('purchasing.orders.title')">
                    <template #extra>
                        <div class="text-xs font-mono font-bold">{{ order.code }}</div>
                    </template>
                </StandardReportHeader>

                <!-- Document Info Header -->
                <div class="grid grid-cols-2 gap-8 mb-8 border-b pb-8">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase mb-2">
                            {{ $t('purchasing.orders.supplier') }}
                        </h3>
                        <div class="text-lg font-black text-gray-900">
                            {{ order.supplier?.name }}
                        </div>
                        <div v-if="order.supplier?.code" class="text-sm font-mono text-gray-500">
                            {{ order.supplier.code }}
                        </div>
                        <div
                            v-if="order.supplier?.phone"
                            class="text-sm text-gray-600 mt-1"
                            dir="ltr"
                        >
                            {{ order.supplier.phone }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="inline-block bg-gray-50 p-4 rounded-xl border">
                            <div class="text-xs text-gray-500 uppercase mb-1">
                                {{ $t('purchasing.orders.code') }}
                            </div>
                            <div class="text-xl font-black font-mono text-gray-900">
                                {{ order.code }}
                            </div>
                            <div class="text-sm font-bold text-blue-600 mt-1 uppercase">
                                {{ $t(`purchasing.orders.statuses.${order.status}`) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logistics Grid -->
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="p-4 bg-gray-50/50 rounded-xl border border-dashed">
                        <div class="text-xs text-gray-500 uppercase mb-1">
                            {{ $t('purchasing.orders.warehouse') }}
                        </div>
                        <div class="text-sm font-bold text-gray-900">
                            {{ order.warehouse?.name }}
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50/50 rounded-xl border border-dashed">
                        <div class="text-xs text-gray-500 uppercase mb-1">
                            {{ $t('purchasing.orders.date') }}
                        </div>
                        <div class="text-sm font-bold text-gray-900 font-mono">
                            {{ formatDate(order.order_date) }}
                        </div>
                    </div>
                    <div
                        class="p-4 bg-gray-50/50 rounded-xl border border-dashed"
                        v-if="order.expected_date"
                    >
                        <div class="text-xs text-gray-500 uppercase mb-1">
                            {{ $t('purchasing.orders.expected_date') }}
                        </div>
                        <div class="text-sm font-bold text-gray-900 font-mono">
                            {{ formatDate(order.expected_date) }}
                        </div>
                    </div>
                    <div
                        class="p-4 bg-selection-bg rounded-xl border border-action-primary/25 border-dashed"
                        v-if="order.create_credit_invoice"
                    >
                        <div class="text-xs text-action-primary uppercase mb-1">
                            {{ $t('purchasing.orders.create_credit_invoice') }}
                        </div>
                        <div class="text-sm font-bold text-selection-text font-mono">
                            {{ order.due_date ? formatDate(order.due_date) : $t('common.active') }}
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <DataTable structured class="w-full mb-8">
                    <thead>
                        <tr class="border-b-2">
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase align-middle text-center"
                            >
                                {{ $t('inventory.parts.title') }}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-bold uppercase align-middle"
                            >
                                {{ $t('common.quantity') }}
                            </th>
                            <th
                                class="px-4 py-3 text-end text-xs font-bold uppercase align-middle text-center"
                            >
                                {{ $t('inventory.parts.unit_price') }}
                            </th>
                            <th
                                class="px-4 py-3 text-end text-xs font-bold uppercase align-middle text-center"
                            >
                                {{ $t('common.total') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-4 py-4 align-middle">
                                <div class="font-bold text-gray-900">
                                    {{ item.part?.name_ar || item.part?.name_en }}
                                </div>
                                <div class="text-xs text-gray-500 font-mono">
                                    {{ item.part?.sku }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center font-mono font-bold align-middle">
                                {{ toEnglish(item.qty_ordered) }}
                            </td>
                            <td class="px-4 py-4 text-end font-mono align-middle">
                                {{ formatCurrency(item.unit_cost) }}
                            </td>
                            <td class="px-4 py-4 text-end font-mono font-bold align-middle">
                                {{ formatCurrency(item.line_total) }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <!-- Financial Summary -->
                <div class="flex justify-end mb-12">
                    <div class="w-64 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ $t('invoices.subtotal') }}</span>
                            <span class="font-mono">{{ formatCurrency(order.subtotal) }}</span>
                        </div>
                        <div
                            class="flex justify-between text-sm"
                            v-if="parseFloat(order.tax_amount) > 0"
                        >
                            <span class="text-gray-500">{{ $t('invoices.tax') }} (15%)</span>
                            <span class="font-mono">{{ formatCurrency(order.tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-black border-t-2 pt-3">
                            <span>{{ $t('common.total') }}</span>
                            <span class="text-blue-600 font-mono">
                                {{ formatCurrency(order.total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <StandardReportFooter class="mt-12 border-t pt-6" />
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
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import BackButton from '@/Components/BackButton.vue';
import LinkedInvoiceBadge from '@/Components/Common/LinkedInvoiceBadge.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import WorkOrderCustomerCard from '@/Components/WorkOrders/WorkOrderCustomerCard.vue';
import WorkOrderFinancialSummary from '@/Components/WorkOrders/WorkOrderFinancialSummary.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import GrnModal from '@/Components/Purchasing/GrnModal.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const props = defineProps({
    order: Object,
});

const showGrnModal = ref(false);
const activeTab = ref('items');

const { t } = useI18n();
const { formatCurrency, toEnglish } = useNumberFormat();
const { confirm } = useConfirm();

const tabs = computed(() => {
    const list = [
        {
            id: 'items',
            label: t('purchasing.orders.items'),
            count: props.order?.items?.length || 0,
        },
    ];
    if (props.order?.goods_received_notes?.length > 0) {
        list.push({
            id: 'grns',
            label: t('purchasing.grn.title'),
            count: props.order.goods_received_notes.length,
        });
    }
    list.push({
        id: 'details',
        label: t('common.details'),
    });
    return list;
});

const itemColumns = computed(() => [
    { key: 'part', label: t('purchasing.orders.part_name'), align: 'start' },
    {
        key: 'qty_ordered',
        label: t('purchasing.orders.qty_ordered'),
        align: 'center',
        class: 'font-mono text-text-primary',
    },
    { key: 'qty_received', label: t('purchasing.orders.qty_received'), align: 'center' },
    {
        key: 'unit_cost',
        label: t('purchasing.orders.unit_cost'),
        align: 'end',
        class: 'font-mono text-text-secondary',
    },
    {
        key: 'line_total',
        label: t('purchasing.orders.line_total'),
        align: 'end',
        class: 'font-mono font-medium text-text-primary',
    },
]);

const grnColumns = computed(() => [
    {
        key: 'code',
        label: t('purchasing.grn.title'),
        align: 'start',
        class: 'font-mono font-medium text-text-primary',
    },
    {
        key: 'received_at',
        label: t('purchasing.grn.received_date'),
        align: 'center',
        class: 'font-mono text-text-secondary',
    },
    { key: 'status', label: t('common.status'), align: 'center' },
    { key: 'actions', label: t('common.actions'), align: 'end' },
]);

const supplierAddress = computed(() => {
    const supplier = props.order?.supplier;
    if (!supplier) return '';
    if (typeof supplier.address === 'string' && supplier.address.trim()) {
        return supplier.address.trim();
    }
    return [supplier.city, supplier.country].filter(Boolean).join(', ');
});

const financialTotals = computed(() => {
    const subtotal = Number(props.order?.subtotal || 0);
    const tax = Number(props.order?.tax_amount || 0);
    const total = Number(props.order?.total || 0);
    return {
        services: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        parts: { price: subtotal, discount: 0, amount: subtotal, tax, total },
        grand: { price: subtotal, discount: 0, amount: subtotal, tax, total },
    };
});

// Date formatters
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

// Computed properties
const canReceive = computed(() => {
    return ['sent', 'partial'].includes(props.order.status);
});

const canCancel = computed(() => {
    return !['received', 'cancelled'].includes(props.order.status);
});

const statusBadgeClass = computed(() => {
    const classes = {
        draft: 'bg-surface-muted text-text-secondary border-border',
        sent: 'bg-info-soft text-info border-info/20',
        partial: 'bg-warning-soft text-warning border-warning/20',
        received: 'bg-success-soft text-success border-success/20',
        cancelled: 'bg-danger-soft text-danger border-danger/20',
    };
    return `${classes[props.order.status] || classes.draft} gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border`;
});

// Helper for received qty styling
const getReceivedClass = (item) => {
    const ordered = parseFloat(item.qty_ordered || 0);
    const received = parseFloat(item.qty_received || 0);

    if (received >= ordered) {
        return 'font-mono text-success font-medium';
    } else if (received > 0) {
        return 'font-mono text-warning font-medium';
    }
    return 'font-mono text-text-muted';
};

// Actions
async function sendOrder() {
    const confirmed = await confirm({
        title: t('purchasing.orders.confirm_send_title'),
        message: t('purchasing.orders.confirm_send'),
        confirmText: t('purchasing.orders.actions.send'),
        cancelText: t('common.cancel'),
        type: 'info',
    });

    if (confirmed) {
        router.post(
            route('app.purchasing.orders.send', props.order.id),
            {},
            {
                preserveScroll: true,
            }
        );
    }
}

async function cancelOrder() {
    const confirmed = await confirm({
        title: t('purchasing.orders.confirm_cancel_title'),
        message: t('purchasing.orders.confirm_cancel'),
        confirmText: t('common.cancel'),
        cancelText: t('common.back'),
        type: 'danger',
    });

    if (confirmed) {
        router.post(
            route('app.purchasing.orders.cancel', props.order.id),
            {},
            {
                preserveScroll: true,
            }
        );
    }
}

function printOrder() {
    printDocument();
}
</script>
