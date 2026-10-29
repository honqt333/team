<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SubscriptionCard from '@/Components/Subscription/SubscriptionCard.vue';
import PlanCard from '@/Components/Subscription/PlanCard.vue';
import SubscribeModal from '@/Components/Subscription/SubscribeModal.vue';
import SubscriptionHistoryTable from '@/Components/Subscription/SubscriptionHistoryTable.vue';
import PaymentMethodsList from '@/Pages/Settings/Billing/Components/PaymentMethodsList.vue';
import ReceiptsList from '@/Pages/Settings/Billing/Components/ReceiptsList.vue';
import DunningPreferencesCard from '@/Components/Subscription/DunningPreferencesCard.vue';

const { t } = useI18n();

const props = defineProps({
    current_subscription: { type: Object, default: null },
    available_plans: { type: Array, default: () => [] },
    subscription_history: { type: Array, default: () => [] },
    recent_payments: { type: Array, default: () => [] },
    pending_receipts: { type: Array, default: () => [] },
    all_receipts: { type: Array, default: () => [] },
    receipt_status_counts: { type: Object, default: () => ({}) },
    receipt_filter: {
        type: Object,
        default: () => ({ status: 'all', search: '', per_page: 20 }),
    },
    enabled_gateways: { type: Array, default: () => [] },
    payment_settings: { type: Object, default: () => ({}) },
    company_info: { type: Object, default: () => ({}) },
    vat_settings: { type: Object, default: () => ({}) },
    tenant: { type: Object, default: () => ({}) },
});

const locale = computed(() => document.documentElement.lang || 'ar');

const subscribeModalOpen = ref(false);
const selectedPlan = ref(null);

const currentPlanId = computed(() => {
    if (props.current_subscription?.kind === 'virtual_trial') {
        return null;
    }
    return props.current_subscription?.plan_id ?? null;
});

const isSubscriptionExpired = computed(() => {
    const status = props.current_subscription?.status;
    if (['expired', 'cancelled', 'failed'].includes(status)) {
        return true;
    }
    if (props.current_subscription?.ends_at) {
        return new Date(props.current_subscription.ends_at) <= new Date();
    }
    return false;
});

const canRenewCurrent = computed(() => {
    if (!props.current_subscription) return false;
    if (isSubscriptionExpired.value) return true;
    if (['trial', 'trialing'].includes(props.current_subscription.status)) return true;
    if (props.current_subscription.ends_at) {
        const diffDays = Math.ceil(
            (new Date(props.current_subscription.ends_at) - new Date()) / (1000 * 60 * 60 * 24)
        );
        return diffDays <= 30;
    }
    return false;
});

const currentPlan = computed(() => {
    if (!props.current_subscription?.plan_id) return null;
    return (
        props.available_plans.find((p) => p.id === props.current_subscription.plan_id) ||
        props.current_subscription.plan
    );
});

function handleRenewCurrent() {
    if (currentPlan.value) {
        openSubscribe(currentPlan.value);
    }
}

const hasPendingSubscription = computed(() => {
    return ['pending', 'pending_payment'].includes(props.current_subscription?.status);
});

