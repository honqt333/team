<template>
    <BaseModal :show="show" @close="$emit('close')" size="5xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('quotes.messages.confirm_approve_title') }}</h3>
            </div>
        </template>

        <div class="space-y-6 py-2">
            <!-- Warning Message if out of stock -->
            <div v-if="hasOutOfStock" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-3">
                <svg class="w-6 h-6 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h4 class="text-sm font-bold text-red-800 dark:text-red-300">{{ $t('inventory.stock.insufficient') || 'المخزون غير كافٍ' }}</h4>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">لا يمكن تحويل عرض السعر وتأكيد صرف القطع، يرجى تعديل مصدر القطع غير المتوفرة أو توفيرها في المخزون أولاً.</p>
                </div>
            </div>
            <div v-else class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="text-sm font-bold text-blue-800 dark:text-blue-300">سوف يتم تسجيل القطع التالية للإصدار من المستودع</h4>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">هل أنت متأكد من رغبتك في تحويل عرض السعر إلى كرت صيانة وحجز هذه القطع؟</p>
                </div>
            </div>

            <!-- Table of Warehouse Parts -->
            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase font-black tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-start">#</th>
                            <th class="px-4 py-3 text-start">رقم القطعة / باركود</th>
                            <th class="px-4 py-3 text-start">اسم القطعة</th>
                            <th class="px-4 py-3 text-start">الخدمة</th>
                            <th class="px-4 py-3 text-end">السعر</th>
                            <th class="px-4 py-3 text-end">الخصم</th>
                            <th class="px-4 py-3 text-end text-indigo-600 dark:text-indigo-400">الكمية المطلوبة</th>
                            <th class="px-4 py-3 text-end">المخزون الحالي</th>
                            <th class="px-4 py-3 text-end">المبلغ</th>
                            <th v-if="hasTax" class="px-4 py-3 text-end">VAT</th>
                            <th class="px-4 py-3 text-end">صافي المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <tr v-for="(part, index) in warehouseParts" :key="part.id" :class="{'bg-red-50/50 dark:bg-red-900/10': isOutOfStock(part)}">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500 font-mono">{{ index + 1 }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-mono text-gray-900 dark:text-gray-300">{{ part.part_number || part.part?.sku || part.part?.barcode || '---' }}</td>
                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">{{ part.name }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 truncate max-w-[150px]" :title="getServiceName(part)">{{ getServiceName(part) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-end font-mono text-gray-500">{{ formatCurrency(part.unit_price) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-end font-mono text-red-500">{{ part.discount > 0 ? '-' + formatCurrency(part.discount) : '0.00' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-end font-mono font-black" :class="isOutOfStock(part) ? 'text-red-600 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400'">
                                {{ formatQuantity(part.qty) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-end font-mono font-bold" :class="isOutOfStock(part) ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                                {{ formatQuantity(getStock(part)) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-end font-mono text-gray-500">{{ formatCurrency(getAmount(part)) }}</td>
                            <td v-if="hasTax" class="px-4 py-3 whitespace-nowrap text-end font-mono text-gray-500">{{ formatCurrency(part.tax_amount || 0) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-end font-mono font-black text-gray-900 dark:text-white">{{ formatCurrency(getTotal(part)) }}</td>
                        </tr>
                        <tr v-if="warehouseParts.length === 0">
                            <td :colspan="hasTax ? 11 : 10" class="px-4 py-6 text-center text-gray-500">
                                لا توجد قطع مستودع في هذا التقييم.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <template #footer>
            <div class="flex w-full gap-3">
                <button @click="$emit('close')"
                    class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-black uppercase tracking-widest transition-all">
                    {{ $t('common.cancel') }}
                </button>
                <button @click="$emit('confirm')" :disabled="hasOutOfStock"
                    :class="[
                        'flex-1 px-4 py-3 rounded-xl text-sm font-black uppercase tracking-widest transition-all flex justify-center items-center gap-2',
                        hasOutOfStock 
                            ? 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' 
                            : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 dark:shadow-none'
                    ]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $t('quotes.actions.approve') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed } from 'vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: Boolean,
    quote: Object,
});

const emit = defineEmits(['close', 'confirm']);

const { getName } = useLocalized();
const { formatCurrency, formatQuantity } = useNumberFormat();

// Computed properties
const hasTax = computed(() => props.quote?.tax_enabled_snapshot || warehouseParts.value.some(p => p.tax_amount > 0));

const warehouseParts = computed(() => {
    if (!props.quote?.parts) return [];
    return props.quote.parts.filter(p => p.source === 'warehouse');
});

const hasOutOfStock = computed(() => {
    return warehouseParts.value.some(part => isOutOfStock(part));
});

// Helper functions
function getStock(part) {
    if (!part.part) return 0;
    return Number(part.part.inventory_balances_sum_qty_on_hand || 0);
}

function isOutOfStock(part) {
    return Number(part.qty || 0) > getStock(part);
}

function getServiceName(part) {
    if (part.quote_line) {
        return part.quote_line.description || getName(part.quote_line.service) || 'بدون خدمة';
    }
    if (part.service) {
        return getName(part.service);
    }
    return 'بدون خدمة';
}

function getAmount(part) {
    const isInclusive = props.quote?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.quote?.tax_rate_snapshot || 15);
    const taxFactor = 1 + (taxRate / 100);
    const taxEnabled = !!props.quote?.tax_enabled_snapshot;

    const qty = Number(part.qty || 0);
    const unitPrice = Number(part.unit_price || 0);
    const discount = Number(part.discount || 0);
    const partNet = (qty * unitPrice) - discount;

    if (taxEnabled && isInclusive) {
        return part.total_excl_tax || (partNet / taxFactor);
    }
    return partNet;
}

function getTotal(part) {
    const qty = Number(part.qty || 0);
    const unitPrice = Number(part.unit_price || 0);
    const discount = Number(part.discount || 0);
    const partNet = (qty * unitPrice) - discount;
    
    if (props.quote?.tax_enabled_snapshot && props.quote?.pricing_mode_snapshot !== 'inclusive') {
        const tax = Number(part.tax_amount || (partNet * (Number(props.quote?.tax_rate_snapshot || 15) / 100)));
        return partNet + tax;
    }
    return Number(part.total_incl_tax || part.total || partNet);
}
</script>
