<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, required: true },
    enabledGateways: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
    bankTransferEnabled: { type: [Boolean, Number, String], default: false },
});

const emit = defineEmits(['update:modelValue']);

const GATEWAYS = [
    {
        key: 'moyasar',
        name_ar: 'مُيَسَّر (Moyasar)',
        name_en: 'Moyasar',
        description_ar: 'مدى • فيزا • ماستركارد • Apple Pay',
        description_en: 'mada • Visa • Mastercard • Apple Pay',
        methods: ['mada', 'visa', 'mastercard', 'applepay'],
        icon: 'moyasar',
    },
    {
        key: 'tap',
        name_ar: 'تاب (Tap Payments)',
        name_en: 'Tap Payments',
        description_ar: 'الدفع الإلكتروني السريع والآمن',
        description_en: 'Fast & secure online payments',
        methods: ['mada', 'visa', 'mastercard', 'applepay'],
        icon: 'tap',
    },
    {
        key: 'tabby',
        name_ar: 'تابي (Tabby)',
        name_en: 'Tabby',
        description_ar: 'قسمها على 4 دفعات بدون فوائد أو رسوم',
        description_en: 'Split in 4 interest-free payments',
        badge_ar: '4 دفعات',
        badge_en: '4 Payments',
        methods: [],
        icon: 'tabby',
    },
    {
        key: 'tamara',
        name_ar: 'تمارا (Tamara)',
        name_en: 'Tamara',
        description_ar: 'ادفع على دفعات شهرية ميسرة',
        description_en: 'Split payments into installments',
        badge_ar: 'تقسيط ميسر',
        badge_en: 'Installments',
        methods: [],
        icon: 'tamara',
    },
    {
        key: 'bank_transfer',
        name_ar: 'تحويل بنكي مباشر',
        name_en: 'Bank Transfer',
        description_ar: 'إيداع على حساب المؤسسة البنكي',
        description_en: 'Direct transfer to company account',
        badge_ar: 'تحويل بنكي',
        badge_en: 'Manual Transfer',
        methods: [],
        icon: 'bank',
    },
];

const locale = computed(() => document.documentElement.lang || 'ar');

const availableGateways = computed(() => {
    // 1. Extract gateway names from enabledGateways array (if provided)
    const enabledNames = (props.enabledGateways || [])
        .map((g) => (typeof g === 'string' ? g : g?.name || g?.key))
        .filter(Boolean);

    // 2. Check paymentMethods object properties
    const pm = props.paymentMethods || {};

    const isMoyasarEnabled = enabledNames.includes('moyasar') || Boolean(pm.moyasar?.enabled);
    const isTapEnabled = enabledNames.includes('tap') || Boolean(pm.tap?.enabled);
    const isTabbyEnabled = enabledNames.includes('tabby') || Boolean(pm.tabby?.enabled);
    const isTamaraEnabled = enabledNames.includes('tamara') || Boolean(pm.tamara?.enabled);
    const isBankEnabled =
        enabledNames.includes('bank_transfer') ||
        Boolean(props.bankTransferEnabled) ||
        Boolean(pm.bank_transfer_enabled);

    return GATEWAYS.filter((g) => {
        if (g.key === 'moyasar') return isMoyasarEnabled;
        if (g.key === 'tap') return isTapEnabled;
        if (g.key === 'tabby') return isTabbyEnabled;
        if (g.key === 'tamara') return isTamaraEnabled;
        if (g.key === 'bank_transfer') return isBankEnabled;
        return false;
    });
});

