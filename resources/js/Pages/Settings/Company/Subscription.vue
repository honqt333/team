<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import SubscriptionCard from '@/Components/Subscription/SubscriptionCard.vue';
import PlanCard from '@/Components/Subscription/PlanCard.vue';
import SubscribeModal from '@/Components/Subscription/SubscribeModal.vue';
import SubscriptionHistoryTable from '@/Components/Subscription/SubscriptionHistoryTable.vue';

const { t } = useI18n();

const props = defineProps({
    current_subscription: { type: Object, default: null },
    available_plans: { type: Array, default: () => [] },
    subscription_history: { type: Array, default: () => [] },
    recent_payments: { type: Array, default: () => [] },
    pending_receipts: { type: Array, default: () => [] },
    enabled_gateways: { type: Array, default: () => [] },
    payment_settings: { type: Object, default: () => ({}) },
    company_info: { type: Object, default: () => ({}) },
    vat_settings: { type: Object, default: () => ({}) },
});

const locale = computed(() => document.documentElement.lang || 'ar');

const subscribeModalOpen = ref(false);
const selectedPlan = ref(null);

const currentPlanId = computed(() => {
    // Use the discriminator set by the controller. Real Subscription
    // models from Eloquent return objects with no `kind` field at all,
    // so we treat the absence of `kind === 'virtual_trial'` as "real
    // subscription, has a plan_id" and `kind === 'virtual_trial'` as
    // "no plan, the tenant is in trial".
    if (props.current_subscription?.kind === 'virtual_trial') {
        return null;
    }
    return props.current_subscription?.plan_id ?? null;
});

const isVirtualTrial = computed(() => props.current_subscription?.kind === 'virtual_trial');

const hasPendingSubscription = computed(() => {
    return ['pending', 'pending_payment'].includes(props.current_subscription?.status);
});

function openSubscribe(plan) {
    // Guard: refuse to open the modal if the tenant already has an in-flight
    // subscription. Defense in depth — the controller also rejects, but this
    // gives instant feedback in the UI.
    if (hasPendingSubscription.value) {
        return;
    }
    selectedPlan.value = plan;
    subscribeModalOpen.value = true;
}

function closeModal() {
    subscribeModalOpen.value = false;
    selectedPlan.value = null;
}

function onSuccess() {
    router.reload();
}

onMounted(() => {
    // Auto-open modal if ?plan=X is in URL
    const params = new URLSearchParams(window.location.search);
    const planId = params.get('plan');
    if (planId) {
        const plan = props.available_plans.find((p) => String(p.id) === planId);
        if (plan && !currentPlanId.value) {
            openSubscribe(plan);
        }
    }
});

const statusClass = (status) => {
    return (
        {
            paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            pending: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
            failed: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
            refunded: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        }[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(amount || 0);
};

const gatewayLabel = (gateway) => {
    return t(`system.company_subscription.gateways.${gateway}`) || gateway;
};
</script>

<template>
    <Head :title="t('system.company_subscription.page.title')" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ t('system.company_subscription.page.title') }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('system.company_subscription.page.subtitle') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="/app/settings/billing"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            {{ t('system.company_subscription.page.my_receipts') }}
                        </a>
                        <a
                            href="/app/settings/company"
                            class="inline-flex items-center gap-1 self-start rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-3.5 w-3.5 rtl:rotate-180"
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
                            {{ t('system.company_subscription.page.back_to_settings') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Current subscription card -->
            <SubscriptionCard :subscription="current_subscription" />

            <!-- Available plans -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                    {{ t('system.company_subscription.page.available_plans') }}
                </h2>
                <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                    {{ t('system.company_subscription.page.available_plans_subtitle') }}
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <PlanCard
                        v-for="plan in available_plans"
                        :key="plan.id"
                        :plan="plan"
                        :is-current="currentPlanId === plan.id"
                        :has-pending-subscription="hasPendingSubscription"
                        @subscribe="openSubscribe"
                    />
                </div>
            </div>

            <!-- Subscription history -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                    {{ t('system.company_subscription.page.history_title') }}
                </h2>
                <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                    {{ t('system.company_subscription.page.history_subtitle') }}
                </p>
                <SubscriptionHistoryTable
                    :subscriptions="subscription_history"
                    @refresh="router.reload()"
                />
            </div>

            <!-- Pending bank transfer receipts -->
            <div
                v-if="pending_receipts.length"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800/50 dark:bg-amber-900/10"
            >
                <h2 class="mb-1 text-base font-bold text-amber-900 dark:text-amber-200">
                    {{ t('system.company_subscription.page.pending_receipts_title') }}
                </h2>
                <p class="mb-5 text-xs text-amber-700/80 dark:text-amber-300/80">
                    {{ t('system.company_subscription.page.pending_receipts_subtitle') }}
                </p>
                <div class="space-y-2">
                    <div
                        v-for="r in pending_receipts"
                        :key="r.id"
                        class="flex items-center justify-between rounded-lg border border-amber-200 bg-white p-3 dark:border-amber-800/30 dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{
                                        locale === 'ar'
                                            ? r.subscription?.plan?.name_ar
                                            : r.subscription?.plan?.name_en
                                    }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatDate(r.transfer_date) }} ·
                                    {{ formatCurrency(r.transfer_amount) }} SAR
                                </p>
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            {{ t('system.company_subscription.page.pending_status_badge') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recent payments -->
            <div
                v-if="recent_payments.length"
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                    {{ t('system.company_subscription.page.recent_payments_title') }}
                </h2>
                <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                    {{ t('system.company_subscription.page.recent_payments_subtitle') }}
                </p>

                <div
                    class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
                >
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ t('system.company_subscription.page.table.date') }}
                                </th>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ t('system.company_subscription.page.table.gateway') }}
                                </th>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ t('system.company_subscription.page.table.amount') }}
                                </th>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ t('system.company_subscription.page.table.status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr
                                v-for="payment in recent_payments"
                                :key="payment.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                            >
                                <td
                                    class="px-4 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300"
                                >
                                    {{ formatDate(payment.created_at) }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-700 dark:text-slate-200">
                                    {{ gatewayLabel(payment.gateway) }}
                                </td>
                                <td
                                    class="px-4 py-2.5 font-mono font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ formatCurrency(payment.amount) }} SAR
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold',
                                            statusClass(payment.status),
                                        ]"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                                        ></span>
                                        {{
                                            t(
                                                `system.company_subscription.page.payment_status.${payment.status}`,
                                                payment.status
                                            )
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Subscribe modal -->
        <SubscribeModal
            :open="subscribeModalOpen"
            :plan="selectedPlan"
            :enabled-gateways="enabled_gateways"
            :payment-settings="payment_settings"
            :bank-accounts="payment_settings.bank_accounts || []"
            :bank-instructions="payment_settings.bank_transfer_instructions"
            :company-info="company_info"
            :vat-settings="vat_settings"
            @close="closeModal"
            @success="onSuccess"
        />
    </AppLayout>
</template>
