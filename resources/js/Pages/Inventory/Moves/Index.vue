<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.moves.title')"
                :subtitle="$t('inventory.moves.subtitle') + (warehouse ? ` - ${warehouse.name}` : '')"
                :totalCount="moves.total"
                :countLabel="$t('inventory.moves.title')"
                gradientFrom="from-purple-600"
                gradientTo="to-indigo-700"
                glowFrom="from-purple-500"
                badgeBg="bg-purple-50/50 dark:bg-purple-900/30"
                badgeText="text-purple-600 dark:text-purple-400"
                badgeBorder="border-purple-100/50 dark:border-purple-800/30"
                badgeDot="bg-purple-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Receipt Button -->
                        <button
                            v-if="can('inventory.receipts.create') || isAnyAdmin()"
                            @click="showReceiptModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 text-green-700 dark:text-green-400 rounded-2xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all group/receipt border border-green-50 dark:border-green-900/30"
                        >
                            <div class="w-7 h-7 rounded-lg bg-green-50 dark:bg-green-900/50 flex items-center justify-center group-hover/receipt:rotate-90 transition-transform duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <span class="text-sm tracking-tight">{{ $t('inventory.moves.receipt') }}</span>
                        </button>

                        <!-- Adjustment Button -->
                        <button
                            v-if="can('inventory.adjustments.create') || isAnyAdmin()"
                            @click="showAdjustmentModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 text-yellow-700 dark:text-yellow-400 rounded-2xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all group/adjust border border-yellow-50 dark:border-yellow-900/30"
                        >
                            <div class="w-7 h-7 rounded-lg bg-yellow-50 dark:bg-yellow-900/50 flex items-center justify-center group-hover/adjust:rotate-12 transition-transform duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                            <span class="text-sm tracking-tight">{{ $t('inventory.moves.adjust') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-purple-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('inventory.moves.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                            <!-- Type Filter -->
                            <div class="w-full md:w-56">
                                <SearchableSelect
                                    v-model="localFilters.type"
                                    :options="moveTypeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('inventory.moves.all_types')"
                                    :label="''"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Date From -->
                            <div class="w-full md:w-44">
                                <CustomDatePicker
                                    v-model="localFilters.date_from"
                                    :placeholder="$t('work_orders.filters.date_from')"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="w-full md:w-44">
                                <CustomDatePicker
                                    v-model="localFilters.date_to"
                                    :placeholder="$t('work_orders.filters.date_to')"
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

            <!-- Moves Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full" dir="rtl">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.row_number') }}</th>
                                <th class="px-3 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.part') }}</th>
                                <th class="px-3 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.action') }}</th>
                                <th class="px-3 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.details') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.stock_before') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.qty') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.stock_after') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.cost_price') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.wac') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.cost_amount') }}</th>
                                <th class="px-3 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('inventory.moves.columns.updated_at') }}</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(move, index) in moves.data" :key="move.id" :class="[
                                'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                                move.reversed_at ? 'opacity-50 line-through' : ''
                            ]">
                                <!-- # -->
                                <td class="px-3 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{{ (moves.current_page - 1) * moves.per_page + index + 1 }}</td>
                                
                                <!-- Part -->
                                <td class="px-3 py-3 text-start">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ move.part?.name_ar }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono" dir="ltr">{{ move.part?.sku }}</div>
                                </td>
                                
                                <!-- Action/Type -->
                                <td class="px-3 py-3 text-start">
                                    <span :class="getMoveTypeBadgeClass(move.move_type)">
                                        {{ getMoveTypeLabel(move.move_type) }}
                                    </span>
                                </td>
                                
                                <!-- Details/Notes -->
                                <td class="px-3 py-3 text-start text-sm text-gray-600 dark:text-gray-300 max-w-[200px] truncate" :title="move.notes">
                                    {{ move.notes || '-' }}
                                </td>
                                
                                <!-- Stock Before -->
                                <td class="px-3 py-3 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ calculateStockBefore(move) }}
                                </td>
                                
                                <!-- Quantity -->
                                <td class="px-3 py-3 text-center">
                                    <span :class="[
                                        'font-mono text-sm font-medium',
                                        move.qty > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ move.qty > 0 ? '+' : '' }}{{ move.qty }}
                                    </span>
                                </td>
                                
                                <!-- Stock After -->
                                <td class="px-3 py-3 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ move.balance_after }}
                                </td>
                                
                                <!-- Cost Price (Unit Cost) -->
                                <td class="px-3 py-3 text-center text-sm text-gray-900 dark:text-white font-mono">
                                    {{ formatCurrency(move.unit_cost) }}
                                </td>
                                
                                <!-- WAC (Weighted Average Cost) -->
                                <td class="px-3 py-3 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ formatCurrency(move.wac_after) }}
                                </td>
                                
                                <!-- Cost Amount (Total Cost) -->
                                <td class="px-3 py-3 text-center text-sm text-gray-900 dark:text-white font-mono">
                                    {{ formatCurrency(move.total_cost) }}
                                </td>
                                
                                <!-- Updated At -->
                                <td class="px-3 py-3 text-start text-sm text-gray-600 dark:text-gray-300" dir="ltr">
                                    {{ formatDate(move.posted_at) }}
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-3 py-3 text-center">
                                    <button
                                        v-if="canReverse(move)"
                                        @click="reverseMove(move)"
                                        class="p-2 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                        :title="$t('inventory.moves.reverse')"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                    </button>
                                    <span v-else-if="move.reversed_at" class="text-xs text-red-500">
                                        {{ $t('inventory.moves.reversed') }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!moves.data.length">
                                <td colspan="12" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('inventory.moves.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="moves.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('common.showing') }} {{ moves.from }} - {{ moves.to }} {{ $t('common.of') }} {{ moves.total }}
                        </span>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in moves.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active 
                                        ? 'bg-purple-600 text-white' 
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

    <!-- Modals -->
    <ReceiptModal
        :show="showReceiptModal"
        :warehouse-id="warehouse?.id"
        @close="showReceiptModal = false"
        @saved="() => {}"
    />
    <AdjustmentModal
        :show="showAdjustmentModal"
        :warehouse-id="warehouse?.id"
        @close="showAdjustmentModal = false"
        @saved="() => {}"
    />
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ReceiptModal from '@/Components/Inventory/ReceiptModal.vue';
import AdjustmentModal from '@/Components/Inventory/AdjustmentModal.vue';

const { t } = useI18n();
const page = usePage();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;

const props = defineProps({
    moves: Object,
    warehouse: Object,
    moveTypes: Array,
    filters: Object,
});

const showReceiptModal = ref(false);
const showAdjustmentModal = ref(false);

const localFilters = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('app.inventory.moves.index'), {
        search: localFilters.value.search || undefined,
        type: localFilters.value.type || undefined,
        date_from: localFilters.value.date_from || undefined,
        date_to: localFilters.value.date_to || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value.search = '';
    localFilters.value.type = '';
    localFilters.value.date_from = '';
    localFilters.value.date_to = '';
    applyFilters();
};

const debouncedSearch = debounce(applyFilters, 300);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('ar-SA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 2,
    }).format(value || 0);
};

