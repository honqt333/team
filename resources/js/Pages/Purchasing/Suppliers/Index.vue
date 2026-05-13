<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('purchasing.suppliers.title')"
                :subtitle="$t('purchasing.suppliers.subtitle')"
                :totalCount="suppliers.total"
                :countLabel="$t('purchasing.suppliers.title')"
                gradientFrom="from-orange-600"
                gradientTo="to-red-700"
                glowFrom="from-orange-500"
                badgeBg="bg-orange-50/50 dark:bg-orange-900/30"
                badgeText="text-orange-600 dark:text-orange-400"
                badgeBorder="border-orange-100/50 dark:border-orange-800/30"
                badgeDot="bg-orange-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <!-- Export/Print Group -->
                        <div class="flex items-center gap-1">
                            <button @click="exportSuppliers" :disabled="exporting"
                                class="p-2.5 text-gray-500 hover:text-orange-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md disabled:opacity-50"
                                :title="$t('common.export')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </button>
                            <button @click="printSuppliers"
                                class="p-2.5 text-gray-500 hover:text-orange-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md"
                                :title="$t('common.print')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Divider -->
                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- View Toggle Group -->
                        <div class="flex items-center gap-1">
                            <button 
                                @click="viewMode = 'grid'"
                                :title="$t('common.grid_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'grid'
                                        ? 'bg-orange-600 text-white shadow-orange-500/20'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button 
                                @click="viewMode = 'list'"
                                :title="$t('common.list_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'list'
                                        ? 'bg-orange-600 text-white shadow-orange-500/20'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Add Supplier Button -->
                    <button
                        v-if="can('purchasing.suppliers.create') || isAnyAdmin()"
                        @click="createSupplier"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-2xl font-black shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:-translate-y-0.5 transition-all group/add"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('purchasing.suppliers.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-orange-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('purchasing.suppliers.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Status Filter -->
                            <div class="w-full md:w-48">
                                <SearchableSelect
                                    v-model="localFilters.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('common.all')"
                                    :label="''"
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

            <!-- GRID VIEW -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                <div v-for="supplier in suppliers.data" :key="supplier.id"
                    @click="router.visit(route('app.purchasing.suppliers.show', supplier.id))"
                    class="group relative flex flex-col h-[340px] bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-[0_40px_80px_-20px_rgba(59,130,246,0.2)] transition-all duration-500 cursor-pointer overflow-hidden">
                    <!-- Background Elements -->
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-blue-50/10 via-transparent to-cyan-50/10 dark:from-blue-900/5 dark:to-transparent pointer-events-none">
                    </div>

                    <!-- Watermark -->
                    <div
                        class="absolute -right-4 -bottom-6 opacity-[0.03] dark:opacity-[0.06] pointer-events-none select-none group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-700">
                        <span class="text-[180px] font-black italic text-blue-900 dark:text-white">{{
                            supplier.name.charAt(0) }}</span>
                    </div>

                    <div class="relative z-10 flex flex-col h-full p-6 pb-8">
                        <!-- 1. Header: Name & Type -->
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h3
                                    class="text-lg font-black text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2 leading-tight">
                                    {{ supplier.name }}
                                </h3>
                                <div class="mt-1.5 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[9px] font-black tracking-widest uppercase border"
                                    :class="supplier.type === 'parts'
                                        ? 'bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-blue-100/50 dark:border-blue-800/30'
                                        : 'bg-purple-50/50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 border-purple-100/50 dark:border-purple-800/30'">
                                    {{ supplier.type === 'parts' ? $t('purchasing.suppliers.type_parts') :
                                        $t('purchasing.suppliers.type_services') }}
                                </div>

                                <span :class="[
                                    'mt-1.5 ms-1 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[9px] font-black tracking-widest uppercase border shadow-sm transition-all',
                                    supplier.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30'
                                        : 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800/30 dark:text-gray-400 dark:border-gray-700/30'
                                ]">
                                    {{ supplier.is_active ? $t('common.active') : $t('common.inactive') }}
                                </span>
                            </div>

                            <!-- Avatar -->
                            <div class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-lg font-black text-white shadow-lg shadow-blue-500/10 transition-transform group-hover:rotate-6"
                                :class="supplier.type === 'parts' ? 'bg-gradient-to-br from-blue-500 to-cyan-600' : 'bg-gradient-to-br from-purple-500 to-indigo-600'">
                                {{ supplier.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <!-- 2. Stats Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div
                                class="flex items-center gap-2 p-2 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-700/50 backdrop-blur-sm">
                                <div
                                    class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-900 dark:text-gray-100">{{
                                        supplier.purchase_orders_count || 0 }}</span>
                                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{
                                        $t('purchasing.suppliers.orders') }}</span>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-2 p-2 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-700/50 backdrop-blur-sm">
                                <div
                                    class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-900 dark:text-gray-100">{{
                                        formatCurrency(supplier.balance) }}</span>
                                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{
                                        $t('purchasing.suppliers.balance') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Contact Box -->
                        <div class="mb-6">
                            <div
                                class="flex items-center gap-3 px-3 py-2.5 rounded-2xl bg-blue-50/30 dark:bg-blue-900/10 border border-blue-100/30 dark:border-blue-800/30 group-hover:border-blue-400 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-black text-gray-800 dark:text-gray-200 tracking-widest truncate">{{
                                        supplier.contact_person || $t('common.no_contact') }}</span>
                            </div>
                        </div>

                        <!-- 4. Fixed Actions Footer -->
                        <div class="mt-auto grid grid-cols-2 gap-2 mb-2">
                            <a :href="supplier.phone ? `tel:${supplier.phone}` : '#'" @click.stop :class="[
                                'flex items-center justify-center gap-1.5 h-11 rounded-2xl transition-all font-black text-[10px] uppercase tracking-wider shadow-sm border',
                                supplier.phone
                                    ? 'bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-400 hover:bg-blue-600 dark:hover:bg-blue-500 hover:text-white border-gray-100 dark:border-gray-700/50'
                                    : 'bg-gray-50 dark:bg-gray-900/20 text-gray-300 dark:text-gray-600 border-gray-100 dark:border-gray-800/50 cursor-not-allowed'
                            ]">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span class="truncate">{{ $t('common.call') }}</span>
                            </a>
                            <a :href="supplier.email ? `mailto:${supplier.email}` : '#'" @click.stop :class="[
                                'flex items-center justify-center gap-1.5 h-11 rounded-2xl transition-all font-black text-[10px] uppercase tracking-wider shadow-sm border',
                                supplier.email
                                    ? 'bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-400 hover:bg-purple-600 dark:hover:bg-purple-500 hover:text-white border-gray-100 dark:border-gray-700/50'
                                    : 'bg-gray-50 dark:bg-gray-900/20 text-gray-300 dark:text-gray-600 border-gray-100 dark:border-gray-800/50 cursor-not-allowed'
                            ]">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="truncate">{{ $t('common.email') }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Empty State for Grid -->
                <div v-if="!suppliers.data.length"
                    class="col-span-full py-12 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
                    <p class="text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.empty') }}</p>
                </div>
            </div>

            <!-- LIST VIEW -->
            <div v-else
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.suppliers.code') }}</th>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.suppliers.name') }}</th>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.suppliers.contact') }}</th>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.suppliers.phone') }}</th>
                                <th
                                    class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.suppliers.balance') }}</th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.suppliers.orders_count') }}</th>
                                <th
                                    class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="supplier in suppliers.data" :key="supplier.id"
                                @click="router.visit(route('app.purchasing.suppliers.show', supplier.id))"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ supplier.code
                                    || '-' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ supplier.name
                                    }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{
                                    supplier.contact_person || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300" dir="ltr">{{
                                    supplier.phone || '-' }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-blue-600 dark:text-blue-400 text-end">{{
                                    formatCurrency(supplier.balance) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300">
                                        {{ supplier.purchase_orders_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <span :class="[
                                        'inline-flex px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all',
                                        supplier.is_active
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30'
                                            : 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800/30 dark:text-gray-400 dark:border-gray-700/30'
                                    ]">
                                        {{ supplier.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!suppliers.data.length">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('purchasing.suppliers.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <CreateModal :show="showCreateModal" :supplier="editingSupplier" @close="closeModal" />
            <!-- Print Section -->
            <Teleport to="body">
                <div class="print-section hidden">
                    <!-- Header -->
                    <div class="print-header">
                        <!-- Arabic Layout: Logo right, info beside it -->
                        <div v-if="isRtl" class="flex items-start gap-4 mb-4" style="direction: rtl;">
                            <!-- Logo -->
                            <div v-if="$page.props.tenant?.logo_url" class="w-20 h-20 flex-shrink-0">
                                <img :src="$page.props.tenant.logo_url" :alt="$page.props.tenant?.name"
                                    class="w-full h-full object-contain" />
                            </div>
                            <!-- Center Info -->
                            <div class="flex-1 text-right">
                                <h1 class="text-xl font-bold">{{ $page.props.tenant?.trade_name ||
                                    $page.props.tenant?.name || 'Carag' }}</h1>
                                <p class="text-sm" v-if="$page.props.center?.phone || $page.props.tenant?.phone">
                                    هاتف: {{ $page.props.center?.phone || $page.props.tenant?.phone }}
                                </p>
                                <p class="text-sm" v-if="$page.props.center?.email || $page.props.tenant?.email">
                                    البريد: {{ $page.props.center?.email || $page.props.tenant?.email }}
                                </p>
                                <p class="text-sm" v-if="$page.props.tenant?.cr_number">
                                    السجل التجاري: {{ $page.props.tenant?.cr_number }}
                                </p>
                            </div>
                        </div>

                        <!-- English Layout: Logo left with info beside it -->
                        <div v-else class="flex items-start gap-4 mb-4">
                            <!-- Logo -->
                            <div v-if="$page.props.tenant?.logo_url" class="w-20 h-20 flex-shrink-0">
                                <img :src="$page.props.tenant.logo_url" :alt="$page.props.tenant?.name"
                                    class="w-full h-full object-contain" />
                            </div>
                            <!-- Center Info -->
                            <div class="flex-1">
                                <h1 class="text-lg font-bold">{{ $page.props.tenant?.trade_name ||
                                    $page.props.tenant?.name || 'Carag' }}</h1>
                                <p class="text-sm" v-if="$page.props.center?.phone || $page.props.tenant?.phone">
                                    Phone: {{ $page.props.center?.phone || $page.props.tenant?.phone }}
                                </p>
                                <p class="text-sm" v-if="$page.props.center?.email || $page.props.tenant?.email">
                                    Email: {{ $page.props.center?.email || $page.props.tenant?.email }}
                                </p>
                                <p class="text-sm" v-if="$page.props.tenant?.cr_number">
                                    CR: {{ $page.props.tenant?.cr_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Title centered (both languages) -->
                        <div class="border-t pt-4 border-gray-300 text-center">
                            <h2 class="text-lg font-bold">{{ $t('purchasing.suppliers.title') }}</h2>
                            <p class="text-xs text-gray-500 mt-1">{{ new Date().toLocaleDateString(isRtl ? 'ar-SA' :
                                'en-US') }}</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <table class="print-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ $t('purchasing.suppliers.name') }}</th>
                                <th>{{ $t('purchasing.suppliers.phone') }}</th>
                                <th>{{ $t('purchasing.suppliers.email') }}</th>
                                <th>{{ $t('purchasing.suppliers.type') }}</th>
                                <th>{{ $t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(supplier, index) in suppliers.data" :key="supplier.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ supplier.name }}</td>
                                <td dir="ltr" class="text-left font-sans">{{ supplier.phone || '-' }}</td>
                                <td dir="ltr" class="text-left">{{ supplier.email || '-' }}</td>
                                <td>{{ supplier.type === 'parts' ? $t('purchasing.suppliers.type_parts') :
                                    $t('purchasing.suppliers.type_services') }}</td>
                                <td>{{ supplier.is_active ? $t('common.active') : $t('common.inactive') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-8 text-center text-xs text-gray-400">
                        {{ $page.props.auth?.user?.name }} - {{ new Date().toLocaleString(isRtl ? 'ar-SA' : 'en-US') }}
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import CreateModal from './CreateModal.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/Composables/usePermission';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const { can, isAnyAdmin } = usePermission();

const isRtl = computed(() => locale.value === 'ar');
const exporting = ref(false);

const viewMode = ref(localStorage.getItem('suppliers-view-mode') || 'grid');

watch(viewMode, (newVal) => {
    localStorage.setItem('suppliers-view-mode', newVal);
});

const localFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const statusOptions = computed(() => [
    { value: '', label: t('common.all') },
    { value: 'active', label: t('common.active') },
    { value: 'inactive', label: t('common.inactive') },
]);

const showCreateModal = ref(false);
const editingSupplier = ref(null);

const createSupplier = () => {
    editingSupplier.value = null;
    showCreateModal.value = true;
};

// No longer used in List view but kept for Create/Edit Modal
const closeModal = () => {
    showCreateModal.value = false;
    editingSupplier.value = null;
};

const applyFilters = () => {
    router.get(route('app.purchasing.suppliers.index'), {
        search: localFilters.value.search || undefined,
        status: localFilters.value.status || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const debouncedSearch = debounce(applyFilters, 300);

const resetFilters = () => {
    localFilters.value.search = '';
    localFilters.value.status = '';
    applyFilters();
};

function formatCurrency(value) {
    if (!value && value !== 0) return '-';
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0
    }).format(value);
}

function exportSuppliers() {
    exporting.value = true;
    const params = new URLSearchParams(window.location.search);
    // Add locale to params
    params.append('locale', locale.value);

    window.location.href = route('app.purchasing.suppliers.export', Object.fromEntries(params));
    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

function printSuppliers() {
    window.print();
}
</script>
