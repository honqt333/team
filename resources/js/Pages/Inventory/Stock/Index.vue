<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.stock.title')"
                :subtitle="$t('inventory.stock.subtitle') + (warehouse ? ` - ${warehouse.name}` : '')"
                :totalCount="balances.total"
                :countLabel="$t('inventory.stock.title')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-700"
                glowFrom="from-emerald-500"
                badgeBg="bg-emerald-50/50 dark:bg-emerald-900/30"
                badgeText="text-emerald-600 dark:text-emerald-400"
                badgeBorder="border-emerald-100/50 dark:border-emerald-800/30"
                badgeDot="bg-emerald-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <button 
                            @click="viewMode = 'grid'"
                            :title="$t('common.grid_view')"
                            :class="[
                                'p-2.5 rounded-xl transition-all shadow-sm',
                                viewMode === 'grid'
                                    ? 'bg-blue-600 text-white shadow-blue-500/20'
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
                                    ? 'bg-blue-600 text-white shadow-blue-500/20'
                                    : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-emerald-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('inventory.stock.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                            <!-- Warehouse Filter -->
                            <div class="w-full md:w-48" v-if="warehouses && warehouses.length > 1">
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

                            <!-- Category Filter -->
                            <div class="w-full md:w-48">
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

                            <!-- Stock Status Filter -->
                            <div class="w-full md:w-40">
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
                                        {{ formatQuantity(balance.qty_on_hand) }} {{ balance.part?.unit?.name_ar || balance.part?.unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-start text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-mono" dir="ltr">{{ formatQuantity(balance.part?.min_qty || 0) }}</span>
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
                            {{ formatQuantity(balance.qty_on_hand) }} {{ $t('inventory.units.piece') }}
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
                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300" dir="ltr">{{ formatCurrency(balance.wac_cost) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.default_sale_price') }}:</span>
                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300" dir="ltr">{{ formatCurrency(balance.part?.default_sale_price) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.min_qty') }}:</span>
                                <span class="font-mono font-medium text-gray-700 dark:text-gray-300" dir="ltr">{{ formatQuantity(balance.part?.min_qty) }}</span>
                            </div>
                        </div>

                        <!-- Min Sale Price Badge -->
                        <div class="flex justify-start pt-2">
                             <div class="inline-flex items-center gap-1 border border-red-400 rounded px-2 py-1 text-xs text-red-500 dark:text-red-400 bg-white dark:bg-gray-800">
                                <span>{{ $t('inventory.parts.min_sale_price') }}:</span>
                                <span class="font-bold font-mono" dir="ltr">{{ formatCurrency(balance.part?.min_sale_price) }}</span>
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
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { debounce } from 'lodash-es';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    balances: Object,
    warehouse: Object,
    warehouses: Array,
    categories: Array,
    filters: Object,
    stats: Object,
});

const { formatQuantity, formatCurrency } = useNumberFormat();

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

const resetFilters = () => {
    localFilters.value.search = '';
    localFilters.value.stock_status = '';
    localFilters.value.category = '';
    localFilters.value.warehouse_id = props.warehouse?.id || '';
    applyFilters();
};

const debouncedSearch = debounce(applyFilters, 300);

const getStockStatusClass = (balance) => {
    const qty = balance.qty_on_hand;
    const minQty = balance.part?.min_qty || 0;
    
    if (qty <= 0) {
        return 'bg-red-500 text-white shadow-sm'; // Out of stock
    } else if (qty <= minQty) {
        return 'bg-yellow-500 text-white shadow-sm'; // Low stock
    }
    return 'bg-blue-600 text-white shadow-sm'; // Normal stock
};
</script>
