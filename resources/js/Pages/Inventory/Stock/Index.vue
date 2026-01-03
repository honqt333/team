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
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_items }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.total_items') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.in_stock }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.in_stock') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.low_stock }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.low_stock') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_value) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.stock.total_value') }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
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

            <!-- Stock Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.sku') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.name') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.unit') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.qty') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.wac') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.value') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="balance in balances.data" :key="balance.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ balance.part?.sku }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ balance.part?.name_ar }}</div>
                                    <div v-if="balance.part?.name_en" class="text-xs text-gray-500 dark:text-gray-400">{{ balance.part?.name_en }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ balance.part?.unit }}</td>
                                <td class="px-4 py-3 text-end">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-sm font-medium',
                                        getStockStatusClass(balance)
                                    ]">
                                        {{ balance.qty_on_hand }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end text-sm text-gray-900 dark:text-white font-mono" dir="ltr">
                                    {{ formatCurrency(balance.wac_cost) }}
                                </td>
                                <td class="px-4 py-3 text-end text-sm text-gray-900 dark:text-white font-mono" dir="ltr">
                                    {{ formatCurrency(balance.qty_on_hand * balance.wac_cost) }}
                                </td>
                            </tr>
                            <tr v-if="!balances.data.length">
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('inventory.stock.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="balances.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
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
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    balances: Object,
    warehouse: Object,
    categories: Array,
    filters: Object,
    stats: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    stock_status: props.filters?.stock_status || '',
    category: props.filters?.category || '',
});

const applyFilters = () => {
    router.get(route('app.inventory.stock.index'), {
        search: localFilters.value.search || undefined,
        stock_status: localFilters.value.stock_status || undefined,
        category: localFilters.value.category || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debouncedSearch = debounce(applyFilters, 300);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 2,
    }).format(value || 0);
};

const getStockStatusClass = (balance) => {
    const qty = balance.qty_on_hand;
    const minQty = balance.part?.min_qty || 0;
    
    if (qty <= 0) {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    } else if (qty <= minQty) {
        return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
    }
    return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
};
</script>
