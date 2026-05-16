<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Action Buttons (Left) -->
                <div class="flex flex-wrap items-center gap-3">
                    <BackButton :href="route('app.purchasing.orders.index')" />

                    <div class="flex items-center gap-1.5 p-1.5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                        <!-- Print -->
                        <button @click="printOrder"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                            :title="$t('common.print')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>

                        <!-- Cancel -->
                        <button v-if="canCancel" @click="cancelOrder"
                            class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all"
                            :title="$t('common.cancel')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Send PO (if draft) -->
                    <button v-if="order.status === 'draft' && order.items?.length > 0" @click="sendOrder"
                        class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <span>{{ $t('purchasing.orders.actions.send') }}</span>
                    </button>

                    <!-- Receive Goods -->
                    <button v-if="canReceive" @click="showGrnModal = true"
                        class="flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white rounded-2xl font-bold shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $t('purchasing.orders.actions.receive') }}</span>
                    </button>
                </div>

                <!-- Right side: Code and Status -->
                <div class="flex items-center gap-4">
                    <div class="text-end">
                        <div class="flex items-center gap-3">
                            <span :class="statusBadgeClass" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest">{{ $t(`purchasing.orders.statuses.${order.status}`) }}</span>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight font-mono">{{ order.code }}</h1>
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 shadow-xl shadow-indigo-500/30 flex items-center justify-center text-white shrink-0">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Section: Supplier Card -->
            <div>
                <!-- Supplier Info -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-indigo-500/5 border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Supplier Icon/Logo -->
                            <div class="shrink-0">
                                <div class="w-24 h-24 rounded-3xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 shadow-sm">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Supplier Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div>
                                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-1">
                                            {{ order.supplier?.name }}
                                        </h3>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-bold font-mono">
                                                {{ order.supplier?.code }}
                                            </span>
                                            <span v-if="order.warehouse" class="text-xs text-gray-400 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                {{ order.warehouse.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Logistics Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <!-- Order Date -->
                                    <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('purchasing.orders.date') }}</div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ formatDate(order.order_date) }}</div>
                                    </div>

                                    <!-- Expected Date -->
                                    <div v-if="order.expected_date" class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                                        <div class="text-[10px] font-black text-orange-500 uppercase tracking-widest mb-1">{{ $t('purchasing.orders.expected_date') }}</div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ formatDate(order.expected_date) }}</div>
                                    </div>

                                    <!-- Credit Invoice Info -->
                                    <div v-if="order.create_credit_invoice" class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                                        <div class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-1">{{ $t('purchasing.orders.create_credit_invoice') }}</div>
                                        <div class="text-sm font-bold text-indigo-700 dark:text-indigo-300 font-mono">
                                            {{ order.due_date ? formatDate(order.due_date) : $t('common.active') }}
                                        </div>
                                    </div>

                                    <!-- Sent By -->
                                    <div v-if="order.sent_at" class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                                        <div class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">{{ $t('purchasing.orders.sent_at') }}</div>
                                        <div class="text-sm font-bold text-blue-700 dark:text-blue-300 truncate" :title="order.sent_by_user?.name">
                                            {{ formatDateTime(order.sent_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section (Optional footer of the card) -->
                    <div v-if="order.notes" class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed italic">
                                {{ order.notes }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Stats Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Grand Total -->
                <div class="p-5 rounded-3xl bg-gradient-to-br from-indigo-600 to-purple-700 shadow-xl shadow-indigo-500/20 text-white relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-100 opacity-80 mb-1">{{ $t('common.total') }}</div>
                        <div class="text-2xl font-black font-mono tracking-tight">{{ formatCurrency(order.total) }}</div>
                    </div>
                </div>

                <!-- Tax -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm group hover:border-blue-200 dark:hover:border-blue-800 transition-all">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('invoices.tax') }} (15%)</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(order.tax_amount) }}</div>
                </div>

                <!-- Subtotal -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm group hover:border-gray-200 dark:hover:border-gray-600 transition-all">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('invoices.subtotal') }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(order.subtotal) }}</div>
                </div>

                <!-- Items Count -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm group hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('purchasing.orders.items_count') }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ toEnglish(order.items?.length || 0) }}</div>
                </div>
            </div>

            <!-- Terms (if any) -->
            <div v-if="order.terms" class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $t('purchasing.orders.terms') }}
                    </h3>
                </div>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap pl-13">
                    {{ order.terms }}
                </p>
            </div>

            <!-- Items Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        {{ $t('purchasing.orders.items') }}
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-600">
                            {{ order.items?.length || 0 }}
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.part_name') }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.qty_ordered') }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.qty_received') }}
                                </th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.unit_cost') }}
                                </th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.line_total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ item.part?.name_ar || item.part?.name_en }}
                                            </p>
                                            <p v-if="item.part?.sku" class="text-xs text-gray-500 font-mono">
                                                {{ item.part.sku }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-gray-900 dark:text-white">
                                    {{ item.qty_ordered }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getReceivedClass(item)">
                                        {{ item.qty_received || 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end font-mono text-gray-600 dark:text-gray-400">
                                    {{ formatCurrency(item.unit_cost) }}
                                </td>
                                <td class="px-4 py-3 text-end font-mono font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.line_total) }}
                                </td>
                            </tr>

                            <tr v-if="!order.items?.length">
                                <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('purchasing.orders.no_items') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Goods Received Notes (if any) -->
            <div v-if="order.goods_received_notes?.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('purchasing.grn.title') }}
                        <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-600">
                            {{ order.goods_received_notes?.length || 0 }}
                        </span>
                    </h3>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="grn in order.goods_received_notes" :key="grn.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white font-mono">{{ grn.code }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(grn.received_at) }}</p>
                            </div>
                            <Link :href="route('app.purchasing.grn.show', grn.id)"
                                class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                {{ $t('common.view') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <ConfirmModal />
        <GrnModal 
            :show="showGrnModal" 
            :purchase-order="order" 
            @close="showGrnModal = false" 
        />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <PrintHeader :title="$t('purchasing.orders.title')" />

                <!-- Document Info Header -->
                <div class="grid grid-cols-2 gap-8 mb-8 border-b pb-8">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase mb-2">{{ $t('purchasing.orders.supplier') }}</h3>
                        <div class="text-lg font-black text-gray-900">{{ order.supplier?.name }}</div>
                        <div v-if="order.supplier?.code" class="text-sm font-mono text-gray-500">{{ order.supplier.code }}</div>
                        <div v-if="order.supplier?.phone" class="text-sm text-gray-600 mt-1" dir="ltr">{{ order.supplier.phone }}</div>
                    </div>
                    <div class="text-end">
                        <div class="inline-block bg-gray-50 p-4 rounded-xl border">
                            <div class="text-xs text-gray-500 uppercase mb-1">{{ $t('purchasing.orders.code') }}</div>
                            <div class="text-xl font-black font-mono text-gray-900">{{ order.code }}</div>
                            <div class="text-sm font-bold text-blue-600 mt-1 uppercase">{{ $t(`purchasing.orders.statuses.${order.status}`) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Logistics Grid -->
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="p-4 bg-gray-50/50 rounded-xl border border-dashed">
                        <div class="text-xs text-gray-500 uppercase mb-1">{{ $t('purchasing.orders.warehouse') }}</div>
                        <div class="text-sm font-bold text-gray-900">{{ order.warehouse?.name }}</div>
                    </div>
                    <div class="p-4 bg-gray-50/50 rounded-xl border border-dashed">
                        <div class="text-xs text-gray-500 uppercase mb-1">{{ $t('purchasing.orders.date') }}</div>
                        <div class="text-sm font-bold text-gray-900 font-mono">{{ formatDate(order.order_date) }}</div>
                    </div>
                    <div class="p-4 bg-gray-50/50 rounded-xl border border-dashed" v-if="order.expected_date">
                        <div class="text-xs text-gray-500 uppercase mb-1">{{ $t('purchasing.orders.expected_date') }}</div>
                        <div class="text-sm font-bold text-gray-900 font-mono">{{ formatDate(order.expected_date) }}</div>
                    </div>
                    <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-200 border-dashed" v-if="order.create_credit_invoice">
                        <div class="text-xs text-indigo-500 uppercase mb-1">{{ $t('purchasing.orders.create_credit_invoice') }}</div>
                        <div class="text-sm font-bold text-indigo-700 font-mono">{{ order.due_date ? formatDate(order.due_date) : $t('common.active') }}</div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full mb-8">
                    <thead>
                        <tr class="border-b-2">
                            <th class="px-4 py-3 text-start text-xs font-bold uppercase">{{ $t('inventory.parts.title') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase">{{ $t('common.quantity') }}</th>
                            <th class="px-4 py-3 text-end text-xs font-bold uppercase">{{ $t('inventory.parts.unit_price') }}</th>
                            <th class="px-4 py-3 text-end text-xs font-bold uppercase">{{ $t('common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-4 py-4">
                                <div class="font-bold text-gray-900">{{ item.part?.name_ar || item.part?.name_en }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ item.part?.sku }}</div>
                            </td>
                            <td class="px-4 py-4 text-center font-mono font-bold">{{ toEnglish(item.qty_ordered) }}</td>
                            <td class="px-4 py-4 text-end font-mono">{{ formatCurrency(item.unit_cost) }}</td>
                            <td class="px-4 py-4 text-end font-mono font-bold">{{ formatCurrency(item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Financial Summary -->
                <div class="flex justify-end mb-12">
                    <div class="w-64 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ $t('invoices.subtotal') }}</span>
                            <span class="font-mono">{{ formatCurrency(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm" v-if="parseFloat(order.tax_amount) > 0">
                            <span class="text-gray-500">{{ $t('invoices.tax') }} (15%)</span>
                            <span class="font-mono">{{ formatCurrency(order.tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-black border-t-2 pt-3">
                            <span>{{ $t('common.total') }}</span>
                            <span class="text-blue-600 font-mono">{{ formatCurrency(order.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-12 text-center text-[10px] text-gray-400 border-t pt-6">
                    {{ $t('common.printed_by') }}: {{ $page.props.auth.user.name }} | {{ new Date().toLocaleString('en-US') }}
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import BackButton from '@/Components/BackButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import GrnModal from '@/Components/Purchasing/GrnModal.vue';
import PrintHeader from '@/Components/Print/PrintHeader.vue';

const props = defineProps({
    order: Object,
});

const showGrnModal = ref(false);

const { t } = useI18n();
const { formatCurrency, toEnglish } = useNumberFormat();
const { confirm } = useConfirm();

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
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        received: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return `${classes[props.order.status] || classes.draft} px-3 py-1 rounded-full text-sm font-medium`;
});

// Helper for received qty styling
const getReceivedClass = (item) => {
    const ordered = parseFloat(item.qty_ordered || 0);
    const received = parseFloat(item.qty_received || 0);
    
    if (received >= ordered) {
        return 'font-mono text-green-600 dark:text-green-400 font-medium';
    } else if (received > 0) {
        return 'font-mono text-yellow-600 dark:text-yellow-400 font-medium';
    }
    return 'font-mono text-gray-400';
};

// Actions
async function sendOrder() {
    const confirmed = await confirm({
        title: t('purchasing.orders.confirm_send_title'),
        message: t('purchasing.orders.confirm_send'),
        confirmText: t('purchasing.orders.actions.send'),
        cancelText: t('common.cancel'),
        type: 'info'
    });

    if (confirmed) {
        router.post(route('app.purchasing.orders.send', props.order.id), {}, {
            preserveScroll: true,
        });
    }
}

async function cancelOrder() {
    const confirmed = await confirm({
        title: t('purchasing.orders.confirm_cancel_title'),
        message: t('purchasing.orders.confirm_cancel'),
        confirmText: t('common.cancel'),
        cancelText: t('common.back'),
        type: 'danger'
    });

    if (confirmed) {
        router.post(route('app.purchasing.orders.cancel', props.order.id), {}, {
            preserveScroll: true,
        });
    }
}

function printOrder() {
    window.print();
}
</script>
