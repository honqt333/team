<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('app.inventory.parts.index')" class="p-2 -ml-2 text-gray-400 hover:text-gray-500 transition-colors">
                            <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ part.name_ar }}</h1>
                                <span :class="[
                                    'px-2.5 py-1 rounded-full text-xs font-medium',
                                    part.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                ]">
                                    {{ part.is_active ? $t('common.active') : $t('common.inactive') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                <span>{{ part.sku }}</span>
                                <span v-if="part.barcode" class="inline-flex items-center gap-1 text-gray-400">
                                    <span>•</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    {{ part.barcode }}
                                </span>
                            </div>
                        </div>
                    </div>
                     <div class="flex items-center gap-2">
                        <button
                            v-if="can('inventory.parts.update')"
                            @click="editPart"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            {{ $t('common.edit') }}
                        </button>
                        <button
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 dark:bg-gray-600 text-white rounded-lg hover:bg-gray-800 dark:hover:bg-gray-500 transition-colors shadow-sm"
                            @click="printBarcode"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            {{ $t('inventory.parts.print_barcode') || 'طباعة باركود' }}
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
                     <!-- Total Stock -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-100 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $t('inventory.stock.total_qty') || 'إجمالي المخزون' }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                            {{ formatQuantity(totalStock) }} <span class="text-sm text-gray-500 font-normal">{{ part.unit?.name_ar }}</span>
                        </div>
                    </div>
                    
                    <!-- Average Cost -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-100 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $t('inventory.stock.avg_cost') || 'متوسط التكلفة' }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                            {{ formatCurrency(avgCost) }} <span class="text-sm text-gray-500 font-normal">{{ $t('common.currency_sar') }}</span>
                        </div>
                    </div>

                    <!-- Selling Price -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-100 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $t('inventory.parts.default_sale_price') }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                            {{ formatCurrency(part.default_sale_price) }} <span class="text-sm text-gray-500 font-normal">{{ $t('common.currency_sar') }}</span>
                        </div>
                    </div>

                     <!-- Category -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-100 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $t('inventory.parts.category') }}</div>
                        <div class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ part.category?.name_ar || '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Stock by Warehouse -->
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 h-fit">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('inventory.stock.warehouses_stock') || 'رصيد المستودعات' }}</h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="balance in balances" :key="balance.id" class="p-4 flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ balance.warehouse?.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ balance.warehouse?.center?.name || 'الفرع الرئيسي' }}</div>
                            </div>
                            <div class="text-end">
                                <div class="font-bold text-gray-900 dark:text-white font-mono">{{ formatQuantity(balance.qty_on_hand) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ formatCurrency(balance.wac_cost) }} / {{ part.unit?.name_ar }}</div>
                            </div>
                        </div>
                        <div v-if="!balances.length" class="p-8 text-center text-gray-500 dark:text-gray-400">
                            {{ $t('inventory.stock.empty') }}
                        </div>
                    </div>
                </div>

                <!-- Recent Movements -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('inventory.moves.title') }}</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-3 py-3 text-start font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">#</th>
                                    <th class="px-3 py-3 text-start font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.moves.type') }}</th>
                                    <th class="px-3 py-3 text-start font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('common.details') }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.stock.previous_stock') || 'السابق' }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.stock.qty') }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.moves.balance_after') }}</th>
                                    
                                    <!-- Cost Section -->
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap bg-gray-50/50 dark:bg-gray-700/30">{{ $t('inventory.parts.cost_price') }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap bg-gray-50/50 dark:bg-gray-700/30">{{ $t('inventory.stock.wac') }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap bg-gray-50/50 dark:bg-gray-700/30">{{ $t('inventory.parts.cost_total') || 'إجمالي التكلفة' }}</th>

                                    <!-- Price Section (Sales) -->
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.parts.sale_price') }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.parts.discount') || 'الخصم' }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.parts.subtotal') || 'المجموع الفرعي' }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.parts.vat') || 'الضريبة' }}</th>
                                    <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.parts.total') || 'الإجمالي' }}</th>
                                    
                                    <th class="px-3 py-3 text-end font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $t('inventory.moves.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="move in moves.data" :key="move.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-3 py-3 font-mono text-gray-400">{{ move.id }}</td>
                                    
                                    <!-- Type -->
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span :class="getAllMoveTypeBadgeClass(move.move_type)">
                                            {{ getMoveTypeLabel(move.move_type) }}
                                        </span>
                                    </td>

                                    <!-- Details (Warehouse / Ref) -->
                                    <td class="px-3 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span>{{ move.warehouse?.name }}</span>
                                            <span v-if="move.reference" class="text-[10px] text-gray-400">
                                                {{ move.reference_type?.split('\\').pop() }} #{{ move.reference_id }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Stock Balance Logic -->
                                    <td class="px-3 py-3 text-center font-mono text-gray-500">
                                        {{ formatQuantity(Number(move.balance_after) - Number(move.qty)) }}
                                    </td>
                                    
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        <span :class="[
                                            'font-mono font-medium',
                                            move.qty > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                        ]">
                                            {{ move.qty > 0 ? '+' : '' }}{{ formatQuantity(move.qty) }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-center font-mono font-bold text-gray-700 dark:text-gray-200">
                                        {{ formatQuantity(move.balance_after) }}
                                    </td>

                                    <!-- Cost Data -->
                                    <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400 bg-gray-50/30 dark:bg-gray-800/30">
                                        {{ formatCurrency(move.unit_cost) }}
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400 bg-gray-50/30 dark:bg-gray-800/30">
                                        {{ formatCurrency(move.wac_after) }}
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400 bg-gray-50/30 dark:bg-gray-800/30">
                                        {{ formatCurrency(Number(move.qty) * Number(move.unit_cost)) }}
                                    </td>

                                    <!-- Sales Data (Only relevant for Issues/WorkOrders) -->
                                    <template v-if="isSalesMove(move)">
                                        <!-- Price -->
                                        <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400">
                                            {{ formatCurrency(getSalesPrice(move)) }}
                                        </td>
                                        <!-- Discount -->
                                        <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400">
                                            {{ formatCurrency(getSalesDiscount(move)) }}
                                        </td>
                                        <!-- Subtotal -->
                                        <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400">
                                            {{ formatCurrency(getSalesSubtotal(move)) }}
                                        </td>
                                        <!-- VAT -->
                                        <td class="px-3 py-3 text-center font-mono text-gray-600 dark:text-gray-400">
                                            {{ formatCurrency(getSalesTax(move)) }}
                                        </td>
                                        <!-- Total -->
                                        <td class="px-3 py-3 text-center font-mono font-bold text-gray-800 dark:text-white">
                                            {{ formatCurrency(getSalesTotal(move)) }}
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-3 py-3 text-center text-gray-300">-</td>
                                        <td class="px-3 py-3 text-center text-gray-300">-</td>
                                        <td class="px-3 py-3 text-center text-gray-300">-</td>
                                        <td class="px-3 py-3 text-center text-gray-300">-</td>
                                        <td class="px-3 py-3 text-center text-gray-300">-</td>
                                    </template>

                                    <!-- Date -->
                                    <td class="px-3 py-3 text-end text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
                                        {{ formatDate(move.posted_at) }}
                                        <div class="text-[10px] opacity-70">{{ move.posted_by_user?.name || '-' }}</div>
                                    </td>
                                </tr>
                                <tr v-if="!moves.data.length">
                                    <td colspan="14" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ $t('inventory.moves.empty') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Create/Edit Modal -->
            <CreateModal
                :show="showEditModal"
                :part="part"
                :units="units"
                :categories="categories"
                @close="showEditModal = false"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import CreateModal from './CreateModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    part: Object,
    balances: Array,
    moves: Object,
    units: Array, // Passed for Edit Modal
    categories: Array, // Passed for Edit Modal
});

