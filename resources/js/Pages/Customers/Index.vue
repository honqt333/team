<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('customers.title') }}</h1>
                            <p v-if="customers" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ customers.total }} {{ $t('customers.total_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('customers.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- View Toggle -->
                        <div class="flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1">
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
                            @click="openCreateModal"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('customers.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!customers" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="customers.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('customers.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('customers.empty_hint') }}</p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('customers.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-5 gap-4">
                <div
                    v-for="customer in customers.data"
                    :key="customer.id"
                    @click="goToCustomer(customer)"
                    class="group relative flex flex-col h-[320px] bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-[0_40px_80px_-20px_rgba(79,70,229,0.2)] transition-all duration-500 cursor-pointer overflow-hidden"
                >
                    <!-- Background Elements -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/10 via-transparent to-purple-50/10 dark:from-indigo-900/5 dark:to-transparent pointer-events-none"></div>
                    
                    <!-- Watermark -->
                    <div class="absolute -right-4 -bottom-6 opacity-[0.03] dark:opacity-[0.06] pointer-events-none select-none group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-700">
                        <span class="text-[180px] font-black italic text-indigo-900 dark:text-white">{{ customer.name.charAt(0) }}</span>
                    </div>

                    <div class="relative z-10 flex flex-col h-full p-6 pb-8">
                        <!-- 1. Header: Name & Type -->
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h3 class="text-lg font-black text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 leading-tight">
                                    {{ customer.name }}
                                </h3>
                                <div 
                                    class="mt-1.5 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[9px] font-black tracking-widest uppercase border"
                                    :class="customer.type === 'company' 
                                        ? 'bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-blue-100/50 dark:border-blue-800/30' 
                                        : 'bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-100/50 dark:border-emerald-800/30'"
                                >
                                    {{ $t(`customers.type.${customer.type}`) }}
                                </div>
                                <div v-if="customer.type === 'company' && customer.contact_name" class="mt-2.5 flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-indigo-50/50 dark:bg-indigo-900/30 border border-indigo-100/50 dark:border-indigo-800/30">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-[11px] font-black text-indigo-700 dark:text-indigo-400 truncate">{{ customer.contact_name }}</span>
                                </div>
                            </div>
                            
                            <!-- Static Initial Avatar -->
                            <div class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-lg font-black text-white shadow-lg shadow-indigo-500/10 transition-transform group-hover:rotate-6"
                                :class="customer.type === 'company' ? 'bg-gradient-to-br from-indigo-500 to-blue-600' : 'bg-gradient-to-br from-emerald-500 to-teal-600'">
                                {{ customer.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <!-- 2. Stats Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div class="flex items-center gap-2 p-2 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-700/50 backdrop-blur-sm">
                                <div class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-900 dark:text-gray-100">{{ customer.vehicles_count || 0 }}</span>
                                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{ $t('customers.vehicles') }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-700/50 backdrop-blur-sm">
                                <div class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-900 dark:text-gray-100">{{ customer.work_orders_count || 0 }}</span>
                                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{ $t('customers.work_orders') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Contact Box -->
                        <div class="mb-6">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-2xl bg-indigo-50/30 dark:bg-indigo-900/10 border border-indigo-100/30 dark:border-indigo-800/30 group-hover:border-indigo-400 transition-colors">
                                <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <span class="text-sm font-black text-gray-800 dark:text-gray-200 tracking-widest" dir="ltr">{{ customer.phone }}</span>
                            </div>
                        </div>

                        <!-- 4. Fixed Actions Footer -->
                        <div class="mt-auto grid grid-cols-2 gap-2">
                            <a 
                                :href="`tel:${customer.phone}`"
                                @click.stop
                                class="flex items-center justify-center gap-1.5 h-11 rounded-2xl bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-400 hover:bg-indigo-600 dark:hover:bg-indigo-500 hover:text-white transition-all font-black text-[10px] uppercase tracking-wider shadow-sm border border-gray-100 dark:border-gray-700/50"
                            >
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span class="truncate">{{ $t('customers.quick.call') }}</span>
                            </a>
                            <a 
                                v-if="customer.whatsapp"
                                :href="`https://wa.me/${getWhatsAppNumber(customer)}`"
                                target="_blank"
                                @click.stop
                                class="flex items-center justify-center gap-1.5 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:text-white transition-all font-black text-[10px] uppercase tracking-wider shadow-sm border border-emerald-100/30 dark:border-emerald-800/20"
                            >
                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.355-5.033c0-5.458 4.441-9.898 9.899-9.898 2.645 0 5.132 1.03 7.002 2.901l.001.001c1.868 1.87 2.898 4.357 2.898 7.002 0 5.459-4.441 9.899-9.897 9.899L12.05 21.785z"/></svg>
                                <span class="truncate">WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="w-full bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700/50 shadow-xl shadow-gray-200/50 dark:shadow-none overflow-hidden">
                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[800px] divide-y divide-gray-100 dark:divide-gray-700/50">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/80">
                                <th class="px-8 py-5 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                                    {{ $t('customers.columns.name') }}
                                </th>
                                <th class="px-8 py-5 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                                    {{ $t('customers.columns.type') }}
                                </th>
                                <th class="px-8 py-5 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                                    {{ $t('customers.columns.phone') }}
                                </th>
                                <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                @click="goToCustomer(customer)"
                                class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 cursor-pointer transition-all duration-300"
                            >
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-sm font-black text-white flex-shrink-0 shadow-lg group-hover:scale-110 transition-transform duration-500"
                                            :class="customer.type === 'company' 
                                                ? 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/20' 
                                                : 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/20'">
                                            {{ customer.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-black text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ customer.name }}</p>
                                            <p v-if="customer.email" class="text-xs font-bold text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ customer.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-black rounded-full border shadow-sm uppercase tracking-tighter"
                                        :class="customer.type === 'company' 
                                            ? 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800/30' 
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800/30'"
                                    >
                                        <span class="w-1 h-1 rounded-full" :class="customer.type === 'company' ? 'bg-blue-400' : 'bg-emerald-400'"></span>
                                        {{ $t(`customers.type.${customer.type}`) }}
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-sm font-black text-gray-700 dark:text-gray-300 tracking-wider" dir="ltr">
                                        {{ customer.phone }}
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center justify-center gap-3">
                                        <button
                                            @click.stop="openEditModal(customer)"
                                            class="p-2.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900/50"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <a
                                            v-if="customer.whatsapp"
                                            :href="'https://wa.me/' + customer.whatsapp.replace(/\D/g, '')"
                                            target="_blank"
                                            @click.stop
                                            class="p-2.5 text-gray-400 hover:text-emerald-500 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm border border-transparent hover:border-emerald-100 dark:hover:border-emerald-900/50"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="customers && customers.data.length > 0" class="flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                    {{ $t('customers.showing') }} 
                    <span class="text-gray-900 dark:text-white">{{ customers.from }}</span>
                    -
                    <span class="text-gray-900 dark:text-white">{{ customers.to }}</span>
                    {{ $t('customers.of') }}
                    <span class="text-gray-900 dark:text-white">{{ customers.total }}</span>
                </div>
                
                <!-- Inertia Pagination (If you have a component, use it. Otherwise, simple links) -->
                <div class="flex gap-2">
                    <Link
                        v-for="link in customers.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-sm',
                            link.active ? 'bg-indigo-600 text-white shadow-indigo-500/20' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Customer Form Modal -->
        <CustomerFormModal
            :show="showModal"
            :customer="selectedCustomer"
            @close="closeModal"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';

const props = defineProps({
    customers: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const { success } = useToast();
const showModal = ref(false);
const selectedCustomer = ref(null);
const searchQuery = ref(props.filters.search || '');
const viewMode = ref(localStorage.getItem('customersViewMode') || 'grid');

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

watch(searchQuery, debounce((value) => {
    router.get(route('customers.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

watch(viewMode, (newMode) => {
    localStorage.setItem('customersViewMode', newMode);
});

function openCreateModal() {
    selectedCustomer.value = null;
    showModal.value = true;
}

function openEditModal(customer) {
    selectedCustomer.value = customer;
    showModal.value = true;
}

function goToCustomer(customer) {
    router.visit(route('customers.show', customer.id));
}

function closeModal() {
    showModal.value = false;
    selectedCustomer.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['customers'] });
}

function getWhatsAppNumber(customer) {
    if (!customer) return null;
    const phone = customer.whatsapp || customer.phone;
    if (!phone) return null;
    return phone.replace(/[^\d+]/g, '').replace(/^\+/, '');
}
</script>
