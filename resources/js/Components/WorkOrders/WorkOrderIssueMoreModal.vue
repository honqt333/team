<template>
    <BaseModal
        :show="show"
        @close="$emit('close')"
        size="lg"
        :overflow-visible="true"
        scroll-entire
    >
        <template #title>
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center shadow-lg shadow-focus-ring-soft transform -rotate-3 hover:rotate-0 transition-transform duration-300"
                >
                    <svg
                        class="w-6 h-6 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {{ $t('inventory.parts.issue_more') }}
                    </h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span
                            class="inline-flex w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"
                        ></span>
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 capitalize"
                        >
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
                    <label
                        class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('inventory.parts.name') }}
                    </label>
                    <AppInput
                        type="text"
                        :model-value="part?.name || ''"
                        disabled
                        class="font-bold cursor-not-allowed"
                    />
                </div>

                <!-- Part Number -->
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('inventory.parts.part_number') }}
                    </label>
                    <AppInput
                        type="text"
                        :model-value="part?.part_number || ''"
                        disabled
                        class="font-bold cursor-not-allowed"
                    />
                </div>

                <!-- Description / Notes -->
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('inventory.parts.description') }}
                    </label>
                    <AppInput
                        type="text"
                        :model-value="part?.notes || ''"
                        disabled
                        class="font-bold cursor-not-allowed"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Issued Qty -->
                <div
                    class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-800"
                >
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                        {{ $t('inventory.parts.issued_qty') }}
                    </p>
                    <p class="text-lg font-mono font-black text-gray-700 dark:text-gray-300">
                        {{ formatQuantity(part?.issued_qty || part?.qty || 0) }}
                    </p>
                </div>

                <!-- Returned Qty -->
                <div
                    class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-800"
                >
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                        {{ $t('inventory.parts.returned_qty') }}
                    </p>
                    <p class="text-lg font-mono font-black text-red-500">
                        {{ formatQuantity(part?.returned_qty || 0) }}
                    </p>
                </div>

                <!-- Current Qty on Card -->
                <div
                    class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-800"
                >
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                        {{ $t('inventory.parts.current_card_qty') }}
                    </p>
                    <p class="text-lg font-mono font-black text-action-primary">
                        {{ formatQuantity(part?.qty || 0) }}
                    </p>
                </div>

                <!-- Current Stock -->
                <div
                    class="p-4 rounded-2xl border transition-colors duration-300"
                    :class="
                        currentStock > 0
                            ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                            : 'bg-red-500/5 border-red-500/20 text-red-600 dark:text-red-400'
                    "
                >
                    <p class="text-[10px] font-black opacity-60 uppercase tracking-widest mb-1">
                        {{ $t('inventory.stock.available') }}
                    </p>
                    <p class="text-lg font-mono font-black">
                        {{ loadingStock ? '...' : formatQuantity(currentStock) }}
                    </p>
                </div>
            </div>

            <!-- Main Input: Issue More Qty -->
            <div
                class="bg-action-primary/10 border-2 border-action-primary rounded-2xl p-5 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-extrabold text-selection-text">
                        {{ $t('inventory.parts.issue_more_qty') }}
                    </label>
                    <span
                        v-if="parseFloat(additionalQty) > currentStock"
                        class="text-xs text-red-500 font-bold"
                    >
                        ⚠️ {{ $t('inventory.parts.insufficient_short') }}
                    </span>
                </div>
                <AppInput
                    type="text"
                    inputmode="decimal"
                    v-model="additionalQty"
                    dir="ltr"
                    @input="additionalQty = toEnglish($event.target.value).replace(/[^0-9.]/g, '')"
                    class="py-4 font-mono text-center text-xl font-extrabold"
                    placeholder="0"
                />
            </div>

            <!-- Locked Pricing Display -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                <!-- Price -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.parts.unit_price') }}
                    </label>
                    <AppInput
                        type="text"
                        readonly
                        :model-value="formatCurrency(part?.unit_price || 0)"
                        dir="ltr"
                        class="text-center font-mono font-bold cursor-not-allowed"
                    />
                </div>

                <!-- Discount -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.parts.discount') }}
                    </label>
                    <AppInput
                        type="text"
                        readonly
                        :model-value="formatCurrency(part?.discount || 0)"
                        dir="ltr"
                        class="text-center font-mono font-bold cursor-not-allowed"
                    />
                </div>

                <!-- Total Qty (Calculated) -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ $t('common.qty') }}
                    </label>
                    <AppInput
                        type="text"
                        readonly
                        :model-value="formatQuantity(calculatedQty)"
                        dir="ltr"
                        class="text-action-primary text-center font-mono font-black cursor-not-allowed"
                    />
                </div>

                <!-- Amount (Calculated Subtotal) -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ $t('common.amount') }}
                    </label>
                    <AppInput
                        type="text"
                        readonly
                        :model-value="formatCurrency(calculatedAmount)"
                        dir="ltr"
                        class="text-center font-mono font-bold cursor-not-allowed"
                    />
                </div>
            </div>

            <!-- Grand Total Summary Box -->
            <div
                class="p-4 bg-selection-bg border border-action-primary/25 rounded-2xl flex items-center justify-between shadow-sm"
            >
                <div class="space-y-0.5">
                    <span
                        class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                    >
                        {{ $t('common.total') }}
                    </span>
                    <p v-if="vatAmount > 0" class="text-[10px] font-bold text-gray-400">
                        + {{ formatCurrency(vatAmount) }} {{ $t('common.vat') }}
                    </p>
                </div>
                <div class="flex items-baseline gap-1 font-mono">
                    <span class="text-2xl font-black text-action-primary">
                        {{ formatCurrency(grandTotalValue) }}
                    </span>
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase">
                        {{ activeCurrencySymbol }}
                    </span>
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton type="button" variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="button"
                    variant="primary"
                    @click="submitForm"
                    :loading="form.processing"
                    :disabled="
                        form.processing ||
                        parseFloat(additionalQty) <= 0 ||
                        parseFloat(additionalQty) > currentStock
                    "
                >
                    {{ form.processing ? $t('common.loading') : $t('common.save') }}
                </AppButton>
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
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    part: Object,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { formatCurrency, formatQuantity, toEnglish, activeCurrencySymbol } = useNumberFormat();

