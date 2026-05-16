<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.parts.title')"
                :subtitle="$t('inventory.parts.subtitle')"
                :totalCount="parts.total"
                :countLabel="$t('inventory.parts.title')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-700"
                glowFrom="from-blue-500"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #back>
                    <Link :href="route('app.inventory.hub')" class="p-2 -ml-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-full transition-all">
                        <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions Glass Container -->
                        <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                            <button 
                                @click="handlePrint"
                                :title="$t('common.print')"
                                class="p-2.5 rounded-xl transition-all shadow-sm text-gray-400 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-800"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </button>
                            <button 
                                @click="toggleView('grid')"
                                :title="$t('common.grid_view')"
                                :class="[
                                    'p-2 rounded-xl transition-all shadow-sm',
                                    viewMode === 'grid'
                                        ? 'bg-blue-600 text-white shadow-blue-500/20'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button 
                                @click="toggleView('list')"
                                :title="$t('common.list_view')"
                                :class="[
                                    'p-2 rounded-xl transition-all shadow-sm',
                                    viewMode === 'list'
                                        ? 'bg-blue-600 text-white shadow-blue-500/20'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Add Part Button -->
                        <button
                            v-if="can('inventory.parts.create') || isAnyAdmin()"
                            @click="createPart"
                            class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-black shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all group/add"
                        >
                            <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <span class="text-sm tracking-tight">{{ $t('inventory.parts.add') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('inventory.parts.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Status Filter -->
                            <div class="w-full md:w-48">
                                <SearchableSelect
                                    v-model="localFilters.status"
                                    :options="[
                                        {value: '', label: $t('common.all')},
                                        {value: 'active', label: $t('common.active')},
                                        {value: 'inactive', label: $t('common.inactive')}
                                    ]"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('common.status')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Category Filter -->
                            <div class="w-full md:w-64" v-if="categories.length">
                                <SearchableSelect
                                    v-model="localFilters.category"
                                    :options="computedCategories"
                                    option-label="name"
                                    option-value="id"
                                    :placeholder="$t('inventory.parts.all_categories')"
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

            <!-- Parts Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="viewMode === 'list'" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-12">#</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-16">
                                    {{ $t('inventory.parts.photo') || 'Photo' }}
                                </th>
                                <th @click="toggleSort('sku')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('inventory.parts.sku') }} / {{ $t('inventory.parts.barcode') || 'Barcode' }}
                                        <SortIcon :active="sortColumn === 'sku'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('name')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('inventory.parts.name') }}
                                        <SortIcon :active="sortColumn === 'name'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('description')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('inventory.parts.description') || 'Description' }}
                                        <SortIcon :active="sortColumn === 'description'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('unit')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('inventory.parts.unit') }}
                                        <SortIcon :active="sortColumn === 'unit'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('category')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('inventory.parts.category') }}
                                        <SortIcon :active="sortColumn === 'category'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th @click="toggleSort('qty')" class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ $t('inventory.stock.qty') }}
                                        <SortIcon :active="sortColumn === 'qty'" :direction="sortDirection" />
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(part, index) in sortedParts" :key="part.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                                <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{{ toEnglish(index + 1) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center">
                                        <div class="relative group/img">
                                            <img :src="part.image_url" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700 shadow-sm transition-transform group-hover/img:scale-110" />
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="text-sm font-mono font-bold text-gray-900 dark:text-white">{{ part.sku }}</span>
                                        <span v-if="part.barcode" class="text-[10px] text-gray-400 font-mono">{{ part.barcode }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="text-sm font-black">
                                        <button @click="editPart(part)" class="text-blue-600 dark:text-blue-400 hover:underline transition-colors">
                                            {{ part.name_ar }}
                                        </button>
                                    </div>
                                    <div v-if="part.name_en" class="text-[10px] text-gray-500 dark:text-gray-400">{{ part.name_en }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 max-w-[200px] mx-auto" :title="part.description">
                                        {{ part.description || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-300">
                                    {{ (locale === 'ar' ? part.unit?.name_ar : (part.unit?.name_en || part.unit?.name_ar)) || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-300">
                                    {{ (locale === 'ar' ? part.category?.name_ar : (part.category?.name_en || part.category?.name_ar)) || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-xs font-black',
                                        part.inventory_balances_sum_qty_on_hand > part.min_qty 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : part.inventory_balances_sum_qty_on_hand > 0
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    ]">
                                        {{ formatQuantity(part.inventory_balances_sum_qty_on_hand ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            v-if="can('inventory.parts.edit')"
                                            @click="editPart(part)"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                            :title="$t('common.edit')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            v-if="can('inventory.parts.deactivate')"
                                            @click="toggleActive(part)"
                                            :class="[
                                                'p-2 transition-colors',
                                                part.is_active 
                                                    ? 'text-gray-400 hover:text-red-600 dark:hover:text-red-400'
                                                    : 'text-gray-400 hover:text-green-600 dark:hover:text-green-400'
                                            ]"
                                            :title="part.is_active ? $t('common.deactivate') : $t('common.activate')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path v-if="part.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!parts.data.length">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('inventory.parts.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Grid View -->
                <div v-else-if="viewMode === 'grid'" class="p-6">
                    <div v-if="parts.data.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        <div 
                            v-for="part in parts.data" 
                            :key="part.id" 
                            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:shadow-md transition-all group relative flex flex-col overflow-hidden"
                        >
                            <!-- Image Area -->
                            <div @click="editPart(part)" class="h-32 bg-gray-50 dark:bg-gray-900/50 relative overflow-hidden group/img cursor-pointer">
                                <img v-if="part.image_url" :src="part.image_url" class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-110" />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-200 dark:text-gray-700">
                                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="absolute inset-0 bg-black/0 group-hover/img:bg-black/10 transition-colors flex items-center justify-center">
                                    <div class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-full shadow-lg opacity-0 group-hover/img:opacity-100 transform translate-y-4 group-hover/img:translate-y-0 transition-all duration-300">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Top Row: SKU & Unit -->
                            <div class="px-4 pt-4 flex justify-between items-start text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-mono font-medium text-blue-600 dark:text-blue-400">{{ part.sku }}</span>
                                <span v-if="part.unit">{{ $t('inventory.parts.unit') }}: {{ locale === 'ar' ? part.unit.name_ar : (part.unit.name_en || part.unit.name_ar) }}</span>
                            </div>

                            <!-- Middle: Barcode & Name & Cart Icon -->
                            <div class="px-4 py-3 flex items-center justify-between gap-3">
                                <div class="flex-1 flex flex-col items-center text-center">
                                    <!-- Fake Barcode Visual -->
                                    <div class="h-8 w-32 flex items-end justify-center gap-[1px] opacity-70 mb-1">
                                        <div v-for="i in 20" :key="i" :class="['bg-gray-800 dark:bg-gray-300', i % 2 === 0 ? 'w-[1px]' : 'w-[2px]', i % 3 === 0 ? 'h-full' : 'h-3/4']"></div>
                                    </div>
                                    <h3 class="font-bold text-sm text-gray-900 dark:text-white line-clamp-2 px-1">
                                        {{ part.name_ar }} 
                                        <span v-if="part.name_en" class="block text-[10px] font-normal text-gray-400">{{ part.name_en }}</span>
                                    </h3>
                                </div>
                                
                                <!-- Cart Icon -->
                                <button 
                                    class="text-gray-300 hover:text-blue-600 dark:text-gray-600 dark:hover:text-blue-400 transition-colors"
                                    :title="$t('inventory.parts.add_to_wo')"
                                >
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Bottom: Collapsible-like Sections -->
                            <div class="mt-auto border-t border-gray-50 dark:border-gray-700/50">
                                <!-- Make/Model Indicator (Placeholder) -->
                                <button class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-50 dark:border-gray-700/50">
                                    <span>{{ $t('inventory.parts.category') }}</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                
                                <!-- Warehouses/Stock Toggle -->
                                <button 
                                    @click="toggleExpanded(part.id)"
                                    class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    :class="{'bg-gray-50 dark:bg-gray-700/50': expandedPartId === part.id}"
                                >
                                    <span class="flex items-center gap-1">
                                        {{ $t('inventory.stock.title') }}
                                        <span 
                                            class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                                            :class="part.inventory_balances_sum_qty_on_hand > 0 ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-gray-100 text-gray-500'"
                                        >
                                            ({{ formatQuantity(part.inventory_balances_sum_qty_on_hand ?? 0) }})
                                        </span>
                                    </span>
                                    <svg 
                                        class="w-3 h-3 text-gray-400 transition-transform duration-200" 
                                        :class="{'rotate-180': expandedPartId === part.id}"
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- Collapsible Table -->
                                <div v-show="expandedPartId === part.id" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 overflow-x-auto">
                                    <table class="w-full text-[10px] whitespace-nowrap">
                                        <thead>
                                            <tr class="text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                                <th class="pb-2 font-medium text-start px-1">#</th>
                                                <th class="pb-2 font-medium text-start px-1">{{ $t('inventory.warehouses.single') }}</th>
                                                <th class="pb-2 font-medium text-center px-1">{{ $t('inventory.parts.cost_price') }}</th>
                                                <th class="pb-2 font-medium text-center px-1">{{ $t('inventory.parts.sale_price') }}</th>
                                                <th class="pb-2 font-medium text-center px-1">{{ $t('inventory.parts.min_sale_price') }}</th>
                                                <th class="pb-2 font-medium text-center px-1">{{ $t('inventory.stock.current_stock') }}</th>
                                                <th class="pb-2 font-medium text-center px-1">{{ $t('common.active') }}</th>
                                                <th class="pb-2 font-medium text-start px-1">{{ $t('inventory.locations.location') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            <tr v-for="(balance, index) in part.inventory_balances" :key="balance.id">
                                                <td class="py-2 text-gray-500 px-1">{{ index + 1 }}</td>
                                                <td class="py-2 font-medium text-gray-700 dark:text-gray-300 px-1">
                                                    {{ balance.warehouse?.center?.name || balance.warehouse?.name || '-' }}
                                                </td>
                                                <td class="py-2 text-center text-gray-600 dark:text-gray-400 px-1">{{ formatCurrency(balance.wac_cost) }}</td>
                                                <td class="py-2 text-center text-gray-600 dark:text-gray-400 px-1">{{ formatCurrency(part.default_sale_price) }}</td>
                                                <td class="py-2 text-center text-gray-600 dark:text-gray-400 px-1">{{ formatCurrency(part.min_sale_price) }}</td>
                                                <td class="py-2 text-center font-medium text-gray-900 dark:text-white px-1">
                                                    {{ formatQuantity(balance.qty_on_hand) }} {{ locale === 'ar' ? part.unit?.name_ar : (part.unit?.name_en || part.unit?.name_ar) }}
                                                </td>
                                                <td class="py-2 text-center px-1">
                                                    <svg class="w-3 h-3 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </td>
                                                <td class="py-2 text-gray-500 px-1">-</td>
                                            </tr>
                                            <tr v-if="!part.inventory_balances?.length">
                                                <td colspan="8" class="py-3 text-center text-gray-400 italic">
                                                    {{ $t('inventory.parts.no_stock') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Edit/Action Overlay (Visible on Hover or Menu) -->
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editPart(part)" class="p-1.5 bg-white dark:bg-gray-700 rounded-full shadow-sm text-gray-400 hover:text-blue-600 border border-gray-100 dark:border-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.parts.empty') }}
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="parts.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('common.showing') }} {{ parts.from }} - {{ parts.to }} {{ $t('common.of') }} {{ parts.total }}
                        </span>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in parts.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active 
                                        ? 'bg-blue-600 text-white' 
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

            <!-- Create/Edit Modal -->
            <CreateModal
                :show="showCreateModal"
                :part="editingPart"
                :units="units"
                :categories="categories"
                @close="closeModal"
            />
        </div>
    </AppLayout>

    <!-- Print Section -->
    <Teleport to="body">
        <div class="print-section hidden">
            <!-- Header -->
            <PrintHeader 
                :title="$t('inventory.parts.title')" 
            />

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>{{ $t('inventory.parts.sku') }}</th>
                        <th>{{ $t('inventory.parts.name') }}</th>
                        <th>{{ $t('inventory.parts.category') }}</th>
                        <th>{{ $t('inventory.parts.unit') }}</th>
                        <th>{{ $t('inventory.stock.qty') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(part, index) in parts.data" :key="part.id">
                        <td>{{ index + 1 }}</td>
                        <td class="font-bold">{{ part.sku }}</td>
                        <td>
                            {{ part.name_ar }}
                            <div v-if="part.name_en" class="text-[10px] text-gray-500">{{ part.name_en }}</div>
                        </td>
                        <td>{{ (locale === 'ar' ? part.category?.name_ar : (part.category?.name_en || part.category?.name_ar)) || '-' }}</td>
                        <td>{{ (locale === 'ar' ? part.unit?.name_ar : (part.unit?.name_en || part.unit?.name_ar)) || '-' }}</td>
                        <td>{{ formatQuantity(part.inventory_balances_sum_qty_on_hand ?? 0) }}</td>
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
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { debounce } from 'lodash-es';
import CreateModal from './CreateModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrintHeader from '@/Components/Print/PrintHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import SortIcon from '@/Components/Common/SortIcon.vue';

import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    parts: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    units: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { formatQuantity, formatCurrency, toEnglish } = useNumberFormat();

// Sorting Logic
const sortColumn = ref('sku');
const sortDirection = ref('asc');

const toggleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const sortedParts = computed(() => {
    if (!props.parts?.data) return [];
    
    return [...props.parts.data].sort((a, b) => {
        let aVal, bVal;
        
        switch (sortColumn.value) {
            case 'sku':
                aVal = a.sku || '';
                bVal = b.sku || '';
                break;
            case 'name':
                aVal = locale.value === 'ar' ? a.name_ar : (a.name_en || a.name_ar);
                bVal = locale.value === 'ar' ? b.name_ar : (b.name_en || b.name_ar);
                break;
            case 'description':
                aVal = a.description || '';
                bVal = b.description || '';
                break;
            case 'unit':
                aVal = locale.value === 'ar' ? a.unit?.name_ar : (a.unit?.name_en || a.unit?.name_ar);
                bVal = locale.value === 'ar' ? b.unit?.name_ar : (b.unit?.name_en || b.unit?.name_ar);
                break;
            case 'category':
                aVal = a.category?.name_ar || '';
                bVal = b.category?.name_ar || '';
                break;
            case 'qty':
                aVal = parseFloat(a.inventory_balances_sum_qty_on_hand || 0);
                bVal = parseFloat(b.inventory_balances_sum_qty_on_hand || 0);
                break;
            default:
                return 0;
        }

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const computedCategories = computed(() => {
    const allOption = { id: '', name: locale.value === 'ar' ? 'جميع التصنيفات' : 'All Categories' };
    const cats = props.categories.map(cat => ({
        ...cat,
        name: locale.value === 'ar' ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
    return [allOption, ...cats];
});

const localFilters = ref({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    status: props.filters?.status || '',
});

// View Mode Logic
const viewMode = ref(localStorage.getItem('inventory_parts_view_mode') || 'list');

const toggleView = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('inventory_parts_view_mode', mode);
};

const showCreateModal = ref(false);
const editingPart = ref(null);

const createPart = () => {
    editingPart.value = null;
    showCreateModal.value = true;
};

const editPart = (part) => {
    editingPart.value = part;
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingPart.value = null;
};

const applyFilters = () => {
    router.get(route('app.inventory.parts.index'), {
        search: localFilters.value.search || undefined,
        category: localFilters.value.category || undefined,
        status: localFilters.value.status || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    localFilters.value.search = '';
    localFilters.value.category = '';
    localFilters.value.status = '';
    applyFilters();
};

const debouncedSearch = debounce(applyFilters, 300);

const toggleActive = (part) => {
    router.patch(route('app.inventory.parts.toggle', part.id), {}, {
        preserveScroll: true,
    });
};

const expandedPartId = ref(null);

const toggleExpanded = (partId) => {
    if (expandedPartId.value === partId) {
        expandedPartId.value = null;
    } else {
        expandedPartId.value = partId;
    }
};

const handlePrint = () => {
    window.print();
};
</script>
