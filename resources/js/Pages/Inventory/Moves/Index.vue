<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.moves.title')"
                :subtitle="$t('inventory.moves.subtitle') + (warehouse ? ` - ${warehouse.name}` : '')"
                :totalCount="moves.total"
                :countLabel="$t('inventory.moves.title')"
                gradientFrom="from-purple-600"
                gradientTo="to-indigo-700"
                glowFrom="from-purple-500"
                badgeBg="bg-purple-50/50 dark:bg-purple-900/30"
                badgeText="text-purple-600 dark:text-purple-400"
                badgeBorder="border-purple-100/50 dark:border-purple-800/30"
                badgeDot="bg-purple-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Print Button -->
                        <button 
                            @click="handlePrint"
                            :title="$t('common.print')"
                            class="p-2.5 rounded-xl transition-all shadow-lg bg-white dark:bg-gray-800 text-gray-400 hover:text-purple-600 hover:bg-white dark:hover:bg-gray-800 border border-gray-100 dark:border-gray-700"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>
                        <!-- Receipt Button -->
                        <button
                            v-if="can('inventory.receipts.create') || isAnyAdmin()"
                            @click="showReceiptModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 text-green-700 dark:text-green-400 rounded-2xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all group/receipt border border-green-50 dark:border-green-900/30"
                        >
                            <div class="w-7 h-7 rounded-lg bg-green-50 dark:bg-green-900/50 flex items-center justify-center group-hover/receipt:rotate-90 transition-transform duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <span class="text-sm tracking-tight">{{ $t('inventory.moves.receipt') }}</span>
                        </button>

                        <!-- Adjustment Button -->
                        <button
                            v-if="can('inventory.adjustments.create') || isAnyAdmin()"
                            @click="showAdjustmentModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 text-yellow-700 dark:text-yellow-400 rounded-2xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all group/adjust border border-yellow-50 dark:border-yellow-900/30"
                        >
                            <div class="w-7 h-7 rounded-lg bg-yellow-50 dark:bg-yellow-900/50 flex items-center justify-center group-hover/adjust:rotate-12 transition-transform duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                            <span class="text-sm tracking-tight">{{ $t('inventory.moves.adjust') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4 no-print">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-purple-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('inventory.moves.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                            <!-- Type Filter -->
                            <div class="w-full md:w-56">
                                <SearchableSelect
                                    v-model="localFilters.type"
                                    :options="moveTypeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('inventory.moves.all_types')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Date From -->
                            <div class="w-full md:w-44">
                                <CustomDatePicker
                                    v-model="localFilters.date_from"
                                    :placeholder="$t('work_orders.filters.date_from')"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="w-full md:w-44">
                                <CustomDatePicker
                                    v-model="localFilters.date_to"
                                    :placeholder="$t('work_orders.filters.date_to')"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Reset Button -->
                            <button 
                                @click="resetFilters"
                                class="p-3.5 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all shadow-sm"
                                :title="$t('common.reset')"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Moves Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full" dir="rtl">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('id')">
                                    <div class="flex items-center justify-center gap-1">
                                        {{ $t('inventory.moves.columns.row_number') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'id' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'id' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('sku')">
                                    <div class="flex items-center gap-1">
                                        {{ $t('inventory.moves.columns.part') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'sku' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'sku' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('inventory.moves.columns.action') }}</th>
                                <th class="px-4 py-4 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('inventory.moves.columns.details') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('balance_after')">
                                    <div class="flex items-center justify-center gap-1">
                                        {{ $t('inventory.moves.columns.stock_before') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'balance_after' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'balance_after' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('qty')">
                                    <div class="flex items-center justify-center gap-1">
                                        {{ $t('inventory.moves.columns.qty') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'qty' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'qty' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('inventory.moves.columns.stock_after') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('unit_cost')">
                                    <div class="flex items-center justify-center gap-1">
                                        {{ $t('inventory.moves.columns.cost_price') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'unit_cost' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'unit_cost' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('inventory.moves.columns.wac') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('total_cost')">
                                    <div class="flex items-center justify-center gap-1">
                                        {{ $t('inventory.moves.columns.cost_amount') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'total_cost' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'total_cost' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer group" @click="sortBy('posted_at')">
                                    <div class="flex items-center gap-1">
                                        {{ $t('inventory.moves.columns.updated_at') }}
                                        <div class="flex flex-col">
                                            <svg v-if="localFilters.sort === 'posted_at' && localFilters.order === 'asc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            <svg v-else-if="localFilters.sort === 'posted_at' && localFilters.order === 'desc'" class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            <svg v-else class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(move, index) in moves.data" :key="move.id" :class="[
                                'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                                move.reversed_at ? 'opacity-50 line-through' : ''
                            ]">
                                <!-- # -->
                                <td class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ (moves.current_page - 1) * moves.per_page + index + 1 }}</td>
                                
                                <!-- Part -->
                                <td class="px-4 py-4 text-start">
                                    <Link :href="route('app.inventory.parts.show', move.part_id)" class="group/part">
                                        <div class="text-sm font-bold text-blue-600 dark:text-blue-400 group-hover/part:text-blue-700 dark:group-hover/part:text-blue-300 group-hover/part:underline transition-all">{{ move.part?.name_ar }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono" dir="ltr">{{ move.part?.sku }}</div>
                                    </Link>
                                </td>
                                
                                <!-- Action/Type -->
                                <td class="px-4 py-4 text-start">
                                    <span :class="getMoveTypeBadgeClass(move.move_type)">
                                        {{ getMoveTypeLabel(move.move_type) }}
                                    </span>
                                </td>
                                
                                <!-- Details/Notes -->
                                <td class="px-4 py-4 text-start text-sm text-gray-600 dark:text-gray-300 max-w-[200px] truncate" :title="move.notes">
                                    {{ move.notes || '-' }}
                                </td>
                                
                                <!-- Stock Before -->
                                <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ calculateStockBefore(move) }}
                                </td>
                                
                                 <!-- Quantity -->
                                <td class="px-4 py-4 text-center">
                                    <span :class="[
                                        'font-mono text-sm font-medium',
                                        move.qty > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ move.qty > 0 ? '+' : '' }}{{ Number(move.qty).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 }) }}
                                    </span>
                                </td>
                                
                                <!-- Stock After -->
                                <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ Number(move.balance_after).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 }) }}
                                </td>
                                
                                <!-- Cost Price (Unit Cost) -->
                                <td class="px-4 py-4 text-center text-sm text-gray-900 dark:text-white font-mono">
                                    {{ formatCurrency(move.unit_cost) }}
                                </td>
                                
                                <!-- WAC (Weighted Average Cost) -->
                                <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ formatCurrency(move.wac_after) }}
                                </td>
                                
                                <!-- Cost Amount (Total Cost) -->
                                <td class="px-4 py-4 text-center text-sm text-gray-900 dark:text-white font-mono">
                                    {{ formatCurrency(move.total_cost) }}
                                </td>
                                
                                <!-- Updated At -->
                                <td class="px-4 py-4 text-start text-sm text-gray-600 dark:text-gray-300" dir="ltr">
                                    {{ formatDate(move.posted_at) }}
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-4 py-4 text-center">
                                    <button
                                        v-if="canReverse(move)"
                                        @click="reverseMove(move)"
                                        class="p-2 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                        :title="$t('inventory.moves.reverse')"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                    </button>
                                    <span v-else-if="move.reversed_at" class="text-xs text-red-500">
                                        {{ $t('inventory.moves.reversed') }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!moves.data.length">
                                <td colspan="12" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('inventory.moves.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="moves.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('common.showing') }} {{ moves.from }} - {{ moves.to }} {{ $t('common.of') }} {{ moves.total }}
                        </span>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in moves.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active 
                                        ? 'bg-purple-600 text-white' 
                                        : link.url 
                                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modals -->
    <ReceiptModal
        :show="showReceiptModal"
        :warehouse-id="warehouse?.id"
        @close="showReceiptModal = false"
        @saved="() => {}"
    />
    <AdjustmentModal
        :show="showAdjustmentModal"
        :warehouse-id="warehouse?.id"
        @close="showAdjustmentModal = false"
        @saved="() => {}"
    />

    <!-- Print Section -->
    <Teleport to="body">
        <div class="print-section hidden">
            <!-- Header -->
            <PrintHeader 
                :title="$t('inventory.moves.title')" 
                :subtitle="warehouse ? warehouse.name : ''"
            />

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>{{ $t('inventory.moves.columns.part') }}</th>
                        <th>{{ $t('inventory.moves.columns.action') }}</th>
                        <th>{{ $t('inventory.moves.columns.qty') }}</th>
                        <th>{{ $t('inventory.moves.columns.stock_after') }}</th>
                        <th>{{ $t('inventory.moves.columns.cost_price') }}</th>
                        <th>{{ $t('inventory.moves.columns.updated_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(move, index) in moves.data" :key="move.id">
                        <td>{{ index + 1 }}</td>
                        <td class="font-bold">
                            {{ move.part?.name_ar }}<br/>
                            <small class="text-gray-500">{{ move.part?.sku }}</small>
                        </td>
                        <td>{{ getMoveTypeLabel(move.move_type) }}</td>
                        <td dir="ltr" :class="move.qty > 0 ? 'text-green-600' : 'text-red-600'">
                            {{ move.qty > 0 ? '+' : '' }}{{ formatQuantity(move.qty) }}
                        </td>
                        <td>{{ formatQuantity(move.balance_after) }}</td>
                        <td>{{ formatCurrency(move.unit_cost) }}</td>
                        <td dir="ltr" class="text-[10px]">{{ formatDate(move.posted_at) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Footer -->
            <div class="mt-8 text-center text-[10px] text-gray-400 border-t pt-4">
                {{ $t('common.printed_by') }}: {{ $page.props.auth.user.name }} | {{ new Date().toLocaleString() }}
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ReceiptModal from '@/Components/Inventory/ReceiptModal.vue';
import AdjustmentModal from '@/Components/Inventory/AdjustmentModal.vue';
import PrintHeader from '@/Components/Print/PrintHeader.vue';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    moves: Object,
    warehouse: Object,
    moveTypes: Array,
    filters: Object,
});

const showReceiptModal = ref(false);
const showAdjustmentModal = ref(false);

const localFilters = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    part_id: props.filters?.part_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    sort: props.filters?.sort || 'posted_at',
    order: props.filters?.order || 'desc',
});

const { formatQuantity, formatCurrency } = useNumberFormat();

const applyFilters = () => {
    router.get(route('app.inventory.moves.index'), {
        search: localFilters.value.search || undefined,
        type: localFilters.value.type || undefined,
        part_id: localFilters.value.part_id || undefined,
        date_from: localFilters.value.date_from || undefined,
        date_to: localFilters.value.date_to || undefined,
        sort: localFilters.value.sort || undefined,
        order: localFilters.value.order || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const sortBy = (field) => {
    if (localFilters.value.sort === field) {
        localFilters.value.order = localFilters.value.order === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort = field;
        localFilters.value.order = 'asc';
    }
    applyFilters();
};

const resetFilters = () => {
    localFilters.value.search = '';
    localFilters.value.type = '';
    localFilters.value.date_from = '';
    localFilters.value.date_to = '';
    applyFilters();
};

const debouncedSearch = debounce(applyFilters, 300);

const formatDate = (date) => {
    if (!date) return '-';
    // Force en-US for English numerals
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const getMoveTypeLabel = (type) => {
    const labels = {
        'receipt': t('inventory.moves.types.receipt'),
        'issue_to_workorder': t('inventory.moves.types.issue'),
        'adjustment_in': t('inventory.moves.types.adjustment_in'),
        'adjustment_out': t('inventory.moves.types.adjustment_out'),
        'transfer_in': t('inventory.moves.types.transfer_in'),
        'transfer_out': t('inventory.moves.types.transfer_out'),
        'reversal': t('inventory.moves.types.reversal'),
    };
    return labels[type] || type;
};

const moveTypeOptions = computed(() => {
    const allOption = { value: '', label: t('inventory.moves.all_types') };
    const types = props.moveTypes.map(type => ({
        value: type,
        label: getMoveTypeLabel(type)
    }));
    return [allOption, ...types];
});

const getMoveTypeBadgeClass = (type) => {
    const classes = {
        'receipt': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200/50 dark:border-green-800/30',
        'issue_to_workorder': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200/50 dark:border-red-800/30',
        'adjustment_in': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200/50 dark:border-blue-800/30',
        'adjustment_out': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border border-orange-200/50 dark:border-orange-800/30',
        'transfer_in': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 border border-purple-200/50 dark:border-purple-800/30',
        'transfer_out': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/30',
        'reversal': 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 border border-gray-200/50 dark:border-gray-500/30',
    };
    return `inline-flex px-2.5 py-1 rounded-full text-xs font-bold shadow-sm ${classes[type] || 'bg-gray-100 text-gray-700'}`;
};

// Calculate stock before from balance_after - qty
const calculateStockBefore = (move) => {
    const balanceAfter = parseFloat(move.balance_after) || 0;
    const qty = parseFloat(move.qty) || 0;
    const before = balanceAfter - qty;
    // Format with en-US to ensure English numerals
    return before.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
        useGrouping: false
    });
};

const canReverse = (move) => {
    if (move.reversed_at) return false;
    
    // Restricted types that should be reversed from their own modules
    const restrictedTypes = ['issue_to_workorder', 'transfer_in', 'transfer_out'];
    if (restrictedTypes.includes(move.move_type)) return false;

    if (move.move_type === 'receipt') return can('inventory.receipts.cancel');
    if (move.move_type.startsWith('adjustment')) return can('inventory.adjustments.cancel');
    
    return false;
};

const reverseMove = (move) => {
    if (!confirm(t('inventory.moves.confirm_reverse'))) return;
    
    router.post(route('app.inventory.moves.reverse', move.id), {}, {
        preserveScroll: true,
    });
};
const handlePrint = () => {
    window.print();
};
</script>

<style scoped>
/* Scoped styles for screen adjustments */
</style>