const { t } = useI18n();
const page = usePage();
const { formatQuantity, formatCurrency } = useNumberFormat();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;

const showEditModal = ref(false);

const editPart = () => {
    showEditModal.value = true;
};

const totalStock = computed(() => {
    return props.balances.reduce((sum, b) => sum + Number(b.qty_on_hand), 0);
});

const avgCost = computed(() => {
    if (totalStock.value === 0) return 0;
    const totalValue = props.balances.reduce((sum, b) => sum + (Number(b.qty_on_hand) * Number(b.wac_cost)), 0);
    return totalValue / totalStock.value;
});

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

const getAllMoveTypeBadgeClass = (type) => {
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

const printBarcode = () => {
    alert('سيتم إضافة ميزة الطباعة قريباً');
};
// Helper to check if move involves sales logic
const isSalesMove = (move) => {
    return ['issue_to_workorder'].includes(move.move_type);
};

// Safe extractors for reference data (Work Order Item mostly)
// Assuming reference is loaded and is likely a WorkOrderItem
const getSalesPrice = (move) => {
    if (!isSalesMove(move) || !move.reference) return 0;
    // If it's a WorkOrderItem, it might have 'price' or 'unit_price'
    return move.reference.price || 0;
};

const getSalesDiscount = (move) => {
    if (!isSalesMove(move) || !move.reference) return 0;
    return move.reference.discount || 0;
};

const getSalesSubtotal = (move) => {
    if (!isSalesMove(move) || !move.reference) return 0;
    // Qty * Price - Discount
    const qty = Math.abs(Number(move.qty));
    const price = Number(getSalesPrice(move));
    const discount = Number(getSalesDiscount(move));
    return (qty * price) - discount;
};

const getSalesTax = (move) => {
    if (!isSalesMove(move) || !move.reference) return 0;
    // Assuming tax is calculated on the subtotal. 
    // If the reference stores tax amount directly, use that.
    // For now returning 0 or reference.tax_amount if available.
    return move.reference.tax_amount || 0;
};

const getSalesTotal = (move) => {
    if (!isSalesMove(move)) return 0;
    return getSalesSubtotal(move) + getSalesTax(move);
};

</script>
