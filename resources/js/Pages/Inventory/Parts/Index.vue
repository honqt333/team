<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('inventory.parts.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.subtitle') }}</p>
                        </div>
                    </div>
                <div class="flex items-center gap-3">
                    <!-- View Toggle -->
                    <div class="bg-gray-100 dark:bg-gray-700 p-1 rounded-lg flex items-center">
                        <button 
                            @click="toggleView('list')"
                            :class="['p-1.5 rounded-md transition-all', viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                            title="List View"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <button 
                            @click="toggleView('grid')"
                            :class="['p-1.5 rounded-md transition-all', viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                            title="Grid View"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </button>
                    </div>

                    <button
                        v-if="can('inventory.parts.create')"
                        @click="createPart"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="hidden sm:inline">{{ $t('inventory.parts.add') }}</span>
                    </button>
                </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            :placeholder="$t('inventory.parts.search_placeholder')"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="w-48">
                        <SearchableSelect
                            v-model="localFilters.status"
                            :options="[
                                {value: '', label: $t('common.all')},
                                {value: 'active', label: $t('common.active')},
                                {value: 'inactive', label: $t('common.inactive')}
                            ]"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('common.all')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="w-64" v-if="categories.length">
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
                </div>
            </div>

            <!-- Parts Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="viewMode === 'list'" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.sku') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.name') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.unit') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.category') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.qty') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.status') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="part in parts.data" :key="part.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ part.sku }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <button @click="editPart(part)" class="hover:text-blue-600 hover:underline text-start">
                                            {{ part.name_ar }}
                                        </button>
                                    </div>
                                    <div v-if="part.name_en" class="text-xs text-gray-500 dark:text-gray-400">{{ part.name_en }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ (locale === 'ar' ? part.unit?.name_ar : (part.unit?.name_en || part.unit?.name_ar)) || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ part.category?.name_ar || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                                        part.inventory_balances_sum_qty_on_hand > part.min_qty 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : part.inventory_balances_sum_qty_on_hand > 0
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    ]">
                                        {{ part.inventory_balances_sum_qty_on_hand ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                                        part.is_active 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300'
                                    ]">
                                        {{ part.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="can('inventory.parts.edit')"
                                            @click="editPart(part)"
                                            class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            v-if="can('inventory.parts.deactivate')"
                                            @click="toggleActive(part)"
                                            :class="[
                                                'p-2',
                                                part.is_active 
                                                    ? 'text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400'
                                                    : 'text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400'
                                            ]"
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
                    <div v-if="parts.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div 
                            v-for="part in parts.data" 
                            :key="part.id" 
                            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:shadow-md transition-all group relative flex flex-col overflow-hidden"
                        >
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
                                            ({{ part.inventory_balances_sum_qty_on_hand ?? 0 }})
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
                                                <td class="py-2 text-center text-gray-600 dark:text-gray-400 px-1">{{ balance.wac_cost || 0 }}</td>
                                                <td class="py-2 text-center text-gray-600 dark:text-gray-400 px-1">{{ part.default_sale_price || 0 }}</td>
                                                <td class="py-2 text-center text-gray-600 dark:text-gray-400 px-1">{{ part.min_sale_price || 0 }}</td>
                                                <td class="py-2 text-center font-medium text-gray-900 dark:text-white px-1">
                                                    {{ balance.qty_on_hand }} {{ locale === 'ar' ? part.unit?.name_ar : (part.unit?.name_en || part.unit?.name_ar) }}
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
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import CreateModal from './CreateModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

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
const { can } = usePermission();

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
</script>
