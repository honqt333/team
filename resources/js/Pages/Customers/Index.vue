<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                 :title="$t('customers.title')"
                 :subtitle="$t('customers.subtitle')"
                 :totalCount="customers ? toEnglish(customers.total) : null"
                 :countLabel="$t('customers.total_count')"
             >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <button v-if="can('crm.customers.export') || isAnyAdmin()" @click="exportCustomers"
                            :disabled="exporting" :title="$t('common.export')"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </button>
                        <button v-if="can('crm.customers.import') || isAnyAdmin()" @click="showImportModal = true"
                            :title="$t('common.import')"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </button>
                        <button v-if="can('crm.customers.print') || isAnyAdmin()" @click="printCustomers"
                            :title="$t('common.print')"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md">
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
                                        ? 'bg-indigo-600 text-white shadow-indigo-200 dark:shadow-none'
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
                                        ? 'bg-indigo-600 text-white shadow-indigo-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button v-if="can('crm.customers.create') || isAnyAdmin()" @click="openCreateModal"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl font-black shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all group/add">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('customers.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="relative flex-1 group">
                            <div
                                class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="searchQuery"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none shadow-sm"
                                :placeholder="$t('customers.search')" />
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-full sm:w-48">
                                <SearchableSelect v-model="typeFilter" :options="typeOptions" option-label="label"
                                    option-value="value" :placeholder="$t('customers.filter.all_types')" :label="''"
                                    class="!rounded-2xl" />
                            </div>

                            <button @click="resetFilters"
                                class="p-3.5 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all shadow-sm"
                                :title="$t('common.reset')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Loading State -->
            <div v-if="!customers" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                    <div
                        class="absolute top-0 left-0 w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
                    </div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="customers.data.length === 0"
                class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div
                    class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('customers.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('customers.empty_hint') }}</p>
                <button v-if="can('crm.customers.create') || isAnyAdmin()" @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('customers.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-5 gap-4">
                <div v-for="customer in allCustomers" :key="customer.id" @click="goToCustomer(customer)"
                    class="group relative flex flex-col h-[340px] bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-[0_40px_80px_-20px_rgba(79,70,229,0.2)] transition-all duration-500 cursor-pointer overflow-hidden">
                    <!-- Background Elements -->
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-indigo-50/10 via-transparent to-purple-50/10 dark:from-indigo-900/5 dark:to-transparent pointer-events-none">
                    </div>

                    <!-- Watermark -->
                    <div
                        class="absolute -right-4 -bottom-6 opacity-[0.03] dark:opacity-[0.06] pointer-events-none select-none group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-700">
                        <span class="text-[180px] font-black italic text-indigo-900 dark:text-white">{{
                            customer.name.charAt(0) }}</span>
                    </div>

                    <div class="relative z-10 flex flex-col h-full p-6 pb-8">
                        <!-- 1. Header: Name & Type -->
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h3
                                    class="text-lg font-black text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 leading-tight">
                                    {{ customer.name }}
                                </h3>
                                <div class="mt-1.5 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[9px] font-black tracking-widest uppercase border"
                                    :class="customer.type === 'company'
                                        ? 'bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-blue-100/50 dark:border-blue-800/30'
                                        : 'bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-100/50 dark:border-emerald-800/30'">
                                    {{ $t(`customers.type.${customer.type}`) }}
                                </div>
                                <div v-if="customer.classification"
                                    class="mt-1.5 ms-1 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[9px] font-black tracking-widest uppercase border"
                                    :class="getClassificationBadgeClass(customer.classification)">
                                    {{ $t(`customers.classification.${customer.classification}`) }}
                                </div>
                                <div v-if="(customer.type === 'company' || customer.type === 'government') && customer.contact_name"
                                    class="mt-2.5 flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-indigo-50/50 dark:bg-indigo-900/30 border border-indigo-100/50 dark:border-indigo-800/30">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span
                                        class="text-[11px] font-black text-indigo-700 dark:text-indigo-400 truncate">{{
                                        customer.contact_name }}</span>
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
                            <div
                                class="flex items-center gap-2 p-2 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-700/50 backdrop-blur-sm">
                                <div
                                    class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-900 dark:text-gray-100">{{
                                        customer.vehicles_count || 0 }}</span>
                                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{
                                        $t('customers.vehicles') }}</span>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-2 p-2 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-700/50 backdrop-blur-sm">
                                <div
                                    class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-900 dark:text-gray-100">{{
                                        customer.work_orders_count || 0 }}</span>
                                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{
                                        $t('customers.work_orders') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Contact Box -->
                        <div class="mb-4">
                            <a :href="`tel:${customer.phone}`" @click.stop
                                class="flex items-center gap-3 px-3 py-1.5 rounded-2xl bg-indigo-50/30 dark:bg-indigo-900/10 border border-indigo-100/30 dark:border-indigo-800/30 hover:border-indigo-400 group-hover:border-indigo-400 transition-colors mb-2">
                                <div
                                    class="w-7 h-7 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <span class="text-[13px] font-black text-gray-800 dark:text-gray-200 tracking-widest"
                                    dir="ltr">{{ customer.phone }}</span>
                            </a>
                            <a v-if="customer.email" :href="`mailto:${customer.email}`" @click.stop
                                class="flex items-center gap-3 px-3 py-1.5 rounded-2xl bg-purple-50/30 dark:bg-purple-900/10 border border-purple-100/30 dark:border-purple-800/30 hover:border-purple-400 group-hover:border-purple-400 transition-colors">
                                <div
                                    class="w-7 h-7 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-[11px] font-black text-gray-800 dark:text-gray-200 truncate">{{
                                    customer.email }}</span>
                            </a>
                        </div>

                        <!-- 4. Fixed Actions Footer -->
                        <div class="mt-auto grid grid-cols-2 gap-2 mb-2">
                            <a :href="`tel:${customer.phone}`" @click.stop
                                class="flex items-center justify-center gap-1.5 h-11 rounded-2xl bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-400 hover:bg-indigo-600 dark:hover:bg-indigo-500 hover:text-white transition-all font-black text-[10px] uppercase tracking-wider shadow-sm border border-gray-100 dark:border-gray-700/50">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span class="truncate">{{ $t('customers.quick.call') }}</span>
                            </a>
                            <a v-if="customer.whatsapp" :href="`https://wa.me/${getWhatsAppNumber(customer)}`"
                                target="_blank" @click.stop
                                class="flex items-center justify-center gap-1.5 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:text-white transition-all font-black text-[10px] uppercase tracking-wider shadow-sm border border-emerald-100/30 dark:border-emerald-800/20">
                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.355-5.033c0-5.458 4.441-9.898 9.899-9.898 2.645 0 5.132 1.03 7.002 2.901l.001.001c1.868 1.87 2.898 4.357 2.898 7.002 0 5.459-4.441 9.899-9.897 9.899L12.05 21.785z" />
                                </svg>
                                <span class="truncate">WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else
                class="w-full bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-xl shadow-gray-200/50 dark:shadow-none overflow-hidden">
                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1000px] divide-y divide-gray-100 dark:divide-gray-700/50">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/80">
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em] w-12">
                                    #</th>
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">
                                    {{ $t('customers.columns.classification') }}</th>
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">
                                    {{ $t('customers.columns.name') }}</th>
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">
                                    {{ $t('customers.columns.contact_name') }}</th>
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">
                                    {{ $t('customers.columns.email') }}</th>
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">
                                    {{ $t('customers.columns.phone') }}</th>
                                <th
                                    class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">
                                    {{ $t('customers.columns.balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                            <tr v-for="(customer, index) in allCustomers" :key="customer.id"
                                @click="goToCustomer(customer)"
                                class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 cursor-pointer transition-all duration-300">
                                <!-- # -->
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ index + 1 }}
                                </td>
                                <!-- Classification (shows Type) -->
                                <td class="px-4 py-4">
                                    <span v-if="customer.type"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-black rounded-lg border shadow-sm uppercase"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800/30': customer.type === 'individual',
                                            'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800/30': customer.type === 'company',
                                            'bg-purple-50 text-purple-700 border-purple-100 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800/30': customer.type === 'government',
                                            'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/30': customer.type === 'vip',
                                        }">
                                        {{ $t(`customers.type.${customer.type}`) }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">-</span>
                                </td>
                                <!-- Name -->
                                <td class="px-4 py-4">
                                    <p
                                        class="font-bold text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ customer.name }}
                                    </p>
                                </td>
                                <!-- Contact Name -->
                                <td class="px-4 py-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ customer.contact_name || '-' }}
                                    </p>
                                </td>
                                <!-- Email -->
                                <td class="px-4 py-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ customer.email || '-' }}
                                    </p>
                                </td>
                                <!-- Phone -->
                                <td class="px-4 py-4">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 tracking-wider">
                                        {{ customer.phone }}
                                    </p>
                                </td>
                                <!-- Balance -->
                                <td class="px-4 py-4">
                                    <p class="text-sm font-bold"
                                        :class="(customer.balance || 0) > 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ (customer.balance || 0).toLocaleString() }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scroll Sentinel -->
            <div ref="loadMoreSentinel" class="py-8 flex justify-center">
                <div v-if="loadingMore" class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>{{ $t('common.loading') }}</span>
                </div>
                <div v-else-if="!nextPageUrl && allCustomers.length > 0"
                    class="text-sm text-gray-400 dark:text-gray-500">
                    {{ $t('customers.all_loaded') || 'تم تحميل جميع العملاء' }}
                </div>
            </div>
        </div>

        <!-- Customer Form Modal -->
        <CustomerFormModal :show="showModal" :customer="selectedCustomer" @close="closeModal" @saved="handleSaved" />

        <!-- Customer Import Modal -->
        <CustomerImportModal :show="showImportModal" @close="showImportModal = false" @imported="router.reload()" />
        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <!-- Header -->
                <PrintHeader :title="$t('customers.title')" />

                <!-- Table -->
                <table class="print-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>{{ $t('customers.columns.name') }}</th>
                            <th>{{ $t('customers.columns.phone') }}</th>
                            <th>{{ $t('customers.columns.type') }}</th>
                            <th>{{ $t('customers.columns.balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(customer, index) in allCustomers" :key="customer.id">
                            <td>{{ toEnglish(index + 1) }}</td>
                            <td class="font-bold">{{ customer.name }}</td>
                            <td dir="ltr">{{ toEnglish(customer.phone) }}</td>
                            <td>
                                <span class="print-badge">
                                    {{ customer.type ? $t(`customers.type.${customer.type}`) : '-' }}
                                </span>
                            </td>
                            <td class="font-bold">
                                {{ formatNumber(customer.balance || 0) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer -->
                <div class="mt-8 text-center text-[10px] text-gray-400">
                    {{ $page.props.auth.user.name }}
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import CustomerImportModal from '@/Components/Customers/CustomerImportModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrintHeader from '@/Components/Print/PrintHeader.vue';

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

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const { success, error, info } = useToast();
const { can, isAnyAdmin } = usePermission();
const { toEnglish, formatNumber } = useNumberFormat();

const typeOptions = computed(() => [
    { value: '', label: t('customers.filter.all_types') },
    { value: 'individual', label: t('customers.type.individual') },
    { value: 'company', label: t('customers.type.company') },
    { value: 'government', label: t('customers.type.government') },
    { value: 'vip', label: t('customers.type.vip') },
]);

const showModal = ref(false);
const selectedCustomer = ref(null);
const searchQuery = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const viewMode = ref(localStorage.getItem('customersViewMode') || 'grid');
const exporting = ref(false);
const showImportModal = ref(false);

// Infinite Scroll Refs
const allCustomers = ref(props.customers?.data || []);
const nextPageUrl = ref(props.customers?.next_page_url);
const loadingMore = ref(false);
const loadMoreSentinel = ref(null);
let observer = null;

// Watch props change (filters)
watch(() => props.customers, (newVal) => {
    if (newVal) {
        allCustomers.value = newVal.data;
        nextPageUrl.value = newVal.next_page_url;
    }
});

// Load more data
const loadMore = async () => {
    if (loadingMore.value || !nextPageUrl.value) return;

    loadingMore.value = true;
    try {
        // Extract page number from next_page_url and build API URL
        const url = new URL(nextPageUrl.value, window.location.origin);
        const page = url.searchParams.get('page') || 2;

        // Build API URL with current filters
        const params = new URLSearchParams();
        params.set('page', page);
        if (searchQuery.value) params.set('search', searchQuery.value);
        if (typeFilter.value) params.set('type', typeFilter.value);

        const response = await axios.get('/app/api/customers?' + params.toString());
        const data = response.data;

        allCustomers.value.push(...data.data);
        nextPageUrl.value = data.next_page_url;
    } catch (e) {
        console.error('Failed to load more customers', e);
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
});

watch(loadMoreSentinel, (el) => {
    if (el && observer) observer.observe(el);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

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

// Type filter watcher
watch(typeFilter, (value) => {
    router.get(route('customers.index'), { search: searchQuery.value, type: value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

// Export customers to Excel
function exportCustomers() {
    exporting.value = true;
    const params = new URLSearchParams();
    if (searchQuery.value) params.set('search', searchQuery.value);
    if (typeFilter.value) params.set('type', typeFilter.value);
    window.location.href = route('customers.export') + '?' + params.toString();
    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

const resetFilters = () => {
    searchQuery.value = '';
    typeFilter.value = '';
    router.get(route('customers.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Print customers list - Load All & Print
async function printCustomers() {
    // If there's more data to load, load it all first
    if (nextPageUrl.value) {
        info(t('customers.loading_all_printing') || 'جاري تحميل جميع البيانات للطباعة...');

        while (nextPageUrl.value) {
            try {
                // Extract page number and use API endpoint
                const url = new URL(nextPageUrl.value, window.location.origin);
                const page = url.searchParams.get('page') || 2;

                const params = new URLSearchParams();
                params.set('page', page);
                if (searchQuery.value) params.set('search', searchQuery.value);
                if (typeFilter.value) params.set('type', typeFilter.value);

                const response = await axios.get('/app/api/customers?' + params.toString());
                const data = response.data;

                if (data.data && Array.isArray(data.data)) {
                    allCustomers.value.push(...data.data);
                }
                nextPageUrl.value = data.next_page_url;
            } catch (e) {
                console.error('Failed to load all customers for print', e);
                break;
            }
        }

        // Allow DOM to update
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Trigger print
    window.print();
}

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

// Classification badge class helper
function getClassificationBadgeClass(classification) {
    const classes = {
        potential: 'bg-sky-50/50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 border-sky-100/50 dark:border-sky-800/30',
        new: 'bg-green-50/50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100/50 dark:border-green-800/30',
        regular: 'bg-gray-50/50 dark:bg-gray-900/20 text-gray-600 dark:text-gray-400 border-gray-100/50 dark:border-gray-700/30',
        vip: 'bg-amber-50/50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border-amber-100/50 dark:border-amber-800/30',
        inactive: 'bg-red-50/50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100/50 dark:border-red-800/30',
    };
    return classes[classification] || classes.regular;
}
</script>

<style scoped>
@media print {
    .print-section {
        display: block !important;
        font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif !important;
        padding: 20px;
        background: white;
    }

    .print-header {
        margin-bottom: 30px;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 20px;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #e5e7eb;
        padding: 8px;
        text-align: center !important;
    }

    .print-table th {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        font-weight: bold;
    }

    .print-footer {
        margin-top: 50px;
        display: grid;
        grid-template-cols: repeat(3, 1fr);
        gap: 20px;
        text-align: center;
        font-size: 12px;
    }

    .print-badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        border: 1px solid #e5e7eb;
    }
}
</style>
