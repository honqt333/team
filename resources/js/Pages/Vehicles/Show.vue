<template>
    <AppLayout>
        <div class="space-y-8">
            <!-- Header Card (Unified Design & Colors) -->
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2.5rem] blur opacity-[0.06] group-hover:opacity-[0.10] transition duration-700"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-[2.25rem] shadow-2xl shadow-indigo-500/10 border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Navigation & Actions Bar -->
                    <div class="absolute top-0 inset-x-0 p-4 flex items-center justify-between z-20" :dir="isRtl ? 'rtl' : 'ltr'">
                        <Link :href="route('vehicles.index')"
                            :title="$t('common.back')"
                            class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all duration-300 text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M14 5l7 7-7 7M21 12H3" />
                            </svg>
                        </Link>

                        <div class="flex items-center gap-2 p-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                            <button v-if="can('crm.vehicles.update') || isAnyAdmin()" @click="showEditModal = true"
                                :title="$t('common.edit')"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-indigo-100/70 dark:border-indigo-800/30 bg-indigo-50/70 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 hover:bg-white dark:hover:bg-indigo-900/30 hover:shadow-md transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.edit') }}</span>
                            </button>

                            <button v-if="can('crm.work_orders.create') || isAnyAdmin()" @click="openWorkOrderModal"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-indigo-100/70 dark:border-indigo-800/30 bg-indigo-50/70 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 hover:bg-white dark:hover:bg-indigo-900/30 hover:shadow-md transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.btn_create_work_order') }}</span>
                            </button>

                            <button v-if="can('crm.quotes.create') || isAnyAdmin()" @click="openQuoteModal"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-amber-100/70 dark:border-amber-800/30 bg-amber-50/70 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 hover:bg-white dark:hover:bg-amber-900/30 hover:shadow-md transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.btn_create_quote') }}</span>
                            </button>

                            <button v-if="can('crm.vehicles.delete') || isAnyAdmin()" @click="confirmDelete" :disabled="!canDelete"
                                :title="canDelete ? $t('common.delete') : $t('customers.cannot_delete_has_data')"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border transition-all',
                                    canDelete
                                        ? 'border-red-100/70 dark:border-red-800/30 bg-red-50/70 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-white dark:hover:bg-red-900/30 hover:shadow-md'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-900/30 text-gray-400 cursor-not-allowed opacity-70'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.delete') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Watermark Decoration -->
                    <div class="absolute top-0 right-0 p-12 opacity-[0.03] dark:opacity-[0.05] pointer-events-none select-none">
                        <svg class="w-80 h-80 text-indigo-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                    </div>

                    <div class="relative z-10 p-6 pt-16">
                        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                            <div class="flex items-center gap-6 flex-1">
                                <!-- Vehicle Brand/Logo Container -->
                                <div class="relative">
                                    <div class="w-24 h-24 rounded-[2.25rem] bg-gradient-to-br from-indigo-600 to-purple-600 shadow-xl shadow-indigo-500/35 flex items-center justify-center text-white ring-4 ring-indigo-50 dark:ring-indigo-900/20 transition-transform group-hover:scale-[1.03] duration-500 overflow-hidden p-4">
                                        <img v-if="vehicle.make?.logo_path" :src="`/storage/${vehicle.make.logo_path}`"
                                            class="w-full h-full object-contain brightness-0 invert" alt="" />
                                        <svg v-else class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <!-- Color Indicator Badge -->
                                    <div v-if="vehicle.color" 
                                        class="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl bg-white dark:bg-gray-700 shadow-lg border-4 border-indigo-50 dark:border-indigo-900/20 flex items-center justify-center">
                                        <div class="w-6 h-6 rounded-lg shadow-inner border border-black/5" :style="{ backgroundColor: getColorHex(vehicle.color) }"></div>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3 mb-1">
                                        <h1 class="text-4xl font-black text-gray-900 dark:text-white leading-tight tracking-tight">
                                            {{ getVehicleName(vehicle) }}
                                        </h1>
                                        <div class="px-4 py-2 bg-gray-900 text-white rounded-xl border border-gray-800 shadow-lg flex items-center gap-2">
                                            <span class="text-xl font-black tracking-widest font-mono">{{ toEnglish(vehicle.plate_number) }}</span>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-indigo-500/80 dark:text-indigo-400/80 mb-4">{{ $t('vehicles.subtitle') }}</p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm font-bold text-gray-500 dark:text-gray-400">
                                        <Link v-if="vehicle.customer" :href="route('customers.show', vehicle.customer.id)"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border border-indigo-100/50 hover:bg-white dark:hover:bg-indigo-900/40 transition-all shadow-sm">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            {{ vehicle.customer?.name }}
                                        </Link>
                                        
                                        <span v-if="vehicle.odometer" 
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-teal-50/50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400 border border-teal-100/50">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            {{ toEnglish(vehicle.odometer?.toLocaleString()) }} {{ $t('common.km') }}
                                        </span>

                                        <span v-if="vehicle.vin" 
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-gray-50/50 dark:bg-gray-700/30 text-gray-700 dark:text-gray-300 border border-gray-100 dark:border-gray-600">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                            </div>
                                            <span class="font-mono">{{ vehicle.vin }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Cards -->
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-indigo-50/50 dark:bg-indigo-900/20 rounded-3xl border border-indigo-100/50 dark:border-indigo-800/30 hover:bg-white dark:hover:bg-indigo-900/40 transition-all hover:shadow-lg hover:shadow-indigo-500/10">
                                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400 group-hover/stat:scale-110 transition-transform">{{ toEnglish(counts.workOrders) }}</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $t('customers.work_orders') }}</span>
                                </div>
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-amber-50/50 dark:bg-amber-900/20 rounded-3xl border border-amber-100/50 dark:border-amber-800/30 hover:bg-white dark:hover:bg-amber-900/40 transition-all hover:shadow-lg hover:shadow-amber-500/10">
                                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400 group-hover/stat:scale-110 transition-transform">{{ toEnglish(counts.quotes) }}</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $t('quotes.title') }}</span>
                                </div>
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-emerald-50/50 dark:bg-emerald-900/20 rounded-3xl border border-emerald-100/50 dark:border-emerald-800/30 hover:bg-white dark:hover:bg-emerald-900/40 transition-all hover:shadow-lg hover:shadow-emerald-500/10">
                                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 group-hover/stat:scale-110 transition-transform">{{ toEnglish(counts.invoices) }}</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $t('invoices.title') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="space-y-4">
                <!-- Tabs Navigation -->
                <div class="p-1 bg-gray-100/50 dark:bg-gray-900/50 backdrop-blur-xl rounded-3xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner overflow-x-auto no-scrollbar">
                    <div class="flex items-center gap-1 min-w-max">
                        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                            'group relative flex items-center gap-3 px-5 py-2.5 rounded-2xl transition-all duration-500 ease-out overflow-hidden',
                            activeTab === tab.key
                                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-xl shadow-indigo-500/30 scale-[1.01]'
                                : 'text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800 hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/20 hover:-translate-y-0.5'
                        ]">
                            <!-- Animated Background Glow for Active -->
                            <div v-if="activeTab === tab.key" class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] animate-[shimmer_2s_infinite]"></div>
                            
                            <div :class="[
                                'w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-500',
                                activeTab === tab.key
                                    ? 'bg-white/20 rotate-[12deg] scale-105 shadow-inner'
                                    : 'bg-white dark:bg-gray-800 shadow-sm group-hover:rotate-[-6deg] group-hover:scale-105'
                            ]">
                                <component :is="tab.icon" :class="[
                                    'w-4.5 h-4.5 transition-all duration-500',
                                    activeTab === tab.key ? 'text-white scale-105' : 'text-indigo-500 dark:text-indigo-400 group-hover:text-purple-600'
                                ]" />
                            </div>

                            <div class="flex flex-col items-start relative z-10">
                                <span :class="[
                                    'text-[9px] font-black uppercase tracking-[0.15em] transition-colors duration-500',
                                    activeTab === tab.key ? 'text-white/70' : 'text-gray-400 dark:text-gray-500'
                                ]">{{ tab.label }}</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-base font-black leading-none">{{ toEnglish(tab.count) }}</span>
                                    <span v-if="activeTab === tab.key" class="w-1 h-1 rounded-full bg-white animate-pulse"></span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Tab Content Container -->
                <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl shadow-indigo-500/5 border border-gray-100/50 dark:border-gray-700/50 p-6 min-h-[400px] relative overflow-hidden">
                    <!-- Background Decoration -->
                    <div class="absolute top-0 right-0 p-16 opacity-[0.01] dark:opacity-[0.02] pointer-events-none select-none -translate-y-8 translate-x-8">
                        <component :is="tabs.find(t => t.key === activeTab)?.icon" class="w-64 h-64" />
                    </div>

                    <!-- ========== WORK ORDERS TAB ========== -->
                    <div v-if="activeTab === 'workOrders'" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative group max-w-md">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none group-focus-within:text-indigo-500 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" v-model="workOrderSearch" :placeholder="$t('work_orders.search')"
                                        class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" />
                                </div>
                                <!-- View Toggle -->
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button @click="workOrderViewMode = 'grid'"
                                        :class="['px-2 py-1 rounded transition-colors', workOrderViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
                                        </svg>
                                    </button>
                                    <button @click="workOrderViewMode = 'list'"
                                        :class="['px-2 py-1 rounded transition-colors', workOrderViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div v-if="workOrderViewMode === 'grid' && filteredWorkOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <Link v-for="order in filteredWorkOrders" :key="order.id" :href="route('work-orders.show', order.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ order.code }}</span>
                                    <span :class="getStatusClass(order.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`work_orders.status.${order.status}`) }}</span>
                                </div>
                                <div class="text-lg font-black text-gray-900 dark:text-white mb-3">
                                    {{ formatCurrency(order.total_incl_tax || 0) }}
                                </div>
                                <div class="text-xs text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700">
                                    {{ formatDate(order.created_at) }}
                                </div>
                            </Link>
                        </div>
                        
                        <!-- List View -->
                        <div v-else-if="workOrderViewMode === 'list' && filteredWorkOrders.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.code') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.status') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.total') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="order in filteredWorkOrders" :key="order.id" @click="router.visit(route('work-orders.show', order.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">{{ order.code }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span :class="getStatusClass(order.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`work_orders.status.${order.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-black text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ formatCurrency(order.total_incl_tax || 0) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatDate(order.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 font-bold">{{ $t('customers.no_work_orders') }}</p>
                        </div>
                    </div>

                    <!-- ========== RATINGS (QUOTES) TAB ========== -->
                    <div v-if="activeTab === 'ratings'" class="space-y-4">
                        <div v-if="quotes.length > 0" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative group max-w-md">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none group-focus-within:text-amber-500 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" v-model="quoteSearch" :placeholder="$t('quotes.search')"
                                        class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500 transition-all shadow-sm" />
                                </div>
                                <!-- View Toggle -->
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button @click="quoteViewMode = 'grid'"
                                        :class="['px-2 py-1 rounded transition-colors', quoteViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
                                        </svg>
                                    </button>
                                    <button @click="quoteViewMode = 'list'"
                                        :class="['px-2 py-1 rounded transition-colors', quoteViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div v-if="quoteViewMode === 'grid' && filteredQuotes.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <Link v-for="quote in filteredQuotes" :key="quote.id" :href="route('app.quotes.show', quote.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-500 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ quote.code }}</span>
                                    <span :class="getQuoteStatusClass(quote.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`quotes.status.${quote.status}`) }}</span>
                                </div>
                                <div class="text-lg font-black text-gray-900 dark:text-white mb-3">
                                    {{ formatCurrency(quote.total_incl_tax || 0) }}
                                </div>
                                <div class="text-xs text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700">
                                    {{ formatDate(quote.created_at) }}
                                </div>
                            </Link>
                        </div>

                        <!-- List View -->
                        <div v-else-if="quoteViewMode === 'list' && filteredQuotes.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.code') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.status') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.total') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">{{ $t('common.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="quote in filteredQuotes" :key="quote.id" @click="router.visit(route('app.quotes.show', quote.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ quote.code }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span :class="getQuoteStatusClass(quote.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`quotes.status.${quote.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-black text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ formatCurrency(quote.total_incl_tax || 0) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatDate(quote.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 font-bold">{{ $t('quotes.empty') }}</p>
                        </div>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-if="activeTab === 'invoices'" class="space-y-4">
                        <div v-if="invoices.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <Link v-for="invoice in invoices" :key="invoice.id" :href="route('invoices.show', invoice.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-500 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ invoice.invoice_number }}</span>
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ $t(`invoices.status.${invoice.status}`) }}</span>
                                </div>
                                <div class="text-lg font-black text-gray-900 dark:text-white mb-3">
                                    {{ formatCurrency(invoice.total_incl_tax) }}
                                </div>
                                <div class="text-xs text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700">
                                    {{ formatDate(invoice.issue_date) }}
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 font-bold">{{ $t('invoices.empty') }}</p>
                        </div>
                    </div>

                    <!-- ========== MILEAGE TAB ========== -->
                    <div v-if="activeTab === 'mileage'" class="space-y-4">
                         <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ $t('vehicles.mileage.history') }}</h3>
                        </div>
                        <div v-if="vehicle.mileage_logs?.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('common.date') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('vehicles.mileage.value') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('vehicles.mileage.difference') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">المصدر</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">المستخدم</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(log, idx) in vehicle.mileage_logs" :key="log.id">
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm font-bold">{{ formatDate(log.recorded_at || log.created_at) }}</td>
                                        <td class="px-4 py-3 font-black text-gray-900 dark:text-white">{{ toEnglish(log.mileage?.toLocaleString()) }} {{ $t('common.km') }}</td>
                                        <td class="px-4 py-3">
                                            <span v-if="log.difference > 0" class="text-sm font-black text-emerald-600">
                                                +{{ toEnglish(log.difference.toLocaleString()) }}
                                            </span>
                                            <span v-else-if="log.difference < 0" class="text-sm font-black text-red-600">
                                                {{ toEnglish(log.difference.toLocaleString()) }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400 italic font-bold">-</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs font-bold text-gray-500">
                                            {{ log.reference_code || 'تحديث يدوي' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs font-bold text-gray-500">
                                            {{ log.creator?.name || 'النظام' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 font-bold">{{ $t('vehicles.mileage.no_history') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <VehicleFormModal v-if="showEditModal" :show="showEditModal" :vehicle="vehicle" :customers="[vehicle.customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake" @close="showEditModal = false" @saved="handleVehicleSaved" />
        <WorkOrderFormModal v-if="showWorkOrderModal" :show="showWorkOrderModal" :customers="[vehicle.customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake" :departments="departments" :defaultVehicleId="vehicle.id" @close="showWorkOrderModal = false" @saved="handleWorkOrderSaved" />
        <QuoteFormModal v-if="showQuoteModal" :show="showQuoteModal" :customers="[vehicle.customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake" :departments="departments" :services="services" :defaultVehicleId="vehicle.id" @close="showQuoteModal = false" @saved="handleQuoteSaved" />
        <ConfirmModal />
    </AppLayout>
</template>

<script setup>
import { ref, computed, h, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    vehicle: Object,
    counts: Object,
    canDelete: Boolean,
    workOrders: Array,
    quotes: Array,
    invoices: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const { confirm } = useConfirm();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

const showEditModal = ref(false);
const showWorkOrderModal = ref(false);
const showQuoteModal = ref(false);
const activeTab = ref(localStorage.getItem('vehicle_active_tab') || 'workOrders');
const workOrderSearch = ref('');
const workOrderViewMode = ref(localStorage.getItem('vehicle_wo_view') || 'grid');
const quoteSearch = ref('');
const quoteViewMode = ref(localStorage.getItem('vehicle_quote_view') || 'grid');

watch(activeTab, (val) => localStorage.setItem('vehicle_active_tab', val));
watch(workOrderViewMode, (val) => localStorage.setItem('vehicle_wo_view', val));
watch(quoteViewMode, (val) => localStorage.setItem('vehicle_quote_view', val));

// Icons
const ClipboardIcon = () => h('svg', { class: 'w-4.5 h-4.5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2.5', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })]);
const StarIcon = () => h('svg', { class: 'w-4.5 h-4.5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2.5', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.382-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })]);
const ReceiptIcon = () => h('svg', { class: 'w-4.5 h-4.5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2.5', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' })]);
const MapIcon = () => h('svg', { class: 'w-4.5 h-4.5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2.5', d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7l5-2.5 5.553 2.776a1 1 0 01.447.894v10.764a1 1 0 01-1.447.894L14 17l-5 3z' })]);

const tabs = computed(() => [
    { key: 'workOrders', label: t('customers.work_orders'), count: props.counts.workOrders, icon: ClipboardIcon },
    { key: 'ratings', label: t('quotes.title'), count: props.counts.quotes, icon: StarIcon },
    { key: 'invoices', label: t('invoices.title'), count: props.counts.invoices, icon: ReceiptIcon },
    { key: 'mileage', label: t('vehicles.mileage.history'), count: props.vehicle.mileage_logs?.length || 0, icon: MapIcon },
]);

const filteredWorkOrders = computed(() => {
    if (!props.workOrders) return [];
    if (!workOrderSearch.value) return props.workOrders;
    const q = workOrderSearch.value.toLowerCase();
    return props.workOrders.filter(w => w.code?.toLowerCase().includes(q));
});

const filteredQuotes = computed(() => {
    if (!props.quotes) return [];
    if (!quoteSearch.value) return props.quotes;
    const q = quoteSearch.value.toLowerCase();
    return props.quotes.filter(quote => quote.code?.toLowerCase().includes(q));
});

// Helper Functions
function getMakeName(vehicle) {
    if (!vehicle) return '';
    return locale.value === 'ar' ? (vehicle.make?.name_ar || vehicle.make?.name_en) : (vehicle.make?.name_en || vehicle.make?.name_ar) || vehicle.make_other || '';
}
function getModelName(vehicle) {
    if (!vehicle) return '';
    return locale.value === 'ar' ? (vehicle.model?.name_ar || vehicle.model?.name_en) : (vehicle.model?.name_en || vehicle.model?.name_ar) || vehicle.model_other || '';
}
function getVehicleName(vehicle) {
    if (!vehicle) return '';
    return [getMakeName(vehicle), getModelName(vehicle), vehicle.year].filter(Boolean).join(' ');
}
function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US');
}
function formatCurrency(amount) {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US', { style: 'currency', currency: 'SAR' }).format(amount);
}
function getStatusClass(status) {
    const classes = { open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    return classes[status] || classes.open;
}
function getQuoteStatusClass(status) {
    const classes = { draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', converted: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' };
    return classes[status] || classes.draft;
}
function getColorHex(colorName) {
    if (!colorName) return '#gray';
    const colorMap = { 'أبيض': '#ffffff', 'أسود': '#1f2937', 'فضي': '#9ca3af', 'رمادي': '#6b7280', 'أحمر': '#ef4444', 'أزرق': '#3b82f6', 'أخضر': '#22c55e', 'ذهبي': '#eab308', 'بني': '#92400e', 'برتقالي': '#f97316', 'بيج': '#d4c4a8', 'white': '#ffffff', 'black': '#1f2937', 'silver': '#9ca3af', 'gray': '#6b7280', 'red': '#ef4444', 'blue': '#3b82f6', 'green': '#22c55e', 'gold': '#eab308', 'brown': '#92400e', 'orange': '#f97316', 'beige': '#d4c4a8' };
    return colorMap[colorName.toLowerCase()] || colorMap[colorName] || '#9ca3af';
}

// Event Handlers
function openWorkOrderModal() { showWorkOrderModal.value = true; }
function openQuoteModal() { showQuoteModal.value = true; }
function handleVehicleSaved() { showEditModal.value = false; router.reload(); }
function handleWorkOrderSaved() { showWorkOrderModal.value = false; router.reload(); }
function handleQuoteSaved() { showQuoteModal.value = false; router.reload(); }
async function confirmDelete() {
     const result = await confirm({
          title: t('common.delete_confirm'),
          message: t('vehicles.delete_confirm_message'),
          confirmText: t('common.delete'),
          type: 'danger'
      });

     if (result) {
         router.delete(route('vehicles.destroy', props.vehicle.id));
     }
}
</script>

<style scoped>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
