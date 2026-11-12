<template>
    <AppLayout :title="`${$t('invoices.purchases.return_invoice')} #${returnInvoice.code}`">
        <PrintEngine document-type="report" class="space-y-6 pb-20">
            <!-- Premium Glassmorphic Header -->
            <PageHeader
                :title="`${$t('invoices.purchases.return_invoice')} #${returnInvoice.code}`"
                :subtitle="returnInvoice.purchase_invoice?.supplier?.name"
                :totalCount="formatCurrency(returnInvoice.total)"
                :countLabel="$t('invoices.grand_total')"
                gradientFrom="from-rose-600"
                gradientTo="to-pink-600"
                glowFrom="from-rose-500"
                badgeBg="bg-rose-50/50 dark:bg-rose-900/30"
                badgeText="text-rose-600 dark:text-rose-400"
                badgeBorder="border-rose-100/50 dark:border-rose-800/30"
                badgeDot="bg-rose-500"
            >
                <template #back>
                    <BackButton :href="route('app.invoices.purchases.index', { tab: 'returns' })" />
                </template>

                <template #icon>
                    <svg
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Print Button -->
                        <Tooltip :content="$t('common.print')">
                            <a
                                :href="
                                    route('app.invoices.purchases.returns.print', returnInvoice.id)
                                "
                                target="_blank"
                                class="flex items-center gap-2 px-4 py-2 bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 hover:text-rose-600 dark:hover:text-rose-400 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-bold text-xs shadow-sm"
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
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 022 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                    />
                                </svg>
                                <span>{{ $t('common.print') }}</span>
                            </a>
                        </Tooltip>

                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- Status Badge -->
                        <span
                            class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800"
                        >
                            {{ $t('invoices.return_invoice') }}
                        </span>
                    </div>
                </template>
            </PageHeader>

            <!-- Unified Linked Documents Bar (Original Purchase Invoice) -->
            <div
                v-if="returnInvoice.purchase_invoice_id"
                class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-gray-800 px-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xs"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400"
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
                        <span>{{ $t('invoices.return_invoice') }}</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <LinkedInvoiceBadge
                        :href="
                            route('app.invoices.purchases.show', returnInvoice.purchase_invoice_id)
                        "
                        kind="standard"
                        :number="
                            returnInvoice.purchase_invoice?.code ||
                            String(returnInvoice.purchase_invoice_id)
                        "
                        :tooltip="`${$t('invoices.original_invoice')}: #${returnInvoice.purchase_invoice?.code || returnInvoice.purchase_invoice_id}`"
                    />
                </div>
            </div>

            <!-- Top Cards Section: Center/Supplier Info & Financial Cost Card -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Unified Center & Supplier Card -->
                <WorkOrderCustomerCard
                    :customer="returnInvoice.purchase_invoice?.supplier || null"
                    :customer-name="returnInvoice.purchase_invoice?.supplier?.name || ''"
                    :vat-number="returnInvoice.purchase_invoice?.supplier?.tax_number || ''"
                    :address="supplierAddress"
                    party-type="supplier"
                    :party-badge="$t('purchasing.orders.supplier')"
                    :party-href="
                        returnInvoice.purchase_invoice?.supplier_id
                            ? route(
                                  'app.purchasing.suppliers.show',
                                  returnInvoice.purchase_invoice.supplier_id
                              )
                            : ''
                    "
                >
                    <template #header>
                        <div
                            class="flex items-center justify-between bg-white dark:bg-gray-800/90 border-b border-gray-100 dark:border-gray-700/80 px-4 py-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-full border border-gray-200 dark:border-gray-700 bg-rose-50 dark:bg-rose-900/20 text-rose-600 flex items-center justify-center shrink-0 shadow-inner"
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
                                        class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                    >
                                        {{ $t('centers.title_singular') }}
                                    </p>
                                    <h4
                                        class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                        dir="auto"
                                    >
                                        {{
                                            returnInvoice.center?.name_ar ||
                                            returnInvoice.center?.name
                                        }}
                                    </h4>
                                    <p
                                        v-if="getCenterAddress(returnInvoice.center)"
                                        class="text-[11px] font-medium text-gray-500 dark:text-gray-400 truncate mt-0.5"
                                        dir="auto"
                                    >
                                        {{ getCenterAddress(returnInvoice.center) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </WorkOrderCustomerCard>

                <!-- 2. Left Card: Financial Cost and Refund Summary -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col h-full"
                >
                    <h3
                        class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"
                    >
                        <div class="w-2 h-5 bg-rose-500 rounded-full"></div>
                        {{ $t('invoices.refund_and_totals') }}
                    </h3>

                    <div class="overflow-x-auto flex-1">
                        <DataTable structured class="w-full text-sm">
                            <thead>
                                <tr
                                    class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50 pb-2"
                                >
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider">
                                        {{ $t('common.description') }}
                                    </th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider">
                                        {{ $t('invoices.subtotal') }}
                                    </th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider">
                                        {{ $t('common.vat') }}
                                    </th>
                                    <th
                                        class="pb-2 text-center font-bold uppercase tracking-wider text-gray-900 dark:text-white"
                                    >
                                        {{ $t('invoices.grand_total') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                <!-- Returned elements row -->
                                <tr
                                    class="group hover:bg-gray-50/50 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td
                                        class="py-3 text-center font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ $t('invoices.returned_items') }}
                                    </td>
                                    <td class="py-3 text-center font-mono text-gray-500">
                                        {{ formatCurrency(returnInvoice.subtotal) }}
                                    </td>
                                    <td class="py-3 text-center font-mono text-gray-500">
                                        {{ formatCurrency(returnInvoice.tax_amount) }}
                                    </td>
                                    <td
                                        class="py-3 text-center font-black text-gray-900 dark:text-white font-mono text-base"
                                    >
                                        {{ formatCurrency(returnInvoice.total) }}
                                    </td>
                                </tr>

                                <!-- Refunded payments row -->
                                <tr
                                    v-if="refundPaymentsTotal > 0.01"
                                    class="group hover:bg-emerald-50/20 dark:hover:bg-emerald-950/10 transition-colors text-emerald-600 dark:text-emerald-400"
                                >
                                    <td class="py-3 text-center font-bold">
                                        {{ $t('invoices.refund_grand_total') }}
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        {{ formatCurrency(refundedSubtotal) }}
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        {{ formatCurrency(refundedTax) }}
                                    </td>
                                    <td class="py-3 text-center font-black font-mono text-base">
                                        {{ formatCurrency(refundPaymentsTotal) }}
                                    </td>
                                </tr>

                                <!-- Debit note row -->
                                <tr
                                    v-if="debitNoteAmount > 0.01"
                                    class="group hover:bg-amber-50/20 dark:hover:bg-amber-950/10 transition-colors text-amber-600 dark:text-amber-400"
                                >
                                    <td class="py-3 text-center font-bold">
                                        {{ $t('invoices.debit_note') }}
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        {{ formatCurrency(debitNoteSubtotal) }}
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        {{ formatCurrency(debitNoteTax) }}
                                    </td>
                                    <td class="py-3 text-center font-black font-mono text-base">
                                        {{ formatCurrency(debitNoteAmount) }}
                                    </td>
                                </tr>

                                <!-- Remaining balance row -->
                                <tr
                                    v-if="remainingTotal > 0.01"
                                    class="group hover:bg-rose-50/20 dark:hover:bg-rose-950/10 transition-colors text-rose-600 dark:text-rose-450"
                                >
                                    <td class="py-3 text-center font-bold">
                                        {{ $t('payments.remaining_refund') }}
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        {{ formatCurrency(remainingSubtotal) }}
                                    </td>
                                    <td class="py-3 text-center font-mono">
                                        {{ formatCurrency(remainingTax) }}
                                    </td>
                                    <td class="py-3 text-center font-black font-mono text-base">
                                        {{ formatCurrency(remainingTotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>

            <!-- Notes Section (If Notes are present) -->
            <div
                v-if="returnInvoice.notes"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5"
            >
                <div class="flex items-center gap-3 text-slate-400 mb-4">
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
                <p
                    class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-semibold italic"
                >
                    {{ returnInvoice.notes }}
                </p>
            </div>

            <!-- Tabs Section (العناصر المرجعة - المدفوعات - المرفقات) -->
            <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url>
                <template #icon-returned_items>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                </template>
                <template #icon-payments>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </template>
                <template #icon-attachments>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </template>
            </AppTabs>

            <!-- Tab 1: Returned Items Content -->
            <div
                v-show="activeTab === 'returned_items'"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden"
            >
                <div
                    class="p-5 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between"
                >
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <div class="w-1.5 h-4 bg-rose-500 rounded-full"></div>
                        {{ $t('invoices.returned_items') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full">
                        <thead>
                            <tr
                                class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/50"
                            >
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                                >
                                    {{ $t('inventory.parts.title_singular') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                                >
                                    {{ $t('work_orders.price') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-red-500 italic"
                                >
                                    {{ $t('work_orders.discount') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                                >
                                    {{ $t('invoices.net_unit_price') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                                >
                                    {{ $t('invoices.returned_qty') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                                >
                                    {{ $t('invoices.subtotal') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest italic"
                                >
                                    {{ $t('common.vat') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-gray-900 dark:text-white"
                                >
                                    {{ $t('common.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr
                                v-for="line in returnInvoice.lines"
                                :key="line.id"
                                class="group hover:bg-rose-50/30 dark:hover:bg-rose-900/10 transition-colors"
                            >
                                <!-- Part Name -->
                                <td class="px-6 py-5 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <Link
                                            v-if="line.part_id"
                                            :href="route('app.inventory.parts.show', line.part_id)"
                                            class="text-sm font-black text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/link"
                                        >
                                            <span>{{ line.part?.name || '—' }}</span>
                                            <svg
                                                class="w-3.5 h-3.5 opacity-60 group-hover/link:opacity-100 transition-opacity shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                                />
                                            </svg>
                                        </Link>
                                        <span
                                            v-else
                                            class="text-sm font-bold text-gray-950 dark:text-white"
                                        >
                                            —
                                        </span>
                                        <span
                                            class="text-[10px] text-gray-400 font-mono mt-1 uppercase tracking-wider"
                                        >
                                            {{ line.part?.sku || 'NO-SKU' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Unit Cost -->
                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatCurrency(line.unit_cost) }}
                                    </span>
                                </td>

                                <!-- Discount -->
                                <td class="px-6 py-5 text-center text-red-500 italic">
                                    <span class="text-sm font-black font-mono">0.00</span>
                                </td>

                                <!-- Net Unit Price -->
                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatCurrency(line.unit_cost) }}
                                    </span>
                                </td>

                                <!-- Returned Qty -->
                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ toEnglish(line.qty) }}
                                    </span>
                                </td>

                                <!-- Subtotal -->
                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatCurrency(line.qty * line.unit_cost) }}
                                    </span>
                                </td>

                                <!-- VAT -->
                                <td
                                    class="px-6 py-5 text-center text-xs text-sky-600 dark:text-sky-400 font-mono"
                                >
                                    {{ formatCurrency(line.tax_amount) }}
                                    <span class="text-[10px] text-gray-400">
                                        ({{ toEnglish(line.tax_rate) }}%)
                                    </span>
                                </td>

                                <!-- Total -->
                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="text-base font-black text-gray-900 dark:text-white font-mono"
                                    >
                                        {{ formatCurrency(line.total) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>

            <!-- Tab 2: Payments Content -->
            <div
                v-show="activeTab === 'payments'"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6"
            >
                <div
                    class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/50"
                >
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                        {{ $t('invoices.payments_history') }}
                    </h3>

                    <!-- Add Refund Button -->
                    <button
                        v-if="remainingRefundToRecord > 0.01"
                        @click="showAddPaymentModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-500/20 active:scale-95 shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        <span>{{ $t('payments.add_payment') }}</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-sm">
                        <thead>
                            <tr
                                class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50"
                            >
                                <th class="py-3 px-4 text-center font-bold">#</th>
                                <th class="py-3 px-4 text-center font-bold">
                                    {{ $t('payments.form.type') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold">
                                    {{ $t('payments.form.date') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold">
                                    {{ $t('payments.form.notes') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold">
                                    {{ $t('payments.form.amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                            <template v-if="refundPayments && refundPayments.length > 0">
                                <tr
                                    v-for="(payment, index) in refundPayments"
                                    :key="payment.id"
                                    class="hover:bg-rose-50/30 dark:hover:bg-rose-900/10 transition-colors"
                                >
                                    <td
                                        class="py-4 px-4 text-center text-gray-500 font-bold font-mono"
                                    >
                                        {{ toEnglish(index + 1) }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-bold">
                                        <span
                                            class="text-red-650 bg-red-50 dark:bg-red-950/30 px-2.5 py-1 rounded-lg text-xs"
                                        >
                                            {{ $t('payments.types.refund') }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono"
                                        dir="ltr"
                                    >
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate"
                                        :title="translateNote(payment.notes)"
                                    >
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span
                                                class="text-xs font-bold text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    $t(
                                                        `payments.methods.${payment.payment_method}`
                                                    ) || payment.payment_method
                                                }}
                                            </span>
                                            <span class="text-[10px]">
                                                {{ translateNote(payment.notes) || '—' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center font-black text-red-650 dark:text-red-400 font-mono"
                                        dir="ltr"
                                    >
                                        -{{ formatCurrency(payment.amount) }}
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="5" class="py-8 text-center text-gray-400 font-bold">
                                    {{ $t('payments.no_refunds') }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>

            <!-- Tab 3: Attachments Content -->
            <div
                v-show="activeTab === 'attachments'"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6 space-y-6"
            >
                <AppAttachmentManager
                    :attachments="
                        returnInvoice.attachment_path
                            ? [
                                  {
                                      id: returnInvoice.id,
                                      path: returnInvoice.attachment_path,
                                      name: returnInvoice.attachment_path.split('/').pop(),
                                  },
                              ]
                            : []
                    "
                    :title="$t('invoices.return_attachment')"
                    :is-read-only="!canManageAttachment"
                    :upload-url="
                        route('app.invoices.purchases.returns.attachment.store', returnInvoice.id)
                    "
                    :delete-url-template="
                        () =>
                            route(
                                'app.invoices.purchases.returns.attachment.destroy',
                                returnInvoice.id
                            )
                    "
                    upload-field="attachment"
                    accepted-types="application/pdf,image/jpeg,image/jpg,image/png"
                    format-label="PDF, JPG, PNG"
                    :max-size-mb="1"
                    :allow-multiple="false"
                />
                <!-- Original Purchase Invoice Attachment Reference Section -->
                <div
                    v-if="returnInvoice.purchase_invoice?.attachment_path"
                    class="pt-4 border-t border-gray-100 dark:border-gray-700/65"
                >
                    <div class="mb-3">
                        <h3
                            class="text-xs font-bold text-gray-500 dark:text-gray-400 flex items-center gap-1.5"
                        >
                            <span class="text-gray-400">💡</span>
                            {{ $t('invoices.original_attachment_ref') }}
                        </h3>
                    </div>
                    <div
                        class="flex items-center justify-between p-3.5 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-700/40 text-xs"
                    >
                        <span class="text-gray-600 dark:text-gray-400 font-semibold">
                            {{ returnInvoice.purchase_invoice.attachment_path.split('/').pop() }}
                        </span>
                        <a
                            :href="`/storage/${returnInvoice.purchase_invoice.attachment_path}`"
                            target="_blank"
                            download
                            class="text-rose-600 hover:text-rose-700 font-bold hover:underline transition-colors flex items-center gap-1"
                        >
                            <span>{{ $t('common.download') }}</span>
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </PrintEngine>

        <!-- Add Refund Payment Modal -->
        <PurchaseOrderPaymentModal
            :show="showAddPaymentModal"
            :balance="remainingRefundToRecord"
            default-type="refund"
            @close="showAddPaymentModal = false"
            @saved="saveRefundPayment"
        />
    </AppLayout>
</template>

<script setup>
import AppAttachmentManager from '@/Components/Common/AppAttachmentManager.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import Tooltip from '@/Components/Tooltip.vue';
import WorkOrderCustomerCard from '@/Components/WorkOrders/WorkOrderCustomerCard.vue';
import LinkedInvoiceBadge from '@/Components/Common/LinkedInvoiceBadge.vue';
import PurchaseOrderPaymentModal from '@/Components/Purchasing/PurchaseOrderPaymentModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    canManageAttachment: Boolean,
    returnInvoice: Object,
});

const { t, locale } = useI18n();
const page = usePage();
const isRtl = computed(() => locale.value === 'ar');
const { toEnglish, formatMoney } = useNumberFormat();

const activeTab = ref('returned_items');

const tabs = computed(() => [
    {
        id: 'returned_items',
        label: t('invoices.returned_items'),
        count: props.returnInvoice?.lines?.length || 0,
    },
    {
        id: 'payments',
        label: t('invoices.payments_history'),
        count: refundPayments.value.length,
    },
    {
        id: 'attachments',
        label: t('invoices.attachments'),
    },
]);

const formatCurrency = (val) => {
    return formatMoney(val, props.returnInvoice?.currency_code);
};

const supplierAddress = computed(() => {
    const sup = props.returnInvoice?.purchase_invoice?.supplier;
    if (!sup) return '';
    if (typeof sup.address === 'string' && sup.address.trim()) {
        return sup.address.trim();
    }
    const isRtl = locale.value === 'ar';
    return [
        sup.address_line,
        sup.building_number,
        sup.street,
        sup.district,
        sup.city,
        sup.region,
        sup.postal_code,
    ]
        .filter(Boolean)
        .join(isRtl ? '، ' : ', ');
});

const getCenterAddress = (center) => {
    if (!center) return '';
    const isRtl = locale.value === 'ar';
    const addr = center.address;
    if (!addr) return '';
    if (addr.address_line) return addr.address_line;
    const parts = [
        addr.building_number,
        addr.street,
        addr.district,
        addr.city,
        addr.region,
        addr.postal_code,
    ].filter(Boolean);
    return parts.join(isRtl ? '، ' : ', ');
};

const printInvoice = () => {
    printDocument();
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch (e) {
        return dateStr;
    }
};

const refundPayments = computed(() => {
    // Real cash refunds returned to the supplier (TYPE_REFUND only).
    const allRefunds =
        props.returnInvoice.purchase_invoice?.payments?.filter((p) => p.type === 'refund') || [];
    const codeMatch = allRefunds.filter((p) => p.notes?.includes(props.returnInvoice.code));
    return codeMatch.length > 0 ? codeMatch : allRefunds;
});

const translateNote = (note) => {
    if (!note) return '—';
    if (note.includes('Refund for return invoice:')) {
        const code = note.replace('Refund for return invoice:', '').trim();
        return t('payments.refund_for_return_notes', { code });
    }
    return note;
};

const refundPaymentsTotal = computed(() => {
    return refundPayments.value.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
});

const refundedSubtotal = computed(() => {
    if (!props.returnInvoice.total) return 0;
    return refundPaymentsTotal.value * (props.returnInvoice.subtotal / props.returnInvoice.total);
});

const refundedTax = computed(() => {
    if (!props.returnInvoice.total) return 0;
    return refundPaymentsTotal.value * (props.returnInvoice.tax_amount / props.returnInvoice.total);
});

// Debit note: this is a BOOKKEEPING note, not a payment.
// It lives on the PurchaseReturnInvoice itself (create_debit_note flag +
// debit_note_date), and the invoice balance has already been reduced by
// the full return total when the return was recorded.
// We display it as "the part of the return that was settled via debit
// note (no cash movement)" so the supplier-accountant can see the split.
const debitNoteAmount = computed(() => {
    if (!props.returnInvoice.create_debit_note) return 0;
    return Math.max(0, (props.returnInvoice.total || 0) - refundPaymentsTotal.value);
});

const debitNoteSubtotal = computed(() => {
    if (!props.returnInvoice.total) return 0;
    return debitNoteAmount.value * (props.returnInvoice.subtotal / props.returnInvoice.total);
});

const debitNoteTax = computed(() => {
    if (!props.returnInvoice.total) return 0;
    return debitNoteAmount.value * (props.returnInvoice.tax_amount / props.returnInvoice.total);
});

const remainingTotal = computed(() => {
    // Once the debit note is acknowledged, the full return total is
    // considered "settled" (refunded in cash + covered by debit note),
    // so nothing remains outstanding.
    return 0;
});

const remainingSubtotal = computed(() => {
    if (!props.returnInvoice.total) return 0;
    return remainingTotal.value * (props.returnInvoice.subtotal / props.returnInvoice.total);
});

const remainingTax = computed(() => {
    if (!props.returnInvoice.total) return 0;
    return remainingTotal.value * (props.returnInvoice.tax_amount / props.returnInvoice.total);
});

const remainingRefundToRecord = computed(() => {
    // Cash refunds only — the debit-note portion is NOT a payment,
    // so it must not block the user from recording remaining cash refunds.
    return Math.max(0, props.returnInvoice.total - refundPaymentsTotal.value);
});

const showAddPaymentModal = ref(false);
const paymentForm = useForm({
    amount: '',
    payment_method: 'cash',
    payment_date: '',
    reference: '',
    notes: '',
});

const hasPayments = computed(() => {
    return (
        props.returnInvoice.purchase_invoice?.payments?.some((p) => p.type === 'payment') || false
    );
});

const saveRefundPayment = (paymentData) => {
    paymentForm.amount = paymentData.amount;
    paymentForm.payment_method = paymentData.payment_method;
    paymentForm.payment_date = paymentData.payment_date;
    paymentForm.reference = paymentData.reference;
    paymentForm.notes = paymentData.notes;

    paymentForm.post(
        route('app.invoices.purchases.returns.refunds.store', props.returnInvoice.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                showAddPaymentModal.value = false;
                paymentForm.reset();
            },
        }
    );
};
</script>
