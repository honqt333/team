<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Modern High-Density Header -->
            <div class="relative bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden group">
                <!-- Subtle Gradient Background -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-indigo-50/50 to-transparent dark:from-indigo-900/10 dark:to-transparent -mr-16 -mt-16 rounded-full blur-3xl transition-opacity group-hover:opacity-100 opacity-50"></div>
                
                <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <!-- Back Button & Part Image -->
                        <div class="flex items-center gap-4">
                            <Link :href="route('app.inventory.stock.index')" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-full transition-all">
                                <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>

                            <div class="relative flex-shrink-0">
                                <img v-if="part.image_url" :src="part.image_url" class="w-24 h-24 rounded-2xl object-cover border-4 border-white dark:border-gray-700 shadow-xl" />
                                <div v-else class="w-24 h-24 rounded-2xl bg-gray-50 dark:bg-gray-900/50 flex items-center justify-center text-gray-300 dark:text-gray-700 border-2 border-dashed border-gray-200 dark:border-gray-700">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ part.name_ar }}</h1>
                                <span :class="[
                                    'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
                                    part.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                ]">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 rtl:mr-0 rtl:ml-1.5" :class="part.is_active ? 'bg-green-500' : 'bg-red-500'"></span>
                                    {{ part.is_active ? $t('common.active') : $t('common.inactive') }}
                                </span>
                            </div>
                            
                            <div class="mt-2 flex flex-wrap items-center gap-y-2 gap-x-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <span class="text-[10px] font-bold text-indigo-500 uppercase">SKU</span>
                                    <span class="font-mono font-bold">{{ part.sku }}</span>
                                </div>
                                <div v-if="part.barcode" class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700">
                                     <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                    <span class="font-mono">{{ part.barcode }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                     <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                     <span>{{ locale === 'ar' ? part.category?.name_ar : (part.category?.name_en || part.category?.name_ar) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                         <button
                            v-if="can('inventory.parts.edit')"
                            @click="editPart"
                            class="flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm hover:shadow active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            {{ $t('common.edit') }}
                        </button>
                        <button
                            class="flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-gray-600 text-white rounded-xl text-sm font-bold transition-all shadow-lg active:scale-95"
                            @click="printBarcode"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            {{ $t('inventory.parts.print_barcode') }}
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                     <!-- Total Stock -->
                    <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 group/stat hover:bg-white dark:hover:bg-gray-800 transition-all cursor-default">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover/stat:text-indigo-500 transition-colors">{{ $t('inventory.stock.total_qty') }}</div>
                        <div class="flex items-baseline gap-1">
                            <div class="text-2xl font-black text-gray-900 dark:text-white font-mono leading-none">
                                {{ formatQuantity(totalStock) }}
                            </div>
                            <span class="text-xs text-gray-400 font-medium">{{ part.unit?.name_ar }}</span>
                        </div>
                    </div>
                    
                    <!-- Average Cost -->
                    <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 group/stat hover:bg-white dark:hover:bg-gray-800 transition-all cursor-default">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover/stat:text-indigo-500 transition-colors">{{ $t('inventory.stock.avg_cost') }}</div>
                        <div class="flex items-baseline gap-1">
                            <div class="text-2xl font-black text-gray-900 dark:text-white font-mono leading-none">
                                {{ formatCurrency(avgCost) }}
                            </div>
                            <span class="text-xs text-gray-400 font-medium">{{ $t('common.currency_sar') }}</span>
                        </div>
                    </div>

                    <!-- Selling Price -->
                    <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 group/stat hover:bg-white dark:hover:bg-gray-800 transition-all cursor-default">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover/stat:text-indigo-500 transition-colors">{{ $t('inventory.parts.default_sale_price') }}</div>
                        <div class="flex items-baseline gap-1">
                            <div class="text-2xl font-black text-gray-900 dark:text-white font-mono leading-none">
                                {{ formatCurrency(part.default_sale_price) }}
                            </div>
                            <span class="text-xs text-gray-400 font-medium">{{ $t('common.currency_sar') }}</span>
                        </div>
                    </div>

                     <!-- Minimum Qty -->
                    <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 group/stat hover:bg-white dark:hover:bg-gray-800 transition-all cursor-default">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover/stat:text-indigo-500 transition-colors">{{ $t('inventory.parts.min_qty') }}</div>
                        <div class="flex items-baseline gap-1">
                            <div class="text-2xl font-black text-gray-900 dark:text-white font-mono leading-none">
                                {{ formatQuantity(part.min_qty) }}
                            </div>
                            <span class="text-xs text-gray-400 font-medium">{{ part.unit?.name_ar }}</span>
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
                                    <th class="px-4 py-4 text-start font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">#</th>
                                    <th class="px-4 py-4 text-start font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.moves.type') }}</th>
                                    <th class="px-4 py-4 text-start font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('common.details') }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.stock.previous_stock') || 'السابق' }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.stock.qty') }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.moves.balance_after') }}</th>
                                    
                                    <!-- Cost Section -->
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider bg-gray-50/50 dark:bg-gray-700/30">{{ $t('inventory.parts.cost_price') }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider bg-gray-50/50 dark:bg-gray-700/30">{{ $t('inventory.stock.wac') }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider bg-gray-50/50 dark:bg-gray-700/30">{{ $t('inventory.parts.cost_total') || 'إجمالي التكلفة' }}</th>
 
                                    <!-- Price Section (Sales) -->
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.parts.sale_price') }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.parts.discount') || 'الخصم' }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.parts.subtotal') || 'المجموع الفرعي' }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.parts.vat') || 'الضريبة' }}</th>
                                    <th class="px-4 py-4 text-center font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.parts.total') || 'الإجمالي' }}</th>
                                    
                                    <th class="px-4 py-4 text-end font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap uppercase tracking-wider">{{ $t('inventory.moves.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="move in moves.data" :key="move.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-4 font-mono text-gray-400">{{ move.id }}</td>
                                    
                                    <!-- Type -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span :class="getAllMoveTypeBadgeClass(move.move_type)">
                                            {{ getMoveTypeLabel(move.move_type) }}
                                        </span>
                                    </td>
 
                                     <!-- Details (Warehouse / Ref) -->
                                    <td class="px-4 py-4 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                         <div class="flex flex-col">
                                             <span>{{ move.warehouse?.name }}</span>
                                             <span v-if="move.reference" class="text-[10px] text-gray-400">
                                                 {{ move.reference_type?.split('\\').pop() }} #{{ move.reference_id }}
                                             </span>
                                         </div>
                                     </td>
 
                                     <!-- Stock Balance Logic -->
                                    <td class="px-4 py-4 text-center font-mono text-gray-500">
                                         {{ formatQuantity(Number(move.balance_after) - Number(move.qty)) }}
                                     </td>
                                     
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                         <span :class="[
                                             'font-mono font-medium',
                                             move.qty > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                         ]">
                                             {{ move.qty > 0 ? '+' : '' }}{{ formatQuantity(move.qty) }}
                                         </span>
                                     </td>
 
                                    <td class="px-4 py-4 text-center font-mono font-bold text-gray-700 dark:text-gray-200">
                                         {{ formatQuantity(move.balance_after) }}
                                     </td>
 
                                     <!-- Cost Data -->
                                    <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400 bg-gray-50/30 dark:bg-gray-800/30">
                                         {{ formatCurrency(move.unit_cost) }}
                                     </td>
                                    <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400 bg-gray-50/30 dark:bg-gray-800/30">
                                         {{ formatCurrency(move.wac_after) }}
                                     </td>
                                    <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400 bg-gray-50/30 dark:bg-gray-800/30">
                                         {{ formatCurrency(Number(move.qty) * Number(move.unit_cost)) }}
                                     </td>
 
                                     <!-- Sales Data (Only relevant for Issues/WorkOrders) -->
                                     <template v-if="isSalesMove(move)">
                                         <!-- Price -->
                                        <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400">
                                             {{ formatCurrency(getSalesPrice(move)) }}
                                         </td>
                                         <!-- Discount -->
                                        <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400">
                                             {{ formatCurrency(getSalesDiscount(move)) }}
                                         </td>
                                         <!-- Subtotal -->
                                        <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400">
                                             {{ formatCurrency(getSalesSubtotal(move)) }}
                                         </td>
                                         <!-- VAT -->
                                        <td class="px-4 py-4 text-center font-mono text-gray-600 dark:text-gray-400">
                                             {{ formatCurrency(getSalesTax(move)) }}
                                         </td>
                                         <!-- Total -->
                                        <td class="px-4 py-4 text-center font-mono font-bold text-gray-800 dark:text-white">
                                             {{ formatCurrency(getSalesTotal(move)) }}
                                         </td>
                                     </template>
                                     <template v-else>
                                        <td class="px-4 py-4 text-center text-gray-300">-</td>
                                        <td class="px-4 py-4 text-center text-gray-300">-</td>
                                        <td class="px-4 py-4 text-center text-gray-300">-</td>
                                        <td class="px-4 py-4 text-center text-gray-300">-</td>
                                        <td class="px-4 py-4 text-center text-gray-300">-</td>
                                     </template>
 
                                     <!-- Date -->
                                    <td class="px-4 py-4 text-end text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
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

const { t, locale } = useI18n();
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
    // Force en-US for English numerals
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
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
