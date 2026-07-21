<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, required: true },
    enabledGateways: { type: Array, default: () => [] }, // [{ name, configured }]
    paymentMethods: { type: Object, default: () => ({}) }, // { mada_enabled, visa_enabled, ... }
    bankTransferEnabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const GATEWAYS = [
    {
        key: 'moyasar',
        name_ar: 'مُيَسَّر',
        name_en: 'Moyasar',
        description_ar: 'بوابة الدفع السعودية الرائدة',
        description_en: 'Leading Saudi payment gateway',
        color: 'from-blue-500 to-indigo-600',
        methods: ['mada', 'visa', 'mastercard', 'applepay'],
        icon: 'M',
    },
    {
        key: 'tap',
        name_ar: 'تاب',
        name_en: 'Tap',
        description_ar: 'بوابة دفع إقليمية',
        description_en: 'Regional payment gateway',
        color: 'from-purple-500 to-pink-600',
        methods: ['mada', 'visa', 'mastercard', 'applepay'],
        icon: 'T',
    },
    {
        key: 'tabby',
        name_ar: 'تابي',
        name_en: 'Tabby',
        description_ar: 'اشتر الآن وادفع لاحقاً (4 أقساط)',
        description_en: 'Buy now, pay later (4 installments)',
        color: 'from-green-500 to-emerald-600',
        methods: [],
        icon: 'Tb',
    },
    {
        key: 'tamara',
        name_ar: 'تمارا',
        name_en: 'Tamara',
        description_ar: 'تقسيم المبلغ على دفعات',
        description_en: 'Split into installments',
        color: 'from-teal-500 to-cyan-600',
        methods: [],
        icon: 'Tm',
    },
    {
        key: 'bank_transfer',
        name_ar: 'تحويل بنكي',
        name_en: 'Bank Transfer',
        description_ar: 'حوّل المبلغ على الحساب البنكي',
        description_en: 'Wire to our bank account',
        color: 'from-slate-500 to-slate-700',
        methods: [],
        icon: 'B',
    },
];

const METHOD_LABELS = {
    mada: { ar: 'مدى', en: 'mada' },
    visa: { ar: 'فيزا', en: 'Visa' },
    mastercard: { ar: 'ماستركارد', en: 'Mastercard' },
    applepay: { ar: 'Apple Pay', en: 'Apple Pay' },
    stcpay: { ar: 'STC Pay', en: 'STC Pay' },
};

const locale = computed(() => document.documentElement.lang || 'ar');

const availableGateways = computed(() => {
    const enabledNames = props.enabledGateways.map((g) => g.name);
    return GATEWAYS.filter((g) => {
        if (g.key === 'bank_transfer') {
            return props.bankTransferEnabled;
        }
        return enabledNames.includes(g.key);
    });
});

const selectedGateway = computed(() => {
    return GATEWAYS.find((g) => g.key === props.modelValue);
});

function isMethodAvailable(method) {
    return props.paymentMethods?.[`${method}_enabled`];
}

function select(key) {
    emit('update:modelValue', key);
}
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ locale === 'ar' ? 'اختر وسيلة الدفع' : 'Choose payment method' }}
        </p>

        <!-- Gateway list -->
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
                v-for="gw in availableGateways"
                :key="gw.key"
                type="button"
                @click="select(gw.key)"
                :class="[
                    'group relative flex items-center gap-3 rounded-xl border-2 p-3 text-start transition-all',
                    modelValue === gw.key
                        ? 'border-indigo-600 bg-indigo-50/50 dark:border-indigo-400 dark:bg-indigo-900/20'
                        : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-slate-600',
                ]"
            >
                <!-- Logo/Icon -->
                <div
                    :class="[
                        'flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br text-lg font-black text-white shadow-sm',
                        gw.color,
                    ]"
                >
                    {{ gw.icon }}
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ locale === 'ar' ? gw.name_ar : gw.name_en }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                        {{ locale === 'ar' ? gw.description_ar : gw.description_en }}
                    </p>

                    <!-- Payment method badges (only for gateways that support cards) -->
                    <div v-if="gw.methods.length" class="mt-1.5 flex flex-wrap gap-1">
                        <span
                            v-for="m in gw.methods.filter(isMethodAvailable)"
                            :key="m"
                            class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                        >
                            {{ METHOD_LABELS[m]?.[locale === 'ar' ? 'ar' : 'en'] || m }}
                        </span>
                    </div>
                </div>

                <!-- Radio indicator -->
                <div
                    :class="[
                        'flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2 transition-colors',
                        modelValue === gw.key
                            ? 'border-indigo-600 bg-indigo-600 dark:border-indigo-400 dark:bg-indigo-400'
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
            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-center dark:border-amber-800/50 dark:bg-amber-900/10"
        >
            <p class="text-sm text-amber-700 dark:text-amber-300">
                {{
                    locale === 'ar'
                        ? 'لا تتوفر وسائل دفع حالياً. يرجى التواصل مع الإدارة.'
                        : 'No payment methods available. Please contact support.'
                }}
            </p>
        </div>
    </div>
</template>
