<template>
    <Head :title="invoice.invoice_number" />
    <AppLayout
        :title="
            invoice.type === 'credit_note'
                ? `${$t('invoices.credit_note.title')} #${invoice.invoice_number}`
                : `${$t('invoices.invoice')} #${invoice.invoice_number}`
        "
    >
        <div class="space-y-6 pb-20">
            <!-- Premium Glassmorphic Header -->
            <PageHeader
                :title="
                    invoice.type === 'credit_note'
                        ? `${$t('invoices.credit_note.title')} #${invoice.invoice_number}`
                        : `${$t('invoices.invoice')} #${invoice.invoice_number}`
                "
                :subtitle="
                    invoice.company_transaction
                        ? invoice.company_transaction.title
                        : invoice.customer_name_snapshot || invoice.customer?.name
                "
                :totalCount="formatCurrency(invoice.total_incl_tax)"
                :countLabel="
                    invoice.tax_enabled_snapshot ? $t('invoices.grand_total') : $t('invoices.total')
                "
                :gradientFrom="invoice.type === 'credit_note' ? 'from-rose-600' : 'from-blue-600'"
                :gradientTo="invoice.type === 'credit_note' ? 'to-amber-600' : 'to-action-primary'"
                :glowFrom="invoice.type === 'credit_note' ? 'from-rose-500' : 'from-blue-500'"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #back>
                    <BackButton :href="backUrl" />
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                        <!-- Print Button -->
                        <Tooltip :content="$t('common.print')">
                            <a
                                :href="route('app.invoices.print', invoice.id)"
                                target="_blank"
                                class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-bold text-xs shadow-sm"
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
                                <span>{{ $t('common.print') }}</span>
                            </a>
                        </Tooltip>

                        <!-- Return Invoice / Credit Note Button -->
                        <Tooltip
                            v-if="canCreateCreditNote"
                            :content="$t('invoices.credit_note.create_button')"
                        >
                            <button
                                @click="showCreditNoteModal = true"
                                class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 bg-gradient-to-r from-rose-500 to-amber-500 hover:from-rose-600 hover:to-amber-600 text-white rounded-xl transition-all font-bold text-xs shadow-sm hover:shadow"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
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
                                <span class="hidden sm:inline">
                                    {{ $t('invoices.credit_note.create_button') }}
                                </span>
                            </button>
                        </Tooltip>

                        <!-- Add Payment Button -->
                        <Tooltip
                            v-if="balance > 0.01 && canRecordPayment"
                            :content="
                                isCreditNote
                                    ? $t('payments.types.refund')
                                    : $t('payments.add_payment')
                            "
                        >
                            <button
                                @click="showPaymentModal = true"
                                class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all font-bold text-xs shadow-sm hover:shadow"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>
                                    {{
                                        isCreditNote
                                            ? $t('payments.types.refund')
                                            : $t('payments.add_payment')
                                    }}
                                </span>
                            </button>
                        </Tooltip>

                        <div
                            class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"
                        ></div>

                        <!-- Status Badge -->
                        <span
                            :class="statusClass(invoice.payment_status)"
                            class="px-3 sm:px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm"
                        >
                            {{ $t(`invoices.status.${invoice.payment_status}`) }}
                        </span>
                    </div>
                </template>
            </PageHeader>

            <!-- Top Cards Section: Customer Info & Financial Cost Card -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Unified Customer & Vehicle Card + Invoice Metadata Footer -->
                <WorkOrderCustomerCard
                    :vehicle="invoice.work_order?.vehicle || null"
                    :customer="invoice.customer"
                    :customer-name="invoice.customer_name_snapshot || invoice.customer?.name"
                    :vat-number="
                        invoice.tax_enabled_snapshot
                            ? invoice.customer_vat_snapshot || invoice.customer?.tax_number || ''
                            : ''
                    "
                    :address="
                        invoice.customer_address_snapshot ||
                        getCustomerAddress(invoice.customer) ||
                        ''
                    "
                    :mileage="invoice.work_order?.mileage ?? invoice.work_order?.odometer"
                    :colors="colors"
                >
                    <template #footer>
                        <div
                            class="px-4 pb-4 pt-3 border-t border-gray-100 dark:border-gray-700/65 space-y-3"
                        >
                            <!-- Invoice Dates & ZATCA QR Code -->
                            <div
                                class="flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-150/50 dark:border-gray-700/30"
                            >
                                <div
                                    class="grid gap-x-6 gap-y-2 flex-1"
                                    :class="
                                        invoice.work_order_id
                                            ? 'grid-cols-1 sm:grid-cols-2'
                                            : 'grid-cols-2'
                                    "
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5"
                                        >
                                            {{ $t('invoices.date') }}
                                        </p>
                                        <div
                                            class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300"
                                        >
                                            <span class="text-xs font-bold font-mono">
                                                {{ formatDate(invoice.issue_date) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="!invoice.work_order_id">
                                        <p
                                            class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5"
                                        >
                                            {{ $t('invoices.supply_date') }}
                                        </p>
                                        <span
                                            class="text-xs font-bold font-mono text-gray-700 dark:text-gray-300"
                                        >
                                            {{ formatDate(invoice.supply_date) }}
                                        </span>
                                    </div>

                                    <div v-if="invoice.work_order">
                                        <p
                                            class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5"
                                        >
                                            {{ $t('work_orders.title_singular') }}
                                        </p>
                                        <Link
                                            :href="route('work-orders.show', invoice.work_order_id)"
                                            class="text-xs font-black text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline inline-flex items-center gap-1"
                                        >
                                            <span dir="ltr">
                                                #{{
                                                    invoice.work_order?.code ||
                                                    invoice.work_order?.id
                                                }}
                                            </span>
                                        </Link>
                                    </div>
                                </div>

                                <!-- QR Code block for ZATCA E-Invoice -->
                                <div
                                    v-if="
                                        invoice.tax_enabled_snapshot &&
                                        isVatEnabled &&
                                        invoice.zatca_qr_tlv
                                    "
                                    class="shrink-0 p-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm"
                                >
                                    <img
                                        :src="generateQRCode(invoice.zatca_qr_tlv)"
                                        alt="ZATCA QR"
                                        class="w-12 h-12 object-contain"
                                    />
                                </div>
                            </div>

                            <!-- Credit Note Reference to Original Invoice -->
                            <div
                                v-if="invoice.type === 'credit_note' && invoice.original_invoice"
                                class="border border-rose-200 dark:border-rose-900/40 bg-rose-50/50 dark:bg-rose-950/20 p-3 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-0.5"
                                    >
                                        {{ $t('invoices.credit_note.original_invoice') }}
                                    </p>
                                    <Link
                                        :href="
                                            route('app.invoices.show', invoice.original_invoice.id)
                                        "
                                        class="text-xs font-black text-rose-700 hover:text-rose-800 dark:text-rose-300 hover:underline flex items-center gap-1.5"
                                    >
                                        <span>#{{ invoice.original_invoice.invoice_number }}</span>
                                        <span class="text-gray-500 font-normal">
                                            ({{ formatDate(invoice.original_invoice.issue_date) }})
                                        </span>
                                    </Link>
                                </div>
                                <div v-if="invoice.return_reason" class="text-start sm:text-end">
                                    <p
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0.5"
                                    >
                                        {{ $t('invoices.credit_note.reason') }}
                                    </p>
                                    <span
                                        class="text-xs font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        {{ invoice.return_reason }}
                                    </span>
                                </div>
                            </div>

                            <!-- Related Credit Notes if viewing original invoice -->
                            <div
                                v-if="invoice.credit_notes && invoice.credit_notes.length > 0"
                                class="border border-amber-200 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 p-3 rounded-xl"
                            >
                                <p
                                    class="text-[10px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest mb-1.5"
                                >
                                    {{ $t('invoices.credit_note.related_credit_notes') }}
                                </p>
                                <div class="space-y-1">
                                    <div
                                        v-for="cn in invoice.credit_notes"
                                        :key="cn.id"
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <Link
                                            :href="route('app.invoices.show', cn.id)"
                                            class="font-mono font-bold text-amber-800 dark:text-amber-300 hover:underline flex items-center gap-1"
                                        >
                                            <span>↩ #{{ cn.invoice_number }}</span>
                                            <span class="text-gray-500 font-normal">
                                                ({{ formatDate(cn.issue_date) }})
                                            </span>
                                        </Link>
                                        <span
                                            class="font-bold text-rose-600 dark:text-rose-400 font-mono"
                                        >
                                            -{{ formatCurrency(cn.total_incl_tax) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </WorkOrderCustomerCard>

                <!-- 2. Left Card: Unified Cost & Payment Summary -->
                <WorkOrderFinancialSummary
                    :totals="financialSummaryTotals"
                    :has-tax="!!invoice.tax_enabled_snapshot"
                    :tax-rate="Number(invoice.tax_rate_snapshot || 15)"
                    :total-paid="invoiceActualPaid"
                    :bad-debt="invoiceBadDebt"
                    :balance="balance"
                    :hide-zero-rows="true"
                    :services-label="$t('work_orders.services')"
                    :parts-label="$t('quotes.show.tabs.spare_parts')"
                    :grand-label="
                        invoice.tax_enabled_snapshot
                            ? $t('invoices.grand_total')
                            : $t('invoices.total')
                    "
                />
            </div>

            <!-- Notes Section (If Notes are present) -->
            <div
                v-if="invoice.notes"
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
                    {{ invoice.notes }}
                </p>
            </div>

            <!-- Tabs Section Selector -->
            <div
                class="flex items-center bg-white dark:bg-gray-800 p-1.5 sm:p-2 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm gap-1 w-full sm:w-fit overflow-x-auto no-scrollbar"
            >
                <!-- Services Tab -->
                <button
                    @click="activeTab = 'invoice_items'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'invoice_items'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50',
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <span>
                        {{
                            invoice.work_order_id
                                ? $t('work_orders.services')
                                : $t('common.details')
                        }}
                    </span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'invoice_items'
                                ? 'bg-white/20 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                        ]"
                    >
                        {{ serviceLines.length }}
                    </span>
                </button>

                <!-- Spare Parts Tab (only for WO invoices that have parts) -->
                <button
                    v-if="invoice.work_order_id && partsLines.length > 0"
                    @click="activeTab = 'parts'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'parts'
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-400/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50',
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    <span>{{ $t('quotes.show.tabs.spare_parts') }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'parts'
                                ? 'bg-white/20 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                        ]"
                    >
                        {{ partsLines.length }}
                    </span>
                </button>

                <!-- Payments Tab -->
                <button
                    @click="activeTab = 'payments'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'payments'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50',
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ $t('invoices.payments_history') }}</span>
                </button>
            </div>

            <!-- Services / Details Tab -->
            <div
                v-show="activeTab === 'invoice_items'"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden"
            >
                <!-- Mobile Card List (< sm) -->
                <div class="block sm:hidden divide-y divide-gray-100 dark:divide-gray-700/50">
                    <div
                        v-for="(line, index) in serviceLines"
                        :key="line.id"
                        class="p-4 space-y-2 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-md bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 font-mono text-xs font-black flex items-center justify-center shrink-0"
                                >
                                    {{ toEnglish(index + 1) }}
                                </span>
                                <h4
                                    class="text-sm font-bold text-gray-900 dark:text-white leading-tight"
                                >
                                    {{ line.description }}
                                </h4>
                            </div>
                            <span
                                class="text-sm font-black text-blue-600 dark:text-blue-400 font-mono shrink-0"
                            >
                                {{ formatCurrency(line.line_total_incl_tax) }}
                            </span>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1"
                        >
                            <span>
                                {{ $t('common.unit_price') }}:
                                <span class="font-mono font-semibold">
                                    {{ formatCurrency(line.unit_price) }}
                                </span>
                                <span v-if="!invoice.work_order_id && line.qty > 1" class="ms-1">
                                    × {{ toEnglish(line.qty) }}
                                </span>
                            </span>
                            <span
                                v-if="getLineDiscount(line, showExclusive) > 0.01"
                                class="text-red-500 italic"
                            >
                                {{ $t('common.discount') }}: -{{
                                    formatCurrency(getLineDiscount(line, showExclusive))
                                }}
                            </span>
                            <span
                                v-if="invoice.tax_enabled_snapshot"
                                class="text-sky-600 dark:text-sky-400 font-mono"
                            >
                                {{ $t('common.vat') }}: {{ formatCurrency(line.tax_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table (sm and larger) -->
                <div class="hidden sm:block overflow-x-auto">
                    <DataTable structured class="w-full min-w-[640px]">
                        <thead>
                            <tr
                                class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/50"
                            >
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle"
                                >
                                    #
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.description') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.unit_price') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-red-500 italic align-middle"
                                >
                                    {{ $t('common.discount') }}
                                </th>
                                <!-- Qty hidden for WO invoices (services have qty=1 always) -->
                                <th
                                    v-if="!invoice.work_order_id"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.qty') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.amount') }}
                                </th>
                                <th
                                    v-if="invoice.tax_enabled_snapshot"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest italic align-middle"
                                >
                                    {{ $t('common.vat') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-gray-900 dark:text-white align-middle"
                                >
                                    {{ $t('common.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr
                                v-for="(line, index) in serviceLines"
                                :key="line.id"
                                class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors"
                            >
                                <!-- Index -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-500 dark:text-gray-400 font-mono"
                                    >
                                        {{ toEnglish(index + 1) }}
                                    </span>
                                </td>

                                <!-- Line description -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-950 dark:text-white">
                                        {{ line.description }}
                                    </span>
                                </td>

                                <!-- Unit Price -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatCurrency(line.unit_price) }}
                                    </span>
                                </td>

                                <!-- Discount -->
                                <td
                                    class="px-6 py-5 text-center text-red-500 font-mono align-middle"
                                >
                                    {{
                                        getLineDiscount(line, showExclusive) > 0.01
                                            ? '-' +
                                              formatCurrency(getLineDiscount(line, showExclusive))
                                            : '—'
                                    }}
                                </td>

                                <!-- Qty (hidden for WO invoices) -->
                                <td
                                    v-if="!invoice.work_order_id"
                                    class="px-6 py-5 text-center align-middle"
                                >
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ toEnglish(line.qty) }}
                                    </span>
                                </td>

                                <!-- Subtotal -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{
                                            formatCurrency(
                                                showExclusive
                                                    ? line.line_total_excl_tax
                                                    : line.line_total_incl_tax
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- VAT -->
                                <td
                                    v-if="invoice.tax_enabled_snapshot"
                                    class="px-6 py-5 text-center text-xs text-sky-600 dark:text-sky-400 font-mono align-middle"
                                >
                                    {{ formatCurrency(line.tax_amount) }}
                                </td>

                                <!-- Total -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-base font-black text-gray-900 dark:text-white font-mono"
                                    >
                                        {{ formatCurrency(line.line_total_incl_tax) }}
                                    </span>
                                </td>
                            </tr>
                            <!-- Empty state -->
                            <tr v-if="serviceLines.length === 0">
                                <td
                                    :colspan="
                                        invoice.work_order_id
                                            ? invoice.tax_enabled_snapshot
                                                ? 6
                                                : 5
                                            : invoice.tax_enabled_snapshot
                                              ? 7
                                              : 6
                                    "
                                    class="py-10 text-center text-sm text-gray-400"
                                >
                                    —
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>

            <!-- Spare Parts Tab -->
            <div
                v-show="activeTab === 'parts'"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden"
            >
                <!-- Mobile Card List (< sm) -->
                <div class="block sm:hidden divide-y divide-gray-100 dark:divide-gray-700/50">
                    <div
                        v-for="(line, index) in partsLines"
                        :key="line.id"
                        class="p-4 space-y-2 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-md bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 font-mono text-xs font-black flex items-center justify-center shrink-0"
                                >
                                    {{ toEnglish(index + 1) }}
                                </span>
                                <h4
                                    class="text-sm font-bold text-gray-900 dark:text-white leading-tight"
                                >
                                    {{ line.description }}
                                </h4>
                            </div>
                            <span
                                class="text-sm font-black text-amber-600 dark:text-amber-400 font-mono shrink-0"
                            >
                                {{ formatCurrency(line.line_total_incl_tax) }}
                            </span>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1"
                        >
                            <span>
                                {{ $t('common.unit_price') }}:
                                <span class="font-mono font-semibold">
                                    {{ formatCurrency(line.unit_price) }}
                                </span>
                                <span v-if="line.qty > 1" class="ms-1">
                                    × {{ toEnglish(line.qty) }}
                                </span>
                            </span>
                            <span
                                v-if="getLineDiscount(line, showExclusive) > 0.01"
                                class="text-red-500 italic"
                            >
                                {{ $t('common.discount') }}: -{{
                                    formatCurrency(getLineDiscount(line, showExclusive))
                                }}
                            </span>
                            <span
                                v-if="invoice.tax_enabled_snapshot"
                                class="text-sky-600 dark:text-sky-400 font-mono"
                            >
                                {{ $t('common.vat') }}: {{ formatCurrency(line.tax_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table (sm and larger) -->
                <div class="hidden sm:block overflow-x-auto">
                    <DataTable structured class="w-full min-w-[640px]">
                        <thead>
                            <tr
                                class="bg-amber-50/50 dark:bg-amber-900/10 border-b border-amber-100 dark:border-amber-800/30"
                            >
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-500 dark:text-amber-400 uppercase tracking-widest align-middle"
                                >
                                    #
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-500 dark:text-amber-400 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.description') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-500 dark:text-amber-400 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.unit_price') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-red-400 italic uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.discount') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-500 dark:text-amber-400 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.qty') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-500 dark:text-amber-400 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.amount') }}
                                </th>
                                <th
                                    v-if="invoice.tax_enabled_snapshot"
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-500 dark:text-amber-400 italic uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.vat') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-amber-600 dark:text-amber-300 uppercase tracking-widest align-middle"
                                >
                                    {{ $t('common.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr
                                v-for="(line, index) in partsLines"
                                :key="line.id"
                                class="group hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors"
                            >
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-500 dark:text-gray-400 font-mono"
                                    >
                                        {{ toEnglish(index + 1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-950 dark:text-white">
                                        {{ line.description }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatCurrency(line.unit_price) }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-5 text-center text-red-500 font-mono align-middle"
                                >
                                    {{
                                        getLineDiscount(line, showExclusive) > 0.01
                                            ? '-' +
                                              formatCurrency(getLineDiscount(line, showExclusive))
                                            : '—'
                                    }}
                                </td>
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{ toEnglish(line.qty) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono"
                                    >
                                        {{
                                            formatCurrency(
                                                showExclusive
                                                    ? line.line_total_excl_tax
                                                    : line.line_total_incl_tax
                                            )
                                        }}
                                    </span>
                                </td>
                                <td
                                    v-if="invoice.tax_enabled_snapshot"
                                    class="px-6 py-5 text-center text-xs text-sky-600 dark:text-sky-400 font-mono align-middle"
                                >
                                    {{ formatCurrency(line.tax_amount) }}
                                </td>
                                <td class="px-6 py-5 text-center align-middle">
                                    <span
                                        class="text-base font-black text-gray-900 dark:text-white font-mono"
                                    >
                                        {{ formatCurrency(line.line_total_incl_tax) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>

            <!-- Payments Tab Content -->
            <div
                v-show="activeTab === 'payments'"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6"
            >
                <!-- Tab Header Action -->
                <div
                    class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/50"
                >
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                        {{ $t('invoices.payments_history') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="balance > 0.01 && canRecordPayment"
                            @click="showPaymentModal = true"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-emerald-500/10 active:scale-95 shrink-0"
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>
                                {{
                                    isCreditNote
                                        ? $t('payments.types.refund')
                                        : $t('payments.add_payment')
                                }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Mobile Card List (< sm) -->
                <div
                    v-if="invoice.payments && invoice.payments.length > 0"
                    class="block sm:hidden divide-y divide-gray-100 dark:divide-gray-700/50 mb-4"
                >
                    <div
                        v-for="(payment, index) in invoice.payments"
                        :key="payment.id"
                        class="p-4 space-y-2 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors rounded-xl"
                        :class="
                            payment.type === 'bad_debt' ? 'bg-amber-50/40 dark:bg-amber-950/10' : ''
                        "
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-md bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 font-mono text-xs font-black flex items-center justify-center shrink-0"
                                >
                                    {{ toEnglish(index + 1) }}
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    <template v-if="payment.type === 'bad_debt'">
                                        {{ $t('payments.types.bad_debt') }}
                                    </template>
                                    <template v-else>
                                        {{
                                            $t(`payments.methods.${payment.payment_method}`) ||
                                            payment.payment_method
                                        }}
                                    </template>
                                </span>
                            </div>
                            <span
                                class="text-base font-black font-mono shrink-0"
                                :class="
                                    payment.type === 'bad_debt'
                                        ? 'text-amber-700 dark:text-amber-400'
                                        : 'text-emerald-600 dark:text-emerald-400'
                                "
                            >
                                {{ formatCurrencyEnglish(payment.amount) }}
                            </span>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1"
                        >
                            <span class="font-mono">{{ formatDate(payment.payment_date) }}</span>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold"
                                :class="
                                    payment.type === 'bad_debt'
                                        ? 'text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-950/40'
                                        : payment.type === 'refund'
                                          ? 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/30'
                                          : 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'
                                "
                            >
                                {{
                                    payment.type === 'bad_debt'
                                        ? $t('payments.types.bad_debt')
                                        : payment.type === 'refund'
                                          ? $t('payments.types.refund')
                                          : $t('payments.types.payment')
                                }}
                            </span>
                            <span v-if="payment.received_by?.name">
                                {{ payment.received_by.name }}
                            </span>
                        </div>
                        <p
                            v-if="payment.notes || payment.reference"
                            class="text-xs text-gray-500 italic"
                        >
                            {{ payment.notes || payment.reference }}
                        </p>
                    </div>
                </div>

                <!-- Desktop Table (sm and larger) -->
                <div class="hidden sm:block overflow-x-auto">
                    <DataTable structured class="w-full text-sm">
                        <thead>
                            <tr
                                class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50"
                            >
                                <th class="py-3 px-4 text-center font-bold align-middle">#</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">
                                    {{ $t('payments.form.method') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold align-middle">
                                    {{ $t('payments.form.type') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold align-middle">
                                    {{ $t('payments.form.date') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold align-middle">
                                    {{ $t('payments.form.notes') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold align-middle">
                                    {{ $t('payments.form.amount') }}
                                </th>
                                <th class="py-3 px-4 text-center font-bold align-middle">
                                    {{ $t('payments.recorded_by') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                            <!-- Show payment details -->
                            <template v-if="invoice.payments && invoice.payments.length > 0">
                                <tr
                                    v-for="(payment, index) in invoice.payments"
                                    :key="payment.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                    :class="
                                        payment.type === 'bad_debt'
                                            ? 'bg-amber-50/40 dark:bg-amber-950/10'
                                            : ''
                                    "
                                >
                                    <td
                                        class="py-4 px-4 text-center text-gray-500 font-bold font-mono align-middle"
                                    >
                                        {{ toEnglish(index + 1) }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center font-bold align-middle"
                                        :class="
                                            payment.type === 'bad_debt'
                                                ? 'text-amber-700 dark:text-amber-400'
                                                : 'text-gray-700 dark:text-gray-300'
                                        "
                                    >
                                        <template v-if="payment.type === 'bad_debt'">—</template>
                                        <template v-else>
                                            {{
                                                $t(`payments.methods.${payment.payment_method}`) ||
                                                payment.payment_method
                                            }}
                                        </template>
                                    </td>
                                    <td class="py-4 px-4 text-center font-bold align-middle">
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold"
                                            :class="
                                                payment.type === 'bad_debt'
                                                    ? 'text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-950/40'
                                                    : payment.type === 'refund'
                                                      ? 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/30'
                                                      : 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'
                                            "
                                        >
                                            {{
                                                payment.type === 'bad_debt'
                                                    ? $t('payments.types.bad_debt')
                                                    : payment.type === 'refund'
                                                      ? $t('payments.types.refund')
                                                      : $t('payments.types.payment')
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono align-middle"
                                        dir="ltr"
                                    >
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate align-middle"
                                        :title="payment.notes || payment.reference"
                                    >
                                        {{ payment.notes || payment.reference || '—' }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center font-black font-mono align-middle"
                                        dir="ltr"
                                        :class="
                                            payment.type === 'bad_debt'
                                                ? 'text-amber-700 dark:text-amber-400'
                                                : 'text-gray-900 dark:text-white'
                                        "
                                    >
                                        {{ formatCurrencyEnglish(payment.amount) }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-semibold align-middle"
                                    >
                                        {{ payment.received_by?.name || '—' }}
                                    </td>
                                </tr>
                            </template>

                            <!-- Display empty state row inside the tbody if there is no payment -->
                            <tr v-else>
                                <td colspan="7" class="py-12 align-middle">
                                    <div
                                        class="flex flex-col items-center justify-center text-center"
                                    >
                                        <div
                                            class="w-16 h-16 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center text-gray-400 mb-4 shadow-inner"
                                        >
                                            <svg
                                                class="w-8 h-8"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $t('payments.no_payments') }}
                                        </h3>
                                        <p class="text-xs text-gray-400 mt-1 max-w-sm mb-4">
                                            {{ $t('payments.no_payments_desc') }}
                                        </p>
                                        <button
                                            v-if="balance > 0.01 && canRecordPayment"
                                            @click="showPaymentModal = true"
                                            class="flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-500/20 active:scale-95"
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
                                                    d="M12 4v16m8-8H4"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    isCreditNote
                                                        ? $t('payments.types.refund')
                                                        : $t('payments.add_payment')
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>

            <!-- Payment Modal Component -->
            <PurchaseOrderPaymentModal
                v-if="showPaymentModal"
                :show="showPaymentModal"
                :balance="balance"
                :default-type="isCreditNote ? 'refund' : 'payment'"
                :allow-bad-debt="
                    !isCreditNote &&
                    invoice.status === 'valid' &&
                    balance > 0.01 &&
                    (Boolean(invoice.due_date) ||
                        invoice.payment_status === 'unpaid' ||
                        invoice.payment_status === 'partial')
                "
                @close="showPaymentModal = false"
                @saved="onPaymentSaved"
            />

            <!-- Return Invoice / Credit Note Modal -->
            <WorkOrderCreditNoteModal
                v-if="showCreditNoteModal"
                :show="showCreditNoteModal"
                :work-order="invoice.work_order"
                :invoice="invoice"
                @close="showCreditNoteModal = false"
                @created="onCreditNoteCreated"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import PurchaseOrderPaymentModal from '@/Components/Purchasing/PurchaseOrderPaymentModal.vue';
import WorkOrderCreditNoteModal from '@/Components/WorkOrders/WorkOrderCreditNoteModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import Tooltip from '@/Components/Tooltip.vue';
import WorkOrderCustomerCard from '@/Components/WorkOrders/WorkOrderCustomerCard.vue';
import WorkOrderFinancialSummary from '@/Components/WorkOrders/WorkOrderFinancialSummary.vue';

const props = defineProps({
    invoice: Object,
    colors: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { confirm } = useConfirm();
const { toEnglish, formatCurrency } = useNumberFormat();

const page = usePage();
const isVatEnabled = computed(() => page?.props?.tenant?.tax_settings?.vat_enabled ?? true);

const isCreditNote = computed(() => {
    const type = props.invoice?.type;
    return type === 'credit_note' || type?.value === 'credit_note';
});

/**
 * Recording a payment is gated by the server on the
 * `purchasing.payments.manage` permission (InvoicePolicy::recordPayment).
 * The same flag hides the button here, so a user without it is not
 * offered an action that would only answer 403.
 */
const canRecordPayment = computed(() => {
    const auth = page.props.auth;
    if (auth?.is_super_admin || auth?.user?.is_system_admin) {
        return true;
    }
    const permissions = auth?.permissions || [];
    return permissions.includes('purchasing.payments.manage');
});

const showCreditNoteModal = ref(false);

const canCreateCreditNote = computed(() => {
    return props.invoice.type !== 'credit_note' && props.invoice.status !== 'cancelled';
});

const onCreditNoteCreated = () => {
    showCreditNoteModal.value = false;
    router.reload({ only: ['invoice'] });
};

const formatCurrencyEnglish = (amount) => {
    return (
        Number(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) +
        ' ' +
        t('common.currency_sar')
    );
};

const activeTab = ref('invoice_items'); // 'invoice_items', 'parts', 'payments'
const showPaymentModal = ref(false);

// Split lines into services vs spare parts
const serviceLines = computed(() => {
    if (!props.invoice.work_order_id) {
        return props.invoice.lines || [];
    }
    return (props.invoice.lines || []).filter((l) => !l.is_part);
});
const partsLines = computed(() => (props.invoice.lines || []).filter((l) => l.is_part));

const invoiceBadDebt = computed(() => {
    return (props.invoice.payments || []).reduce((sum, p) => {
        return p.type === 'bad_debt' ? sum + Number(p.amount || 0) : sum;
    }, 0);
});

const invoiceActualPaid = computed(() => {
    return Math.max(0, Number(props.invoice.total_paid || 0) - invoiceBadDebt.value);
});

const balance = computed(() => {
    return Math.max(
        0,
        Number(props.invoice.total_incl_tax || 0) - Number(props.invoice.total_paid || 0)
    );
});

const backUrl = computed(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const from = params.get('from');
        if (from === 'center' && props.invoice.center_id) {
            return route('settings.centers.show', props.invoice.center_id) + '?tab=invoices';
        }
        if (from === 'company') {
            return route('settings.company') + '?tab=invoices';
        }
    }

    if (props.invoice.company_transaction) {
        if (props.invoice.center_id) {
            return route('settings.centers.show', props.invoice.center_id) + '?tab=invoices';
        }
        return route('settings.company') + '?tab=invoices';
    }
    return route('app.invoices.sales.index');
});

const showExclusive = computed(() => {
    if (!props.invoice.tax_enabled_snapshot) {
        return false;
    }
    if (props.invoice.pricing_mode_snapshot !== 'inclusive') {
        return true;
    }
    const taxSettings = usePage().props.tenant?.tax_settings;
    return taxSettings?.show_amount_before_vat ?? true;
});

const calculatedInvoiceSubtotal = computed(() => {
    const excl = Number(props.invoice?.total_excl_tax || 0);
    const incl = Number(props.invoice?.total_incl_tax || 0);
    return showExclusive.value ? excl : incl;
});

const calculatedInvoiceTaxAmount = computed(() => {
    return Number(props.invoice?.total_tax || 0);
});

const calculatedInvoiceTotal = computed(() => {
    return Number(props.invoice?.total_incl_tax || 0);
});

const discount = computed(() => {
    return (props.invoice.lines || []).reduce(
        (sum, l) => sum + getLineDiscount(l, showExclusive.value),
        0
    );
});

const grossTotal = computed(() => {
    const isInclusive = props.invoice.pricing_mode_snapshot === 'inclusive';
    if (isInclusive) {
        const discountIncl = (props.invoice.lines || []).reduce(
            (sum, l) => sum + getLineDiscount(l, false),
            0
        );
        return calculatedInvoiceTotal.value + discountIncl;
    } else {
        return calculatedInvoiceSubtotal.value + discount.value;
    }
});

// ── Services breakdown ──────────────────────────────────────
const servicesDiscount = computed(() => {
    if (!props.invoice.work_order_id) return 0;
    return (props.invoice.lines || [])
        .filter((l) => !l.is_part)
        .reduce((sum, l) => sum + getLineDiscount(l, showExclusive.value), 0);
});

const servicesSubtotal = computed(() => {
    if (!props.invoice.work_order_id) return 0;
    const excl = (props.invoice.lines || [])
        .filter((l) => !l.is_part)
        .reduce((sum, l) => sum + Number(l.line_total_excl_tax || 0), 0);
    const incl = (props.invoice.lines || [])
        .filter((l) => !l.is_part)
        .reduce((sum, l) => sum + Number(l.line_total_incl_tax || 0), 0);
    return showExclusive.value ? excl : incl;
});

const servicesGross = computed(() => {
    const isInclusive = props.invoice.pricing_mode_snapshot === 'inclusive';
    if (isInclusive) {
        const discountIncl = (props.invoice.lines || [])
            .filter((l) => !l.is_part)
            .reduce((sum, l) => sum + getLineDiscount(l, false), 0);
        return servicesTotal.value + discountIncl;
    } else {
        return servicesSubtotal.value + servicesDiscount.value;
    }
});

const servicesTax = computed(() => {
    if (!props.invoice.work_order_id) return 0;
    return (props.invoice.lines || [])
        .filter((l) => !l.is_part)
        .reduce((sum, l) => sum + Number(l.tax_amount || 0), 0);
});

const servicesTotal = computed(() => {
    if (!props.invoice.work_order_id) return 0;
    return (props.invoice.lines || [])
        .filter((l) => !l.is_part)
        .reduce((sum, l) => sum + Number(l.line_total_incl_tax || 0), 0);
});

// ── Spare Parts breakdown ───────────────────────────────────
const partsDiscountAmt = computed(() => {
    const filterFn = props.invoice.work_order_id ? (l) => l.is_part : (l) => true;
    return (props.invoice.lines || [])
        .filter(filterFn)
        .reduce((sum, l) => sum + getLineDiscount(l, showExclusive.value), 0);
});

const partsSubtotal = computed(() => {
    const filterFn = props.invoice.work_order_id ? (l) => l.is_part : (l) => true;
    const excl = (props.invoice.lines || [])
        .filter(filterFn)
        .reduce((sum, l) => sum + Number(l.line_total_excl_tax || 0), 0);
    const incl = (props.invoice.lines || [])
        .filter(filterFn)
        .reduce((sum, l) => sum + Number(l.line_total_incl_tax || 0), 0);
    return showExclusive.value ? excl : incl;
});

const partsGross = computed(() => {
    const isInclusive = props.invoice.pricing_mode_snapshot === 'inclusive';
    if (isInclusive) {
        const filterFn = props.invoice.work_order_id ? (l) => l.is_part : (l) => true;
        const discountIncl = (props.invoice.lines || [])
            .filter(filterFn)
            .reduce((sum, l) => sum + getLineDiscount(l, false), 0);
        return partsTotal.value + discountIncl;
    } else {
        return partsSubtotal.value + partsDiscountAmt.value;
    }
});

const partsTax = computed(() => {
    const filterFn = props.invoice.work_order_id ? (l) => l.is_part : (l) => true;
    return (props.invoice.lines || [])
        .filter(filterFn)
        .reduce((sum, l) => sum + Number(l.tax_amount || 0), 0);
});

const partsTotal = computed(() => {
    const filterFn = props.invoice.work_order_id ? (l) => l.is_part : (l) => true;
    return (props.invoice.lines || [])
        .filter(filterFn)
        .reduce((sum, l) => sum + Number(l.line_total_incl_tax || 0), 0);
});

const financialSummaryTotals = computed(() => ({
    services: {
        price: servicesGross.value,
        discount: servicesDiscount.value,
        amount: servicesSubtotal.value,
        tax: servicesTax.value,
        total: servicesTotal.value,
    },
    parts: {
        price: partsGross.value,
        discount: partsDiscountAmt.value,
        amount: partsSubtotal.value,
        tax: partsTax.value,
        total: partsTotal.value,
    },
    grand: {
        price: grossTotal.value,
        discount: discount.value,
        amount: calculatedInvoiceSubtotal.value,
        tax: calculatedInvoiceTaxAmount.value,
        total: calculatedInvoiceTotal.value,
    },
}));

const onPaymentSaved = (paymentData) => {
    router.post(route('app.invoices.payments.store', props.invoice.id), paymentData, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        },
    });
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const generateQRCode = (base64Data) => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(base64Data)}`;
};

const getLineDiscount = (line, exclusive = true) => {
    const qty = Number(line.qty || 0);
    const unitPrice = Number(line.unit_price || 0);
    const isInclusive = props.invoice.pricing_mode_snapshot === 'inclusive';

    // Prefer the stored discount_amount (carried over from WorkOrderItem on
    // conversion). The stored value is always the user-entered discount
    // amount, which sits on top of the unit price AS-IS regardless of the
    // pricing mode — i.e. it is the same number in inclusive or exclusive
    // displays. So we never divide it by (1+rate) here.
    if (
        line.discount_amount !== undefined &&
        line.discount_amount !== null &&
        Number(line.discount_amount) > 0
    ) {
        return Number(line.discount_amount);
    }

    // Fallback: derive from line total. Always return the inclusive discount amount.
    // The discount is applied to the full service value and does not change
    // with the display mode (exclusive vs inclusive).
    if (isInclusive) {
        return Math.max(0, qty * unitPrice - Number(line.line_total_incl_tax || 0));
    } else {
        return Math.max(0, qty * unitPrice - Number(line.line_total_excl_tax || 0));
    }
};

const getCustomerAddress = (customer) => {
    if (!customer) return '';
    const parts = [
        customer.building_number ? `${t('common.building')} ${customer.building_number}` : '',
        customer.address_line ? customer.address_line : '',
        customer.district ? `${t('common.district')} ${customer.district}` : '',
        customer.city ? customer.city : '',
        customer.region ? customer.region : '',
        customer.postal_code ? `${t('common.postal_code')} ${customer.postal_code}` : '',
    ].filter(Boolean);
    return parts.join('، ');
};

const getCenterAddress = (center) => {
    if (!center || !center.address) return '';
    const addr = center.address;
    const parts = [
        addr.building_number ? `${t('common.building')} ${addr.building_number}` : '',
        addr.street ? `${t('common.street')} ${addr.street}` : '',
        addr.district ? `${t('common.district')} ${addr.district}` : '',
        addr.city ? addr.city : '',
        addr.postal_code ? `${t('common.postal_code')} ${addr.postal_code}` : '',
    ].filter(Boolean);
    return parts.join('، ');
};

const statusClass = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
        partial:
            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800',
    };
    return (
        map[status] ||
        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600'
    );
};
</script>