function select(key) {
    emit('update:modelValue', key);
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg
                    class="w-4 h-4 text-action-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                </svg>
                {{ $t('subscription.select_payment_method') }}
            </p>
        </div>

        <!-- Gateway list -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button
                v-for="gw in availableGateways"
                :key="gw.key"
                type="button"
                @click="select(gw.key)"
                :class="[
                    'group relative flex items-center gap-3.5 rounded-2xl border-2 p-3.5 text-start transition-all duration-200 cursor-pointer shadow-sm',
                    modelValue === gw.key
                        ? 'border-action-primary bg-selection-bg shadow-md ring-2 ring-focus-ring'
                        : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700/80 dark:bg-slate-800 dark:hover:border-slate-600',
                ]"
            >
                <!-- Authentic Brand Logo Badge Container -->
                <div
                    class="flex h-12 w-14 flex-shrink-0 items-center justify-center rounded-xl shadow-sm transition-transform duration-200 group-hover:scale-105 overflow-hidden"
                >
                    <!-- Moyasar Brand Logo -->
                    <div
                        v-if="gw.icon === 'moyasar'"
                        class="w-full h-full bg-gradient-to-br from-[#1E52D9] to-[#0D38A8] text-white flex flex-col items-center justify-center p-1 rounded-xl shadow-inner font-extrabold select-none"
                    >
                        <span class="text-[11px] font-black tracking-tighter leading-none">
                            moyasar
                        </span>
                        <span class="text-[7px] text-blue-200 font-medium tracking-widest mt-0.5">
                            {{ $t('system.company_subscription.gateways.moyasar') }}
                        </span>
                    </div>

                    <!-- Tap Payments Brand Logo -->
                    <div
                        v-else-if="gw.icon === 'tap'"
                        class="w-full h-full bg-gradient-to-br from-[#2ACE95] to-[#128082] text-white font-black text-xs tracking-tighter flex items-center justify-center rounded-xl shadow-inner select-none"
                    >
                        <span class="text-sm font-black tracking-tight">tap</span>
                    </div>

                    <!-- Tabby Official Brand Logo Badge -->
                    <div
                        v-else-if="gw.icon === 'tabby'"
                        class="w-full h-full bg-[#3BFFC3] text-black font-black text-xs tracking-tighter flex items-center justify-center rounded-xl shadow-inner border border-black/10 select-none"
                    >
                        <span class="text-sm font-black tracking-tighter font-sans lowercase">
                            tabby
                        </span>
                    </div>

                    <!-- Tamara Official Brand Logo Badge -->
                    <div
                        v-else-if="gw.icon === 'tamara'"
                        class="w-full h-full bg-gradient-to-tr from-[#E8927C] via-[#F4B49F] to-[#E5836C] text-[#2B2320] font-black text-xs tracking-tighter flex items-center justify-center rounded-xl shadow-inner select-none"
                    >
                        <span class="text-sm font-black tracking-tight lowercase">tamara</span>
                    </div>

                    <!-- Bank Transfer Icon Badge -->
                    <div
                        v-else-if="gw.icon === 'bank'"
                        class="w-full h-full bg-gradient-to-br from-slate-700 to-slate-900 text-amber-400 flex items-center justify-center rounded-xl shadow-inner select-none"
                    >
                        <svg
                            class="w-6 h-6 text-amber-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V10m0 0H7m4 0h4m-4 0v11"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Text Details -->
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight">
                            {{ locale === 'ar' ? gw.name_ar : gw.name_en }}
                        </p>
                        <span
                            v-if="gw.badge_ar"
                            class="px-2 py-0.5 text-[9px] font-extrabold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                        >
                            {{ locale === 'ar' ? gw.badge_ar : gw.badge_en }}
                        </span>
                    </div>
                    <p
                        class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug line-clamp-1"
                    >
                        {{ locale === 'ar' ? gw.description_ar : gw.description_en }}
                    </p>

                    <!-- Recognized Brand Logos (Mada, Visa, Mastercard, Apple Pay) -->
                    <div
                        v-if="gw.methods.length"
                        class="flex items-center gap-1.5 pt-0.5 flex-wrap"
                    >
                        <!-- Mada Official Style Logo Badge -->
                        <span
                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-[10px] font-black text-[#0082BA] border border-emerald-200/60 dark:bg-emerald-950/40 dark:text-[#38bdf8] dark:border-emerald-800/40"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-[#00A859]"></span>
                            {{ $t('payments.methods.mada') }}
                        </span>
                        <!-- Visa Logo Badge -->
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-[10px] font-black text-blue-700 border border-blue-200/60 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/40"
                        >
                            VISA
                        </span>
                        <!-- Mastercard Logo Badge -->
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-[10px] font-black text-amber-700 border border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40"
                        >
                            Mastercard
                        </span>
                        <!-- Apple Pay Logo Badge -->
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-black text-slate-800 border border-slate-300/60 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600"
                        >
                            Pay
                        </span>
                    </div>
                </div>

                <!-- Radio Choice Indicator -->
                <div
                    :class="[
                        'flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2 transition-colors',
                        modelValue === gw.key
                            ? 'border-action-primary bg-action-primary'
                            : 'border-slate-300 dark:border-slate-600',
                    ]"
                >
                    <svg
                        v-if="modelValue === gw.key"
                        class="h-3 w-3 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="3"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>
            </button>
        </div>

        <!-- Empty state -->
        <div
            v-if="!availableGateways.length"
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center dark:border-amber-800/50 dark:bg-amber-900/10"
        >
            <p class="text-xs font-bold text-amber-700 dark:text-amber-300">
                {{ $t('subscription.no_payment_methods') }}
            </p>
        </div>
    </div>
</template>