function openSubscribe(plan) {
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
    const params = new URLSearchParams(window.location.search);
    const planId = params.get('plan');
    if (planId) {
        const plan = props.available_plans.find((p) => String(p.id) === planId);
        if (plan && (!currentPlanId.value || isSubscriptionExpired.value)) {
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
    if (!dateStr) return t('system.billing.page.em_dash');
    return new Date(dateStr).toLocaleDateString(
        locale.value === 'ar'
            ? t('system.billing.page.locale_ar')
            : t('system.billing.page.locale_en'),
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }
    );
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(t('system.billing.page.locale_en'), {
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

const gatewayLabel = (gateway) => {
    return t(`system.company_subscription.gateways.${gateway}`) || gateway;
};

const noPlanLabel = () => t('system.billing.page.no_plan');
</script>

<template>
    <Head :title="t('system.billing.page.title')" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="t('system.billing.page.title')"
                :subtitle="t('system.billing.page.subtitle')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-700"
                glowFrom="from-blue-500"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #back>
                    <BackButton href="/app/settings" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- 1. Current subscription (the hero card, Stripe-style) -->
            <section>
                <h2
                    class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                    {{ t('system.billing.page.section_current') }}
                </h2>
                <SubscriptionCard
                    :subscription="current_subscription"
                    @renew="handleRenewCurrent"
                />
            </section>

            <!-- 2. Available plans (upgrade / downgrade / renew) -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2
                        class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                    >
                        {{ t('system.billing.page.section_plans') }}
                    </h2>
                </div>
                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                    {{ t('system.billing.page.section_plans_subtitle') }}
                </p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <PlanCard
                        v-for="plan in available_plans"
                        :key="plan.id"
                        :plan="plan"
                        :is-current="currentPlanId === plan.id"
                        :is-expired="isSubscriptionExpired"
                        :can-renew="canRenewCurrent && currentPlanId === plan.id"
                        :has-pending-subscription="hasPendingSubscription"
                        @subscribe="openSubscribe"
                    />
                </div>
            </section>

            <!-- 3. Active payment methods (read-only summary card) -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2
                        class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                    >
                        {{ t('system.billing.page.section_payment_methods') }}
                    </h2>
                </div>
                <PaymentMethodsList
                    :gateways="enabled_gateways"
                    :payment-settings="payment_settings"
                />
            </section>

            <!-- 4. Pending receipts (collapsible alert-style) -->
            <section v-if="pending_receipts.length">
                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800/50 dark:bg-amber-900/10"
                >
                    <h2 class="mb-1 text-base font-bold text-amber-900 dark:text-amber-200">
                        {{ t('system.billing.page.section_pending_receipts') }}
                    </h2>
                    <p class="mb-5 text-xs text-amber-700/80 dark:text-amber-300/80">
                        {{ t('system.billing.page.section_pending_receipts_subtitle') }}
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
                                                ? r.subscription?.plan?.name_ar || noPlanLabel()
                                                : r.subscription?.plan?.name_en || noPlanLabel()
                                        }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatDate(r.transfer_date) }} ·
                                        {{ formatCurrency(r.transfer_amount) }}
                                        {{ t('system.billing.page.currency') }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                            >
                                {{ t('system.billing.page.pending_badge') }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Subscription history (table) -->
            <section>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
                >
                    <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                        {{ t('system.billing.page.section_history') }}
                    </h2>
                    <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('system.billing.page.section_history_subtitle') }}
                    </p>
                    <SubscriptionHistoryTable
                        :subscriptions="subscription_history"
                        @refresh="router.reload()"
                    />
                </div>
            </section>

            <!-- 6. All receipts (inline — replaces /settings/company/subscription/receipts) -->
            <!-- Show the section whenever there is at least one receipt on
                 file OR the user is actively filtering. The filter chips
                 and search input are useful even with zero rows, since
                 the empty state inside ReceiptsList tells the tenant
                 "no receipts match your filters" — and the active
                 filters can then be cleared with one click. -->
            <section v-if="all_receipts.length || (receipt_status_counts.all || 0) > 0">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
                >
                    <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                        {{ t('system.billing.page.section_receipts') }}
                    </h2>
                    <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('system.billing.page.section_receipts_subtitle') }}
                    </p>
                    <ReceiptsList
                        :receipts="all_receipts"
                        :status-counts="receipt_status_counts"
                        :filter="receipt_filter"
                    />
                </div>
            </section>

            <!-- 6b. Dunning preferences — opt out of collection
                  reminders (Phase 6). -->
            <section v-if="tenant && tenant.id">
                <DunningPreferencesCard :tenant="tenant" />
            </section>

            <!-- 7. Recent payments -->
            <section v-if="recent_payments.length">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
                >
                    <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                        {{ t('system.billing.page.section_recent_payments') }}
                    </h2>
                    <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('system.billing.page.section_recent_payments_subtitle') }}
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
                                        {{ t('system.billing.page.table.date') }}
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                    >
                                        {{ t('system.billing.page.table.gateway') }}
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                    >
                                        {{ t('system.billing.page.table.amount') }}
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                    >
                                        {{ t('system.billing.page.table.status') }}
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
                                        {{ formatCurrency(payment.amount) }}
                                        {{ t('system.billing.page.currency') }}
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
                                                    `system.billing.page.payment_status.${payment.status}`,
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
            </section>
        </div>

        <!-- Subscribe modal (same as before, routes to /settings/billing/* POST endpoints) -->
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
