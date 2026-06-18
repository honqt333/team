<template>
    <AppLayout :title="`${$t('invoices.invoice')} #${invoice.invoice_number}`">
        <div class="space-y-6 pb-20">
            <!-- Premium Glassmorphic Header -->
            <PageHeader
                :title="`${$t('invoices.invoice')} #${invoice.invoice_number}`"
                :subtitle="invoice.customer_name_snapshot || invoice.customer?.name"
                :totalCount="formatCurrency(invoice.total_incl_tax)"
                :countLabel="$t('invoices.grand_total')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-600"
                glowFrom="from-blue-500"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #back>
                    <BackButton :href="route('app.invoices.sales.index')" />
                </template>

                <template #icon>
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Print Button -->
                        <Tooltip :content="$t('common.print')">
                            <a
                                :href="route('app.invoices.print', invoice.id)"
                                target="_blank"
                                class="flex items-center gap-2 px-4 py-2 bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-bold text-xs shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>{{ $t('common.print') }}</span>
                            </a>
                        </Tooltip>

                        <!-- Add Payment Button -->
                        <Tooltip v-if="balance > 0.01" :content="$t('payments.add_payment')">
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
                        <span :class="statusClass(invoice.payment_status)" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm">
                            {{ $t(`invoices.status.${invoice.payment_status}`) }}
                        </span>
                    </div>
                </template>
            </PageHeader>

            <!-- Top Cards Section: Center/Customer Info & Financial Cost Card -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Center & Customer Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full relative group">
                    <!-- Card Decoration -->
                    <div class="absolute -top-12 -end-12 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>

                    <!-- Center Name Header -->
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700 px-5 py-4">
                        <div class="flex items-center gap-3 text-blue-600">
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('common.center') }}</h3>
                        </div>
                        <span class="text-lg font-black text-gray-900 dark:text-white" dir="auto">
                            {{ invoice.center?.name_ar || invoice.center?.name }}
                        </span>
                    </div>

                    <!-- Middle Section: Center Address, Invoice Meta & Customer Info -->
                    <div class="p-5 flex-1 flex flex-col justify-between gap-5 relative z-10">
                        <!-- Center Address -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('common.address') }}</p>
                            <div class="flex items-start gap-2.5 text-gray-600 dark:text-gray-300">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm font-semibold leading-relaxed" dir="auto">
                                    {{ getCenterAddress(invoice.center) || $t('common.na') }}
                                </span>
                            </div>
                        </div>

                        <!-- Divider line -->
                        <div class="border-t border-gray-100 dark:border-gray-700/65"></div>

                        <!-- Invoice Dates & ZATCA QR Code -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('invoices.details') }}</p>
                            <div class="flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-100/50 dark:border-gray-700/30">
                                <div class="grid grid-cols-2 gap-x-6 gap-y-3 flex-1">
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">{{ $t('invoices.date') }}</p>
                                        <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm font-bold font-mono">{{ formatDate(invoice.issue_date) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">{{ $t('invoices.supply_date') }}</p>
                                        <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm font-bold font-mono">{{ formatDate(invoice.supply_date) }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- QR Code block for ZATCA E-Invoice -->
                                <div v-if="invoice.zatca_qr_tlv" class="shrink-0 p-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition-all group-hover:scale-105 duration-300">
                                    <img :src="generateQRCode(invoice.zatca_qr_tlv)" alt="ZATCA QR" class="w-14 h-14 object-contain" />
                                </div>
                            </div>
                        </div>

                        <!-- Divider line -->
                        <div class="border-t border-gray-100 dark:border-gray-700/65"></div>

                        <!-- Customer Info -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('customers.customer') }}</p>
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/20 text-blue-600 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <Link
                                        v-if="invoice.customer_id"
                                        :href="route('customers.show', invoice.customer_id)"
                                        class="text-base font-black text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/sup"
                                    >
                                        <span>{{ invoice.customer_name_snapshot || invoice.customer?.name }}</span>
                                        <svg class="w-4 h-4 opacity-60 group-hover/sup:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </Link>
                                    <h4 v-else class="text-base font-bold text-gray-900 dark:text-white">—</h4>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span v-if="invoice.customer?.phone" class="font-mono flex items-center gap-1">
                                            <span>📞</span> <span dir="ltr">{{ invoice.customer.phone }}</span>
                                        </span>
                                        <span v-if="invoice.customer_vat_snapshot || invoice.customer?.tax_number" class="font-mono flex items-center gap-1">
                                            <span>📋</span> <span>{{ $t('customers.tax_number') }}: {{ invoice.customer_vat_snapshot || invoice.customer?.tax_number }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customer Address sub-block -->
                            <div class="flex items-start gap-2.5 text-xs text-gray-600 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-150/50 dark:border-gray-700/30 shadow-inner">
                                <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div class="space-y-0.5">
                                    <p class="font-bold text-[10px] uppercase text-gray-400 dark:text-gray-500">{{ $t('customers.address') }}</p>
                                    <p class="font-semibold leading-relaxed" dir="auto">
                                        {{ invoice.customer_address_snapshot || getCustomerAddress(invoice.customer) || $t('customers.no_address') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Optional Work Order & Vehicle Info Link -->
                        <div v-if="invoice.work_order" class="mt-2 pt-4 border-t border-gray-100 dark:border-gray-700/65">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('work_orders.title_singular') }}</p>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <Link
                                    :href="route('work-orders.show', invoice.work_order_id)"
                                    class="text-sm font-black text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline transition-colors flex items-center gap-1.5 group/wo"
                                >
                                    <span>#{{ invoice.work_order?.code || invoice.work_order?.id }}</span>
                                    <svg class="w-4 h-4 opacity-60 group-hover/wo:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </Link>
                                <span v-if="invoice.work_order?.vehicle" class="text-xs text-gray-500 dark:text-gray-400 font-semibold flex items-center gap-1.5">
                                    <span>🚗</span>
                                    <span>{{ invoice.work_order.vehicle.display_make }} {{ invoice.work_order.vehicle.display_model }}</span>
                                    <SaudiPlateDisplay :plate-number="invoice.work_order.vehicle.plate_number" size="sm" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Left Card: Financial Cost and Payment Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col h-full">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <div class="w-2 h-5 bg-blue-500 rounded-full"></div>
                        {{ $t('work_orders.cost_and_payment') }}
                    </h3>

                    <div class="overflow-x-auto md:overflow-x-visible flex-1">
                        <table class="w-full min-w-[600px] md:min-w-0 text-sm">
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
                                <!-- Services Row -->
                                <tr v-if="servicesSubtotal > 0" class="group">
                                    <td class="py-2.5 text-center font-bold text-gray-900 dark:text-white align-middle">{{ $t('work_orders.services') }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(servicesGross) }}</td>
                                    <td class="py-2.5 text-center font-mono text-red-500 italic align-middle">{{ servicesDiscount > 0 ? '-' + formatCurrency(servicesDiscount) : '—' }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(servicesSubtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(servicesTax) }}</td>
                                    <td class="py-2.5 text-center font-black text-gray-900 dark:text-white font-mono text-base align-middle">{{ formatCurrency(servicesTotal) }}</td>
                                </tr>

                                <!-- Spare Parts Row -->
                                <tr v-if="partsSubtotal > 0" class="group">
                                    <td class="py-2.5 text-center font-bold text-gray-900 dark:text-white align-middle">{{ $t('quotes.show.tabs.spare_parts') }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(partsGross) }}</td>
                                    <td class="py-2.5 text-center font-mono text-red-500 italic align-middle">{{ partsDiscountAmt > 0 ? '-' + formatCurrency(partsDiscountAmt) : '—' }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(partsSubtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(partsTax) }}</td>
                                    <td class="py-2.5 text-center font-black text-gray-900 dark:text-white font-mono text-base align-middle">{{ formatCurrency(partsTotal) }}</td>
                                </tr>

                                <!-- Fallback row if no work_order lines breakdown -->
                                <tr v-if="servicesSubtotal === 0 && partsSubtotal === 0" class="group">
                                    <td class="py-2.5 text-center font-bold text-gray-900 dark:text-white align-middle">{{ $t('invoices.invoice') }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(grossTotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-red-500 italic align-middle">{{ discount > 0 ? '-' + formatCurrency(discount) : '—' }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(calculatedInvoiceSubtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500 align-middle">{{ formatCurrency(calculatedInvoiceTaxAmount) }}</td>
                                    <td class="py-2.5 text-center font-black text-gray-900 dark:text-white font-mono text-base align-middle">{{ formatCurrency(calculatedInvoiceTotal) }}</td>
                                </tr>

                                <!-- Paid Row -->
                                <tr class="group text-emerald-600 dark:text-emerald-400">
                                    <td class="py-2.5 text-center font-bold align-middle">{{ $t('invoices.paid') }}</td>
                                    <td colspan="4" class="py-2.5 align-middle"></td>
                                    <td class="py-2.5 text-center font-black font-mono text-base align-middle">{{ formatCurrency(invoice.total_paid) }}</td>
                                </tr>

                                <!-- Balance Row -->
                                <tr class="group" :class="balance > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                                    <td class="py-2.5 text-center font-bold align-middle">{{ $t('invoices.balance') }}</td>
                                    <td colspan="4" class="py-2.5 align-middle"></td>
                                    <td class="py-2.5 text-center font-black font-mono text-base align-middle">{{ formatCurrency(balance) }}</td>
                                </tr>

                                <!-- Grand Total Summary Row -->
                                <tr class="bg-blue-50 dark:bg-blue-900/10 font-black border-t-2 border-blue-200 dark:border-blue-800">
                                    <td class="py-2.5 text-center text-blue-900 dark:text-blue-400 font-black uppercase align-middle">{{ $t('invoices.grand_total') }}</td>
                                    <td class="py-2.5 text-center font-mono text-blue-700 dark:text-blue-300 align-middle">{{ formatCurrency(grossTotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-red-600 italic align-middle">{{ discount > 0 ? '-' + formatCurrency(discount) : '—' }}</td>
                                    <td class="py-2.5 text-center font-mono text-blue-700 dark:text-blue-300 align-middle">{{ formatCurrency(calculatedInvoiceSubtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-blue-700 dark:text-blue-300 align-middle">{{ formatCurrency(calculatedInvoiceTaxAmount) }}</td>
                                    <td class="py-2.5 text-center font-black text-blue-600 dark:text-blue-400 font-mono text-xl align-middle">{{ formatCurrency(calculatedInvoiceTotal) }}</td>
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
                    {{ invoice.notes }}
                </p>
            </div>

            <!-- Tabs Section Selector -->
            <div class="flex items-center bg-white dark:bg-gray-800 p-2 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm gap-1 w-fit">
                <button
                    @click="activeTab = 'invoice_items'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'invoice_items'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ $t('common.details') }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'invoice_items' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                        ]"
                    >
                        {{ invoice.lines?.length || 0 }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'payments'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'payments'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $t('invoices.payments_history') }}</span>
                </button>
            </div>

            <!-- Elements Sold Tab -->
            <div v-show="activeTab === 'invoice_items'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/50">
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('common.description') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('common.qty') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('common.unit_price') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest align-middle">{{ $t('invoices.subtotal') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest italic align-middle">{{ $t('common.vat') }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-gray-900 dark:text-white align-middle">{{ $t('common.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr v-for="line in invoice.lines" :key="line.id" class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors">
                                <!-- Line description -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-950 dark:text-white">{{ line.description }}</span>
                                </td>
                                
                                <!-- Qty -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ toEnglish(line.qty) }}</span>
                                </td>
                                
                                <!-- Unit Price -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_price) }}</span>
                                </td>
                                
                                <!-- Subtotal -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.qty * line.unit_price) }}</span>
                                </td>
                                
                                <!-- VAT -->
                                <td class="px-6 py-5 text-center text-xs text-sky-600 dark:text-sky-400 font-mono align-middle">
                                    {{ formatCurrency(line.line_total_incl_tax - (line.qty * line.unit_price)) }}
                                </td>
                                
                                <!-- Total -->
                                <td class="px-6 py-5 text-center align-middle">
                                    <span class="text-base font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(line.line_total_incl_tax) }}</span>
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
                            v-if="balance > 0.01"
                            @click="showPaymentModal = true"
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
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50">
                                <th class="py-3 px-4 text-center font-bold align-middle">#</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.method') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.type') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.date') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.notes') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.form.amount') }}</th>
                                <th class="py-3 px-4 text-center font-bold align-middle">{{ $t('payments.recorded_by') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                            <!-- Show payment details -->
                            <template v-if="invoice.payments && invoice.payments.length > 0">
                                <tr v-for="(payment, index) in invoice.payments" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-4 px-4 text-center text-gray-500 font-bold font-mono align-middle">{{ toEnglish(index + 1) }}</td>
                                    <td class="py-4 px-4 text-center font-bold text-gray-700 dark:text-gray-300 align-middle">
                                        {{ $t(`payments.methods.${payment.payment_method}`) || payment.payment_method }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-bold align-middle">
                                        <span :class="payment.type === 'refund' ? 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30' : 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'" class="px-2.5 py-1 rounded-lg text-xs font-bold">
                                            {{ payment.type === 'refund' ? $t('payments.types.refund') : $t('payments.types.payment') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono align-middle" dir="ltr">
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate align-middle" :title="payment.notes || payment.reference">
                                        {{ payment.notes || payment.reference || '—' }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle" dir="ltr">
                                        {{ formatCurrencyEnglish(payment.amount) }}
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-semibold align-middle">
                                        {{ payment.received_by?.name || '—' }}
                                    </td>
                                </tr>
                            </template>
                            
                            <!-- Display empty state row inside the tbody if there is no payment -->
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
                                            v-if="balance > 0.01"
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

            <!-- Payment Modal Component -->
            <PurchaseOrderPaymentModal
                v-if="showPaymentModal"
                :show="showPaymentModal"
                :balance="balance"
                default-type="payment"
                @close="showPaymentModal = false"
                @saved="onPaymentSaved"
            />

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import PurchaseOrderPaymentModal from '@/Components/Purchasing/PurchaseOrderPaymentModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import Tooltip from '@/Components/Tooltip.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const props = defineProps({
    invoice: Object,
});

const { t } = useI18n();
const { confirm } = useConfirm();
const { toEnglish, formatCurrency } = useNumberFormat();

const formatCurrencyEnglish = (amount) => {
    return Number(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' ' + t('common.currency_sar');
};

const activeTab = ref('invoice_items'); // 'invoice_items', 'payments'
const showPaymentModal = ref(false);

const balance = computed(() => {
    return Math.max(0, Number(props.invoice.total_incl_tax || 0) - Number(props.invoice.total_paid || 0));
});

const calculatedInvoiceSubtotal = computed(() => {
    return Number(props.invoice?.total_excl_tax || 0);
});

const calculatedInvoiceTaxAmount = computed(() => {
    return Number(props.invoice?.total_tax || 0);
});

const calculatedInvoiceTotal = computed(() => {
    return Number(props.invoice?.total_incl_tax || 0);
});

const discount = computed(() => {
    if (!props.invoice.work_order) return 0;
    const itemsDiscount = props.invoice.work_order.items?.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0) || 0;
    const partsDiscount = props.invoice.work_order.parts?.reduce((sum, part) => sum + Number(part.discount || 0), 0) || 0;
    return itemsDiscount + partsDiscount;
});

const grossTotal = computed(() => {
    return calculatedInvoiceSubtotal.value + discount.value;
});

// ── Services breakdown ──────────────────────────────────────
const servicesDiscount = computed(() => {
    if (!props.invoice.work_order) return 0;
    return props.invoice.work_order.items?.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0) || 0;
});

const servicesSubtotal = computed(() => {
    return (props.invoice.lines || [])
        .filter(l => !l.is_part)
        .reduce((sum, l) => sum + Number(l.line_total_excl_tax || 0), 0);
});

const servicesGross = computed(() => servicesSubtotal.value + servicesDiscount.value);

const servicesTax = computed(() => {
    return (props.invoice.lines || [])
        .filter(l => !l.is_part)
        .reduce((sum, l) => sum + Number(l.tax_amount || 0), 0);
});

const servicesTotal = computed(() => servicesSubtotal.value + servicesTax.value);

// ── Spare Parts breakdown ───────────────────────────────────
const partsDiscountAmt = computed(() => {
    if (!props.invoice.work_order) return 0;
    return props.invoice.work_order.parts?.reduce((sum, part) => sum + Number(part.discount || 0), 0) || 0;
});

const partsSubtotal = computed(() => {
    return (props.invoice.lines || [])
        .filter(l => l.is_part)
        .reduce((sum, l) => sum + Number(l.line_total_excl_tax || 0), 0);
});

const partsGross = computed(() => partsSubtotal.value + partsDiscountAmt.value);

const partsTax = computed(() => {
    return (props.invoice.lines || [])
        .filter(l => l.is_part)
        .reduce((sum, l) => sum + Number(l.tax_amount || 0), 0);
});

const partsTotal = computed(() => partsSubtotal.value + partsTax.value);


const onPaymentSaved = (paymentData) => {
    router.post(route('app.invoices.payments.store', props.invoice.id), paymentData, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        }
    });
};


const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const generateQRCode = (base64Data) => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(base64Data)}`;
};

const getCustomerAddress = (customer) => {
    if (!customer) return '';
    const parts = [
        customer.building_number ? `${t('common.building') || 'مبنى'} ${customer.building_number}` : '',
        customer.address_line ? customer.address_line : '',
        customer.district ? `${t('common.district') || 'حي'} ${customer.district}` : '',
        customer.city ? customer.city : '',
        customer.region ? customer.region : '',
        customer.postal_code ? `${t('common.postal_code') || 'الرمز البريدي'} ${customer.postal_code}` : '',
    ].filter(Boolean);
    return parts.join('، ');
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
        partial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
};
</script>
