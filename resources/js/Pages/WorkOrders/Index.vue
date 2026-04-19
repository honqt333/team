<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between sm:flex-wrap gap-3 sm:gap-4">
                    <!-- Back + Title + Count -->
                    <div class="flex items-center gap-4">
                        <!-- Back Button -->
                        <Link
                            :href="route('work-orders.hub')"
                            class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-300 transition-all"
                        >
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" :class="statusFilter === 'closed' ? 'bg-gradient-to-br from-slate-500 to-gray-600 shadow-slate-500/30' : 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/30'">
                            <svg v-if="statusFilter === 'closed'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ pageTitle }}</h1>
                            <p v-if="workOrders" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ toEnglish(workOrders.total) }} {{ $t('work_orders.total_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <!-- Filters Row - Responsive Grid -->
                    <div class="w-full mt-4 lg:mt-0">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:flex lg:flex-wrap items-end gap-3">
                            <!-- Search -->
                            <div class="col-span-2 md:col-span-1 lg:w-48">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="searchQuery"
                                        :placeholder="$t('work_orders.search')"
                                        class="w-full ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Date From -->
                            <div class="lg:w-36">
                                <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1 px-1">
                                    {{ $t('work_orders.filters.date_from') }}
                                </label>
                                <input
                                    type="date"
                                    v-model="dateFrom"
                                    dir="ltr"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="lg:w-36">
                                <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1 px-1">
                                    {{ $t('work_orders.filters.date_to') }}
                                </label>
                                <input
                                    type="date"
                                    v-model="dateTo"
                                    dir="ltr"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Customer Type Filter -->
                            <div class="lg:w-40">
                                <label class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1 px-1">
                                    {{ $t('work_orders.filters.customer_type') }}
                                </label>
                                <SearchableSelect
                                    v-model="customerTypeFilter"
                                    :options="customerTypeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('work_orders.filters.all_types')"
                                    :label="''"
                                />
                            </div>

                            <!-- Actions Group -->
                            <div class="col-span-2 md:col-span-3 lg:col-span-1 flex items-end justify-end gap-2 lg:ms-auto">
                                <!-- Print/Export Buttons -->
                                <div class="flex gap-1.5">
                                    <!-- Print -->
                                    <button
                                        v-if="can('crm.work_orders.print')"
                                        @click="printWorkOrders"
                                        class="flex items-center justify-center w-10 h-10 text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all"
                                        :title="$t('common.print')"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </button>
                                    <!-- Export -->
                                    <button
                                        v-if="can('crm.work_orders.export')"
                                        @click="exportWorkOrders"
                                        :disabled="exporting"
                                        class="flex items-center justify-center w-10 h-10 text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all disabled:opacity-50"
                                        :title="$t('common.export')"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- View Toggle -->
                                <div class="hidden sm:flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1">
                                    <button
                                        @click="viewMode = 'grid'"
                                        :class="[
                                            'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                            viewMode === 'grid'
                                                ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="viewMode = 'list'"
                                        :class="[
                                            'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                            viewMode === 'list'
                                                ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Add Button -->
                                <button
                                    v-if="can('crm.work_orders.create')"
                                    @click="showCreateModal = true"
                                    class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span class="hidden sm:inline">{{ $t('work_orders.add') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs (only for open status) - Modern Compact Design -->
            <div v-if="statusFilter === 'open'" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-3">
                <button
                    v-for="tab in filterTabs"
                    :key="tab.key"
                    @click="setSubFilter(tab.key)"
                    :class="[
                        'relative flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200',
                        currentSubFilter === tab.key
                            ? 'text-white shadow-lg'
                            : 'bg-white dark:bg-gray-800 hover:shadow-md border border-gray-200 dark:border-gray-700'
                    ]"
                    :style="currentSubFilter === tab.key ? `background: linear-gradient(135deg, ${tab.gradientFrom}, ${tab.gradientTo})` : ''"
                >
                    <!-- Icon -->
                    <div :class="[
                        'w-8 h-8 rounded-lg flex items-center justify-center',
                        currentSubFilter === tab.key
                            ? 'bg-white/20'
                            : tab.bgColor
                    ]">
                        <component 
                            :is="tab.icon" 
                            :class="[
                                'w-4 h-4',
                                currentSubFilter === tab.key ? 'text-white' : tab.iconColor
                            ]" 
                        />
                    </div>
                    
                    <!-- Label & Count -->
                    <div class="flex items-center gap-2">
                        <span :class="[
                            'font-medium text-sm',
                            currentSubFilter === tab.key ? 'text-white' : 'text-gray-700 dark:text-gray-300'
                        ]">
                            {{ tab.label }}
                        </span>
                        <span :class="[
                            'px-2 py-0.5 rounded-full text-xs font-bold',
                            currentSubFilter === tab.key 
                                ? 'bg-white/20 text-white' 
                                : tab.bgColor + ' ' + tab.iconColor
                        ]">
                            {{ toEnglish(tab.count) }}
                        </span>
                    </div>
                    
                    <!-- Selected Check -->
                    <svg v-if="currentSubFilter === tab.key" class="w-4 h-4 text-white/80" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!workOrders" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="workOrders.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('work_orders.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('work_orders.empty_hint') }}</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('work_orders.add') }}
                </button>
            </div>

            <!-- Grid View: Modern Beautiful Cards -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                <div
                    v-for="order in allWorkOrders"
                    :key="order.id"
                    @click="router.visit(route('work-orders.show', order.id))"
                    class="group relative bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/50 hover:border-indigo-400/50 dark:hover:border-indigo-500/50 hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1.5 cursor-pointer transition-all duration-500 overflow-hidden flex flex-col"
                >
                    <!-- Top Accent / Status Gradient Line -->
                    <div class="h-1.5 w-full bg-gradient-to-r" :class="getStatusGradient(order.status)"></div>

                    <!-- Card Header: Code & Status -->
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex flex-col gap-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 tracking-widest uppercase">#{{ toEnglish(order.code || order.id) }}</span>
                                </div>
                                <div v-if="order.entry_date" class="text-[9px] font-bold text-slate-400">
                                    {{ formatDate(order.entry_date) }}
                                </div>
                            </div>
                            <div :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm', getStatusClass(order.status)]">
                                {{ $t(`work_orders.status.${order.status}`) }}
                            </div>
                        </div>
                    </div>

                    <!-- Main Vehicle Info Section -->
                    <div class="px-4 py-3 flex-1">
                        <!-- Saudi Plate Component & Brand Logo -->
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <!-- Manufacturer Logo -->
                            <Tooltip :text="order.vehicle?.make?.name || ''" v-if="order.vehicle?.make?.logo_path">
                                <div class="w-10 h-10 flex items-center justify-center p-1 bg-white dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm transition-transform hover:scale-110 cursor-pointer">
                                    <img :src="`/storage/${order.vehicle.make.logo_path}`" class="w-full h-full object-contain filter dark:brightness-110" alt="Brand" />
                                </div>
                            </Tooltip>
                            
                            <!-- Saudi Plate (Realistic Style) -->
                            <Tooltip :text="$t('vehicles.plate_number')">
                                <div class="relative w-40 h-10 bg-white border-2 border-gray-900 rounded-lg flex overflow-hidden shadow-sm group-hover:shadow-md transition-all cursor-pointer">
                                    <div class="w-1/4 border-r-2 border-gray-900 bg-gray-50 flex flex-col items-center justify-between py-0.5">
                                        <span class="text-[7px] font-black leading-none text-gray-500">KSA</span>
                                        <div class="w-2 h-2 rounded-full bg-green-600 shadow-sm shadow-green-500/50"></div>
                                        <span class="text-[7px] font-black leading-none text-gray-500">{{ $t('vehicles.plate.countries.sa') }}</span>
                                    </div>
                                    <div class="flex-1 flex items-center justify-center gap-3 px-1.5">
                                        <span class="text-lg font-black text-gray-900 tracking-widest font-mono">
                                            {{ toEnglish(order.vehicle?.plate_number?.split(' ')?.[0] || '1234') }}
                                        </span>
                                        <span class="text-lg font-black text-gray-900 tracking-[0.4em] font-mono">
                                            {{ toEnglish(order.vehicle?.plate_number?.split(' ')?.[1] || 'ABC') }}
                                        </span>
                                    </div>
                                </div>
                            </Tooltip>
                        </div>

                        <!-- Vehicle Name & Customer -->
                        <div class="space-y-3">
                            <div class="text-center">
                                <div class="flex flex-col items-center justify-center gap-1 mb-1">
                                    <Tooltip :text="getVehicleName(order.vehicle)">
                                        <h3 class="text-sm font-black text-gray-900 dark:text-white leading-tight group-hover:text-indigo-600 transition-colors cursor-pointer line-clamp-1">
                                            {{ getVehicleName(order.vehicle) }}
                                        </h3>
                                    </Tooltip>
                                    <!-- Vehicle Color Badge -->
                                    <div v-if="order.vehicle?.color" 
                                        class="flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-md border border-slate-200 dark:border-slate-600 cursor-default">
                                        <span class="w-2 h-2 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" :style="{ backgroundColor: getColorHex(order.vehicle.color) }"></span>
                                        {{ order.vehicle.color }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-center gap-1.5">
                                    <div class="w-4 h-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                        </svg>
                                    </div>
                                    <Tooltip :text="$t('common.customer')">
                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 truncate max-w-[120px] cursor-pointer">
                                            {{ order.customer?.name || $t('common.unknown') }}
                                        </span>
                                    </Tooltip>
                                </div>
                            </div>

                            <!-- Financial Metrics Grid -->
                            <div class="grid grid-cols-3 gap-1 py-2 border-y border-gray-50 dark:border-gray-700/50">
                                <!-- Total -->
                                <Tooltip :text="$t('work_orders.invoice_total')" class="flex-1">
                                    <div class="group/stat flex flex-col items-center justify-center border-e border-gray-100 dark:border-gray-700/50 cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/30 rounded-lg py-1 w-full h-full">
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter">{{ $t('work_orders.total') }}</span>
                                        <span class="text-[11px] font-black text-slate-700 dark:text-slate-300">{{ formatNumber(order.total || 0) }}</span>
                                    </div>
                                </Tooltip>
                                <!-- Paid -->
                                <Tooltip :text="$t('work_orders.paid')" class="flex-1">
                                    <div class="group/stat flex flex-col items-center justify-center border-e border-gray-100 dark:border-gray-700/50 cursor-pointer transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-900/10 rounded-lg py-1 w-full h-full">
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter">{{ $t('work_orders.paid') }}</span>
                                        <span class="text-[11px] font-black text-emerald-600 dark:text-emerald-400">{{ formatNumber(order.paid_amount || 0) }}</span>
                                    </div>
                                </Tooltip>
                                <!-- Balance -->
                                <Tooltip :text="$t('work_orders.remaining')" class="flex-1">
                                    <div class="group/stat flex flex-col items-center justify-center cursor-pointer transition-colors hover:bg-red-50 dark:hover:bg-red-900/10 rounded-lg py-1 w-full h-full">
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter">{{ $t('work_orders.remaining') }}</span>
                                        <span class="text-[11px] font-black" :class="(order.total - (order.paid_amount || 0)) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-400'">
                                            {{ formatNumber((order.total || 0) - (order.paid_amount || 0)) }}
                                        </span>
                                    </div>
                                </Tooltip>
                            </div>
                        </div>
                    </div>

                    <!-- Footer: Status/Date -->
                    <div class="px-5 py-4 bg-slate-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between mt-auto">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            {{ getDateLabel(order) }}
                        </span>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-lg border shadow-sm" :class="getDateClass(order)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDate(getDisplayDate(order)) }}
                        </div>
                    </div>

                    <!-- View Details Arrow (Hover Only) -->
                    <div class="absolute top-1/2 -end-4 group-hover:end-4 opacity-0 group-hover:opacity-100 transition-all duration-500" @click.stop>
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <!-- # (Index) -->
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">
                                    #
                                </th>
                                <!-- رقم كرت الصيانة -->
                                <th 
                                    @click="toggleSort('code')"
                                    class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                                >
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('work_orders.columns.code') }}
                                        <SortIcon :active="sortColumn === 'code'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- تاريخ الانتهاء المتوقع -->
                                <th 
                                    @click="toggleSort('expected_end_date')"
                                    class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('work_orders.columns.expected_end') }}
                                        <SortIcon :active="sortColumn === 'expected_end_date'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- المركبة -->
                                <th 
                                    @click="toggleSort('vehicle')"
                                    class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('work_orders.columns.vehicle') }}
                                        <SortIcon :active="sortColumn === 'vehicle'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- اسم المسؤول -->
                                <th 
                                    @click="toggleSort('contact_name')"
                                    class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('work_orders.columns.contact_name') }}
                                        <SortIcon :active="sortColumn === 'contact_name'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- الخدمات (عدد) -->
                                <th 
                                    @click="toggleSort('services_count')"
                                    class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('work_orders.columns.services') }}
                                        <SortIcon :active="sortColumn === 'services_count'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- مبلغ الفاتورة -->
                                <th 
                                    @click="toggleSort('total')"
                                    class="px-4 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{ $t('work_orders.columns.total') }}
                                        <SortIcon :active="sortColumn === 'total'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- المبلغ المدفوع -->
                                <th 
                                    @click="toggleSort('paid')"
                                    class="px-4 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{ $t('work_orders.columns.paid') }}
                                        <SortIcon :active="sortColumn === 'paid'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <!-- الباقي -->
                                <th 
                                    @click="toggleSort('balance')"
                                    class="px-4 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{ $t('work_orders.columns.balance') }}
                                        <SortIcon :active="sortColumn === 'balance'" :direction="sortDirection" />
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="(order, index) in sortedWorkOrders"
                                :key="order.id"
                                @click="router.visit(route('work-orders.show', order.id))"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors"
                            >
                                <!-- # -->
                                <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400 font-medium">
                                    {{ toEnglish(index + 1) }}
                                </td>
                                <!-- رقم الكرت -->
                                <td class="px-4 py-3">
                                    <span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">#{{ toEnglish(order.code || order.id) }}</span>
                                </td>
                                <!-- تاريخ الانتهاء -->
                                <td class="px-4 py-3">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-lg"
                                        :class="getDateClass(order)"
                                    >
                                        <svg v-if="isOverdue(order)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ formatDate(order.expected_end_date) }}
                                    </span>
                                </td>
                                <!-- المركبة -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-sm font-bold text-gray-800 dark:text-gray-200 font-mono" dir="ltr">
                                            {{ toEnglish(order.vehicle?.plate_number) || '-' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[120px]">
                                            {{ getVehicleName(order.vehicle) }}
                                        </span>
                                    </div>
                                </td>
                                <!-- المسؤول -->
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ order.contact_name || order.customer?.name || '-' }}
                                </td>
                                <!-- الخدمات -->
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-bold">
                                        {{ toEnglish(order.items?.length || 0) }}
                                    </span>
                                </td>
                                <!-- مبلغ الفاتورة -->
                                <td class="px-4 py-3 text-end font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(order.total || 0) }}
                                </td>
                                <!-- المدفوع -->
                                <td class="px-4 py-3 text-end font-medium text-green-600 dark:text-green-400">
                                    {{ formatCurrency(order.paid_amount || 0) }}
                                </td>
                                <!-- الباقي -->
                                <td class="px-4 py-3 text-end font-bold" :class="getBalanceColor(order)">
                                    {{ formatCurrency(getBalance(order)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scroll Sentinel -->
            <div ref="loadMoreSentinel" class="py-6 flex justify-center w-full">
                <div v-if="loadingMore" class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ $t('common.loading') }}</span>
                </div>
                <div v-else-if="allWorkOrders.length >= (workOrders?.total || 0) && (workOrders?.total || 0) > 0" class="text-sm text-gray-400 dark:text-gray-600">
                    {{ $t('work_orders.all_loaded') || 'All work orders loaded' }}
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <WorkOrderFormModal
            :show="showCreateModal"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="showCreateModal = false"
            @saved="handleSaved"
        />
        
        <!-- Print Section (Teleported to body for printing) -->
        <Teleport to="body">
            <div class="print-section hidden">
                <!-- Header -->
                <div class="print-header">
                    <!-- Arabic Layout: Logo right, info beside it -->
                    <div class="flex items-start gap-4 mb-4" style="direction: rtl;">
                        <!-- Logo -->
                        <div v-if="$page.props.tenant?.logo_url" class="w-20 h-20 flex-shrink-0">
                            <img 
                                :src="$page.props.tenant.logo_url" 
                                :alt="$page.props.tenant?.name"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <!-- Center Info -->
                        <div class="flex-1 text-right">
                            <h1 class="text-xl font-bold">{{ $page.props.tenant?.trade_name || $page.props.tenant?.name || 'Carag' }}</h1>
                            <p class="text-sm" v-if="$page.props.auth.center?.phone || $page.props.tenant?.phone">
                                هاتف: {{ $page.props.auth.center?.phone || $page.props.tenant?.phone }}
                            </p>
                            <p class="text-sm" v-if="$page.props.auth.center?.email || $page.props.tenant?.email">
                                البريد: {{ $page.props.auth.center?.email || $page.props.tenant?.email }}
                            </p>
                            <p class="text-sm" v-if="$page.props.tenant?.cr_number">
                                السجل التجاري: {{ $page.props.tenant?.cr_number }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Title centered -->
                    <div class="border-t pt-4 border-gray-300 text-center">
                        <h2 class="text-lg font-bold">{{ $t('work_orders.title') }}</h2>
                        <p class="text-xs text-gray-500 mt-1">{{ new Date().toLocaleDateString('ar-SA') }}</p>
                    </div>
                </div>

                <!-- Table -->
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $t('work_orders.columns.code') }}</th>
                            <th>{{ $t('work_orders.columns.customer') }}</th>
                            <th>{{ $t('work_orders.columns.vehicle') }}</th>
                            <th>{{ $t('work_orders.columns.status') }}</th>
                            <th>{{ $t('work_orders.columns.invoice_total') }}</th>
                            <th>{{ $t('work_orders.columns.paid_amount') }}</th>
                            <th>{{ $t('work_orders.columns.balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(order, index) in allWorkOrders" :key="order.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ order.code }}</td>
                            <td>{{ order.customer?.name || '-' }}</td>
                            <td dir="ltr" class="text-left font-sans">{{ order.vehicle?.plate_number || '-' }}</td>
                            <td>
                                <span class="print-badge">
                                    {{ $t(`work_orders.status.${order.status}`) }}
                                </span>
                            </td>
                            <td>{{ (order.total || 0).toLocaleString() }}</td>
                            <td>{{ (order.paid_amount || 0).toLocaleString() }}</td>
                            <td>{{ ((order.total || 0) - (order.paid_amount || 0)).toLocaleString() }}</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-8 text-center text-xs text-gray-400">
                    {{ $page.props.auth.user.name }} - {{ new Date().toLocaleString('ar-SA') }}
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import SortIcon from '@/Components/Common/SortIcon.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { success } = useToast();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    workOrders: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    colors: {
        type: Array,
        default: () => [],
    },
    modelsByMake: {
        type: Object,
        default: () => ({}),
    },
    departments: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statusFilter: {
        type: String,
        default: null,
    },
    subFilter: {
        type: String,
        default: null,
    },
    filterCounts: {
        type: Object,
        default: () => ({}),
    },
});

