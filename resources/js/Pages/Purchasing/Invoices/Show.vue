<template>
    <AppLayout :title="`${$t('invoices.purchases.invoice')} #${invoice.code}`">
        <div class="space-y-6 pb-20">
            <!-- Premium Glassmorphic Header -->
            <PageHeader
                :title="`${$t('invoices.purchases.invoice')} #${invoice.code}`"
                :subtitle="invoice.company_transaction ? invoice.company_transaction.title : invoice.supplier?.name"
                :totalCount="formatCurrency(invoice.total)"
                :countLabel="$t('invoices.grand_total')"
                gradientFrom="from-amber-600"
                gradientTo="to-orange-600"
                glowFrom="from-amber-500"
                badgeBg="bg-amber-50/50 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-100/50 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
            >
                <template #back>
                    <BackButton :href="invoice.company_transaction ? (route('settings.company') + '?tab=invoices') : route('app.invoices.purchases.index')" />
                </template>

                <template #icon>
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <!-- Return Button (Only if has returnable items) -->
                        <Tooltip v-if="hasReturnableItems" :content="$t('invoices.return_invoice')">
                            <button
                                @click="showReturnModal = true"
                                class="flex items-center gap-2 px-4 py-2 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/40 rounded-xl border border-rose-150 dark:border-rose-900 transition-all font-bold text-xs shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3" />
                                </svg>
                                <span>{{ $t('invoices.return_invoice') }}</span>
                            </button>
                        </Tooltip>

                        <!-- Print Button -->
                        <Tooltip :content="$t('common.print')">
                            <a
                                :href="route('app.invoices.purchases.print', invoice.id)"
                                target="_blank"
                                class="flex items-center gap-2 px-4 py-2 bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 hover:text-amber-600 dark:hover:text-amber-400 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-bold text-xs shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 022 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>{{ $t('common.print') }}</span>
                            </a>
                        </Tooltip>

                        <!-- Add Payment Button -->
                        <Tooltip v-if="Number(invoice.balance) > 0.01" :content="$t('payments.add_payment')">
                            <button
                                @click="showPaymentModal = true"
                                class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all font-bold text-xs shadow-sm hover:shadow"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $t('payments.add_payment') }}</span>
                            </button>
                        </Tooltip>

                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- Status Badge -->
                        <span :class="statusClass(invoice.status)" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm">
                            {{ $t(`invoices.purchases.statuses.${invoice.status}`) }}
                        </span>

                        <!-- Reference Number Badge -->
                        <div v-if="invoice.invoice_number" class="px-4 py-1.5 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            REF: {{ invoice.invoice_number }}
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Top Cards Section: Center/Supplier Info & Financial Cost Card -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- 1. Right Card: Center & Supplier Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full relative group">
                    <!-- Card Decoration -->
                    <div class="absolute -top-12 -end-12 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-colors"></div>

                    <!-- Center Name Header -->
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700 px-5 py-4">
                        <div class="flex items-center gap-3 text-amber-600">
                            <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('common.center') }}</h3>
                        </div>
                        <span class="text-lg font-black text-gray-900 dark:text-white" dir="auto">
                            {{ isCompany ? (tenant.trade_name || tenant.legal_name || tenant.name) : (invoice.center?.name_ar || invoice.center?.name) }}
                        </span>
                    </div>

                    <!-- Middle Section: Center Address & Supplier Info -->
                    <div class="p-5 flex-1 flex flex-col justify-between gap-6 relative z-10">
                        <!-- Center Address -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('common.address') }}</p>
                            <div class="flex items-start gap-2.5 text-gray-600 dark:text-gray-300">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm font-semibold leading-relaxed" dir="auto">
                                    {{ isCompany ? (tenant.address || 'المكتب الرئيسي للشركة') : (getCenterAddress(invoice.center) || $t('common.na')) }}
                                </span>
                            </div>
                        </div>

                        <!-- Divider line -->
                        <div class="border-t border-gray-100 dark:border-gray-700/65"></div>

                        <!-- Supplier Info -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                                {{ isCompany ? (invoice.company_transaction?.contact_type === 'customer' ? (locale === 'ar' ? 'العميل' : 'Customer') : $t('common.supplier')) : $t('common.supplier') }}
                            </p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-orange-50 dark:bg-orange-950/20 text-orange-600 flex items-center justify-center shrink-0">
                                    <svg v-if="isCompany && invoice.company_transaction?.contact_type === 'customer'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div>
                                    <template v-if="isCompany && invoice.company_transaction?.contact">
                                        <Link
                                            v-if="invoice.company_transaction.contact_type === 'supplier'"
                                            :href="route('app.purchasing.suppliers.show', invoice.company_transaction.contact.id)"
                                            class="text-base font-black text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/sup"
                                        >
                                            <span>{{ invoice.company_transaction.contact.name }}</span>
                                            <svg class="w-4 h-4 opacity-60 group-hover/sup:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </Link>
                                        <Link
                                            v-else-if="invoice.company_transaction.contact_type === 'customer'"
                                            :href="route('app.customers.show', invoice.company_transaction.contact.id)"
                                            class="text-base font-black text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/sup"
                                        >
                                            <span>{{ invoice.company_transaction.contact.name }}</span>
                                            <svg class="w-4 h-4 opacity-60 group-hover/sup:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </Link>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span v-if="invoice.company_transaction.contact.phone" class="font-mono flex items-center gap-1">
                                                <span>📞</span> <span dir="ltr">{{ invoice.company_transaction.contact.phone }}</span>
                                            </span>
                                            <span v-if="invoice.company_transaction.contact.tax_number" class="font-mono flex items-center gap-1">
                                                <span>📋</span> <span>{{ $t('suppliers.tax_number') }}: {{ invoice.company_transaction.contact.tax_number }}</span>
                                            </span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <Link
                                            v-if="invoice.supplier_id"
                                            :href="route('app.purchasing.suppliers.show', invoice.supplier_id)"
                                            class="text-base font-black text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/sup"
                                        >
                                            <span>{{ invoice.supplier?.name }}</span>
                                            <svg class="w-4 h-4 opacity-60 group-hover/sup:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </Link>
                                        <h4 v-else class="text-base font-bold text-gray-900 dark:text-white">—</h4>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span v-if="invoice.supplier?.phone" class="font-mono flex items-center gap-1">
                                                <span>📞</span> <span dir="ltr">{{ invoice.supplier.phone }}</span>
                                            </span>
                                            <span v-if="invoice.supplier?.tax_number" class="font-mono flex items-center gap-1">
                                                <span>📋</span> <span>{{ $t('suppliers.tax_number') }}: {{ invoice.supplier.tax_number }}</span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Left Card: Financial Cost and Payment Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col h-full">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <div class="w-2 h-5 bg-amber-500 rounded-full"></div>
                        {{ $t('work_orders.cost_and_payment') }}
                    </h3>

                    <div class="overflow-x-auto md:overflow-x-visible flex-1">
                        <table class="w-full min-w-[820px] md:min-w-0 text-sm">
                            <thead>
                                <tr class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50 pb-2">
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider align-middle">{{ $t('common.description') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider align-middle">{{ $t('common.unit_price') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider text-red-500 italic align-middle">{{ $t('common.discount') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider align-middle">{{ $t('common.amount') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider italic align-middle">{{ $t('common.vat') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider text-gray-900 dark:text-white align-middle">{{ $t('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                <!-- Spare Parts row -->
                                <tr class="group">
                                    <td class="py-2.5 text-center font-bold text-gray-900 dark:text-white align-middle">{{ $t('quotes.show.tabs.spare_parts') }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(calculatedInvoiceSubtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-red-500 italic align-middle">{{ invoice.discount_amount > 0 ? '-' + formatCurrency(invoice.discount_amount) : '0.00' }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(calculatedInvoiceSubtotal - invoice.discount_amount) }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(calculatedInvoiceTaxAmount) }}</td>
                                    <td class="py-2.5 text-center font-black text-gray-900 dark:text-white font-mono text-base align-middle">{{ formatCurrency(calculatedInvoiceTotal) }}</td>
                                </tr>

                                <!-- Paid Row -->
                                <tr class="group text-emerald-600 dark:text-emerald-400">
                                    <td class="py-2.5 text-center font-bold align-middle">{{ $t('invoices.paid') }}</td>
                                    <td colspan="4" class="py-2.5 align-middle"></td>
                                    <td class="py-2.5 text-center font-black font-mono text-base align-middle">{{ formatCurrency(invoice.total - invoice.balance) }}</td>
                                </tr>

                                <!-- Balance Row -->
                                <tr class="group text-red-600 dark:text-red-400">
                                    <td class="py-2.5 text-center font-bold align-middle">{{ $t('invoices.balance') }}</td>
                                    <td colspan="4" class="py-2.5 align-middle"></td>
                                    <td class="py-2.5 text-center font-black font-mono text-base align-middle">{{ formatCurrency(invoice.balance) }}</td>
                                </tr>

                                <!-- Grand Total Row -->
                                <tr class="bg-amber-50 dark:bg-amber-900/10 font-black border-t-2 border-amber-200 dark:border-amber-800">
                                    <td class="py-2.5 text-center text-amber-900 dark:text-amber-400 font-black uppercase align-middle">{{ $t('common.total') }}</td>
                                    <td class="py-2.5 text-center font-mono text-amber-700 dark:text-amber-300 align-middle">{{ formatCurrency(calculatedInvoiceSubtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-red-600 italic align-middle">{{ invoice.discount_amount > 0 ? '-' + formatCurrency(invoice.discount_amount) : '0.00' }}</td>
                                    <td class="py-2.5 text-center font-mono text-amber-700 dark:text-amber-300 align-middle">{{ formatCurrency(calculatedInvoiceSubtotal - invoice.discount_amount) }}</td>
                                    <td class="py-2.5 text-center font-mono text-amber-700 dark:text-amber-300 align-middle">{{ formatCurrency(calculatedInvoiceTaxAmount) }}</td>
                                    <td class="py-2.5 text-center font-black text-amber-600 dark:text-amber-400 font-mono text-xl align-middle">{{ formatCurrency(calculatedInvoiceTotal) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notes Section (If Notes are present) -->
            <div v-if="invoice.notes" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3 text-slate-400 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('common.notes') }}</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-semibold italic">
                    {{ 
                        invoice.notes && invoice.notes.startsWith('Auto-generated from GRN:') 
                        ? $t('purchasing.invoices.auto_generated_from_grn', { code: invoice.notes.replace('Auto-generated from GRN:', '').trim() })
                        : invoice.notes 
                    }}
                </p>
            </div>

            <!-- Tabs Section Selector -->
            <div class="flex items-center bg-white dark:bg-gray-800 p-2 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm gap-1 max-w-full overflow-x-auto whitespace-nowrap">
                <button
                    @click="activeTab = 'received_items'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2 shrink-0',
                        activeTab === 'received_items'
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>{{ $t('invoices.received_items') }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'received_items' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                        ]"
                    >
                        {{ invoice.lines?.length || 0 }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'payments'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2 shrink-0',
                        activeTab === 'payments'
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $t('invoices.payments_history') }}</span>
                </button>

                <button
                    @click="activeTab = 'attachments'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2 shrink-0',
                        activeTab === 'attachments'
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span>{{ $t('invoices.attachments') }}</span>
                    <span
                        v-if="invoice.attachment_path"
                        class="w-2 h-2 rounded-full bg-red-500"
                    ></span>
                </button>

                <button
                    v-if="invoice.return_invoices && invoice.return_invoices.length > 0"
                    @click="activeTab = 'returns'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2 shrink-0',
                        activeTab === 'returns'
                            ? 'bg-red-600 text-white shadow-md shadow-red-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3" />
                    </svg>
                    <span>{{ $t('invoices.return_history') }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'returns' ? 'bg-white/20 text-white' : 'bg-red-50 dark:bg-red-950/30 text-red-650 dark:text-red-400'
                        ]"
                    >
                        {{ invoice.return_invoices.length }}
                    </span>
                </button>
            </div>

            <!-- Elements Received Tab -->
            <div v-show="activeTab === 'received_items'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px]">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/50">
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">
                                    {{ isCompany ? (locale === 'ar' ? 'البند' : 'Item') : $t('inventory.parts.title_singular') }}
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('common.unit_price') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-red-500 italic align-middle">{{ $t('common.discount') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('invoices.net_unit_price') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('invoices.received_qty') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('invoices.subtotal') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest italic align-middle">{{ $t('common.vat') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-gray-900 dark:text-white align-middle">{{ $t('common.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr v-for="line in invoice.lines" :key="line.id" class="group hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
                                <!-- Part Name (Clickable Link) -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center">
                                        <Link
                                            v-if="line.part_id"
                                            :href="isCompany ? (route('settings.company') + '?tab=transactions') : route('app.inventory.parts.show', line.part_id)"
                                            class="text-sm font-black text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/link"
                                        >
                                            <span v-if="isCompany">{{ invoice.company_transaction?.income_category ? (locale === 'ar' ? invoice.company_transaction.income_category.name_ar : invoice.company_transaction.income_category.name_en) : (invoice.company_transaction?.title || '—') }}</span>
                                            <span v-else>{{ line.part?.name || '—' }}</span>
                                            <svg class="w-3.5 h-3.5 opacity-60 group-hover/link:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </Link>
                                        <span v-else class="text-sm font-bold text-gray-950 dark:text-white">—</span>
                                        <span v-if="isCompany" class="text-[11px] text-gray-400 font-semibold mt-1">{{ invoice.company_transaction?.title }}</span>
                                        <span v-else class="text-[10px] text-gray-400 font-mono mt-1 uppercase tracking-wider">{{ line.part?.sku || 'NO-SKU' }}</span>
                                    </div>
                                </td>
                                
                                <!-- Unit Cost / Price -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_cost) }}</span>
                                </td>
                                
                                <!-- Discount (Applied globally, 0.00 for individual line) -->
                                <td class="px-6 py-5 text-center text-red-500 italic align-middle">
                                    <span class="text-sm font-black font-mono">0.00</span>
                                </td>
                                
                                <!-- Net Unit Price (unit_cost) -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_cost) }}</span>
                                </td>
                                
                                <!-- Received Qty -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ toEnglish(line.qty) }}</span>
                                </td>
                                
                                <!-- Subtotal (qty * unit_cost) -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.qty * line.unit_cost) }}</span>
                                </td>
                                
                                <!-- VAT -->
                                <td class="px-6 py-5 text-center text-xs text-sky-600 dark:text-sky-400 font-mono align-middle">
                                    {{ formatCurrency(line.tax_amount) }} <span class="text-[10px] text-gray-400">({{ toEnglish(line.tax_rate) }}%)</span>
                                </td>
                                
                                <!-- Total -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-base font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(line.total) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments Tab Content -->
            <div v-show="activeTab === 'payments'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6">
                <!-- Tab Header Action -->
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/50">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                        {{ $t('invoices.payments_history') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="Number(invoice.balance) > 0.01"
                            @click="showPaymentModal = true; paymentModalType = 'payment'"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-emerald-500/10 active:scale-95 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ $t('payments.add_payment') }}</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50">
                                <th class="py-3 px-4 text-center font-bold align-middle">#</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.type') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.date') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.notes') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.amount') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.recorded_by') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                            <!-- Show payment details from the invoice.payments list -->
                            <template v-if="invoice.payments && invoice.payments.length > 0">
                                <tr v-for="(payment, index) in invoice.payments" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-4 px-4 text-center text-gray-500 font-bold font-mono align-middle">{{ toEnglish(index + 1) }}</td>
                                    <td class="py-4 px-4 text-center font-bold align-middle">
                                        <span
                                            :class="payment.type === 'refund' ? 'text-red-600 dark:text-red-450 bg-red-50 dark:bg-red-950/30' : 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'"
                                            class="px-2.5 py-1 rounded-lg text-xs"
                                        >
                                            {{ payment.type === 'refund' ? $t('payments.types.refund') : $t('payments.types.payment') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono align-middle" dir="ltr">
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate align-middle" :title="payment.notes === 'payments.auto_payment_notes' ? $t('payments.auto_payment_notes') : (payment.notes || payment.reference)">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t(`payments.methods.${payment.payment_method}`) || payment.payment_method }}</span>
                                            <span class="text-[10px]">{{ payment.notes === 'payments.auto_payment_notes' ? $t('payments.auto_payment_notes') : (payment.notes || payment.reference || '—') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle" dir="ltr">
                                        {{ formatCurrencyEnglish(payment.amount) }}
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-semibold align-middle">
                                        {{ payment.received_by?.name || '—' }}
                                    </td>
                                </tr>
                            </template>
                            
                            <!-- Display empty state row inside the tbody if there is no paid amount -->
                            <tr v-else>
                                <td colspan="7" class="py-12 align-middle">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <div class="w-16 h-16 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center text-gray-400 mb-4 shadow-inner">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $t('payments.no_payments') }}</h3>
                                        <p class="text-xs text-gray-400 mt-1 max-w-sm mb-4">{{ $t('payments.no_payments_desc') }}</p>
                                        <button
                                            v-if="Number(invoice.balance) > 0.01"
                                            @click="showPaymentModal = true"
                                            class="flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-500/20 active:scale-95"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>{{ $t('payments.add_payment') }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Attachments Tab Content -->
            <div v-show="activeTab === 'attachments'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6">
                <!-- If attachment exists -->
                <div v-if="invoice.attachment_path" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950/20 text-red-500 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $t('common.invoice_attachment') }}</h4>
                            <p class="text-xs text-gray-400 mt-0.5 font-mono select-all">{{ invoice.attachment_path.split('/').pop() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            :href="`/storage/${invoice.attachment_path}`"
                            target="_blank"
                            download
                            class="flex items-center justify-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-amber-500/10 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>{{ $t('common.download') }}</span>
                        </a>
                        <button
                            @click="deleteAttachment"
                            :disabled="deleteForm.processing"
                            class="flex items-center justify-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-950/20 dark:hover:bg-red-950/40 rounded-xl text-sm font-bold transition-all shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>{{ deleteForm.processing ? $t('common.deleting') : $t('common.delete') }}</span>
                        </button>
                    </div>
                </div>
                
                <!-- Upload form empty state -->
                <div v-else class="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700/60 rounded-2xl p-6">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center text-gray-400 mb-4 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V4a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $t('common.no_attachments') }}</h3>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm mb-4">{{ $t('common.no_attachments_desc') }}</p>
                    
                    <!-- Hidden File Input -->
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFileChange"
                        class="hidden"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />

                    <!-- Upload Trigger Button -->
                    <button
                        @click="triggerFileInput"
                        :disabled="uploadForm.processing"
                        class="flex items-center justify-center gap-2 px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-amber-500/25 active:scale-95 disabled:opacity-50"
                    >
                        <svg v-if="uploadForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>{{ uploadForm.processing ? $t('common.uploading') : $t('common.upload_attachment') }}</span>
                    </button>
                    
                    <!-- Hints -->
                    <p class="text-[10px] text-gray-400 mt-2">
                        {{ $t('common.attachment_limits_hint') }}
                    </p>

                    <!-- Errors display -->
                    <div v-if="uploadError" class="mt-3 text-xs text-red-500 font-bold bg-red-50 dark:bg-red-950/20 px-3 py-1.5 rounded-lg border border-red-200 dark:border-red-900/40">
                        {{ uploadError }}
                    </div>
                </div>
            </div>

            <!-- Return History Tab Content -->
            <div v-show="activeTab === 'returns'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm text-start">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50 text-xs font-bold uppercase tracking-wider">
                                <th class="py-4 px-6 text-center font-bold align-middle">{{ $t('common.index') }}</th>
                                <th class="py-4 px-6 text-center font-bold align-middle">{{ $t('invoices.return_code') }}</th>
                                <th class="py-4 px-6 text-center font-bold align-middle">{{ $t('payments.form.date') }}</th>
                                <th class="py-4 px-6 text-center font-bold align-middle">{{ $t('invoices.returned_items') }}</th>
                                <th class="py-4 px-6 text-center font-bold align-middle">{{ $t('payments.form.notes') }}</th>
                                <th class="py-4 px-6 text-center font-bold align-middle">{{ $t('payments.remaining_refund') }}</th>
                                <th class="py-4 px-6 text-center font-bold text-gray-900 dark:text-white align-middle">{{ $t('common.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                            <tr v-for="(ret, index) in invoice.return_invoices" :key="ret.id" class="hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
                                <td class="py-4 px-6 text-center text-gray-500 font-bold font-mono align-middle">{{ toEnglish(index + 1) }}</td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <Link :href="route('app.invoices.purchases.returns.show', ret.id)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-400 font-mono transition-all">
                                        {{ ret.code }}
                                    </Link>
                                </td>
                                <td class="py-4 px-6 text-center text-gray-600 dark:text-gray-300 font-mono align-middle" dir="ltr">
                                    {{ formatDate(ret.return_date) }}
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <div class="space-y-1.5 flex flex-col items-center justify-center">
                                        <div v-for="line in ret.lines" :key="line.id" class="text-xs text-gray-600 dark:text-gray-300 flex items-center justify-center gap-1.5">
                                            <span class="font-black text-red-650 bg-red-50 dark:bg-red-950/20 px-1.5 py-0.5 rounded text-[10px]">{{ toEnglish(line.qty) }}x</span>
                                            <span class="font-bold">{{ line.part?.name_ar || line.part?.name }}</span>
                                            <span class="text-gray-400 font-mono">({{ formatCurrency(line.unit_cost) }})</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate align-middle" :title="ret.notes">
                                    {{ ret.notes || '—' }}
                                </td>
                                <td class="py-4 px-6 text-center font-black font-mono text-xs align-middle" :class="calculateRemainingRefund(ret) > 0.01 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'" dir="ltr">
                                    {{ formatCurrency(calculateRemainingRefund(ret)) }}
                                </td>
                                <td class="py-4 px-6 text-center font-black text-gray-900 dark:text-white font-mono align-middle" dir="ltr">
                                    {{ formatCurrency(ret.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Return Invoice Modal (Main Modal) -->
            <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
                <!-- Modal Backdrop -->
                <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" @click="showReturnModal = false"></div>
                
                <!-- Modal Content Wrapper -->
                <div class="relative bg-white dark:bg-gray-800 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all duration-300 flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-red-600 to-orange-600 text-white relative">
                        <div class="absolute -top-12 -end-12 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="p-2 bg-white/10 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black">{{ $t('invoices.new_return') }}</h3>
                                <p class="text-xs text-white/80 mt-0.5">{{ $t('invoices.return_desc') }}</p>
                            </div>
                        </div>
                        <button @click="showReturnModal = false" class="p-2 hover:bg-white/10 rounded-xl transition-all text-white relative z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-6">
                        <!-- Table 1: Items Available for Return -->
                        <div>
                            <h4 class="text-sm font-black text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-red-500 rounded-full"></div>
                                {{ $t('invoices.available_items') }}
                            </h4>
                            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-x-auto md:overflow-x-visible bg-gray-50/30 dark:bg-gray-900/20">
                                <table class="w-full min-w-[980px] md:min-w-0 text-sm md:table-fixed">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-900/60 text-gray-400 border-b border-gray-100 dark:border-gray-700/60 text-[10px] font-black uppercase tracking-wider">
                                            <th class="py-3 px-3 lg:px-4 text-start w-72 align-middle text-center">{{ $t('inventory.parts.title_singular') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center w-16 align-middle">{{ $t('invoices.ordered_qty') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center text-red-550 w-16 align-middle">{{ $t('invoices.returned_previously') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center text-emerald-600 w-16 align-middle">{{ $t('invoices.available_qty') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center w-24 align-middle">{{ $t('invoices.original_cost') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center w-14 align-middle">{{ $t('invoices.tax') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center w-24 align-middle">{{ $t('common.total') }}</th>
                                            <th class="py-3 px-2 lg:px-4 text-center w-12 align-middle">{{ $t('common.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-150 dark:divide-gray-700/40">
                                        <tr v-for="line in availableReturnLines" :key="line.id" class="bg-white dark:bg-gray-800/40 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                            <td class="py-3.5 px-3 lg:px-4 font-bold text-gray-900 dark:text-white align-middle">
                                                <div>{{ line.part?.name_ar || line.part?.name }}</div>
                                                <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ line.part?.sku || 'NO-SKU' }}</div>
                                            </td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center font-bold text-gray-600 dark:text-gray-300 font-mono align-middle">{{ toEnglish(line.qty) }}</td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center font-bold text-red-500 font-mono align-middle">
                                                {{ toEnglish(line.return_lines?.reduce((sum, r) => sum + Number(r.qty), 0) || 0) }}
                                            </td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center font-black text-emerald-600 font-mono align-middle">
                                                {{ toEnglish(getAvailableQty(line)) }}
                                            </td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center font-bold text-gray-900 dark:text-white font-mono align-middle" dir="ltr">{{ formatCurrencyEnglish(line.unit_cost) }}</td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center text-xs font-mono align-middle">
                                                <span v-if="Number(line.tax_rate) > 0" class="px-1.5 py-0.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded font-bold">{{ toEnglish(line.tax_rate) }}%</span>
                                                <span v-else class="text-gray-400">—</span>
                                            </td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center font-bold text-gray-900 dark:text-white font-mono align-middle" dir="ltr">{{ formatCurrencyEnglish(line.total) }}</td>
                                            <td class="py-3.5 px-2 lg:px-4 text-center align-middle">
                                                <button
                                                    v-if="getAvailableQty(line) > 0.001"
                                                    type="button"
                                                    @click="openItemReturnModal(line)"
                                                    class="p-1.5 bg-red-50 hover:bg-red-600 text-red-650 hover:text-white dark:bg-red-950/20 dark:hover:bg-red-900 rounded-lg transition-all"
                                                    :title="$t('invoices.return_this')"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3" />
                                                    </svg>
                                                </button>
                                                <span v-else class="text-xs text-gray-400 dark:text-gray-500 font-bold italic">{{ $t('invoices.fully_returned') }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Table 2: Items Selected for Return -->
                        <div>
                            <h4 class="text-sm font-black text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-orange-500 rounded-full"></div>
                                {{ $t('invoices.items_to_return') }}
                            </h4>
                            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-x-auto bg-gray-50/30 dark:bg-gray-900/20">
                                <table class="w-full min-w-[760px] text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-900/60 text-gray-400 border-b border-gray-100 dark:border-gray-700/60 text-[10px] font-black uppercase tracking-wider">
                                            <th class="py-3 px-4 text-start align-middle text-center">{{ $t('inventory.parts.title_singular') }}</th>
                                            <th class="py-3 px-4 text-center w-28 align-middle">{{ $t('invoices.returned_qty') }}</th>
                                            <th class="py-3 px-4 text-center align-middle">{{ $t('invoices.return_unit_price') }}</th>
                                            <th class="py-3 px-4 text-center align-middle">{{ $t('invoices.tax') }}</th>
                                            <th class="py-3 px-4 text-center align-middle">{{ $t('common.total') }}</th>
                                            <th class="py-3 px-4 text-center w-12 align-middle"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-150 dark:divide-gray-700/40">
                                        <tr v-if="returnForm.items.length === 0">
                                            <td colspan="6" class="py-8 text-center text-gray-400 dark:text-gray-500 font-bold italic align-middle">
                                                {{ $t('invoices.no_items_selected_return') }}
                                            </td>
                                        </tr>
                                        <tr v-for="(item, idx) in returnForm.items" :key="item.purchase_invoice_line_id" class="bg-white dark:bg-gray-800/40 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                            <td class="py-3 px-4 align-middle">
                                                <div class="font-bold text-gray-900 dark:text-white">{{ item.part_name }}</div>
                                                <div v-if="item.is_inclusive" class="text-[10px] text-blue-500 font-bold mt-0.5">{{ $t('common.vat_included') }}</div>
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white font-mono align-middle" dir="ltr">
                                                {{ toEnglish(Number(item.qty).toFixed(2)) }}
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white font-mono align-middle" dir="ltr">
                                                {{ formatCurrencyEnglish(item.unit_cost) }}
                                            </td>
                                            <td class="py-3 px-4 text-center font-mono align-middle" dir="ltr">
                                                <span v-if="item.tax_amount > 0" class="text-amber-600 dark:text-amber-400 font-bold">{{ formatCurrencyEnglish(item.tax_amount) }}</span>
                                                <span v-else class="text-gray-400 text-xs">—</span>
                                            </td>
                                            <td class="py-3 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle" dir="ltr">
                                                {{ formatCurrencyEnglish(item.total) }}
                                            </td>
                                            <td class="py-3 px-4 text-center align-middle">
                                                <button
                                                    type="button"
                                                    @click="removeItemFromReturn(idx)"
                                                    class="p-1.5 text-gray-400 hover:text-red-650 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-all"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Date, Notes and Summary Column Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-150 dark:border-gray-700/60">
                            <!-- Left: Date and Notes -->
                            <div class="flex flex-col gap-4 w-full md:border-r border-gray-100 dark:border-gray-700/60 md:pr-5">
                                <!-- Return Date -->
                                <div>
                                    <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                                        {{ $t('invoices.return_date') }} <span class="text-red-500">*</span>
                                    </label>
                                    <CustomDatePicker
                                        v-model="returnForm.return_date"
                                        class="w-full"
                                        required
                                    />
                                </div>
                                <!-- Notes -->
                                <div>
                                    <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">{{ $t('payments.form.notes') }}</label>
                                    <textarea
                                        v-model="returnForm.notes"
                                        rows="3"
                                        :placeholder="$t('invoices.return_notes_placeholder')"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl font-semibold text-sm"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Right: Summary Card -->
                            <div class="bg-gray-50 dark:bg-gray-900/40 border border-gray-150 dark:border-gray-700/60 rounded-2xl p-5 flex flex-col justify-between">
                                <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">{{ $t('invoices.return_totals_summary') }}</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.subtotal') }}</span>
                                        <span class="font-bold text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrencyEnglish(returnSubtotal) }}</span>
                                    </div>
                                    <div v-if="returnTaxAmount > 0" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.tax') }}</span>
                                        <span class="font-bold text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrencyEnglish(returnTaxAmount) }}</span>
                                    </div>
                                    <div class="h-px bg-gray-200 dark:bg-gray-700 my-2"></div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-black text-red-650 dark:text-red-400">{{ $t('invoices.grand_total_returned') }}</span>
                                        <span class="text-xl font-black text-red-650 dark:text-red-400 font-mono" dir="ltr">{{ formatCurrencyEnglish(returnTotal) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Refund / Money Recovery Section -->
                        <div v-if="hasPayments || returnTotal > Number(invoice.balance)" class="bg-gradient-to-br from-rose-50/60 to-red-50/30 dark:from-red-950/15 dark:to-rose-950/5 border border-red-200/70 dark:border-red-900/40 rounded-2xl p-5 space-y-4 mt-4 shadow-inner">
                            <!-- Section Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/50 flex items-center justify-center text-red-650 dark:text-red-400 shadow-sm">
                                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-red-650 dark:text-red-400 uppercase tracking-widest">
                                            {{ $t('invoices.refund_details') }}
                                        </h4>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 font-bold">
                                            {{ $t('invoices.refund_desc') }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    v-if="hasPayments"
                                    type="button"
                                    @click="openRefundPaymentModal()"
                                    class="flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all duration-300 shadow-md shadow-red-500/10 hover:shadow-lg hover:shadow-red-500/20 hover:-translate-y-0.5 active:scale-95 w-full sm:w-auto"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ $t('payments.add_payment') }}
                                </button>
                                <span v-else class="text-xs text-amber-600 dark:text-amber-400 font-bold bg-amber-50 dark:bg-amber-950/20 px-3 py-1.5 rounded-lg border border-amber-200/50 dark:border-amber-900/30">
                                    {{ $t('payments.cannot_refund_unpaid') }}
                                </span>
                            </div>

                            <!-- High-tier Metrics Box -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                                <div class="bg-white/80 dark:bg-gray-800/80 rounded-xl p-3 border border-red-100 dark:border-red-900/20 flex items-center justify-between shadow-xs">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-black uppercase tracking-widest">{{ $t('invoices.refund_amount') }}</span>
                                    <span class="text-sm font-black text-red-650 dark:text-red-400 font-mono" dir="ltr">{{ formatCurrencyEnglish(calculatedRefundAmount) }}</span>
                                </div>
                                <div class="bg-white/80 dark:bg-gray-800/80 rounded-xl p-3 border border-red-100 dark:border-red-900/20 flex items-center justify-between shadow-xs">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-black uppercase tracking-widest">{{ $t('invoices.refunded_so_far') }}</span>
                                    <span :class="refundPaymentsTotal >= calculatedRefundAmount ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'" class="text-sm font-black font-mono" dir="ltr">{{ formatCurrencyEnglish(refundPaymentsTotal) }}</span>
                                </div>
                            </div>

                            <!-- Payments Summary Table -->
                            <div v-if="returnForm.refund_payments.length > 0" class="overflow-x-auto rounded-xl border border-red-200/60 dark:border-red-900/40 shadow-sm">
                                <table class="w-full min-w-[720px] text-xs">
                                    <thead class="bg-red-100/60 dark:bg-red-950/30 text-red-700 dark:text-red-400">
                                        <tr>
                                            <th class="px-3 py-2.5 text-center font-black uppercase tracking-widest w-8 align-middle">#</th>
                                            <th class="px-3 py-2.5 text-center font-black uppercase tracking-widest align-middle">{{ $t('payments.form.type') }}</th>
                                            <th class="px-3 py-2.5 text-center font-black uppercase tracking-widest align-middle">{{ $t('payments.form.date') }}</th>
                                            <th class="px-3 py-2.5 text-center font-black uppercase tracking-widest align-middle">{{ $t('payments.form.reference') }}</th>
                                            <th class="px-3 py-2.5 text-center font-black uppercase tracking-widest align-middle">{{ $t('payments.form.amount') }}</th>
                                            <th class="px-3 py-2.5 w-10 align-middle text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-red-100 dark:divide-red-900/30">
                                        <tr v-for="(rp, idx) in returnForm.refund_payments" :key="idx" class="bg-white dark:bg-gray-800 group hover:bg-red-50/30 dark:hover:bg-red-950/10 transition-colors">
                                            <td class="px-3 py-2.5 text-center text-gray-400 font-mono align-middle">{{ idx + 1 }}</td>
                                            <td class="px-3 py-2.5 text-center align-middle">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400 rounded-lg font-black text-[10px] uppercase tracking-widest">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"/></svg>
                                                    {{ $t(`payments.methods.${rp.payment_method}`) || rp.payment_method }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center text-gray-600 dark:text-gray-400 font-mono align-middle" dir="ltr">{{ rp.payment_date || returnForm.return_date }}</td>
                                            <td class="px-3 py-2.5 text-center text-gray-500 dark:text-gray-400 font-mono align-middle" dir="ltr">{{ rp.reference || '—' }}</td>
                                            <td class="px-3 py-2.5 text-center font-black text-red-650 dark:text-red-400 font-mono align-middle" dir="ltr">{{ formatCurrencyEnglish(rp.amount) }}</td>
                                            <td class="px-3 py-2.5 text-center align-middle">
                                                <button type="button" @click="removeRefundPaymentRow(idx)" class="p-1 text-gray-300 hover:text-red-500 rounded transition-colors opacity-0 group-hover:opacity-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-red-50 dark:bg-red-950/20 border-t border-red-200/60 dark:border-red-900/40">
                                        <tr>
                                            <td colspan="4" class="px-3 py-2.5 text-center text-xs font-black text-red-650 dark:text-red-400 uppercase tracking-widest align-middle">{{ $t('common.total') }}</td>
                                            <td class="px-3 py-2.5 text-center font-black text-red-650 dark:text-red-400 font-mono text-sm align-middle" dir="ltr">{{ formatCurrencyEnglish(refundPaymentsTotal) }}</td>
                                            <td class="align-middle"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-5 text-xs text-gray-400 dark:text-gray-500 border border-dashed border-red-200 dark:border-red-900/40 rounded-xl">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $t('payments.no_payments') }}
                            </div>

                            <!-- Debit Note Option -->
                            <div v-if="calculatedRefundAmount > 0.001 && refundPaymentsTotal < calculatedRefundAmount" class="mt-4 pt-4 border-t border-red-200/40 dark:border-red-900/30">
                                <div class="flex items-center justify-between bg-red-50/50 dark:bg-red-950/10 p-4 rounded-xl border border-red-100 dark:border-red-900/30">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/40 flex items-center justify-center text-red-650 dark:text-red-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 dark:text-white">{{ $t('invoices.create_debit_note') }}</h5>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $t('invoices.debit_note_hint') }}</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="returnForm.create_debit_note" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                                    </label>
                                </div>

                                <!-- Debit Note Date -->
                                <div v-if="returnForm.create_debit_note" class="grid grid-cols-1 gap-4 mt-3 bg-white dark:bg-gray-800 p-4 rounded-xl border border-red-150 dark:border-red-900/20 shadow-sm animate-in fade-in slide-in-from-top-2 duration-300">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                                            {{ $t('invoices.debit_note_date') }} <span class="text-red-500">*</span>
                                        </label>
                                        <CustomDatePicker
                                            v-model="returnForm.debit_note_date"
                                            class="w-full"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-3">
                        <div></div>
                        <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="showReturnModal = false"
                            class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold transition-all"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="button"
                            @click="submitReturn"
                            :disabled="!isReturnFormValid"
                            class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-red-500/20 active:scale-95 disabled:opacity-50"
                        >
                            <svg v-if="returnForm.processing" class="animate-spin w-4 h-4 text-white inline-block mr-1.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ returnForm.processing ? $t('common.saving') : $t('invoices.confirm_return') }}</span>
                        </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refund Payment Modal (uses existing PurchaseOrderPaymentModal with type=refund) -->
            <PurchaseOrderPaymentModal
                :show="showRefundPaymentModal"
                :balance="calculatedRefundAmount - refundPaymentsTotal"
                default-type="refund"
                @close="showRefundPaymentModal = false"
                @saved="onRefundPaymentSaved"
            />

            <!-- Nested Submodal: Item Return Detail Form -->
            <div v-if="showItemReturnModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-xs" @click="showItemReturnModal = false"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-750">
                        <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-1.5 h-4 bg-red-600 rounded-full"></div>
                            {{ $t('invoices.set_return_details') }}
                        </h4>
                        <button @click="showItemReturnModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-5 space-y-4">
                        <div class="p-3 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-100/50 dark:border-amber-800/20 text-xs">
                            <div class="font-black text-amber-800 dark:text-amber-450">{{ selectedReturnLine?.part?.name_ar || selectedReturnLine?.part?.name }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ selectedReturnLine?.part?.sku || 'NO-SKU' }}</div>
                        </div>

                        <!-- Qty Input -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('invoices.qty_to_return') }}</label>
                                <span class="text-[10px] font-bold text-emerald-600">({{ $t('invoices.available') }}: {{ toEnglish(Number(getAvailableQty(selectedReturnLine)).toFixed(2)) }})</span>
                            </div>
                            <div class="relative flex flex-col items-center">
                                <input
                                    type="number"
                                    v-model="itemReturnQty"
                                    :max="getAvailableQty(selectedReturnLine)"
                                    min="0.01"
                                    step="0.01"
                                    class="w-full text-center font-bold py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                    dir="ltr"
                                />
                                <span v-if="itemReturnQty > getAvailableQty(selectedReturnLine)" class="text-[10px] font-bold text-red-500 mt-1.5">
                                    {{ $t('invoices.errors.qty_exceeds_available') }}
                                </span>
                            </div>
                        </div>

                        <!-- Cost Input (Editable!) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('invoices.return_unit_price') }}</label>
                                <span class="text-[9px] font-semibold text-amber-600 bg-amber-50 dark:bg-amber-950/20 px-1.5 py-0.5 rounded">{{ $t('invoices.editable_price') }}</span>
                            </div>
                            <input
                                type="number"
                                v-model="itemReturnPrice"
                                min="0"
                                step="0.01"
                                class="w-full text-center font-bold py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                dir="ltr"
                            />
                        </div>

                        <!-- Live Line Total Summary Box -->
                        <div class="p-3.5 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-150 dark:border-gray-700/60 space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-400">{{ $t('invoices.subtotal') }}</span>
                                <span class="font-bold text-gray-750 dark:text-gray-300 font-mono" dir="ltr">{{ formatCurrencyEnglish(isLineInclusive(selectedReturnLine) ? ((itemReturnQty * itemReturnPrice) / (1 + ((selectedReturnLine?.tax_rate || 0)/100))) : (itemReturnQty * itemReturnPrice)) }}</span>
                            </div>
                            <div v-if="(selectedReturnLine?.tax_rate || 0) > 0" class="flex justify-between">
                                <span class="text-gray-400">{{ $t('common.vat_with_rate', { rate: selectedReturnLine?.tax_rate || 0 }) }} <span v-if="isLineInclusive(selectedReturnLine)" class="text-[10px] text-gray-500">({{ $t('common.vat_included') }})</span></span>
                                <span class="font-bold text-gray-750 dark:text-gray-300 font-mono" dir="ltr">{{ formatCurrencyEnglish(isLineInclusive(selectedReturnLine) ? ((itemReturnQty * itemReturnPrice) - ((itemReturnQty * itemReturnPrice) / (1 + ((selectedReturnLine?.tax_rate || 0)/100)))) : ((itemReturnQty * itemReturnPrice) * ((selectedReturnLine?.tax_rate || 0) / 100))) }}</span>
                            </div>
                            <div class="h-px bg-gray-200 dark:bg-gray-700 my-1"></div>
                            <div class="flex justify-between text-sm">
                                <span class="font-black text-red-650">{{ $t('common.total') }}</span>
                                <span class="font-black text-red-650 font-mono" dir="ltr">{{ formatCurrencyEnglish(isLineInclusive(selectedReturnLine) ? (itemReturnQty * itemReturnPrice) : ((itemReturnQty * itemReturnPrice) * (1 + (selectedReturnLine?.tax_rate || 0) / 100))) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-150 dark:border-gray-700 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="showItemReturnModal = false"
                            class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold transition-all"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="button"
                            @click="addItemToReturn"
                            :disabled="itemReturnQty <= 0 || itemReturnQty > getAvailableQty(selectedReturnLine)"
                            class="px-5 py-2 bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-red-500/10 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ $t('common.add') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Modal Component -->
            <PurchaseOrderPaymentModal
                v-if="showPaymentModal"
                :show="showPaymentModal"
                :balance="Number(invoice.balance)"
                :default-type="paymentModalType"
                @close="showPaymentModal = false"
                @saved="onPaymentSaved"
            />

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import PurchaseOrderPaymentModal from '@/Components/Purchasing/PurchaseOrderPaymentModal.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    invoice: Object,
});

const { t, locale } = useI18n();
const page = usePage();
const isRtl = computed(() => locale.value === 'ar');
const { toEnglish, formatCurrency } = useNumberFormat();

const tenant = computed(() => props.invoice.tenant || page.props.tenant || {});
const isCompany = computed(() => !!props.invoice.company_transaction);

const formatCurrencyEnglish = (amount) => {
    return Number(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' ' + t('common.currency_sar');
};

const activeTab = ref('received_items'); // 'received_items', 'payments', 'attachments'

const showPaymentModal = ref(false);
const paymentModalType = ref('payment'); // 'payment' or 'refund'

const onPaymentSaved = (paymentData) => {
    router.post(route('app.invoices.purchases.payments.store', props.invoice.id), paymentData, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        }
    });
};

// Form for uploading attachment
const uploadForm = useForm({
    attachment: null,
});

// Return Invoice State
const showReturnModal = ref(false);
const showItemReturnModal = ref(false);
const selectedReturnLine = ref(null);
const itemReturnQty = ref(1);
const itemReturnPrice = ref(0);

const returnForm = useForm({
    return_date: new Date().toISOString().substring(0, 10),
    notes: '',
    items: [],
    refund_payments: [],
    create_debit_note: false,
    debit_note_date: new Date().toISOString().substring(0, 10),
});

const availableReturnLines = computed(() => {
    if (!props.invoice?.lines) return [];
    return props.invoice.lines.filter(line => {
        const isAdded = returnForm.items.some(item => item.purchase_invoice_line_id === line.id);
        return !isAdded;
    });
});

const hasPayments = computed(() => {
    return props.invoice.payments?.some(p => p.type === 'payment') || false;
});

const getAvailableQty = (line) => {
    if (!line) return 0;
    const alreadyReturned = line.return_lines?.reduce((sum, r) => sum + Number(r.qty), 0) || 0;
    return Math.max(0, Number(line.qty) - alreadyReturned);
};

const hasReturnableItems = computed(() => {
    if (props.invoice.company_transaction) return false;
    if (!props.invoice?.lines) return false;
    return props.invoice.lines.some(line => getAvailableQty(line) > 0.001);
});

const openItemReturnModal = (line) => {
    selectedReturnLine.value = line;
    itemReturnQty.value = getAvailableQty(line);
    itemReturnPrice.value = Number(line.unit_cost);
    showItemReturnModal.value = true;
};

// Detection Logic: determine if original line was inclusive of tax
const isLineInclusive = (line) => {
    if (!line || !line.qty || !line.unit_cost) return false;
    const qty = Number(line.qty);
    const unitCost = Number(line.unit_cost);
    const total = Number(line.line_total_incl_tax || line.total);
    // If original total matches qty * unit_cost closely, it means the price was inclusive
    return Math.abs(total - (qty * unitCost)) < 0.05;
};

const calculatedInvoiceSubtotal = computed(() => {
    return Number(props.invoice?.subtotal || 0);
});

const calculatedInvoiceTaxAmount = computed(() => {
    return Number(props.invoice?.tax_amount || 0);
});

const calculatedInvoiceTotal = computed(() => {
    return Number(props.invoice?.total || 0);
});

const addItemToReturn = () => {
    if (itemReturnQty.value <= 0 || itemReturnQty.value > getAvailableQty(selectedReturnLine.value)) {
        return;
    }
    
    const existingIndex = returnForm.items.findIndex(item => item.purchase_invoice_line_id === selectedReturnLine.value.id);
    const isInclusive = isLineInclusive(selectedReturnLine.value);
    const qty = Number(itemReturnQty.value);
    const unitCost = Number(itemReturnPrice.value);
    const taxRate = Number(selectedReturnLine.value.tax_rate || 0);
    
    let taxAmount = 0;
    let total = 0;
    
    if (isInclusive) {
        total = qty * unitCost;
        const subtotal = total / (1 + (taxRate / 100));
        taxAmount = total - subtotal;
    } else {
        const subtotal = qty * unitCost;
        taxAmount = subtotal * (taxRate / 100);
        total = subtotal + taxAmount;
    }

    const itemData = {
        purchase_invoice_line_id: selectedReturnLine.value.id,
        qty: qty,
        unit_cost: unitCost,
        part_name: selectedReturnLine.value.part?.name_ar || selectedReturnLine.value.part?.name,
        max_qty: getAvailableQty(selectedReturnLine.value),
        tax_rate: taxRate,
        tax_amount: taxAmount,
        total: total,
        is_inclusive: isInclusive
    };

    if (existingIndex > -1) {
        returnForm.items[existingIndex] = itemData;
    } else {
        returnForm.items.push(itemData);
    }

    showItemReturnModal.value = false;
    selectedReturnLine.value = null;
};

const removeItemFromReturn = (index) => {
    returnForm.items.splice(index, 1);
};

const returnSubtotal = computed(() => {
    const raw = returnForm.items.reduce((sum, item) => {
        if (item.is_inclusive) {
            return sum + (item.total - item.tax_amount);
        }
        return sum + (item.qty * item.unit_cost);
    }, 0);
    return Math.round(raw * 100) / 100;
});

const returnTaxAmount = computed(() => {
    const raw = returnForm.items.reduce((sum, item) => sum + (item.tax_amount || 0), 0);
    return Math.round(raw * 100) / 100;
});

const returnTotal = computed(() => {
    const raw = returnForm.items.reduce((sum, item) => sum + (item.total || 0), 0);
    return Math.round(raw * 100) / 100;
});

const calculatedRefundAmount = computed(() => {
    const total = returnTotal.value;
    const balance = parseFloat(props.invoice.balance || 0);
    if (total > balance) {
        const raw = total - balance;
        return Math.round(raw * 100) / 100;
    }
    return 0;
});

// ── Refund payments helpers ───────────────────────────────────────────────────
const showRefundPaymentModal = ref(false);

const refundPaymentsTotal = computed(() => {
    const raw = returnForm.refund_payments.reduce((sum, rp) => sum + (Number(rp.amount) || 0), 0);
    return Math.round(raw * 100) / 100;
});

const isReturnFormValid = computed(() => {
    if (returnForm.items.length === 0) return false;
    if (returnForm.processing) return false;
    const refundAmt = calculatedRefundAmount.value;
    if (refundAmt > 0.001) {
        if (refundPaymentsTotal.value >= refundAmt - 0.01) {
            return true;
        }
        return !!returnForm.create_debit_note;
    }
    return true;
});

watch(showReturnModal, (val) => {
    if (val) {
        returnForm.reset();
        returnForm.return_date = new Date().toISOString().substring(0, 10);
        returnForm.create_debit_note = false;
        returnForm.debit_note_date = new Date().toISOString().substring(0, 10);
    }
});

const openRefundPaymentModal = () => {
    showRefundPaymentModal.value = true;
};

const onRefundPaymentSaved = (paymentData) => {
    returnForm.refund_payments.push({
        payment_method: paymentData.payment_method,
        amount: Number(paymentData.amount),
        payment_date: paymentData.payment_date,
        reference: paymentData.reference || '',
        notes: paymentData.notes || '',
    });
    showRefundPaymentModal.value = false;
};

const addRefundPaymentRow = () => {
    openRefundPaymentModal();
};

const removeRefundPaymentRow = (index) => {
    returnForm.refund_payments.splice(index, 1);
};

const submitReturn = () => {
    returnForm.post(route('app.invoices.purchases.returns.store', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showReturnModal.value = false;
            returnForm.reset();
        }
    });
};

// Form for deleting attachment
const deleteForm = useForm({});

const fileInput = ref(null);
const uploadError = ref(null);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Check size limit: 1MB = 1024 * 1024 bytes
    if (file.size > 1 * 1024 * 1024) {
        uploadError.value = t('common.errors.file_size_exceeded') || 'الحجم الأقصى للملف هو 1 ميجابايت';
        return;
    }

    // Check file type: pdf, jpg, jpeg, png
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        uploadError.value = t('common.errors.invalid_file_type') || 'يسمح فقط بملفات pdf, jpg, png';
        return;
    }

    uploadError.value = null;
    uploadForm.attachment = file;
    submitUpload();
};

const submitUpload = () => {
    uploadForm.post(route('app.invoices.purchases.attachment.store', props.invoice.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
        },
        onError: (errors) => {
            uploadError.value = errors.attachment;
        }
    });
};

const deleteAttachment = () => {
    if (confirm(t('common.confirm_delete') || 'هل أنت متأكد من حذف المرفق؟')) {
        deleteForm.delete(route('app.invoices.purchases.attachment.destroy', props.invoice.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const getCenterAddress = (center) => {
    if (!center || !center.address) return '';
    const addr = center.address;
    const parts = [
        addr.building_number ? `${t('common.building') || 'مبنى'} ${addr.building_number}` : '',
        addr.street ? `${t('common.street') || 'شارع'} ${addr.street}` : '',
        addr.district ? `${t('common.district') || 'حي'} ${addr.district}` : '',
        addr.city ? addr.city : '',
        addr.postal_code ? `${t('common.postal_code') || 'الرمز البريدي'} ${addr.postal_code}` : '',
    ].filter(Boolean);
    return parts.join('، ');
};

const statusClass = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
        draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};

const calculateRemainingRefund = (ret) => {
    const allRefunds = props.invoice.payments?.filter(p => p.type === 'refund') || [];
    const matchingRefunds = allRefunds.filter(p => 
        p.notes?.includes(ret.code) || p.reference?.includes(ret.code)
    );
    const refundsTotal = matchingRefunds.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    return Math.max(0, parseFloat(ret.total) - refundsTotal);
};
</script>
