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
                                    {{ $t('system.subscriptions.yearly') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.subscriptions.price') }}
                                </p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{
                                        (Number(subscription.price) > 0
                                            ? subscription.price
                                            : (subscription.plan?.price_yearly ?? 0)) +
                                        $t('system.subscriptions.currency_suffix')
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
                                v-if="['active', 'trial', 'trialing'].includes(subscription.status)"
                                @click="cancelSubscription"
                                class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-sm font-medium"
                            >
                                {{ $t('system.subscriptions.cancel_subscription') }}
                            </button>
                            <button
                                v-if="
                                    ['cancelled', 'expired', 'pending', 'pending_payment'].includes(
                                        subscription.status
                                    )
                                "
                                @click="activateSubscription"
                                class="w-full px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg text-sm font-medium"
                            >
                                {{ $t('system.subscriptions.activate_subscription') }}
                            </button>
                            <button
                                @click="showExtendModal = true"
                                class="w-full px-4 py-2 bg-selection-bg text-action-primary hover:bg-selection-bg rounded-lg text-sm font-medium"
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
                                class="text-action-primary hover:text-selection-text text-sm"
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
                            class="font-mono font-bold text-action-primary bg-selection-bg px-3 py-1 rounded"
                        >
                            {{ subscription.promo_code }}
                        </span>
                        <p
                            v-if="subscription.discount_amount"
                            class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{
                                $t('system.subscriptions.discount') +
                                formatCurrency(subscription.discount_amount) +
                                $t('system.subscriptions.create_modal.currency')
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Extend Modal -->
        <BaseModal
            :show="showExtendModal"
            :title="$t('system.subscriptions.extend_subscription')"
            size="sm"
            @close="showExtendModal = false"
        >
            <div class="mb-4">
                <AppInput
                    v-model.number="extendDays"
                    type="number"
                    min="1"
                    max="365"
                    :label="$t('system.subscriptions.number_of_days')"
                />
            </div>
            <template #footer>
                <div class="flex gap-3 justify-end w-full">
                    <button
                        @click="showExtendModal = false"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        @click="extendSubscription"
                        class="px-4 py-2 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg"
                    >
                        {{ $t('system.subscriptions.extend') }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormatters } from '@/Composables/useFormatters';

const { confirm } = useConfirm();
const { formatDate } = useFormatters();

const props = defineProps({
    subscription: Object,
});

const showExtendModal = ref(false);
const extendDays = ref(30);

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        trialing: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        pending_payment: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || classes.expired;
};

const getStatusLabel = (status) => {
    const labels = {
        active: t('system.subscriptions.stats.active'),
        trial: t('system.subscriptions.stats.trial'),
        trialing: t('system.subscriptions.stats.trial'),
        pending: t('system.subscriptions.pending'),
        pending_payment: t('system.subscriptions.pending_payment'),
        cancelled: t('system.subscriptions.stats.cancelled'),
        expired: t('system.subscriptions.stats.expired'),
        failed: t('system.status.failed'),
    };
    return labels[status] || status;
};

const cancelSubscription = async () => {
    const ok = await confirm({
        title: t('system.subscriptions.cancel_subscription'),
        message: t('system.subscriptions.confirm_cancel_message'),
        confirmText: t('system.subscriptions.cancel_subscription'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.post(`/system/subscriptions/${props.subscription.id}/cancel`);
    }
};

const activateSubscription = async () => {
    const ok = await confirm({
        title: t('system.subscriptions.activate_subscription'),
        message: t('system.subscriptions.confirm_activate_message'),
        confirmText: t('system.subscriptions.activate_subscription'),
        cancelText: t('common.cancel'),
        type: 'success',
    });
    if (ok) {
        router.post(`/system/subscriptions/${props.subscription.id}/activate`);
    }
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