// Infinite Scroll Refs
const allWorkOrders = ref(props.workOrders?.data || []);
const nextPageUrl = ref(props.workOrders?.next_page_url);
const loadingMore = ref(false);
const loadMoreSentinel = ref(null);
let observer = null;

// Watch props change (filters, etc)
watch(() => props.workOrders, (newVal) => {
    if (newVal) {
        allWorkOrders.value = newVal.data;
        nextPageUrl.value = newVal.next_page_url;
    }
});

// Load more data
const loadMore = async () => {
    if (loadingMore.value || !nextPageUrl.value) return;

    loadingMore.value = true;
    try {
        const url = new URL(nextPageUrl.value);
        // Map to API endpoint
        const apiUrl = url.toString().replace('/app/work-orders', '/app/api/work-orders-index');
        
        const response = await axios.get(apiUrl);
        const data = response.data;
        
        allWorkOrders.value.push(...data.data);
        nextPageUrl.value = data.next_page_url;
    } catch (e) {
        console.error('Failed to load more work orders', e);
    } finally {
        loadingMore.value = false;
    }
};

// Intersection Observer Setup
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && nextPageUrl.value) {
            loadMore();
        }
    }, {
        root: null,
        threshold: 0.1,
    });

    if (loadMoreSentinel.value) {
        observer.observe(loadMoreSentinel.value);
    }
    
    // Check sentinel ref changes
    watch(loadMoreSentinel, (el) => {
        if (observer) observer.disconnect();
        if (el) observer.observe(el);
    });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// Icons for filter tabs
