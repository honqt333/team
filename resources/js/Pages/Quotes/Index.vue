<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('quotes.title')"
                :subtitle="$t('quotes.subtitle')"
                :totalCount="quotes ? toEnglish(quotes.total) : null"
                :countLabel="$t('quotes.total_count')"
                gradientFrom="from-amber-500"
                gradientTo="to-orange-600"
                glowFrom="from-amber-500"
                glowTo="to-orange-600"
                badgeBg="bg-amber-50/50 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-100/50 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <button @click="printQuotes" :title="$t('common.print')"
                            class="p-2.5 text-gray-500 hover:text-amber-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>

                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <div class="flex gap-1.5">
                            <button @click="viewMode = 'grid'" :title="$t('common.grid_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'grid'
                                        ? 'bg-amber-600 text-white shadow-amber-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button @click="viewMode = 'list'" :title="$t('common.list_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'list'
                                        ? 'bg-amber-600 text-white shadow-amber-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button v-if="can('quotes.create')" @click="openCreateModal"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-2xl font-black shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all group/add">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('quotes.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col gap-6">
                        <!-- Search Bar -->
                        <div class="relative group">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none group-focus-within:text-amber-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="searchQuery"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all outline-none shadow-sm"
                                :placeholder="$t('quotes.search')" />
                        </div>

                        <!-- Filter Tabs -->
                        <div class="flex flex-wrap gap-3">
                            <button v-for="tab in filterTabs" :key="tab.key" @click="setStatusFilter(tab.key)" :class="[
                                'relative flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200',
                                currentStatus === tab.key
                                    ? 'text-white shadow-lg'
                                    : 'bg-white dark:bg-gray-800 hover:shadow-md border border-gray-200 dark:border-gray-700'
                            ]"
                                :style="currentStatus === tab.key ? `background: linear-gradient(135deg, ${tab.gradientFrom}, ${tab.gradientTo})` : ''">
                                <!-- Icon -->
                                <div :class="[
                                    'w-8 h-8 rounded-lg flex items-center justify-center',
                                    currentStatus === tab.key
                                        ? 'bg-white/20'
                                        : tab.bgColor
                                ]">
                                    <component :is="tab.icon" :class="[
                                        'w-4 h-4',
                                        currentStatus === tab.key ? 'text-white' : tab.iconColor
                                    ]" />
                                </div>

                                <!-- Label & Count -->
                                <div class="flex items-center gap-2">
                                    <span :class="[
                                        'font-medium text-sm',
                                        currentStatus === tab.key ? 'text-white' : 'text-gray-700 dark:text-gray-300'
                                    ]">
                                        {{ tab.label }}
                                    </span>
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-xs font-bold',
                                        currentStatus === tab.key
                                            ? 'bg-white/20 text-white'
                                            : tab.bgColor + ' ' + tab.iconColor
                                    ]">
                                        {{ toEnglish(tab.count) }}
                                    </span>
                                </div>

                                <!-- Selected Check -->
                                <svg v-if="currentStatus === tab.key" class="w-4 h-4 text-white/80" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>


            <!-- Loading State -->
            <div v-if="!quotes" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-amber-200 dark:border-amber-900 rounded-full"></div>
                    <div
                        class="absolute top-0 left-0 w-12 h-12 border-4 border-amber-600 border-t-transparent rounded-full animate-spin">
                    </div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredQuotes.length === 0"
                class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div
                    class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('quotes.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('quotes.empty_hint') }}</p>
                <button v-if="can('quotes.create')" @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('quotes.add') }}
                </button>
            </div>

            <!-- Grid View: Modern Elegant Cards -->
            <div v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                <div v-for="quote in filteredQuotes" :key="quote.id" @click="navigateToShow(quote)"
                    class="group relative bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/50 hover:border-amber-400/50 dark:hover:border-amber-500/50 hover:shadow-2xl hover:shadow-amber-500/10 hover:-translate-y-1.5 cursor-pointer transition-all duration-500 overflow-hidden flex flex-col">
                    
                    <!-- Top Accent / Status Gradient Line -->
                    <div class="h-1.5 w-full bg-gradient-to-r" :class="getStatusGradient(quote.status)"></div>



                    <!-- Card Header: Code & Date -->
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex flex-col gap-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 tracking-widest uppercase">#{{ toEnglish(quote.code) }}</span>
                                </div>
                                <!-- Converted WO Link (Under ID) -->
                                <div v-if="quote.status === 'converted' && (quote.converted_work_order?.id || quote.converted_work_order_id)" 
                                    class="flex items-center gap-1 group/link"
                                    @click.stop>
                                            <Link :href="route('work-orders.show', quote.converted_work_order?.id || quote.converted_work_order_id)" 
                                        class="text-[10px] font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/40 px-2 py-0.5 rounded-lg flex items-center gap-1 hover:bg-purple-100 transition-all border border-purple-100 dark:border-purple-800/50">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        {{ quote.converted_work_order?.code }}
                                    </Link>
                                </div>
                            </div>
                            <div :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm', getStatusClass(quote.status)]">
                                {{ $t(`quotes.status.${quote.status}`) }}
                            </div>
                        </div>
                    </div>

                    <!-- Main Vehicle Info Section -->
                    <div class="px-4 py-3 flex-1">
                        <!-- Saudi Plate Component & Brand Logo -->
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <!-- Manufacturer Logo -->
                            <Tooltip :text="quote.vehicle.make.name" v-if="quote.vehicle?.make?.logo_path">
                                <div class="w-10 h-10 flex items-center justify-center p-1 bg-white dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm transition-transform hover:scale-110 cursor-pointer">
                                    <img :src="`/storage/${quote.vehicle.make.logo_path}`" class="w-full h-full object-contain filter dark:brightness-110" alt="Brand" />
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
                                            {{ toEnglish(quote.vehicle?.plate_number?.split(' ')?.[0] || '1234') }}
                                        </span>
                                        <span class="text-lg font-black text-gray-900 tracking-[0.4em] font-mono">
                                            {{ toEnglish(quote.vehicle?.plate_number?.split(' ')?.[1] || 'ABC') }}
                                        </span>
                                    </div>
                                </div>
                            </Tooltip>
                        </div>

                        <!-- Vehicle Name & Customer -->
                        <div class="space-y-3">
                            <div class="text-center">
                                <div class="flex flex-col items-center justify-center gap-1 mb-1">
                                    <Tooltip :text="getVehicleName(quote.vehicle)">
                                        <h3 class="text-sm font-black text-gray-900 dark:text-white leading-tight group-hover:text-amber-600 transition-colors cursor-pointer line-clamp-1">
                                            {{ getVehicleName(quote.vehicle) }}
                                        </h3>
                                    </Tooltip>
                                    <!-- Vehicle Color Badge with Visual Indicator -->
                                    <div v-if="quote.vehicle?.color" 
                                        class="flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-md border border-slate-200 dark:border-slate-600 cursor-default">
                                        <span class="w-2 h-2 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" :style="{ backgroundColor: getColorHex(quote.vehicle.color) }"></span>
                                        {{ quote.vehicle.color }}
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
                                            {{ quote.customer?.name || $t('common.unknown') }}
                                        </span>
                                    </Tooltip>
                                </div>
                            </div>

                        <!-- Odometer & Stats Grid -->
                        <div class="grid grid-cols-3 gap-1 py-2 border-y border-gray-50 dark:border-gray-700/50">
                            <!-- KM -->
                            <Tooltip :text="$t('work_orders.form.odometer')" class="flex-1">
                                <div class="group/stat flex flex-col items-center justify-center border-e border-gray-100 dark:border-gray-700/50 cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/30 rounded-lg py-1 w-full h-full">
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter transition-colors group-hover/stat:text-amber-600">{{ $t('work_orders.form.odometer') }}</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[11px] font-black text-slate-700 dark:text-slate-300">{{ formatNumber(quote.odometer || quote.vehicle?.odometer || 0) }}</span>
                                        <span class="text-[8px] font-black text-amber-500">KM</span>
                                    </div>
                                </div>
                            </Tooltip>
                            <!-- Services Count -->
                            <Tooltip :text="$t('quotes.card.services')" class="flex-1">
                                <div class="group/stat flex flex-col items-center justify-center border-e border-gray-100 dark:border-gray-700/50 cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/10 rounded-lg py-1 w-full h-full">
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter transition-colors group-hover/stat:text-blue-600">{{ $t('quotes.card.services') }}</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[11px] font-black text-blue-600 dark:text-blue-400">{{ toEnglish(quote.lines_count || 0) }}</span>
                                        <svg class="w-2 h-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </div>
                                </div>
                            </Tooltip>
                            <!-- Parts Count -->
                            <Tooltip :text="$t('quotes.card.parts')" class="flex-1">
                                <div class="group/stat flex flex-col items-center justify-center cursor-pointer transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-900/10 rounded-lg py-1 w-full h-full">
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter transition-colors group-hover/stat:text-emerald-600">{{ $t('quotes.card.parts') }}</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[11px] font-black text-emerald-600 dark:text-emerald-400">{{ toEnglish(quote.parts_count || 0) }}</span>
                                        <svg class="w-2 h-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                    </div>
                                </div>
                            </Tooltip>
                        </div>
                        </div>
                    </div>

                    <!-- Footer: Total Section -->
                    <div class="px-5 py-4 bg-slate-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between mt-auto">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            {{ $t('quotes.form.grand_total') }}
                        </span>
                        <div class="flex items-baseline gap-1 whitespace-nowrap">
                            <span class="text-base font-black bg-gradient-to-br from-amber-600 to-orange-600 bg-clip-text text-transparent">
                                {{ formatCurrencyValue(quote.total) }}
                            </span>
                            <span class="text-[9px] font-black text-amber-600 uppercase">{{ $t('common.currency') }}</span>
                        </div>
                    </div>

                    <!-- View Details Arrow (Hover Only) -->
                    <div class="absolute top-1/2 -end-4 group-hover:end-4 opacity-0 group-hover:opacity-100 transition-all duration-500" @click.stop>
                        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">#</th>
                                <th @click="toggleSort('code')" class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('quotes.columns.code') }}
                                        <SortIcon :active="sortColumn === 'code'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('vehicle')" class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('quotes.columns.vehicle') }}
                                        <SortIcon :active="sortColumn === 'vehicle'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('customer')" class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        {{ $t('quotes.columns.customer') }}
                                        <SortIcon :active="sortColumn === 'customer'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('services')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('quotes.card.services') }}
                                        <SortIcon :active="sortColumn === 'services'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('subtotal')" class="px-4 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{ $t('quotes.form.subtotal') }}
                                        <SortIcon :active="sortColumn === 'subtotal'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('discount')" class="px-4 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{ $t('quotes.form.discount') }}
                                        <SortIcon :active="sortColumn === 'discount'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('total')" class="px-4 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{ $t('quotes.columns.total') }}
                                        <SortIcon :active="sortColumn === 'total'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('date')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors w-24">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('common.created_at') }}
                                        <SortIcon :active="sortColumn === 'date'" :direction="sortDirection" />
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <tr v-for="(quote, index) in sortedQuotes" :key="quote.id" @click="navigateToShow(quote)"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors group">
                                <td class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400 font-medium">
                                    {{ toEnglish(index + 1) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline">
                                        #{{ toEnglish(quote.code) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-sm font-bold text-gray-800 dark:text-gray-200 font-mono" dir="ltr">
                                            {{ toEnglish(quote.vehicle?.plate_number) || '-' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[120px]">
                                            {{ getVehicleName(quote.vehicle) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                        {{ quote.customer?.name || '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold">
                                        {{ toEnglish(quote.lines_count || 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-end">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ formatNumber(quote.subtotal || 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-end">
                                    <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                        {{ formatNumber(quote.total_discount || 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-end">
                                    <span class="text-sm font-black text-amber-600 dark:text-amber-400">
                                        {{ formatNumber(quote.total || 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                                        {{ formatDate(quote.created_at) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scroll Sentinel -->
            <div ref="loadMoreSentinel" class="py-6 flex justify-center w-full">
                <div v-if="loadingMore" class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="text-sm font-medium">{{ $t('common.loading') }}</span>
                </div>
                <div v-else-if="allQuotes.length >= (quotes?.total || 0) && (quotes?.total || 0) > 0"
                    class="text-sm text-gray-400 dark:text-gray-600">
                    {{ $t('quotes.all_loaded') || 'All quotes loaded' }}
                </div>
            </div>
        </div>

        <!-- Quote Form Modal -->
        <QuoteFormModal :show="showModal" :quote="selectedQuote" :customers="customers" :departments="departments"
            :makes="makes" :colors="colors" :modelsByMake="modelsByMake" @close="closeModal" @saved="handleSaved" />

        <!-- Print Section (Teleported to body for printing) -->
        <Teleport to="body">
            <div class="print-section hidden">
                <PrintHeader :title="$t('quotes.title')" />

                <!-- Table -->
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $t('quotes.columns.code') }}</th>
                            <th>{{ $t('common.date') }}</th>
                            <th>{{ $t('quotes.columns.vehicle') }}</th>
                            <th>{{ $t('quotes.columns.customer') }}</th>
                            <th>{{ $t('quotes.card.services') }}</th>
                            <th>{{ $t('quotes.columns.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(quote, index) in sortedQuotes" :key="quote.id">
                            <td>{{ toEnglish(index + 1) }}</td>
                            <td>{{ toEnglish(quote.code) }}</td>
                            <td>{{ formatDate(quote.created_at) }}</td>
                            <td dir="ltr" class="text-left font-sans">{{ toEnglish(quote.vehicle?.plate_number) || '-' }}</td>
                            <td>{{ quote.customer?.name || '-' }}</td>
                            <td>{{ toEnglish(quote.lines_count || 0) }}</td>
                            <td>{{ formatNumber(quote.total || 0) }}</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-8 text-center text-xs text-gray-400">
                    {{ $page.props.auth.user.name }}
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Tooltip from '@/Components/Tooltip.vue';
import SortIcon from '@/Components/Common/SortIcon.vue';
import PrintHeader from '@/Components/Print/PrintHeader.vue';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    quotes: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    departments: {
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
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterCounts: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const { success } = useToast();
const { confirm } = useConfirm();
const { formatCurrency: formatCurrencyEN, toEnglish } = useNumberFormat();
const { can } = usePermission();
const showModal = ref(false);
const selectedQuote = ref(null);
const activeTab = ref(localStorage.getItem('quotesActiveTab') || 'pending');
const viewMode = ref(localStorage.getItem('quotesViewMode') || 'grid');

// Infinite Scroll Refs
const allQuotes = ref(props.quotes?.data || []);
const nextPageUrl = ref(props.quotes?.next_page_url);
const loadingMore = ref(false);
const loadMoreSentinel = ref(null);
let observer = null;

// Watch props change (filters, etc)
watch(() => props.quotes, (newVal) => {
    if (newVal) {
        allQuotes.value = newVal.data;
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
        const apiUrl = url.toString().replace('/app/quotes', '/app/api/quotes-index');

        const response = await axios.get(apiUrl);
        const data = response.data;

        allQuotes.value.push(...data.data);
        nextPageUrl.value = data.next_page_url;
    } catch (e) {
        console.error('Failed to load more quotes', e);
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

    // Check sentinel ref changes (in case of tab switch/v-if)
    watch(loadMoreSentinel, (el) => {
        if (observer) observer.disconnect();
        if (el) observer.observe(el);
    });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// Icons for filter tabs
const IconAll = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>` };
const IconPending = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };
const IconApproved = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };
const IconConverted = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>` };
const IconRejected = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };

const filterTabs = computed(() => [
    {
        key: 'pending',
        label: t('quotes.tabs.without_card'),
        icon: IconPending,
        iconColor: 'text-amber-500',
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        gradientFrom: '#f59e0b',
        gradientTo: '#d97706',
        count: props.filterCounts.pending || 0
    },
    {
        key: 'all',
        label: t('quotes.tabs.all'),
        icon: IconAll,
        iconColor: 'text-slate-500',
        bgColor: 'bg-slate-100 dark:bg-slate-900/30',
        gradientFrom: '#64748b',
        gradientTo: '#475569',
        count: props.filterCounts.all || 0
    },
    {
        key: 'approved',
        label: t('quotes.status.approved'),
        icon: IconApproved,
        iconColor: 'text-emerald-500',
        bgColor: 'bg-emerald-100 dark:bg-emerald-900/30',
        gradientFrom: '#10b981',
        gradientTo: '#059669',
        count: props.filterCounts.approved || 0
    },
    {
        key: 'converted',
        label: t('quotes.status.converted'),
        icon: IconConverted,
        iconColor: 'text-blue-500',
        bgColor: 'bg-blue-100 dark:bg-blue-900/30',
        gradientFrom: '#3b82f6',
        gradientTo: '#2563eb',
        count: props.filterCounts.converted || 0
    },
    {
        key: 'rejected',
        label: t('quotes.status.rejected'),
        icon: IconRejected,
        iconColor: 'text-red-500',
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        gradientFrom: '#ef4444',
        gradientTo: '#dc2626',
        count: props.filterCounts.rejected || 0
    },
]);

// Determine current status from URL filters
const currentStatus = computed(() => props.filters.status || 'pending');

const searchQuery = ref(props.filters.search || '');

function setStatusFilter(status) {
    router.get(route('app.quotes.index'), {
        status: status,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const filters = ref({
    search: props.filters.search || '',
    status: '',
    dateRange: 'all',
});

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

watch(searchQuery, debounce((value) => {
    router.get(route('app.quotes.index'), {
        search: value,
        status: currentStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

watch(viewMode, (newMode) => {
    localStorage.setItem('quotesViewMode', newMode);
});

watch(activeTab, (newTab) => {
    localStorage.setItem('quotesActiveTab', newTab);
});

// Computed: Count of quotes without work card (not converted and not rejected)
const pendingCount = computed(() => {
    if (!props.quotes?.data) return 0;
    return props.quotes.data.filter(q => q.status !== 'converted' && q.status !== 'rejected').length;
});

// Computed: Total count
const allCount = computed(() => {
    return props.quotes?.total || 0;
});

// Computed: Check if any filters are active
const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.status || filters.value.dateRange !== 'all';
});

// Computed: Filter quotes - using allQuotes ref for infinite scroll
const filteredQuotes = computed(() => {
    return allQuotes.value;
});

function clearFilters() {
    filters.value = {
        search: '',
        status: '',
        dateRange: 'all',
    };
}

function getStatusClass(status) {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        converted: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    };
    return classes[status] || classes.draft;
}

function getStatusGradient(status) {
    const classes = {
        draft: 'from-gray-400 to-gray-500',
        sent: 'from-blue-400 to-blue-500',
        approved: 'from-emerald-400 to-emerald-500',
        rejected: 'from-red-400 to-red-500',
        converted: 'from-purple-400 to-purple-500',
    };
    return classes[status] || classes.draft;
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    const parts = [];
    if (vehicle.make?.name) parts.push(vehicle.make.name);
    if (vehicle.model?.name) parts.push(vehicle.model.name);
    if (vehicle.year) parts.push(vehicle.year);
    return parts.join(' ') || '';
}

function formatCurrency(amount) {
    return formatCurrencyEN(amount) + ' ' + t('common.currency');
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
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

function formatCurrencyValue(value) {
    return formatCurrencyEN(value);
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0';
    return value.toLocaleString('en-US');
}

function getWhatsAppNumber(customer) {
    if (!customer) return null;
    const phone = customer.whatsapp || customer.phone;
    if (!phone) return null;
    return phone.replace(/[^\d+]/g, "").replace(/^\+/, "");
}

function openCreateModal() {
    selectedQuote.value = null;
    showModal.value = true;
}

function openEditModal(quote) {
    if (quote.status !== 'draft' && quote.status !== 'sent') {
        return;
    }
    selectedQuote.value = quote;
    showModal.value = true;
}

function navigateToShow(quote) {
    router.visit(route('app.quotes.show', quote.id));
}

function closeModal() {
    showModal.value = false;
    selectedQuote.value = null;
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

// Sorted quotes based on selected column
const sortedQuotes = computed(() => {
    if (!sortColumn.value) return filteredQuotes.value;
    
    const sorted = [...filteredQuotes.value].sort((a, b) => {
        let valA, valB;
        
        switch (sortColumn.value) {
            case 'code':
                valA = a.code || '';
                valB = b.code || '';
                break;
            case 'vehicle':
                valA = a.vehicle?.plate_number || '';
                valB = b.vehicle?.plate_number || '';
                break;
            case 'customer':
                valA = a.customer?.name || '';
                valB = b.customer?.name || '';
                break;
            case 'services':
                valA = a.lines_count || 0;
                valB = b.lines_count || 0;
                break;
            case 'subtotal':
                valA = parseFloat(a.subtotal) || 0;
                valB = parseFloat(b.subtotal) || 0;
                break;
            case 'discount':
                valA = parseFloat(a.total_discount) || 0;
                valB = parseFloat(b.total_discount) || 0;
                break;
            case 'total':
                valA = parseFloat(a.total) || 0;
                valB = parseFloat(b.total) || 0;
                break;
            case 'date':
                valA = a.created_at ? new Date(a.created_at).getTime() : 0;
                valB = b.created_at ? new Date(b.created_at).getTime() : 0;
                break;
            default:
                return 0;
        }
        
        if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
    
    return sorted;
});

// Print quotes
async function printQuotes() {
    // We can only print what's currently loaded in allQuotes
    // If the user wants to print everything, they should scroll to the bottom first
    // Or we could fetch everything here, but usually printing current view is expected
    
    // Check if we need to load more data to have everything (up to a limit for performance)
    if (nextPageUrl.value && allQuotes.value.length < (props.quotes?.total || 0)) {
        // Option to warn user or just print what's loaded
        // For now, print what's loaded like in WorkOrders
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    // Trigger print
    window.print();
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['quotes'] });
}
</script>
