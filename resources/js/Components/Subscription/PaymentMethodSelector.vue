<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import PaymentMethodBadge from '@/Components/Common/PaymentMethodBadge.vue';

const { t, locale } = useI18n();

const props = defineProps({
    modelValue: { type: String, required: true },
    enabledGateways: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
    paymentIcons: { type: Object, default: () => ({ style: 'colored', logos: {} }) },
    bankTransferEnabled: { type: [Boolean, Number, String], default: false },
    defaultGateway: { type: String, default: '' },
    totalAmount: { type: [Number, String], default: null },
    lockedGateway: { type: String, default: '' },
    lockedReason: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const CARD_GATEWAY_KEYS = ['moyasar', 'tap', 'paytabs'];

const enabledCardMethods = computed(() => {
    const pm = props.paymentMethods || {};
    const list = [];
    if (pm.mada_enabled !== false) list.push('mada');
    if (pm.visa_enabled !== false) list.push('visa');
    if (pm.mastercard_enabled !== false) list.push('mastercard');
    if (pm.applepay_enabled !== false) list.push('applepay');
    return list;
});

const resolvedCardGateway = computed(() => {
    const enabledNames = (props.enabledGateways || [])
        .map((g) => (typeof g === 'string' ? g : g?.name || g?.key))
        .filter(Boolean);
    const pm = props.paymentMethods || {};

    const enabledCards = CARD_GATEWAY_KEYS.filter(
        (key) => enabledNames.includes(key) || Boolean(pm[key]?.enabled)
    );

    if (!enabledCards.length) return null;

    if (props.defaultGateway && enabledCards.includes(props.defaultGateway)) {
        return props.defaultGateway;
    }

    return enabledCards[0];
});

const availableGateways = computed(() => {
    const enabledNames = (props.enabledGateways || [])
        .map((g) => (typeof g === 'string' ? g : g?.name || g?.key))
        .filter(Boolean);
    const pm = props.paymentMethods || {};

    const options = [];

    if (resolvedCardGateway.value) {
        options.push({
            key: resolvedCardGateway.value,
            title: t('subscription.payment_methods.card_title'),
            description: t('subscription.payment_methods.card_desc'),
            methods: enabledCardMethods.value,
            icon: 'card',
        });
    }

    if (enabledNames.includes('tabby') || Boolean(pm.tabby?.enabled)) {
        options.push({
            key: 'tabby',
            title: t('subscription.payment_methods.tabby_title'),
            description: t('subscription.payment_methods.tabby_desc'),
            badge: t('subscription.payment_methods.tabby_badge'),
            methods: [],
            icon: 'tabby',
        });
    }

    if (enabledNames.includes('tamara') || Boolean(pm.tamara?.enabled)) {
        options.push({
            key: 'tamara',
            title: t('subscription.payment_methods.tamara_title'),
            description: t('subscription.payment_methods.tamara_desc'),
            badge: t('subscription.payment_methods.tamara_badge'),
            methods: [],
            icon: 'tamara',
        });
    }

    const bankMinAmount = Number(pm.bank_transfer_min_amount || 0);
    const totalNum =
        props.totalAmount !== null && props.totalAmount !== undefined
            ? Number(props.totalAmount)
            : null;
    const bankBelowMin =
        bankMinAmount > 0 && totalNum !== null && totalNum > 0 && totalNum < bankMinAmount;

    if (
        enabledNames.includes('bank_transfer') ||
        Boolean(props.bankTransferEnabled) ||
        Boolean(pm.bank_transfer_enabled)
    ) {
        options.push({
            key: 'bank_transfer',
            title: t('subscription.payment_methods.bank_title'),
            description: bankBelowMin
                ? t('subscription.receipt.bank_below_min_amount', {
                      amount: bankMinAmount.toFixed(2),
                  })
                : bankMinAmount > 0
                  ? `${t('subscription.payment_methods.bank_desc')} (${t('subscription.receipt.bank_min_amount', { amount: bankMinAmount.toFixed(2) })})`
                  : t('subscription.payment_methods.bank_desc'),
            badge: t('subscription.payment_methods.bank_badge'),
            methods: [],
            icon: 'bank',
            disabled: totalNum === null || bankBelowMin,
        });
    }

    if (props.lockedGateway) {
        return options.map((opt) =>
            opt.key === props.lockedGateway
                ? opt
                : { ...opt, disabled: true, subtitle: props.lockedReason || opt.subtitle }
        );
    }

    return options;
});

watch(
    availableGateways,
    (list) => {
        const current = list.find((g) => g.key === props.modelValue);
        if (current && current.disabled) {
            const fallback = list.find((g) => !g.disabled);
            if (fallback) {
                emit('update:modelValue', fallback.key);
            }
        }
    },
    { immediate: true }
);

function select(gw) {
    if (gw.disabled) return;
    emit('update:modelValue', gw.key);
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
                {{ t('subscription.select_payment_method') }}
            </p>
        </div>

        <!-- Gateway list -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button
                v-for="gw in availableGateways"
                :key="gw.key"
                type="button"
                :disabled="Boolean(gw.disabled)"
                @click="select(gw)"
                :class="[
                    'group relative flex items-center gap-3.5 rounded-2xl border-2 p-3.5 text-start transition-all duration-200 shadow-sm',
                    gw.disabled
                        ? 'cursor-not-allowed opacity-60 border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800/60'
                        : modelValue === gw.key
                          ? 'cursor-pointer border-action-primary bg-selection-bg shadow-md ring-2 ring-focus-ring'
                          : 'cursor-pointer border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700/80 dark:bg-slate-800 dark:hover:border-slate-600',
                ]"
            >
                <!-- Brand / Method Icon -->
                <div
                    class="flex h-12 w-14 flex-shrink-0 items-center justify-center rounded-xl shadow-sm transition-transform duration-200 group-hover:scale-105 overflow-hidden"
                >
                    <!-- Unified Card Payment Icon -->
                    <div
                        v-if="gw.icon === 'card'"
                        class="w-full h-full bg-gradient-to-br from-slate-800 to-slate-950 text-white flex items-center justify-center rounded-xl shadow-inner select-none"
                    >
                        <svg
                            class="w-6 h-6 text-action-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"
                            />
                        </svg>
                    </div>

                    <!-- Tabby Brand Badge -->
                    <div
                        v-else-if="gw.icon === 'tabby'"
                        class="w-full h-full bg-[#3BFFC3] text-black font-black text-xs tracking-tighter flex items-center justify-center rounded-xl shadow-inner border border-black/10 select-none"
                    >
                        <span class="text-sm font-black tracking-tighter font-sans lowercase">
                            tabby
                        </span>
                    </div>

                    <!-- Tamara Brand Badge -->
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
                            {{ gw.title }}
                        </p>
                        <span
                            v-if="gw.badge"
                            class="px-2 py-0.5 text-[9px] font-extrabold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 whitespace-nowrap"
                        >
                            {{ gw.badge }}
                        </span>
                    </div>
                    <p
                        class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug line-clamp-2"
                    >
                        {{ gw.description }}
                    </p>

                    <!-- Recognized Card Brand Icons (mada, Visa, Mastercard, Apple Pay) -->
                    <div
                        v-if="gw.methods.length"
                        class="flex items-center gap-1 pt-1 flex-nowrap"
                    >
                        <PaymentMethodBadge
                            v-for="m in gw.methods"
                            :key="m"
                            :method="m"
                            size="sm"
                            :style-mode="paymentIcons?.style || 'colored'"
                            :custom-logo-url="paymentIcons?.logos?.[m] || null"
                            :is-rtl="locale === 'ar'"
                        />
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
                {{ t('subscription.no_payment_methods') }}
            </p>
        </div>
    </div>
</template>