const IconOpen = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>` };
const IconDraft = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>` };
const IconOverdue = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };
const IconPending = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };

const filterTabs = computed(() => [
    { 
        key: 'in_progress', 
        label: t('work_orders.filters.open'), 
        icon: IconOpen, 
        iconColor: 'text-emerald-500', 
        bgColor: 'bg-emerald-100 dark:bg-emerald-900/30',
        gradientFrom: '#10b981',
        gradientTo: '#059669',
        count: props.filterCounts.open || 0 
    },
    { 
        key: 'draft', 
        label: t('work_orders.filters.draft'), 
        icon: IconDraft, 
        iconColor: 'text-slate-500', 
        bgColor: 'bg-slate-100 dark:bg-slate-900/30',
        gradientFrom: '#64748b',
        gradientTo: '#475569',
        count: props.filterCounts.draft || 0 
    },
    { 
        key: 'overdue', 
        label: t('work_orders.filters.overdue'), 
        icon: IconOverdue, 
        iconColor: 'text-red-500', 
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        gradientFrom: '#ef4444',
        gradientTo: '#dc2626',
        count: props.filterCounts.overdue || 0 
    },
    { 
        key: 'pending_payment', 
        label: t('work_orders.filters.pending_payment'), 
        icon: IconPending, 
        iconColor: 'text-amber-500', 
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        gradientFrom: '#f59e0b',
        gradientTo: '#d97706',
        count: props.filterCounts.pending_payment || 0 
    },
]);

