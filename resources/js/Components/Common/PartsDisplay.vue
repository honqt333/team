<template>
    <div class="space-y-4">
        <!-- Header with View Toggle -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ parts.length }} {{ $t('quotes.tabs.parts') }}
                </span>
            </div>

            <!-- View Toggle Buttons -->
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                <button @click="viewMode = 'grid'" :class="[
                    'p-2 rounded-md transition-all',
                    viewMode === 'grid'
                        ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                ]" :title="$t('quotes.parts_display.view_grid')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                </button>
                <button @click="viewMode = 'table'" :class="[
                    'p-2 rounded-md transition-all',
                    viewMode === 'table'
                        ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                ]" :title="$t('quotes.parts_display.view_table')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" :class="[
            'grid gap-4',
            compactGrid
                ? 'grid-cols-1 sm:grid-cols-2'
                : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
        ]">
            <div v-for="(part, index) in parts" :key="part.id"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow group">
                <!-- Card Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <!-- Wrench Icon -->
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <!-- Part Number -->
                        <span class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ part.part_number || part.sku
                            || (index + 1) }}</span>
                    </div>

                    <!-- Price Badge -->
                    <span :class="[
                        'px-2.5 py-1 text-sm font-bold rounded-lg',
                        getSourceColorClass(part.source, 'badge')
                    ]">
                        {{ formatCurrency(part.total || (part.unit_price * part.qty)) }}
                    </span>
                </div>

                <!-- Card Body -->
                <div class="p-4 space-y-3">
                    <!-- Source Badge -->
                    <span :class="[
                        'inline-flex items-center px-2 py-0.5 text-xs rounded-full',
                        getSourceColorClass(part.source, 'source')
                    ]">
                        {{ $t(`quotes.part_source.${part.source}`) }}
                    </span>

                    <!-- Part Name -->
                    <h4 class="font-medium text-gray-900 dark:text-white truncate" :title="part.name">
                        {{ part.name }}
                    </h4>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ $t('quotes.parts_display.price')
                                }}:</span>
                            <span class="ms-1 font-medium text-gray-700 dark:text-gray-300">{{
                                formatCurrency(part.unit_price) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ $t('quotes.parts_display.qty') }}:</span>
                            <span class="ms-1 font-medium text-gray-700 dark:text-gray-300">{{ part.qty }} {{
                                getUnitName(part) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ $t('quotes.parts_display.discount')
                                }}:</span>
                            <span class="ms-1 font-medium"
                                :class="part.discount > 0 ? 'text-red-500' : 'text-gray-400'">
                                {{ part.discount > 0 ? formatCurrency(part.discount) : '0' }}
                            </span>
                        </div>
                        <div v-if="showVat">
                            <span class="text-gray-500 dark:text-gray-400">{{ $t('quotes.parts_display.vat') }}:</span>
                            <span class="ms-1 font-medium text-gray-700 dark:text-gray-300">{{
                                formatCurrency(part.tax_amount || 0) }}</span>
                        </div>
                    </div>

                    <!-- Linked Service (hidden when showService is false) -->
                    <template v-if="showService">
                        <div v-if="part.quote_line || part.service"
                            class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-indigo-600 dark:text-indigo-400">
                                {{ part.quote_line?.description || getName(part.quote_line?.service) ||
                                getName(part.service) }}
                            </span>
                        </div>
                        <div v-else class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-400">{{ $t('quotes.parts_display.without_service') }}</span>
                        </div>
                    </template>

                    <!-- Pending Badge -->
                    <div v-if="isPending(part)" class="pt-2"
                        :class="{ 'border-t border-gray-100 dark:border-gray-700': !showService }">
                        <span
                            class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                            {{ $t('common.pending') }}
                        </span>
                    </div>
                </div>

                <!-- Card Actions (on hover) -->
                <div v-if="!readOnly"
                    class="flex items-center justify-end gap-1 px-4 py-2 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="$emit('edit', part)"
                        class="p-2 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                        :title="$t('common.edit')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button @click="$emit('delete', part)"
                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        :title="$t('common.delete')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div v-else
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-start text-gray-500 dark:text-gray-400 font-medium">#</th>
                            <th class="px-4 py-3 text-start text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.part_number') }}</th>
                            <th class="px-4 py-3 text-start text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.supplier') }}</th>
                            <th v-if="showService"
                                class="px-4 py-3 text-start text-gray-500 dark:text-gray-400 font-medium">{{
                                    $t('quotes.parts_display.service') }}</th>
                            <th class="px-4 py-3 text-end text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.price') }}</th>
                            <th class="px-4 py-3 text-end text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.discount') }}</th>
                            <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.qty') }}</th>
                            <th class="px-4 py-3 text-end text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.amount') }}</th>
                            <th v-if="showVat" class="px-4 py-3 text-end text-gray-500 dark:text-gray-400 font-medium">
                                {{
                                    $t('quotes.parts_display.vat') }}</th>
                            <th class="px-4 py-3 text-end text-gray-500 dark:text-gray-400 font-medium">{{
                                $t('quotes.parts_display.total') }}</th>
                            <th v-if="!readOnly"
                                class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-medium">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="(part, index) in parts" :key="part.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ part.name }}</span>
                                    <span v-if="part.part_number || part.sku" class="text-xs font-mono text-gray-400">
                                        {{ part.part_number || part.sku }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 text-xs rounded-full',
                                    getSourceColorClass(part.source, 'source')
                                ]">
                                    {{ $t(`quotes.part_source.${part.source}`) }}
                                </span>
                            </td>
                            <td v-if="showService"
                                class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[150px] truncate">
                                {{ part.quote_line?.description || getName(part.quote_line?.service) ||
                                    getName(part.service) ||
                                $t('quotes.parts_display.without_service') }}
                            </td>
                            <td class="px-4 py-3 text-end font-mono text-gray-700 dark:text-gray-300">
                                {{ formatCurrency(part.unit_price) }}
                            </td>
                            <td class="px-4 py-3 text-end font-mono"
                                :class="part.discount > 0 ? 'text-red-500' : 'text-gray-400'">
                                {{ part.discount > 0 ? formatCurrency(part.discount) : '0.00' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                {{ part.qty }} {{ getUnitName(part) }}
                            </td>
                            <td class="px-4 py-3 text-end font-mono text-gray-700 dark:text-gray-300">
                                {{ formatCurrency((part.unit_price * part.qty) - (part.discount || 0)) }}
                            </td>
                            <td v-if="showVat" class="px-4 py-3 text-end font-mono text-gray-700 dark:text-gray-300">
                                {{ formatCurrency(part.tax_amount || 0) }}
                            </td>
                            <td class="px-4 py-3 text-end font-bold font-mono text-gray-900 dark:text-white">
                                {{ formatCurrency(part.total || (part.unit_price * part.qty) - (part.discount || 0) +
                                (part.tax_amount || 0)) }}
                            </td>
                            <td v-if="!readOnly" class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="$emit('edit', part)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button @click="$emit('delete', part)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <!-- Table Footer with Totals -->
                    <tfoot class="bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        <tr class="font-bold">
                            <td :colspan="showService ? 4 : 3" class="px-4 py-3 text-gray-700 dark:text-gray-300">{{
                                $t('common.total') }}</td>
                            <td class="px-4 py-3 text-end font-mono text-gray-700 dark:text-gray-300">{{
                                formatCurrency(totalPrice) }}</td>
                            <td class="px-4 py-3 text-end font-mono text-red-500">{{ formatCurrency(totalDiscount) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ totalQty }}</td>
                            <td class="px-4 py-3 text-end font-mono text-gray-700 dark:text-gray-300">{{
                                formatCurrency(totalAmount) }}</td>
                            <td v-if="showVat" class="px-4 py-3 text-end font-mono text-gray-700 dark:text-gray-300">{{
                                formatCurrency(totalVat) }}</td>
                            <td class="px-4 py-3 text-end font-mono text-lg text-indigo-600 dark:text-indigo-400">{{
                                formatCurrency(grandTotal) }}</td>
                            <td v-if="!readOnly"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="parts.length === 0" class="text-center py-12">
            <div
                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ emptyMessage || $t('quotes.show.no_parts') }}</p>
            <button v-if="!readOnly" @click="$emit('add')"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ addButtonText || $t('quotes.show.add_part') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    parts: {
        type: Array,
        default: () => []
    },
    readOnly: {
        type: Boolean,
        default: false
    },
    showVat: {
        type: Boolean,
        default: false
    },
    emptyMessage: {
        type: String,
        default: ''
    },
    addButtonText: {
        type: String,
        default: ''
    },
    storageKey: {
        type: String,
        default: 'parts_display_view_mode'
    },
    showService: {
        type: Boolean,
        default: true
    },
    compactGrid: {
        type: Boolean,
        default: false
    },
    pendingCheck: {
        type: Function,
        default: null
    }
});

