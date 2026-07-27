<template>
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ isAr ? 'الملخص المالي' : 'Financial Summary' }}
        </h3>

        <dl class="mt-4 space-y-3 text-sm">
            <!-- Subtotal -->
            <div class="flex items-center justify-between">
                <dt class="text-gray-600 dark:text-gray-400">
                    {{ isAr ? 'المجموع الفرعي' : 'Subtotal' }}
                </dt>
                <dd class="font-mono tabular-nums text-gray-900 dark:text-white">
                    {{ formatMoney(invoice.subtotal) }}
                </dd>
            </div>

            <!-- Discount (only if > 0) -->
            <div v-if="hasDiscount" class="flex items-center justify-between">
                <dt class="text-emerald-600 dark:text-emerald-400">
                    {{ isAr ? 'الخصم' : 'Discount' }}
                </dt>
                <dd class="font-mono tabular-nums text-emerald-600 dark:text-emerald-400">
                    − {{ formatMoney(invoice.discount) }}
                </dd>
            </div>

            <!-- VAT -->
            <div class="flex items-center justify-between">
                <dt class="text-gray-600 dark:text-gray-400">
                    {{ isAr ? 'ضريبة القيمة المضافة' : 'VAT' }}
                    <span class="text-xs text-gray-400">({{ vatRate }}%)</span>
                </dt>
                <dd class="font-mono tabular-nums text-gray-900 dark:text-white">
                    {{ formatMoney(invoice.vat) }}
                </dd>
            </div>

            <!-- Divider -->
            <div class="!mt-4 border-t border-gray-200 pt-3 dark:border-gray-700" />

            <!-- Grand total -->
            <div class="flex items-baseline justify-between">
                <dt class="text-sm font-bold uppercase text-gray-700 dark:text-gray-300">
                    {{ isAr ? 'الإجمالي' : 'Total' }}
                </dt>
                <dd
                    class="font-mono text-2xl font-bold tabular-nums text-indigo-600 dark:text-indigo-400"
                >
                    {{ formatMoney(invoice.total) }}
                </dd>
            </div>

            <!-- Currency badge -->
            <div class="flex items-center justify-end">
                <span
                    class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ currency }}
                </span>
            </div>
        </dl>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    currency: {
        type: String,
        default: 'SAR',
    },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const hasDiscount = computed(() => Number(props.invoice.discount) > 0);

const vatRate = computed(() => {
    // Derive VAT rate from the actual numbers when possible —
    // this is more accurate than reading a separate rate column
    // that may be stale or missing.
    const subtotal = Number(props.invoice.subtotal) || 0;
    const vat = Number(props.invoice.vat) || 0;
    if (subtotal <= 0) return 15;
    return Math.round((vat / subtotal) * 100);
});

function formatMoney(n) {
    return new Intl.NumberFormat(isAr.value ? 'ar-SA-u-nu-latn' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n || 0);
}
</script>
