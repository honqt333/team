<template>
    <SystemLayout>
        <div class="max-w-2xl mx-auto py-4">
            <PageHeader
                :title="$t('system.payment.complete_payment')"
                :subtitle="$t('system.payment.choose_a_payment_method_to_complete_the_subscrip')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Order Summary -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.payment.order_summary') }}
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('system.payment.tenant') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ subscription.tenant?.trade_name }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('system.payment.package') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ subscription.plan?.name_ar }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('system.payment.cycle') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{
                                subscription.billing_cycle === 'yearly'
                                    ? $t('system.payment.yearly')
                                    : $t('common.monthly')
                            }}
                        </span>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex justify-between text-lg">
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $t('system.payment.total') }}
                        </span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">
                            {{ subscription.price + $t('common.currency') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Form Placeholder -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.payment.payment_details') }}
                </h2>

                <!-- Gateway not configured message -->
                <div v-if="!isGatewayConfigured" class="text-center py-8">
                    <svg
                        class="w-16 h-16 mx-auto text-amber-500 mb-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.46 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ $t('system.payment.gateway_not_configured') }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        {{ $t('system.payment.gateway_not_configured_desc') }}
                    </p>
                    <a
                        :href="route('system.settings.payment')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('system.payment.configure_gateway') }}
                    </a>
                </div>

                <!-- Payment form when gateway is configured -->
                <template v-else>
                    <div class="space-y-6">
                        <div v-for="gateway in availableGateways" :key="gateway" class="space-y-4">
                            <label
                                class="flex items-center gap-3 cursor-pointer p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all"
                            >
                                <input
                                    type="radio"
                                    :value="gateway"
                                    v-model="selectedGateway"
                                    class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                />
                                <div class="flex items-center gap-3 flex-1">
                                    <div
                                        :class="[
                                            'w-10 h-10 rounded-xl flex items-center justify-center',
                                            gateway === 'moyasar'
                                                ? 'bg-indigo-50 text-indigo-600'
                                                : gateway === 'tabby'
                                                  ? 'bg-pink-50 text-pink-600'
                                                  : gateway === 'tamara'
                                                    ? 'bg-emerald-50 text-emerald-600'
                                                    : 'bg-gray-100 text-gray-600',
                                        ]"
                                    >
                                        <svg
                                            v-if="gateway === 'tabby'"
                                            class="w-6 h-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-6 h-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ gatewayLabel(gateway) }}
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $t('system.payment.select_this_gateway') }}
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <button
                            @click="proceedToPayment"
                            :disabled="form.processing || !selectedGateway"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/30 transition-all disabled:opacity-50"
                        >
                            <span
                                v-if="form.processing"
                                class="flex items-center justify-center gap-2"
                            >
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span>{{ $t('system.payment.proceeding') }}</span>
                            </span>
                            <span v-else>{{ $t('system.payment.proceed_to_payment') }}</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const { t } = useI18n();

const props = defineProps({
    subscription: { type: Object, required: true },
    isGatewayConfigured: { type: Boolean, required: true },
    availableGateways: { type: Array, required: true },
});

const form = useForm({
    payment_method: '',
});

const selectedGateway = ref('');

const gatewayLabel = (g) => {
    const map = {
        moyasar: 'Moyasar',
        tap: 'Tap',
        paytabs: 'PayTabs',
        tabby: 'Tabby',
        tamara: 'Tamara',
        bank_transfer: t('system.payment.gateway.bank_transfer'),
    };
    return map[g] || g;
};

const proceedToPayment = () => {
    if (!selectedGateway.value) return;
    form.payment_method = selectedGateway.value;
    router.post('/system/payment/checkout', {
        payment_method: selectedGateway.value,
    });
};
</script>
