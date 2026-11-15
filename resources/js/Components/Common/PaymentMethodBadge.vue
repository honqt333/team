<script setup>
import { computed } from 'vue';

const props = defineProps({
    method: {
        type: String,
        required: true,
        validator: (v) =>
            ['mada', 'visa', 'mastercard', 'applepay', 'tabby', 'tamara', 'bank_transfer'].includes(
                v
            ),
    },
    styleMode: {
        type: String,
        default: 'colored',
        validator: (v) => ['colored', 'monochrome'].includes(v),
    },
    customLogoUrl: { type: String, default: null },
    isRtl: { type: Boolean, default: true },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v),
    },
});

const isMono = computed(() => props.styleMode === 'monochrome');

const sizeClasses = computed(() =>
    props.size === 'sm'
        ? 'h-6 w-[38px] shrink-0 justify-center px-1 text-[9px]'
        : 'h-9 min-w-[58px] justify-center px-3 text-xs'
);

const labelMap = computed(() => ({
    mada: props.isRtl ? 'مدى' : 'mada',
    visa: 'VISA',
    mastercard: 'Mastercard',
    applepay: 'Apple Pay',
    tabby: 'tabby',
    tamara: 'tamara',
    bank_transfer: props.isRtl ? 'تحويل بنكي' : 'Bank Transfer',
}));
</script>

<template>
    <!-- Custom uploaded logo override -->
    <span
        v-if="customLogoUrl"
        :class="[
            'inline-flex items-center justify-center rounded-md border bg-white dark:bg-slate-900 shadow-2xs overflow-hidden',
            isMono
                ? 'border-slate-300/80 dark:border-slate-700 grayscale'
                : 'border-slate-200/80 dark:border-slate-700',
            sizeClasses,
        ]"
        :aria-label="labelMap[method]"
    >
        <img
            :src="customLogoUrl"
            :alt="labelMap[method]"
            :class="size === 'sm' ? 'max-h-4 max-w-full' : 'h-5 max-w-[68px]'"
            class="w-auto object-contain"
        />
    </span>

    <!-- Monochrome style -->
    <span
        v-else-if="isMono"
        :class="[
            'inline-flex items-center gap-1 rounded-md border border-slate-300/80 bg-white/95 font-bold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
            sizeClasses,
        ]"
        :aria-label="labelMap[method]"
    >
        <svg
            v-if="method === 'mastercard'"
            class="h-3.5 w-5"
            viewBox="0 0 24 16"
            fill="none"
        >
            <circle cx="9" cy="8" r="6" fill="currentColor" fill-opacity="0.75" />
            <circle cx="15" cy="8" r="6" fill="currentColor" fill-opacity="0.45" />
        </svg>
        <span v-else-if="method === 'visa'" class="font-black italic tracking-tight">VISA</span>
        <span v-else-if="method === 'applepay'" class="font-bold">Pay</span>
        <span v-else-if="method === 'tabby' || method === 'tamara'" class="font-black lowercase">
            {{ labelMap[method] }}
        </span>
        <span v-else>{{ labelMap[method] }}</span>
    </span>

    <!-- Colored official brand style -->
    <span
        v-else-if="method === 'mada'"
        :class="[
            'inline-flex items-center rounded-md border border-slate-200/80 bg-white shadow-2xs dark:border-slate-700 dark:bg-slate-900',
            size === 'sm' ? 'gap-0.5' : 'gap-1.5',
            sizeClasses,
        ]"
        aria-label="mada مدى"
    >
        <svg
            :class="size === 'sm' ? 'h-3 w-3.5 shrink-0' : 'h-5 w-6'"
            viewBox="0 0 28 20"
            fill="none"
        >
            <rect x="1" y="2" width="26" height="6.5" rx="1" fill="#0097D7" />
            <rect x="1" y="11.5" width="26" height="6.5" rx="1" fill="#7AC143" />
        </svg>
        <span class="flex flex-col leading-none text-slate-800 dark:text-slate-100">
            <span :class="size === 'sm' ? 'text-[7px]' : 'text-[10px]'" class="font-black">
                مدى
            </span>
            <span
                :class="size === 'sm' ? 'text-[6.5px]' : 'text-[9px]'"
                class="font-extrabold tracking-tight lowercase"
            >
                mada
            </span>
        </span>
    </span>

    <span
        v-else-if="method === 'visa'"
        :class="[
            'inline-flex items-center rounded-md border border-slate-200/80 bg-white font-black italic tracking-tight text-[#1434CB] shadow-2xs dark:border-slate-700 dark:bg-slate-900 dark:text-[#60A5FA]',
            sizeClasses,
        ]"
    >
        VISA
    </span>

    <span
        v-else-if="method === 'mastercard'"
        :class="[
            'inline-flex items-center rounded-md border border-slate-200/80 bg-white shadow-2xs dark:border-slate-700 dark:bg-slate-900',
            sizeClasses,
        ]"
        aria-label="Mastercard"
    >
        <svg class="h-3.5 w-5" viewBox="0 0 24 16" fill="none">
            <circle cx="9" cy="8" r="6" fill="#EB001B" />
            <circle cx="15" cy="8" r="6" fill="#F79E1B" fill-opacity="0.9" />
        </svg>
    </span>

    <span
        v-else-if="method === 'applepay'"
        :class="[
            'inline-flex items-center rounded-md border border-slate-800 bg-slate-950 font-bold text-white shadow-2xs dark:border-slate-200 dark:bg-white dark:text-slate-950',
            sizeClasses,
        ]"
    >
        Pay
    </span>

    <span
        v-else-if="method === 'tabby'"
        :class="[
            'inline-flex items-center rounded-md border border-black/10 bg-[#3BFFC3] font-black lowercase text-black shadow-2xs',
            sizeClasses,
        ]"
    >
        tabby
    </span>

    <span
        v-else-if="method === 'tamara'"
        :class="[
            'inline-flex items-center rounded-md bg-gradient-to-tr from-[#E8927C] to-[#F4B49F] font-black lowercase text-[#2B2320] shadow-2xs',
            sizeClasses,
        ]"
    >
        tamara
    </span>

    <span
        v-else
        :class="[
            'inline-flex items-center rounded-md border border-slate-200/80 bg-white font-bold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
            sizeClasses,
        ]"
    >
        {{ labelMap.bank_transfer }}
    </span>
</template>
