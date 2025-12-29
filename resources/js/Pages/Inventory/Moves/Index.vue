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
                            v-if="can('inventory.receipts.create')"
                            @click="showReceiptModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $t('inventory.moves.receipt') }}
                        </button>
                        <button
                            v-if="can('inventory.adjustments.create')"
                            @click="showAdjustmentModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            {{ $t('inventory.moves.adjust') }}
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
                    <select
                        v-model="localFilters.type"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">{{ $t('inventory.moves.all_types') }}</option>
                        <option v-for="type in moveTypes" :key="type" :value="type">
                            {{ getMoveTypeLabel(type) }}
                        </option>
                    </select>
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
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.moves.date') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.moves.type') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.name') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.qty') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.moves.cost') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.moves.balance_after') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.moves.by') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="move in moves.data" :key="move.id" :class="[
                                'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                                move.reversed_at ? 'opacity-50 line-through' : ''
                            ]">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ move.id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" dir="ltr">
                                    {{ formatDate(move.posted_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="getMoveTypeBadgeClass(move.move_type)">
                                        {{ getMoveTypeLabel(move.move_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ move.part?.name_ar }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ move.part?.sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <span :class="[
                                        'font-mono text-sm font-medium',
                                        move.qty > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ move.qty > 0 ? '+' : '' }}{{ move.qty }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end text-sm text-gray-900 dark:text-white font-mono" dir="ltr">
                                    {{ formatCurrency(move.unit_cost) }}
                                </td>
                                <td class="px-4 py-3 text-end text-sm text-gray-600 dark:text-gray-300 font-mono" dir="ltr">
                                    {{ move.balance_after }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ move.posted_by_user?.name || '-' }}
                                </td>
                                <td class="px-4 py-3 text-end">
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
                                <td colspan="9" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
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
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';

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
