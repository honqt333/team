<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    gateways: { type: Array, default: () => [] },
    paymentSettings: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div
        class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
    >
        <div v-if="gateways.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
                v-for="gw in gateways"
                :key="gw.name"
                class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 hover:border-indigo-200 dark:hover:border-indigo-700 transition-colors"
            >
                <div
                    class="w-10 h-10 rounded-lg flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex-shrink-0"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                        {{ gw.label || gw.name }}
                    </p>
                    <p
                        class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ t('system.billing.page.payment_method_active') }}
                    </p>
                </div>
            </div>
        </div>

        <p v-else class="text-sm text-slate-500 dark:text-slate-400 italic text-center py-4">
            {{ t('system.billing.page.payment_method_none') }}
        </p>

        <!-- Subscription VAT (read-only display, applies to all subscriptions) -->
        <div
            v-if="paymentSettings?.subscription_vat_enabled"
            class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700"
        >
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
                {{ t('system.billing.page.vat_on_subscriptions') }}:
            </p>
            <div class="flex items-center gap-3 text-sm">
                <span class="font-mono font-semibold text-slate-900 dark:text-white">
                    {{ paymentSettings.subscription_vat_rate }}%
                </span>
                <span class="text-slate-500 dark:text-slate-400">·</span>
                <span class="text-slate-600 dark:text-slate-300">
                    {{
                        paymentSettings.subscription_vat_inclusive
                            ? t('system.billing.page.vat_inclusive')
                            : t('system.billing.page.vat_exclusive')
                    }}
                </span>
            </div>
        </div>
    </div>
</template>
