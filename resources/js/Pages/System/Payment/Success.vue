<template>
    <SystemLayout>
        <div class="max-w-xl mx-auto py-6 text-center">
            <PageHeader
                :title="$t('system.payment.success_title')"
                :subtitle="$t('system.payment.success_subtitle')"
            >
                <template #icon>
                    <div
                        class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                </template>
            </PageHeader>

            <!-- Success Message -->
            <div class="space-y-4 text-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $t('system.payment.success_title') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t('system.payment.success_message') }}
                </p>
            </div>

            <!-- Payment Details -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 text-start mb-8"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('payments.form.details') }}
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('system.payment.transaction_number') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white font-mono">
                            {{ payment.gateway_payment_id || payment.id }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('common.amount') }}
                        </span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">
                            {{ payment.amount }} {{ payment.currency }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('customers.statement.method') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ getPaymentMethodLabel(payment.payment_method) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('system.payment.transaction_date') }}
                        </span>
                        <span class="font-mono text-gray-900 dark:text-white">
                            {{ formatDate(payment.created_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a
                    :href="route('system.subscriptions.show', subscription)"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-action-primary hover:bg-action-primary-hover text-white font-semibold rounded-xl transition-colors"
                >
                    {{ $t('system.payment.view_subscription') }}
                </a>
                <a
                    :href="route('system.subscriptions.index')"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    {{ $t('system.payment.view_all_subscriptions') }}
                </a>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useFormatters } from '@/Composables/useFormatters';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { formatDateTime: formatDate } = useFormatters();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    payment: { type: Object, required: true },
    subscription: { type: Object, required: true },
});

const getPaymentMethodLabel = (method) => {
    const map = {
        moyasar: 'Moyasar',
        tap: 'Tap',
        paytabs: 'PayTabs',
        tabby: 'Tabby',
        tamara: 'Tamara',
        bank_transfer: 'Bank Transfer',
    };
    return map[method] || method;
};
</script>