// Check if a part is pending (not saved to server yet)
function isPending(part) {
    if (props.pendingCheck) {
        return props.pendingCheck(part);
    }
    return !part.id;
}

defineEmits(['edit', 'delete', 'add']);

const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();

// View mode state
const viewMode = ref('grid');

// Load saved preference
onMounted(() => {
    const saved = localStorage.getItem(props.storageKey);
    if (saved && ['grid', 'table'].includes(saved)) {
        viewMode.value = saved;
    }
});

// Save preference when changed
watch(viewMode, (newVal) => {
    localStorage.setItem(props.storageKey, newVal);
});

// Computed totals
const totalPrice = computed(() => {
    return props.parts.reduce((sum, part) => sum + (parseFloat(part.unit_price) || 0) * (parseFloat(part.qty) || 0), 0);
});

const totalDiscount = computed(() => {
    return props.parts.reduce((sum, part) => sum + (parseFloat(part.discount) || 0), 0);
});

const totalQty = computed(() => {
    return props.parts.reduce((sum, part) => sum + (parseFloat(part.qty) || 0), 0);
});

const totalAmount = computed(() => {
    return totalPrice.value - totalDiscount.value;
});

const totalVat = computed(() => {
    return props.parts.reduce((sum, part) => sum + (parseFloat(part.tax_amount) || 0), 0);
});

const grandTotal = computed(() => {
    return props.parts.reduce((sum, part) => {
        const total = part.total || ((part.unit_price * part.qty) - (part.discount || 0) + (part.tax_amount || 0));
        return sum + (parseFloat(total) || 0);
    }, 0);
});

// Helper functions
function getSourceColorClass(source, type) {
    const colors = {
        warehouse: {
            badge: 'bg-green-500 text-white',
            source: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
        },
        external: {
            badge: 'bg-blue-500 text-white',
            source: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
        },
        customer: {
            badge: 'bg-orange-500 text-white',
            source: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
        }
    };
    return colors[source]?.[type] || colors.external[type];
}

function getUnitName(part) {
    if (part.unit) {
        return getName(part.unit) || '';
    }
    return '';
}
</script>