const currentSubFilter = computed(() => props.subFilter || 'in_progress');

const pageTitle = computed(() => {
    if (props.statusFilter === 'open') return t('work_orders.list.open_title');
    if (props.statusFilter === 'closed') return t('work_orders.list.closed_title');
    return t('work_orders.list.all_title');
});

const showCreateModal = ref(false);
const viewMode = ref(localStorage.getItem('workOrdersViewMode') || 'grid');
const searchQuery = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const customerTypeFilter = ref(props.filters.customer_type || '');
const exporting = ref(false);

// Customer type options for dropdown
const customerTypeOptions = computed(() => [
    { value: '', label: t('work_orders.filters.all_types') },
    { value: 'individual', label: t('customers.type.individual') },
    { value: 'company', label: t('customers.type.company') },
]);

// Permission composable
import { usePermission } from '@/Composables/usePermission';
const { can } = usePermission();

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

// Apply filters
function applyFilters() {
    router.get(route('work-orders.index'), { 
        status: props.statusFilter,
        sub_filter: props.subFilter,
        search: searchQuery.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        customer_type: customerTypeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Watch filters and apply with debounce
const debouncedApplyFilters = debounce(applyFilters, 400);

watch([dateFrom, dateTo, customerTypeFilter], () => {
    debouncedApplyFilters();
});

// Print work orders - Load All & Print
async function printWorkOrders() {
    // If there's more data to load, load it all first
    if (nextPageUrl.value) {
        // Load all pages first
        while (nextPageUrl.value) {
            try {
                const url = new URL(nextPageUrl.value, window.location.origin);
                const page = url.searchParams.get('page') || 2;
                
                const params = new URLSearchParams();
                params.set('page', page);
                if (searchQuery.value) params.set('search', searchQuery.value);
                if (props.statusFilter) params.set('status', props.statusFilter);
                if (props.subFilter) params.set('sub_filter', props.subFilter);
                
                const response = await axios.get('/app/api/work-orders-index?' + params.toString());
                const data = response.data;
                
                if (data.data && Array.isArray(data.data)) {
                    allWorkOrders.value.push(...data.data);
                }
                nextPageUrl.value = data.next_page_url;
            } catch (e) {
                console.error('Failed to load all work orders for print', e);
                break;
            }
        }
        
        // Allow DOM to update
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    // Trigger print
    window.print();
}

// Export work orders
async function exportWorkOrders() {
    exporting.value = true;
    try {
        const params = new URLSearchParams();
        if (props.statusFilter) params.append('status', props.statusFilter);
        if (props.subFilter) params.append('sub_filter', props.subFilter);
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo.value) params.append('date_to', dateTo.value);
        if (customerTypeFilter.value) params.append('customer_type', customerTypeFilter.value);
        
        window.location.href = `${route('work-orders.export')}?${params.toString()}`;
    } finally {
        setTimeout(() => {
            exporting.value = false;
        }, 2000);
    }
}

function setSubFilter(filter) {
    router.get(route('work-orders.index'), { 
        status: props.statusFilter,
        sub_filter: filter,
        search: searchQuery.value 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(searchQuery, debounce((value) => {
    router.get(route('work-orders.index'), { 
        status: props.statusFilter,
        sub_filter: props.subFilter,
        search: value 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

watch(viewMode, (newMode) => {
    localStorage.setItem('workOrdersViewMode', newMode);
});

function getStatusClass(status) {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        in_progress: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        done: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    };
    return classes[status] || classes.draft;
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    if (vehicle.make?.name) {
        return `${vehicle.make.name} ${vehicle.model?.name || ''}`.trim();
    }
    return vehicle.make_other || '';
}

function calculateTotal(items) {
    if (!items || !Array.isArray(items)) return '0.00';
    return items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0).toFixed(2);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { 
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Check if order is overdue (expected_end_date passed and still open)
function isOverdue(order) {
    if (!order.expected_end_date) return false;
    if (['done', 'cancelled'].includes(order.status)) return false;
    const dueDate = new Date(order.expected_end_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return dueDate < today;
}

// Get date to display: closed_at for closed orders, expected_end_date for open
function getDisplayDate(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return order.closed_at || order.updated_at;
    }
    return order.expected_end_date || order.entry_date;
}

// Get date label based on status
function getDateLabel(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return t('work_orders.closed_date');
    }
    return t('work_orders.due_date');
}

// Get date styling class based on status and overdue
function getDateClass(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return 'bg-gray-50 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700';
    }
    if (isOverdue(order)) {
        return 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-200 dark:border-red-700';
    }
    return 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-800/50';
}

function formatCurrency(value) {
    return parseFloat(value || 0).toFixed(2);
}

function formatCurrencyValue(value) {
    return parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0';
    return parseFloat(value).toLocaleString('en-US', { maximumFractionDigits: 2 });
}

function getColorHex(colorName) {
    if (!colorName) return '#e2e8f0';
    const colors = {
        'أبيض': '#ffffff', 'White': '#ffffff',
        'أسود': '#000000', 'Black': '#000000',
        'فضي': '#c0c0c0', 'Silver': '#c0c0c0',
        'رمادي': '#808080', 'Gray': '#808080',
        'أحمر': '#ff0000', 'Red': '#ff0000',
        'أزرق': '#0000ff', 'Blue': '#0000ff',
        'أصفر': '#ffff00', 'Yellow': '#ffff00',
        'أخضر': '#008000', 'Green': '#008000',
    };
    return colors[colorName] || '#e2e8f0';
}

function getStatusGradient(status) {
    const classes = {
        draft: 'from-gray-400 to-gray-500',
        open: 'from-blue-400 to-blue-500',
        in_progress: 'from-amber-400 to-amber-500',
        done: 'from-emerald-400 to-emerald-500',
        cancelled: 'from-red-400 to-red-500',
    };
    return classes[status] || classes.draft;
}

function handleSaved() {
    showCreateModal.value = false;
    success(t('common.saved_success'));
    router.reload({ only: ['workOrders'] });
}

// ==================== Sorting Logic ====================
const sortColumn = ref('');
const sortDirection = ref('asc'); // 'asc' or 'desc'

function toggleSort(column) {
    if (sortColumn.value === column) {
        // Toggle direction
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // New column
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
}

// Sorted work orders based on selected column
const sortedWorkOrders = computed(() => {
    if (!sortColumn.value) return allWorkOrders.value;
    
    const sorted = [...allWorkOrders.value].sort((a, b) => {
        let valA, valB;
        
        switch (sortColumn.value) {
            case 'code':
                valA = a.code || a.id || 0;
                valB = b.code || b.id || 0;
                break;
            case 'expected_end_date':
                valA = a.expected_end_date ? new Date(a.expected_end_date).getTime() : 0;
                valB = b.expected_end_date ? new Date(b.expected_end_date).getTime() : 0;
                break;
            case 'vehicle':
                valA = a.vehicle?.plate_number || '';
                valB = b.vehicle?.plate_number || '';
                break;
            case 'contact_name':
                valA = a.contact_name || a.customer?.name || '';
                valB = b.contact_name || b.customer?.name || '';
                break;
            case 'services_count':
                valA = a.items?.length || 0;
                valB = b.items?.length || 0;
                break;
            case 'total':
                valA = parseFloat(a.total) || 0;
                valB = parseFloat(b.total) || 0;
                break;
            case 'paid':
                valA = parseFloat(a.paid_amount) || 0;
                valB = parseFloat(b.paid_amount) || 0;
                break;
            case 'balance':
                valA = getBalance(a);
                valB = getBalance(b);
                break;
            default:
                return 0;
        }
        
        // String comparison
        if (typeof valA === 'string') {
            const compare = valA.localeCompare(valB, 'ar');
            return sortDirection.value === 'asc' ? compare : -compare;
        }
        
        // Number comparison
        if (sortDirection.value === 'asc') {
            return valA - valB;
        }
        return valB - valA;
    });
    
    return sorted;
});

// ==================== Balance Helpers ====================
function getBalance(order) {
    return (parseFloat(order.total) || 0) - (parseFloat(order.paid_amount) || 0);
}

function getBalanceColor(order) {
    const balance = getBalance(order);
    if (balance > 0) return 'text-red-600 dark:text-red-400';
    if (balance < 0) return 'text-amber-600 dark:text-amber-400';
    return 'text-green-600 dark:text-green-400';
}
</script>
