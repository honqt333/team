<template>
    <span class="inline-flex items-baseline gap-1 whitespace-nowrap" dir="ltr">
        <span v-if="negate">-</span>
        <span>{{ formattedNumber }}</span>
        <template v-if="shouldShowCurrency">
            <svg
                v-if="useSvgGlyph"
                :viewBox="SAR_SVG_VIEWBOX"
                class="inline-block w-[0.9em] h-[0.95em] self-center shrink-0"
                fill="currentColor"
                aria-label="SAR"
            >
                <path v-for="(d, idx) in SAR_SVG_PATHS" :key="idx" :d="d" />
            </svg>
            <span v-else class="text-[0.92em]">{{ currencyText }}</span>
        </template>
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import {
    SAR_SVG_PATHS,
    SAR_SVG_VIEWBOX,
    getCurrencyDecimals,
    getCurrencyGlyph,
    getCurrencyLabel,
    isSvgCurrencyGlyph,
    resolveTenantCurrency,
} from '@/Utils/currency';

const props = defineProps({
    amount: {
        type: [Number, String],
        default: 0,
    },
    currencyCode: {
        type: String,
        default: null,
    },
    visualSettings: {
        type: Object,
        default: null,
    },
    showCurrency: {
        type: Boolean,
        default: true,
    },
    negate: {
        type: Boolean,
        default: false,
    },
});

const { locale } = useI18n();
const page = usePage();

const effectiveVisual = computed(() => {
    return props.visualSettings || page?.props?.tenant?.print_settings?.visual || {};
});

const effectiveCurrency = computed(() => {
    return resolveTenantCurrency(page?.props?.tenant, props.currencyCode);
});

const decimals = computed(() => getCurrencyDecimals(effectiveCurrency.value));

const formattedNumber = computed(() => {
    const num = typeof props.amount === 'string' ? parseFloat(props.amount) : Number(props.amount);
    const safe = Number.isFinite(num) ? num : 0;
    return safe.toLocaleString('en-US', {
        minimumFractionDigits: decimals.value,
        maximumFractionDigits: decimals.value,
    });
});

const shouldShowCurrency = computed(() => {
    if (!props.showCurrency) return false;
    return effectiveVisual.value?.show_currency_symbol !== false;
});

const displayStyle = computed(() => {
    return effectiveVisual.value?.currency_display_style || 'text';
});

const useSvgGlyph = computed(() => {
    return displayStyle.value === 'symbol' && isSvgCurrencyGlyph(effectiveCurrency.value);
});

const currencyText = computed(() => {
    if (displayStyle.value === 'symbol') {
        return getCurrencyGlyph(effectiveCurrency.value);
    }
    return getCurrencyLabel(effectiveCurrency.value, locale.value);
});
</script>
