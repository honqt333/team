<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <!-- Header -->
            <PageHeader
                :title="$t('system.subscriptions.subscription_details')"
                :subtitle="subscription.tenant?.trade_name || subscription.tenant?.name || ''"
            >
                <template #back>
                    <Link
                        href="/system/subscriptions"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/60 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                        :title="$t('system.subscriptions.title')"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <span
                        :class="getStatusClass(subscription.status)"
                        class="px-3 py-1.5 rounded-full text-sm font-medium"
                    >
                        {{ getStatusLabel(subscription.status) }}
                    </span>
                </template>
            </PageHeader>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Subscription Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Plan & Billing -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.subscriptions.subscription_information') }}
                        </h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.plan_label') }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ subscription.plan?.name_ar }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.billing_cycle') }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{
                                        subscription.billing_cycle === 'yearly'
                                            ? $t('system.subscriptions.yearly')
                                            : $t('system.subscriptions.monthly')
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.price') }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{
                                        Number(subscription.price) > 0
                                            ? subscription.price +
                                              $t('system.subscriptions.expression_literal')
                                            : subscription.billing_cycle === 'yearly'
                                              ? (subscription.plan?.price_yearly ?? 0) +
                                                $t('system.subscriptions.expression_literal')
                                              : (subscription.plan?.price_monthly ?? 0) +
                                                $t('system.subscriptions.expression_literal')
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.auto-renewal') }}
                                </p>
                                <p
                                    class="font-medium"
                                    :class="
                                        subscription.auto_renew
                                            ? 'text-emerald-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        subscription.auto_renew
                                            ? $t('common.enabled')
                                            : $t('system.subscriptions.disabled')
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.subscriptions.dates') }}
                        </h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.period_start') }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ formatDate(subscription.starts_at) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.period_end') }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ formatDate(subscription.ends_at) }}
                                </p>
                            </div>
                            <div v-if="subscription.trial_ends_at">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.trial_end') }}
                                </p>
                                <p class="font-medium text-amber-600">
                                    {{ formatDate(subscription.trial_ends_at) }}
                                </p>
                            </div>
                            <div v-if="subscription.cancelled_at">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.cancellation_date') }}
                                </p>
                                <p class="font-medium text-red-600">
                                    {{ formatDate(subscription.cancelled_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.subscriptions.invoices') }}
                        </h2>
                        <div v-if="subscription.invoices?.length" class="space-y-3">
                            <div
                                v-for="invoice in subscription.invoices"
                                :key="invoice.id"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ invoice.invoice_number }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(invoice.created_at) }}
                                    </p>
                                </div>
                                <div class="text-end">
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        {{
                                            invoice.total +
                                            $t('system.subscriptions.create_modal.currency')
                                        }}
                                    </p>
                                    <span
                                        :class="
                                            invoice.status === 'paid'
                                                ? 'text-emerald-600'
                                                : 'text-amber-600'
                                        "
                                        class="text-sm"
                                    >
                                        {{
                                            invoice.status === 'paid'
                                                ? $t('common.status_paid')
                                                : $t('system.subscriptions.pending')
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                            {{ $t('system.subscriptions.no_invoices') }}
                        </p>
                    </div>
                </div>

                <!-- Sidebar Actions -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('common.actions') }}
                        </h2>
                        <div class="space-y-3">
                            <button
                                v-if="
                                    subscription.status === 'active' ||
                                    subscription.status === 'trial'
                                "
                                @click="cancelSubscription"
                                class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-sm font-medium"
                            >
                                {{ $t('system.subscriptions.cancel_subscription') }}
                            </button>
                            <button
                                v-if="subscription.status === 'cancelled'"
                                @click="activateSubscription"
                                class="w-full px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg text-sm font-medium"
                            >
                                {{ $t('system.subscriptions.activate_subscription') }}
                            </button>
                            <button
                                @click="showExtendModal = true"
                                class="w-full px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg text-sm font-medium"
                            >
                                {{ $t('system.subscriptions.extend_subscription') }}
                            </button>
                        </div>
                    </div>

                    <!-- Tenant Info -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.subscriptions.create_modal.tenant_label') }}
                        </h2>
                        <div class="space-y-2">
                            <p class="text-gray-900 dark:text-white font-medium">
                                {{ subscription.tenant?.trade_name || subscription.tenant?.name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                #{{ subscription.tenant?.id }}
                            </p>
                            <Link
                                :href="`/system/tenants/${subscription.tenant?.id}`"
                                class="text-indigo-600 hover:text-indigo-700 text-sm"
                            >
                                {{ $t('system.subscriptions.view_tenant_details') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div
                        v-if="subscription.promo_code"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700"
                    >
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.subscriptions.discount_code') }}
                        </h2>
                        <span
                            class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded"
                        >
                            {{ subscription.promo_code }}
                        </span>
                        <p
                            v-if="subscription.discount_amount"
                            class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{
                                $t('system.subscriptions.discount') +
                                formatCurrency(subscription.discount_amount)
                            }}$t('system.subscriptions.create_modal.currency') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Extend Modal -->
        <Transition name="modal">
            <div v-if="showExtendModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showExtendModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.subscriptions.extend_subscription') }}
                        </h3>
                        <div class="mb-4">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('system.subscriptions.number_of_days') }}
                            </label>
                            <input
                                v-model.number="extendDays"
                                type="number"
                                min="1"
                                max="365"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button
                                @click="showExtendModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                @click="extendSubscription"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg"
                            >
                                {{ $t('system.subscriptions.extend') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    subscription: Object,
});

const showExtendModal = ref(false);
const extendDays = ref(30);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || classes.expired;
};

const getStatusLabel = (status) => {
    const labels = {
        active: t('system.subscriptions.stats.active'),
        trial: t('system.subscriptions.stats.trial'),
        cancelled: t('system.subscriptions.stats.cancelled'),
        expired: t('system.subscriptions.stats.expired'),
    };
    return labels[status] || status;
};

const cancelSubscription = () => {
    if (confirm(t('system.subscriptions.do_you_want_to_cancel_this_subscription'))) {
        router.post(`/system/subscriptions/${props.subscription.id}/cancel`);
    }
};

const activateSubscription = () => {
    router.post(`/system/subscriptions/${props.subscription.id}/activate`);
};

const extendSubscription = () => {
    router.post(`/system/subscriptions/${props.subscription.id}/extend`, {
        days: extendDays.value,
    });
    showExtendModal.value = false;
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