const currentStock = ref(0);
const loadingStock = ref(false);
const additionalQty = ref('0');

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

// Watch show and part to reset additional quantity and populate form
watch(
    [() => props.show, () => props.part],
    ([newShow, newPart]) => {
        if (newShow && newPart) {
            additionalQty.value = '0';
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
    },
    { immediate: true, deep: true }
);

async function fetchStock() {
    const partId = form.part_id || props.part?.part_id;
    const warehouseId = form.warehouse_id || props.part?.warehouse_id;
    if (!partId || !warehouseId) {
        console.warn('Cannot fetch stock: partId or warehouseId is missing', {
            partId,
            warehouseId,
        });
        return;
    }
    loadingStock.value = true;
    try {
        const response = await axios.get(route('app.inventory.stock.part', { part: partId }), {
            params: { warehouse_id: warehouseId },
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
    const moreQty = parseFloat(additionalQty.value) || 0;
    return originalQty + moreQty;
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
        return netAmount - netAmount / (1 + rate / 100);
    } else {
        return netAmount * (rate / 100);
    }
});

const grandTotalValue = computed(() => {
    const netAmount = calculatedTotal.value;
    if (
        !props.workOrder?.tax_enabled_snapshot ||
        props.workOrder.pricing_mode_snapshot === 'inclusive'
    ) {
        return netAmount;
    }
    return netAmount + vatAmount.value;
});

function submitForm() {
    form.qty = calculatedQty.value;
    form.clearErrors();

    form.put(route('work-orders.parts.update', { workOrderPart: props.part.id }), {
        onSuccess: (page) => {
            emit(
                'saved',
                {
                    ...props.part,
                    qty: form.qty,
                    total: calculatedTotal.value,
                    grand_total: grandTotalValue.value,
                },
                { close: true }
            );
        },
    });
}
</script>
