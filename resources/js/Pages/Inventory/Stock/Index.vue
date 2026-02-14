<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('inventory.stock.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ warehouse?.name }}</p>
                        </div>
                    </div>
                    <!-- View Toggle -->
                    <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        <button 
                            @click="viewMode = 'list'"
                            :class="['p-2 rounded-md transition-all', viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <button 
                            @click="viewMode = 'grid'"
                            :class="['p-2 rounded-md transition-all', viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono" dir="ltr">{{ stats.total_items }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.total_items') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 font-mono" dir="ltr">{{ stats.in_stock }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.in_stock') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 font-mono" dir="ltr">{{ stats.low_stock }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.low_stock') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrency(stats.total_value) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.total_value') }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="w-64" v-if="warehouses && warehouses.length > 1">
                        <SearchableSelect
                            v-model="localFilters.warehouse_id"
                            :options="warehouses"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('inventory.stock.select_warehouse') || 'اختر المستودع'"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            :placeholder="$t('inventory.stock.search_placeholder')"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="w-48">
                        <SearchableSelect
                            v-model="localFilters.category"
                            :options="[{id: '', name: $t('common.all_categories')}, ...categories]"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('common.all_categories')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="w-48">
                        <SearchableSelect
                            v-model="localFilters.stock_status"
                            :options="[
                                {value: '', label: $t('common.all')},
                                {value: 'in_stock', label: $t('inventory.stock.in_stock')},
                                {value: 'low_stock', label: $t('inventory.stock.low_stock')},
                                {value: 'out_of_stock', label: $t('inventory.stock.out_of_stock')}
                            ]"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('common.all')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- View Content -->
            <div v-if="viewMode === 'list'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.sku_barcode') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.name') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.description') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.qty') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.min_qty') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.wac') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.price') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.min_sale_price') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(balance, index) in balances.data" :key="balance.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-start text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-mono" dir="ltr">{{ (balances.current_page - 1) * balances.per_page + index + 1 }}</span>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-900 dark:text-white">
                                    <div class="font-mono"><span dir="ltr">{{ balance.part?.sku }}</span></div>
                                    <div v-if="balance.part?.barcode" class="text-xs text-gray-500 font-mono"><span dir="ltr">{{ balance.part?.barcode }}</span></div>
                                </td>
                                <td class="px-4 py-3 text-start">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <Link :href="route('app.inventory.parts.show', balance.part_id)" class="hover:text-blue-600 hover:underline">
                                            {{ balance.part?.name_ar }}
                                        </Link>
                                    </div>
                                    <div v-if="balance.part?.name_en" class="text-xs text-gray-500 dark:text-gray-400">{{ balance.part?.name_en }}</div>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">{{ balance.part?.description || '-' }}</td>
                                <td class="px-4 py-3 text-start">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-sm font-medium font-mono',
                                        getStockStatusClass(balance)
                                    ]" dir="ltr">
                                        {{ formatDecimal(balance.qty_on_hand) }} {{ balance.part?.unit?.name_ar || balance.part?.unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-mono" dir="ltr">{{ formatDecimal(balance.part?.min_qty || 0) }}</span>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-900 dark:text-white">
                                    <span class="font-mono" dir="ltr">{{ formatCurrency(balance.wac_cost) }}</span>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-900 dark:text-white">
                                    <span class="font-mono" dir="ltr">{{ formatCurrency(balance.part?.default_sale_price) }}</span>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-900 dark:text-white">
                                    <span class="font-mono" dir="ltr">{{ formatCurrency(balance.part?.min_sale_price) }}</span>
                                </td>
                            </tr>
                            <tr v-if="!balances.data.length">
                                <td colspan="9" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('inventory.stock.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Grid View -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="balance in balances.data" :key="balance.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow relative overflow-hidden group">
                    
                    <!-- Header -->
                    <div class="px-4 py-3 bg-slate-50 dark:bg-gray-750 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center gap-2 text-teal-600 dark:text-teal-400 font-mono text-sm font-medium" dir="ltr">
                            <span>{{ balance.part?.sku }}</span>
                            <span class="text-gray-400">-</span>
                            <span>{{ balance.part?.unit?.name_ar || balance.part?.unit }}</span>
                        </div>
                        <span :class="[
                            'px-2 py-0.5 rounded text-xs font-bold font-mono',
                            getStockStatusClass(balance)
                        ]" dir="ltr">
                            {{ formatDecimal(balance.qty_on_hand) }} {{ $t('inventory.units.piece') }}
                        </span>
                    </div>

                    <!-- Body -->
                    <div class="p-4 space-y-4">
                        <!-- Name -->
                        <div class="min-h-[3rem]">
                            <Link :href="route('app.inventory.parts.show', balance.part_id)" class="text-base font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 hover:underline line-clamp-2 leading-snug">
                                {{ balance.part?.name_ar }}
                            </Link>
                        </div>

                        <!-- Details Grid -->
                        <div class="space-y-1.5 pt-2 border-t border-gray-50 dark:border-gray-700">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.wac') }}:</span>
                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300" dir="ltr">{{ formatDecimal(balance.wac_cost) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.default_sale_price') }}:</span>
                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300" dir="ltr">{{ formatDecimal(balance.part?.default_sale_price) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.min_qty') }}:</span>
                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300" dir="ltr">{{ formatDecimal(balance.part?.min_qty) }}</span>
                            </div>
                        </div>

                        <!-- Min Sale Price Badge -->
                        <div class="flex justify-start pt-2">
                             <div class="inline-flex items-center gap-1 border border-red-400 rounded px-2 py-1 text-xs text-red-500 dark:text-red-400 bg-white dark:bg-gray-800">
                                <span>{{ $t('inventory.parts.min_sale_price') }}:</span>
                                <span class="font-bold font-mono" dir="ltr">{{ formatDecimal(balance.part?.min_sale_price) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Brand Placeholder -->
                     <div class="px-4 py-2 bg-gray-50 dark:bg-gray-750 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                            <span>{{ $t('inventory.parts.category') }} ({{ balance.part?.category?.name_ar || '-' }})</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="balances.links?.length > 3" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-4 py-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('common.showing') }} {{ balances.from }} - {{ balances.to }} {{ $t('common.of') }} {{ balances.total }}
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in balances.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded text-sm',
                                link.active 
                                    ? 'bg-emerald-600 text-white' 
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
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    balances: Object,
    warehouse: Object,
    warehouses: Array,
    categories: Array,
    filters: Object,
    stats: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    stock_status: props.filters?.stock_status || '',
    category: props.filters?.category || '',
    warehouse_id: props.filters?.warehouse_id || props.warehouse?.id || '',
});

const viewMode = ref(localStorage.getItem('inventory_stock_view_mode') || 'list');

watch(viewMode, (mode) => {
    localStorage.setItem('inventory_stock_view_mode', mode);
});

const applyFilters = () => {
    router.get(route('app.inventory.stock.index'), {
        search: localFilters.value.search || undefined,
        stock_status: localFilters.value.stock_status || undefined,
        category: localFilters.value.category || undefined,
        warehouse_id: localFilters.value.warehouse_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debouncedSearch = debounce(applyFilters, 300);

const formatDecimal = (value) => {
    if (!value) return '0';
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { // English numbers requested
        style: 'decimal', // Using decimal style to control format manually easily or just use currency with custom formatting
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value || 0);
};

const getStockStatusClass = (balance) => {
    const qty = balance.qty_on_hand;
    const minQty = balance.part?.min_qty || 0;
    
    if (qty <= 0) {
        return 'bg-slate-700 text-white'; // Out of stock - Dark as per image
    } else if (qty <= minQty) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    }
    return 'bg-slate-700 text-white'; // Default stock badge per image
};
</script>