const getMoveTypeLabel = (type) => {
    const labels = {
        'receipt': t('inventory.moves.types.receipt'),
        'issue_to_workorder': t('inventory.moves.types.issue'),
        'adjustment_in': t('inventory.moves.types.adjustment_in'),
        'adjustment_out': t('inventory.moves.types.adjustment_out'),
        'transfer_in': t('inventory.moves.types.transfer_in'),
        'transfer_out': t('inventory.moves.types.transfer_out'),
        'reversal': t('inventory.moves.types.reversal'),
    };
    return labels[type] || type;
};

const moveTypeOptions = computed(() => {
    const allOption = { value: '', label: t('inventory.moves.all_types') };
    const types = props.moveTypes.map(type => ({
        value: type,
        label: getMoveTypeLabel(type)
    }));
    return [allOption, ...types];
});

const getMoveTypeBadgeClass = (type) => {
    const classes = {
        'receipt': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200/50 dark:border-green-800/30',
        'issue_to_workorder': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200/50 dark:border-red-800/30',
        'adjustment_in': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200/50 dark:border-blue-800/30',
        'adjustment_out': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border border-orange-200/50 dark:border-orange-800/30',
        'transfer_in': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 border border-purple-200/50 dark:border-purple-800/30',
        'transfer_out': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/30',
        'reversal': 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 border border-gray-200/50 dark:border-gray-500/30',
    };
    return `inline-flex px-2.5 py-1 rounded-full text-xs font-bold shadow-sm ${classes[type] || 'bg-gray-100 text-gray-700'}`;
};

// Calculate stock before from balance_after - qty
const calculateStockBefore = (move) => {
    const balanceAfter = parseFloat(move.balance_after) || 0;
    const qty = parseFloat(move.qty) || 0;
    return (balanceAfter - qty).toFixed(3).replace(/\.?0+$/, '');
};

const canReverse = (move) => {
    if (move.reversed_at) return false;
    if (move.move_type === 'receipt') return can('inventory.receipts.cancel');
    if (move.move_type.startsWith('adjustment')) return can('inventory.adjustments.cancel');
    if (move.move_type === 'issue_to_workorder') return can('inventory.issue.reverse');
    return false;
};

const reverseMove = (move) => {
    if (!confirm(t('inventory.moves.confirm_reverse'))) return;
    
    router.post(route('app.inventory.moves.reverse', move.id), {}, {
        preserveScroll: true,
    });
};
</script>
