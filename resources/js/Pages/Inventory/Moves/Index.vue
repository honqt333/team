<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('inventory.moves.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ warehouse?.name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="showReceiptModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                        >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                            <span class="hidden sm:inline">{{ $t('inventory.moves.receipt') }}</span>
                        </button>
                        <button
                            @click="showAdjustmentModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors"
                        >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                            <span class="hidden sm:inline">{{ $t('inventory.moves.adjust') }}</span>
                        </button>

                        <!-- Print Button -->
                        <button
                            @click="printMoves"
                            class="flex items-center justify-center w-10 h-10 text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-purple-600 dark:hover:text-purple-400 transition-all"
                            :title="$t('common.print')"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
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
                            :placeholder="$t('inventory.moves.search_placeholder')"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="w-64">
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
                    <input
                        v-model="localFilters.date_from"
                        type="date"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @change="applyFilters"
                    />
                    <input
                        v-model="localFilters.date_to"
                        type="date"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @change="applyFilters"
                    />
                </div>
            </div>

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

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <!-- Header -->
                <div class="print-header">
                    <!-- Arabic Layout -->
                    <div v-if="isRtl" class="flex items-start gap-4 mb-4" style="direction: rtl;">
                        <div v-if="$page.props.tenant?.logo_url" class="w-20 h-20 flex-shrink-0">
                            <img :src="$page.props.tenant.logo_url" class="w-full h-full object-contain" />
                        </div>
                        <div class="flex-1 text-right">
                            <h1 class="text-xl font-bold">{{ $page.props.tenant?.trade_name || $page.props.tenant?.name || 'Carag' }}</h1>
                            <p v-if="$page.props.auth.available_centers?.length > 1" class="text-sm font-bold text-indigo-600">
                                الفرع: {{ $page.props.center?.name }}
                            </p>
                            <p class="text-sm" v-if="warehouse?.name">المستودع: {{ warehouse.name }}</p>
                        </div>
                    </div>
                    <!-- English Layout -->
                    <div v-else class="flex items-start gap-4 mb-4">
                        <div v-if="$page.props.tenant?.logo_url" class="w-20 h-20 flex-shrink-0">
                            <img :src="$page.props.tenant.logo_url" class="w-full h-full object-contain" />
                        </div>
                        <div class="flex-1">
                            <h1 class="text-lg font-bold">{{ $page.props.tenant?.trade_name || $page.props.tenant?.name || 'Carag' }}</h1>
                            <p v-if="$page.props.auth.available_centers?.length > 1" class="text-sm font-bold text-indigo-600">
                                Branch: {{ $page.props.center?.name }}
                            </p>
                            <p class="text-sm" v-if="warehouse?.name">Warehouse: {{ warehouse.name }}</p>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 border-gray-300 text-center">
                        <h2 class="text-lg font-bold">{{ $t('inventory.moves.title') }}</h2>
                        <p class="text-xs text-gray-500 mt-1">{{ new Date().toLocaleDateString(isRtl ? 'ar-SA' : 'en-US') }}</p>
                    </div>
                </div>

                <!-- Table -->
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $t('inventory.moves.columns.part') }}</th>
                            <th>{{ $t('inventory.moves.columns.action') }}</th>
                            <th>{{ $t('inventory.moves.columns.stock_before') }}</th>
                            <th>{{ $t('inventory.moves.columns.qty') }}</th>
                            <th>{{ $t('inventory.moves.columns.stock_after') }}</th>
                            <th>{{ $t('inventory.moves.columns.cost_price') }}</th>
                            <th>{{ $t('inventory.moves.columns.cost_amount') }}</th>
                            <th>{{ $t('inventory.moves.columns.updated_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(move, index) in allMovesForPrint" :key="move.id" :class="move.reversed_at ? 'opacity-50 line-through' : ''">
                            <td>{{ toEnglish(index + 1) }}</td>
                            <td>
                                <div class="font-bold">{{ move.part?.name_ar }}</div>
                                <div class="text-xs font-mono">{{ toEnglish(move.part?.sku) }}</div>
                            </td>
                            <td>{{ getMoveTypeLabel(move.move_type) }}</td>
                            <td class="font-mono">{{ toEnglish(calculateStockBefore(move)) }}</td>
                            <td class="font-mono font-bold" :class="move.qty > 0 ? 'text-green-600' : 'text-red-600'">
                                {{ move.qty > 0 ? '+' : '' }}{{ toEnglish(move.qty) }}
                            </td>
                            <td class="font-mono">{{ toEnglish(move.balance_after) }}</td>
                            <td class="font-mono">{{ toEnglish(formatCurrency(move.unit_cost)) }}</td>
                            <td class="font-mono">{{ toEnglish(formatCurrency(move.total_cost)) }}</td>
                            <td class="text-xs">{{ formatDate(move.posted_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ReceiptModal from '@/Components/Inventory/ReceiptModal.vue';
import AdjustmentModal from '@/Components/Inventory/AdjustmentModal.vue';

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const page = usePage();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;
const { toEnglish } = useNumberFormat();

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

// Printing
const printing = ref(false);
const allMovesForPrint = ref(props.moves?.data || []);

const printMoves = async () => {
    try {
        printing.value = true;
        
        // Fetch all if there are multiple pages
        if (props.moves?.total > props.moves?.data?.length) {
            const response = await axios.get(route('app.inventory.moves.index'), {
                params: {
                    ...localFilters.value,
                    per_page: -1
                }
            });
            allMovesForPrint.value = response.data.data || [];
        } else {
            allMovesForPrint.value = props.moves?.data || [];
        }

        // Wait for DOM
        await new Promise(resolve => setTimeout(resolve, 100));
        window.print();
    } catch (error) {
        console.error('Print failed:', error);
    } finally {
        printing.value = false;
    }
};

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
        'receipt': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'issue_to_workorder': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'adjustment_in': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'adjustment_out': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'transfer_in': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'transfer_out': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        'reversal': 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300',
    };
    return `inline-flex px-2 py-1 rounded-full text-xs font-medium ${classes[type] || 'bg-gray-100 text-gray-700'}`;
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
