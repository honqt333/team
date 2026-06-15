<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg" :overflow-visible="true" scroll-entire>
        <template #title>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-500 to-rose-500 flex items-center justify-center shadow-lg shadow-red-500/20 transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {{ $t('quotes.parts.return_to_warehouse') }}
                    </h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 capitalize">
                            {{ part ? part.name : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Part Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('inventory.parts.name') }}
                    </label>
                    <input type="text" :value="part?.name || ''" disabled
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 rounded-xl text-sm font-bold cursor-not-allowed" />
                </div>

                <!-- Part Number -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('inventory.parts.part_number') }}
                    </label>
                    <input type="text" :value="part?.part_number || ''" disabled
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 rounded-xl text-sm font-bold cursor-not-allowed" />
                </div>

                <!-- Description / Notes -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('inventory.parts.description') }}
                    </label>
                    <input type="text" :value="part?.notes || ''" disabled
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 rounded-xl text-sm font-bold cursor-not-allowed" />
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Issued Qty -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-150 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                        {{ $t('inventory.parts.issued_qty') }}
                    </p>
                    <p class="text-lg font-mono font-black text-gray-700 dark:text-gray-300">
                        {{ formatQuantity(part?.issued_qty || part?.qty || 0) }}
                    </p>
                </div>

                <!-- Returned Qty -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-150 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                        {{ $t('inventory.parts.returned_qty') }}
                    </p>
                    <p class="text-lg font-mono font-black text-red-500">
                        {{ formatQuantity(part?.returned_qty || 0) }}
                    </p>
                </div>

                <!-- Current Qty on Card -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-150 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                        {{ $t('inventory.parts.current_card_qty') || 'الكمية الحالية بالكرت' }}
                    </p>
                    <p class="text-lg font-mono font-black text-indigo-600 dark:text-indigo-400">
                        {{ formatQuantity(part?.qty || 0) }}
                    </p>
                </div>

                <!-- Current Stock -->
                <div class="p-4 rounded-2xl border bg-gray-50 dark:bg-gray-900/50 border-gray-150 dark:border-gray-800 text-gray-700 dark:text-gray-300">
                    <p class="text-[10px] font-black opacity-60 uppercase tracking-widest mb-1">
                        {{ $t('inventory.stock.available') }}
                    </p>
                    <p class="text-lg font-mono font-black">
                        {{ loadingStock ? '...' : formatQuantity(currentStock) }}
                    </p>
                </div>
            </div>

            <!-- Main Input: Returned Qty -->
            <div class="bg-red-500/5 dark:bg-red-500/10 border-2 border-red-500/20 rounded-2xl p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-extrabold text-red-700 dark:text-red-400">
                        {{ $t('inventory.parts.returned_qty') }} ...
                    </label>
                    <span v-if="parseFloat(returnedQty) === parseFloat(part?.qty)" class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        ⚠️ {{ $t('inventory.parts.full_return_will_remove') || 'سيتم إزالة قطعة الغيار بالكامل من كرت العمل عند إرجاع كامل الكمية' }}
                    </span>
                    <span v-else-if="parseFloat(returnedQty) > parseFloat(part?.qty)" class="text-xs text-red-500 font-bold">
                        ⚠️ {{ $t('inventory.parts.cannot_return_more_than_current') || 'لا يمكن إرجاع كمية أكبر من الكمية الحالية بالكرت' }}
                    </span>
                </div>
                <input 
                    type="text" 
                    inputmode="decimal" 
                    v-model="returnedQty"
                    dir="ltr"
                    @input="returnedQty = toEnglish($event.target.value).replace(/[^0-9.]/g, '')"
                    class="w-full px-5 py-4 border-2 border-red-300 focus:border-red-500 dark:border-red-700 dark:focus:border-red-500 focus:ring-4 focus:ring-red-500/10 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-2xl font-mono text-center text-xl font-extrabold transition-all"
                    placeholder="0"
                />
            </div>

            <!-- Locked Pricing Display -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                <!-- Price -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.unit_price') }}</label>
                    <input type="text" readonly :value="formatCurrency(part?.unit_price || 0)" dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-center font-mono text-sm font-bold cursor-not-allowed rounded-xl" />
                </div>

                <!-- Discount -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.discount') }}</label>
                    <input type="text" readonly :value="formatCurrency(part?.discount || 0)" dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-center font-mono text-sm font-bold cursor-not-allowed rounded-xl" />
                </div>

                <!-- Total Qty (Calculated) -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $t('common.qty') }}</label>
                    <input type="text" readonly :value="formatQuantity(calculatedQty)" dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-red-650 dark:text-red-400 text-center font-mono text-sm font-black cursor-not-allowed rounded-xl" />
                </div>

                <!-- Amount (Calculated Subtotal) -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $t('common.amount') }}</label>
                    <input type="text" readonly :value="formatCurrency(calculatedAmount)" dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-center font-mono text-sm font-bold cursor-not-allowed rounded-xl" />
                </div>
            </div>

            <!-- Grand Total Summary Box -->
            <div class="p-4 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="space-y-0.5">
                    <span class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        {{ $t('common.total') }}
                    </span>
                    <p v-if="vatAmount > 0" class="text-[10px] font-bold text-gray-400">
                        + {{ formatCurrency(vatAmount) }} {{ $t('common.vat') }}
                    </p>
                </div>
                <div class="flex items-baseline gap-1 font-mono">
                    <span class="text-2xl font-black text-red-600 dark:text-red-400">
                        {{ formatCurrency(grandTotalValue) }}
                    </span>
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase">
                        {{ $t('common.currency') }}
                    </span>
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button type="button" @click="$emit('close')"
                    class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-red-500 transition-all uppercase tracking-widest">
                    {{ $t('common.cancel') }}
                </button>
                <button type="button" @click="submitForm" :disabled="form.processing || !returnedQty || isNaN(parseFloat(returnedQty)) || parseFloat(returnedQty) <= 0 || parseFloat(returnedQty) > parseFloat(part?.qty)"
                    class="px-10 py-3 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black uppercase tracking-widest text-xs transition-all shadow-lg shadow-red-500/20 disabled:opacity-50">
                    {{ form.processing ? $t('common.loading') : $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    part: Object,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();

const currentStock = ref(0);
const loadingStock = ref(false);
const returnedQty = ref('0');

const form = useForm({
    work_order_item_id: null,
    part_id: null,
    warehouse_id: null,
    name: '',
    part_number: '',
    source: 'warehouse',
    unit_id: null,
    qty: 0,
    unit_price: 0,
    discount: 0,
    include_in_package: false,
    hide_on_print: false,
    notes: '',
});

// Watch show and part to reset returned quantity and populate form
watch([() => props.show, () => props.part], ([newShow, newPart]) => {
    if (newShow && newPart) {
        returnedQty.value = '0';
        currentStock.value = 0;
        
        // Populate form fields
        form.work_order_item_id = newPart.work_order_item_id;
        form.part_id = newPart.part_id;
        form.warehouse_id = newPart.warehouse_id;
        form.name = newPart.name;
        form.part_number = newPart.part_number;
        form.source = newPart.source;
        form.unit_id = newPart.unit_id;
        form.unit_price = newPart.unit_price;
        form.discount = newPart.discount;
        form.include_in_package = newPart.include_in_package;
        form.hide_on_print = newPart.hide_on_print;
        form.notes = newPart.notes;
        form.qty = newPart.qty;

        fetchStock();
    }
}, { immediate: true, deep: true });

async function fetchStock() {
    const partId = form.part_id || props.part?.part_id;
    const warehouseId = form.warehouse_id || props.part?.warehouse_id;
    if (!partId || !warehouseId) {
        console.warn('Cannot fetch stock: partId or warehouseId is missing', { partId, warehouseId });
        return;
    }
    loadingStock.value = true;
    try {
        const response = await axios.get(route('app.inventory.stock.part', { part: partId }), {
            params: { warehouse_id: warehouseId }
        });
        currentStock.value = response.data.qty_on_hand || 0;
    } catch (e) {
        console.error('Error fetching part stock:', e);
    } finally {
        loadingStock.value = false;
    }
}

const calculatedQty = computed(() => {
    const originalQty = parseFloat(props.part?.qty) || 0;
    const returnQty = parseFloat(returnedQty.value) || 0;
    return Math.max(0, originalQty - returnQty);
});

const calculatedAmount = computed(() => {
    const price = parseFloat(props.part?.unit_price) || 0;
    return calculatedQty.value * price;
});

const calculatedTotal = computed(() => {
    const discount = parseFloat(props.part?.discount) || 0;
    return Math.max(0, calculatedAmount.value - discount);
});

const vatAmount = computed(() => {
    if (!props.workOrder?.tax_enabled_snapshot) return 0;
    const isInclusive = props.workOrder.pricing_mode_snapshot === 'inclusive';
    const rate = props.workOrder.tax_rate_snapshot || 15;
    const netAmount = calculatedTotal.value;
    
    if (isInclusive) {
        return netAmount - (netAmount / (1 + (rate / 100)));
    } else {
        return netAmount * (rate / 100);
    }
});

const grandTotalValue = computed(() => {
    const netAmount = calculatedTotal.value;
    if (!props.workOrder?.tax_enabled_snapshot || props.workOrder.pricing_mode_snapshot === 'inclusive') {
        return netAmount;
    }
    return netAmount + vatAmount.value;
});

function submitForm() {
    const returnQtyVal = parseFloat(returnedQty.value) || 0;
    const issuedQtyVal = parseFloat(props.part?.qty) || 0;

    if (returnQtyVal <= 0 || returnQtyVal > issuedQtyVal) {
        return;
    }

    form.clearErrors();

    if (returnQtyVal === issuedQtyVal) {
        form.delete(route('work-orders.parts.destroy', { workOrderPart: props.part.id }), {
            onSuccess: (page) => {
                emit('saved', {
                    ...props.part,
                    qty: 0,
                    status: 'cancelled',
                    total: 0,
                    grand_total: 0
                }, { close: true });
            }
        });
    } else {
        form.qty = calculatedQty.value;
        form.put(route('work-orders.parts.update', { workOrderPart: props.part.id }), {
            onSuccess: (page) => {
                emit('saved', {
                    ...props.part,
                    qty: form.qty,
                    total: calculatedTotal.value,
                    grand_total: grandTotalValue.value
                }, { close: true });
            }
        });
    }
}
</script>
