<template>
    <AppLayout :title="`${$t('invoices.purchases.return_invoice')} #${returnInvoice.code}`">
        <div class="space-y-6 pb-20">
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
                    <Tooltip :content="$t('common.back')">
                        <Link
                            :href="route('app.invoices.purchases.index')"
                            class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-rose-200 transition-all duration-300 text-rose-600 group"
                        >
                            <svg class="w-5 h-5 rtl:rotate-180 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                    </Tooltip>
                </template>

                <template #icon>
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Original Invoice Link -->
                        <Tooltip :content="$t('invoices.original_invoice')">
                            <Link
                                :href="route('app.invoices.purchases.show', returnInvoice.purchase_invoice_id)"
                                class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 rounded-xl border border-amber-200/50 dark:border-amber-800/30 transition-all font-bold text-xs shadow-sm hover:shadow-md"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>#{{ returnInvoice.purchase_invoice?.code }}</span>
                            </Link>
                        </Tooltip>

                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- Print Button -->
                        <Tooltip :content="$t('common.print')">
                            <button
                                @click="printInvoice"
                                class="flex items-center gap-2 px-4 py-2 bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 hover:text-rose-600 dark:hover:text-rose-400 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-bold text-xs shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>{{ $t('common.print') }}</span>
                            </button>
                        </Tooltip>

                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- Status Badge -->
                        <span class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800">
                            {{ $t('invoices.return_invoice') }}
                        </span>
                    </div>
                </template>
            </PageHeader>

            <!-- Top Cards Section: Center/Supplier Info & Financial Cost Card -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Center & Supplier Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full relative group">
                    <!-- Card Decoration -->
                    <div class="absolute -top-12 -end-12 w-32 h-32 bg-rose-500/5 rounded-full blur-3xl group-hover:bg-rose-500/10 transition-colors"></div>

                    <!-- Center Name Header -->
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700 px-5 py-4">
                        <div class="flex items-center gap-3 text-rose-600">
                            <div class="p-2 bg-rose-50 dark:bg-rose-900/30 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('centers.title_singular') || 'المركز' }}</h3>
                        </div>
                        <span class="text-lg font-black text-gray-900 dark:text-white" dir="auto">
                            {{ returnInvoice.center?.name_ar || returnInvoice.center?.name }}
                        </span>
                    </div>

                    <!-- Middle Section: Center Address & Supplier Info -->
                    <div class="p-5 flex-1 flex flex-col justify-between gap-6 relative z-10">
                        <!-- Center Address -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('common.address') || 'العنوان' }}</p>
                            <div class="flex items-start gap-2.5 text-gray-600 dark:text-gray-300">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm font-semibold leading-relaxed" dir="auto">
                                    {{ getCenterAddress(returnInvoice.center) || $t('centers.no_address') || 'العنوان غير متوفر' }}
                                </span>
                            </div>
                        </div>

                        <!-- Divider line -->
                        <div class="border-t border-gray-100 dark:border-gray-700/65"></div>

                        <!-- Supplier Info -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">{{ $t('purchasing.orders.supplier') || 'المورد' }}</p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/20 text-rose-605 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div>
                                    <Link
                                        v-if="returnInvoice.purchase_invoice?.supplier_id"
                                        :href="route('app.purchasing.suppliers.show', returnInvoice.purchase_invoice.supplier_id)"
                                        class="text-base font-black text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/sup"
                                    >
                                        <span>{{ returnInvoice.purchase_invoice?.supplier?.name }}</span>
                                        <svg class="w-4 h-4 opacity-60 group-hover/sup:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </Link>
                                    <h4 v-else class="text-base font-bold text-gray-900 dark:text-white">—</h4>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span v-if="returnInvoice.purchase_invoice?.supplier?.phone" class="font-mono flex items-center gap-1">
                                            <span>📞</span> <span dir="ltr">{{ returnInvoice.purchase_invoice.supplier.phone }}</span>
                                        </span>
                                        <span v-if="returnInvoice.purchase_invoice?.supplier?.tax_number" class="font-mono flex items-center gap-1">
                                            <span>📋</span> <span>{{ $t('suppliers.tax_number') || 'الرقم الضريبي' }}: {{ returnInvoice.purchase_invoice.supplier.tax_number }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Left Card: Financial Cost and Refund Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col h-full">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <div class="w-2 h-5 bg-rose-500 rounded-full"></div>
                        {{ $t('invoices.refund_and_totals') || 'تفاصيل المبالغ المستردة' }}
                    </h3>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50 pb-2">
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider">{{ $t('common.description') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider">{{ $t('common.unit_price') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider text-rose-500 italic">{{ $t('common.amount') }}</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider italic">VAT (15%)</th>
                                    <th class="pb-2 text-center font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ $t('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                <!-- Returned elements row -->
                                <tr class="group">
                                    <td class="py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $t('invoices.purchases.tab_invoices') }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500">{{ formatCurrency(returnInvoice.subtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500">{{ formatCurrency(returnInvoice.subtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-gray-500">{{ formatCurrency(returnInvoice.tax_amount) }}</td>
                                    <td class="py-2.5 text-center font-black text-gray-900 dark:text-white font-mono text-base">{{ formatCurrency(returnInvoice.total) }}</td>
                                </tr>

                                <!-- Grand Total Row -->
                                <tr class="bg-rose-50 dark:bg-rose-900/10 font-black border-t-2 border-rose-200 dark:border-rose-800">
                                    <td class="py-2.5 text-center text-rose-900 dark:text-rose-450 font-black uppercase">{{ $t('invoices.grand_total') }}</td>
                                    <td class="py-2.5 text-center font-mono text-rose-700 dark:text-rose-300">{{ formatCurrency(returnInvoice.subtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-rose-700 dark:text-rose-300">{{ formatCurrency(returnInvoice.subtotal) }}</td>
                                    <td class="py-2.5 text-center font-mono text-rose-700 dark:text-rose-300">{{ formatCurrency(returnInvoice.tax_amount) }}</td>
                                    <td class="py-2.5 text-center font-black text-rose-600 dark:text-rose-400 font-mono text-xl">{{ formatCurrency(returnInvoice.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notes Section (If Notes are present) -->
            <div v-if="returnInvoice.notes" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3 text-slate-400 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('common.notes') || 'ملاحظات' }}</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-semibold italic">
                    {{ returnInvoice.notes }}
                </p>
            </div>

            <!-- Tabs Section (العناصر المرجعة - المدفوعات - المرفقات) -->
            <div class="flex items-center gap-2 p-1.5 bg-gray-100 dark:bg-gray-900/50 rounded-2xl w-fit">
                <button
                    @click="activeTab = 'returned_items'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'returned_items'
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>{{ $t('invoices.returned_items') || 'العناصر المرجعة' }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'returned_items' ? 'bg-white/20 text-white' : 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400'
                        ]"
                    >
                        {{ returnInvoice.lines?.length || 0 }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'payments'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'payments'
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $t('invoices.payments_history') || 'سجل الدفعات' }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-xs rounded-full font-bold',
                            activeTab === 'payments' ? 'bg-white/20 text-white' : 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400'
                        ]"
                    >
                        {{ refundPayments.length }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'attachments'"
                    :class="[
                        'px-5 py-2.5 text-sm font-black rounded-xl transition-all flex items-center gap-2',
                        activeTab === 'attachments'
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-500/20'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span>{{ $t('invoices.attachments') || 'المرفقات' }}</span>
                    <span
                        v-if="returnInvoice.attachment_path || returnInvoice.purchase_invoice?.attachment_path"
                        class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"
                    ></span>
                </button>
            </div>

            <!-- Tab 1: Returned Items Content -->
            <div v-show="activeTab === 'returned_items'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-rose-500 rounded-full"></div>
                        {{ $t('invoices.returned_items') || 'القطع المسترجعة في هذه الفاتورة' }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/50">
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('inventory.parts.title_singular') || 'القطعة' }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('work_orders.price') || 'السعر' }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-red-500 italic">{{ $t('work_orders.discount') || 'الخصم' }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('invoices.net_unit_price') || 'صافي السعر/الوحدة' }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('invoices.returned_qty') || 'الكمية المرجعة' }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('invoices.subtotal') || 'المجموع الفرعي' }}</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest italic">VAT</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-gray-900 dark:text-white">{{ $t('common.total') || 'الإجمالي' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr v-for="line in returnInvoice.lines" :key="line.id" class="group hover:bg-rose-50/30 dark:hover:bg-rose-900/10 transition-colors">
                                <!-- Part Name -->
                                <td class="px-6 py-5 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <Link
                                            v-if="line.part_id"
                                            :href="route('app.inventory.parts.show', line.part_id)"
                                            class="text-sm font-black text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 hover:underline transition-colors flex items-center gap-1.5 w-fit group/link"
                                        >
                                            <span>{{ line.part?.name || '—' }}</span>
                                            <svg class="w-3.5 h-3.5 opacity-60 group-hover/link:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </Link>
                                        <span v-else class="text-sm font-bold text-gray-950 dark:text-white">—</span>
                                        <span class="text-[10px] text-gray-400 font-mono mt-1 uppercase tracking-wider">{{ line.part?.sku || 'NO-SKU' }}</span>
                                    </div>
                                </td>
                                
                                <!-- Unit Cost -->
                                <td class="px-6 py-5 text-center">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_cost) }}</span>
                                </td>
                                
                                <!-- Discount -->
                                <td class="px-6 py-5 text-center text-red-500 italic">
                                    <span class="text-sm font-black font-mono">0.00</span>
                                </td>
                                
                                <!-- Net Unit Price -->
                                <td class="px-6 py-5 text-center">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_cost) }}</span>
                                </td>
                                
                                <!-- Returned Qty -->
                                <td class="px-6 py-5 text-center">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ toEnglish(line.qty) }}</span>
                                </td>
                                
                                <!-- Subtotal -->
                                <td class="px-6 py-5 text-center">
                                    <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.qty * line.unit_cost) }}</span>
                                </td>
                                
                                <!-- VAT -->
                                <td class="px-6 py-5 text-center text-xs text-sky-600 dark:text-sky-400 font-mono">
                                    {{ formatCurrency(line.tax_amount) }} <span class="text-[10px] text-gray-400">({{ toEnglish(line.tax_rate) }}%)</span>
                                </td>
                                
                                <!-- Total -->
                                <td class="px-6 py-5 text-center">
                                    <span class="text-base font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(line.total) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Payments Content -->
            <div v-show="activeTab === 'payments'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/50">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                        {{ $t('invoices.payments_history') || 'سجل الدفعات المستردة' }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50">
                                <th class="py-3 px-4 text-center font-bold">#</th>
                                <th class="py-3 px-4 text-center font-bold">{{ $t('payments.form.type') || 'نوع العملية' }}</th>
                                <th class="py-3 px-4 text-center font-bold">{{ $t('payments.form.date') || 'تاريخ الدفع' }}</th>
                                <th class="py-3 px-4 text-center font-bold">{{ $t('payments.form.notes') || 'التفاصيل' }}</th>
                                <th class="py-3 px-4 text-center font-bold">{{ $t('payments.form.amount') || 'المبلغ' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                            <template v-if="refundPayments && refundPayments.length > 0">
                                <tr v-for="(payment, index) in refundPayments" :key="payment.id" class="hover:bg-rose-50/30 dark:hover:bg-rose-900/10 transition-colors">
                                    <td class="py-4 px-4 text-center text-gray-500 font-bold font-mono">{{ toEnglish(index + 1) }}</td>
                                    <td class="py-4 px-4 text-center font-bold">
                                        <span class="text-red-650 bg-red-50 dark:bg-red-950/30 px-2.5 py-1 rounded-lg text-xs">
                                            {{ $t('payments.types.refund') || 'استرجاع ائتماني' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono" dir="ltr">
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate" :title="payment.notes">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t(`payments.methods.${payment.payment_method}`) || payment.payment_method }}</span>
                                            <span class="text-[10px]">{{ payment.notes || '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center font-black text-red-650 dark:text-red-400 font-mono" dir="ltr">
                                        -{{ formatCurrency(payment.amount) }}
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="5" class="py-8 text-center text-gray-400 font-bold">
                                    {{ $t('payments.no_refunds') || 'لا توجد دفعات مستردة مسجلة.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Attachments Content -->
            <div v-show="activeTab === 'attachments'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6 space-y-6">
                <!-- Return Invoice Attachment Section -->
                <div>
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-1.5 h-4 bg-rose-500 rounded-full"></div>
                            {{ $t('invoices.return_attachment') || 'مرفق فاتورة الارتجاع' }}
                        </h3>
                        <span v-if="uploadError" class="text-xs text-red-500 font-bold">{{ uploadError }}</span>
                    </div>

                    <!-- Return Invoice Attachment Details View -->
                    <div v-if="returnInvoice.attachment_path" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-inner">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/20 text-rose-600 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $t('common.invoice_attachment') || 'مستند الإرجاع' }}</h4>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono select-all">{{ returnInvoice.attachment_path.split('/').pop() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                :href="`/storage/${returnInvoice.attachment_path}`"
                                target="_blank"
                                download
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-rose-500/10 shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>{{ $t('common.download') || 'تحميل' }}</span>
                            </a>
                            <button
                                @click="deleteAttachment"
                                :disabled="deleteForm.processing"
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-650 dark:bg-red-950/20 dark:hover:bg-red-950/40 rounded-xl text-sm font-bold transition-all shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>{{ deleteForm.processing ? ($t('common.deleting') || 'جاري الحذف...') : ($t('common.delete') || 'حذف') }}</span>
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
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $t('common.no_attachments') || 'لا يوجد مرفق للمرتجع' }}</h3>
                        <p class="text-xs text-gray-400 mt-1 max-w-sm mb-4">{{ $t('common.no_return_attachments_desc') || 'يمكنك رفع مستند أو صورة إيصال الاستلام الخاص بالارتجاع هنا.' }}</p>
                        
                        <!-- Hidden File Input -->
                        <input
                            type="file"
                            id="return-attachment-input"
                            @change="handleFileChange"
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png"
                        />

                        <!-- Upload Trigger Button -->
                        <button
                            @click="triggerFileInput"
                            :disabled="uploadForm.processing"
                            class="flex items-center justify-center gap-2 px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-rose-500/25 active:scale-95 disabled:opacity-50"
                        >
                            <svg v-if="uploadForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>{{ uploadForm.processing ? ($t('common.uploading') || 'جاري الرفع...') : ($t('common.upload_attachment') || 'رفع مرفق المرتجع') }}</span>
                        </button>
                        
                        <!-- Hints -->
                        <p class="text-[10px] text-gray-400 mt-2">
                            {{ $t('common.attachment_limits_hint') || 'يُسمح برفع ملفات PDF, JPG, PNG بحجم أقصى 1 ميجابايت.' }}
                        </p>
                    </div>
                </div>

                <!-- Original Purchase Invoice Attachment Reference Section -->
                <div v-if="returnInvoice.purchase_invoice?.attachment_path" class="pt-4 border-t border-gray-100 dark:border-gray-700/65">
                    <div class="mb-3">
                        <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                            <span class="text-gray-400">💡</span>
                            {{ $t('invoices.original_attachment_ref') || 'مرجع: مرفق فاتورة الشراء الأصلية' }}
                        </h3>
                    </div>
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-700/40 text-xs">
                        <span class="text-gray-600 dark:text-gray-400 font-semibold">{{ returnInvoice.purchase_invoice.attachment_path.split('/').pop() }}</span>
                        <a
                            :href="`/storage/${returnInvoice.purchase_invoice.attachment_path}`"
                            target="_blank"
                            download
                            class="text-rose-600 hover:text-rose-700 font-bold hover:underline transition-colors flex items-center gap-1"
                        >
                            <span>{{ $t('common.download') || 'تحميل المرفق الأصلي' }}</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    returnInvoice: Object,
});

const { t, locale } = useI18n();
const { toEnglish } = useNumberFormat();

const activeTab = ref('returned_items');

const uploadForm = useForm({
    attachment: null,
});

const deleteForm = useForm({});
const uploadError = ref(null);

const triggerFileInput = () => {
    document.getElementById('return-attachment-input').click();
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Check file size: max 1MB
    if (file.size > 1024 * 1024) {
        uploadError.value = t('common.errors.file_too_large') || 'حجم الملف يجب أن يكون أقل من 1 ميجابايت';
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
    uploadForm.post(route('app.invoices.purchases.returns.attachment.store', props.returnInvoice.id), {
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
        deleteForm.delete(route('app.invoices.purchases.returns.attachment.destroy', props.returnInvoice.id), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (val) => {
    const num = parseFloat(val) || 0;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + (locale.value === 'ar' ? 'ر.س' : 'SAR');
};

const getCenterAddress = (center) => {
    if (!center) return '';
    const isRtl = locale.value === 'ar';
    const parts = [
        center.address?.address_line,
        center.address?.building_number,
        center.address?.district,
        center.address?.city,
        center.address?.region,
        center.address?.postal_code,
    ].filter(Boolean);
    return parts.join(isRtl ? '، ' : ', ');
};

const printInvoice = () => {
    window.print();
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
    const allRefunds = props.returnInvoice.purchase_invoice?.payments?.filter(p => p.type === 'refund') || [];
    const codeMatch = allRefunds.filter(p => p.notes?.includes(props.returnInvoice.code));
    return codeMatch.length > 0 ? codeMatch : allRefunds;
});
</script>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    .space-y-6, .space-y-6 * {
        visibility: visible;
    }
    .space-y-6 {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    button, a, Link {
        display: none !important;
    }
}
</style>
