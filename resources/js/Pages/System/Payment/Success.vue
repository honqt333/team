<template>
    <SystemLayout>
        <div class="max-w-xl mx-auto py-6 text-center">
            <PageHeader :title="statusConfig.title" :subtitle="statusConfig.subtitle">
                <template #icon>
                    <div
                        :class="[
                            'w-12 h-12 rounded-full flex items-center justify-center',
                            statusConfig.iconBgClass,
                        ]"
                    >
                        <svg
                            v-if="statusConfig.tone === 'success'"
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <svg
                            v-else-if="statusConfig.tone === 'danger'"
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
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
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </template>
            </PageHeader>

            <!-- Status Message -->
            <div class="space-y-4 text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ statusConfig.title }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ statusConfig.message }}
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
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('system.payment.status_label') }}
                        </span>
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                statusConfig.badgeClass,
                            ]"
                        >
                            {{ statusConfig.badgeLabel }}
                        </span>
                    </div>
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
                        <span :class="['font-medium font-mono', statusConfig.amountClass]">
                            {{ payment.amount }} {{ payment.currency }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $t('customers.statement.method') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ getPaymentMethodLabel(payment.payment_method || payment.gateway) }}
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
                    v-if="subscriptionId"
                    :href="route('system.subscriptions.show', subscriptionId)"
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
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useFormatters } from '@/Composables/useFormatters';

const { t } = useI18n();
const { formatDateTime: formatDate } = useFormatters();

const props = defineProps({
    payment: { type: Object, required: true },
    subscription: { type: Object, default: null },
    isConfirmedPaid: { type: Boolean, default: false },
});

const subscriptionId = computed(
    () => props.subscription?.id ?? props.payment?.subscription?.id ?? props.payment?.subscription_id ?? null
);

const statusConfig = computed(() => {
    const status = props.payment?.status;

    if (props.isConfirmedPaid && status === 'paid') {
        return {
            tone: 'success',
            title: t('system.payment.success_title'),
            subtitle: t('system.payment.success_subtitle'),
            message: t('system.payment.success_message'),
            badgeLabel: t('system.payment.statuses.paid'),
            badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
            iconBgClass: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
            amountClass: 'text-emerald-600 dark:text-emerald-400',
        };
    }

    if (status === 'pending') {
        return {
            tone: 'warning',
            title: t('system.payment.status_pending_title'),
            subtitle: t('system.payment.status_pending_subtitle'),
            message: t('system.payment.status_pending_message'),
            badgeLabel: t('system.payment.statuses.pending'),
            badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            iconBgClass: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
            amountClass: 'text-gray-900 dark:text-white',
        };
    }

    if (status === 'initiated') {
        return {
            tone: 'warning',
            title: t('system.payment.status_initiated_title'),
            subtitle: t('system.payment.status_initiated_subtitle'),
            message: t('system.payment.status_initiated_message'),
            badgeLabel: t('system.payment.statuses.initiated'),
            badgeClass: 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
            iconBgClass: 'bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400',
            amountClass: 'text-gray-900 dark:text-white',
        };
    }

    if (status === 'failed') {
        return {
            tone: 'danger',
            title: t('system.payment.failed_title'),
            subtitle: t('system.payment.failed_subtitle'),
            message: t('system.payment.failed_message'),
            badgeLabel: t('system.payment.statuses.failed'),
            badgeClass: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            iconBgClass: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
            amountClass: 'text-red-600 dark:text-red-400',
        };
    }

    if (status === 'cancelled') {
        return {
            tone: 'neutral',
            title: t('system.payment.status_cancelled_title'),
            subtitle: t('system.payment.status_cancelled_subtitle'),
            message: t('system.payment.status_cancelled_message'),
            badgeLabel: t('system.payment.statuses.cancelled'),
            badgeClass: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
            iconBgClass: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
            amountClass: 'text-gray-900 dark:text-white',
        };
    }

    if (status === 'refunded') {
        return {
            tone: 'neutral',
            title: t('system.payment.status_refunded_title'),
            subtitle: t('system.payment.status_refunded_subtitle'),
            message: t('system.payment.status_refunded_message'),
            badgeLabel: t('system.payment.statuses.refunded'),
            badgeClass: 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
            iconBgClass: 'bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400',
            amountClass: 'text-gray-900 dark:text-white',
        };
    }

    return {
        tone: 'warning',
        title: t('system.payment.status_unconfirmed_title'),
        subtitle: t('system.payment.status_unconfirmed_subtitle'),
        message: t('system.payment.status_unconfirmed_message'),
        badgeLabel: t('system.payment.statuses.unknown'),
        badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        iconBgClass: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        amountClass: 'text-gray-900 dark:text-white',
    };
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
    return map[method] || method || '-';
};
</script>
