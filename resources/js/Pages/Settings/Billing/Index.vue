<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useConfirm } from '@/Composables/useConfirm';
import SubscriptionCard from '@/Components/Subscription/SubscriptionCard.vue';
import PlanCard from '@/Components/Subscription/PlanCard.vue';
import SubscribeModal from '@/Components/Subscription/SubscribeModal.vue';
import SubscriptionHistoryTable from '@/Components/Subscription/SubscriptionHistoryTable.vue';
import ReceiptsList from '@/Pages/Settings/Billing/Components/ReceiptsList.vue';
import DunningPreferencesCard from '@/Components/Subscription/DunningPreferencesCard.vue';

const { t, locale } = useI18n();
const { confirm } = useConfirm();
const page = usePage();

const props = defineProps({
    current_subscription: { type: Object, default: null },
    subscription_lifecycle: { type: Object, default: null },
    billing_state: { type: Object, default: null },
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
    payment_icons: { type: Object, default: () => ({ style: 'colored', logos: {} }) },
    company_info: { type: Object, default: () => ({}) },
    vat_settings: { type: Object, default: () => ({}) },
    tenant: { type: Object, default: () => ({}) },
});

const subscribeModalOpen = ref(false);
const selectedPlan = ref(null);
const modalInitialGateway = ref('');
const cancellingPending = ref(false);
const billingActionError = ref('');

const csrfToken = computed(
    () =>
        page.props?.csrf_token ||
        page.props?.value?.csrf_token ||
        document.querySelector('meta[name="csrf-token"]')?.content ||
        ''
);

const canManageBilling = computed(() => {
    const auth = page.props?.auth ?? page.props?.value?.auth;
    if (auth?.is_super_admin) {
        return true;
    }
    const perms = auth?.permissions;
    return Array.isArray(perms) && perms.includes('settings.company.manage');
});

const displayStatus = computed(() => props.billing_state?.display_status || 'none');
const isUnderReview = computed(() => displayStatus.value === 'under_review');
const isReceiptRejected = computed(() => displayStatus.value === 'receipt_rejected');
const isPartiallyPaid = computed(() => displayStatus.value === 'partially_paid');
const isAwaitingPayment = computed(() => displayStatus.value === 'awaiting_payment');
const canUploadReceipt = computed(
    () => canManageBilling.value && Boolean(props.billing_state?.allowed_actions?.can_upload_receipt)
);
const canCancelPending = computed(
    () => canManageBilling.value && Boolean(props.billing_state?.allowed_actions?.can_cancel_pending)
);
const canChangePlanOrGateway = computed(() => {
    if (!canManageBilling.value) return false;
    return Boolean(props.billing_state?.allowed_actions?.can_change_plan_or_gateway);
});

const openInvoicePlan = computed(() => {
    const planId = props.billing_state?.open_invoice?.plan_id;
    if (planId) {
        return (
            props.available_plans.find((p) => p.id === planId) ||
            props.current_subscription?.plan ||
            null
        );
    }
    return props.current_subscription?.plan || null;
});

const planLockReason = computed(() => {
    if (isUnderReview.value) {
        return t('system.billing.page.state.under_review_locked_hint');
    }
    if (isPartiallyPaid.value) {
        return t('system.billing.page.state.partial_paid_locked_hint');
    }
    return '';
});

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

const hasPendingSubscription = computed(() => !canChangePlanOrGateway.value);

function openSubscribe(plan) {
    const isSamePartialPlan =
        isPartiallyPaid.value &&
        canUploadReceipt.value &&
        props.billing_state?.open_invoice?.plan_id === plan?.id;

    if (!canChangePlanOrGateway.value && !isSamePartialPlan) {
        return;
    }

    modalInitialGateway.value = isSamePartialPlan ? 'bank_transfer' : '';
    selectedPlan.value = plan;
    subscribeModalOpen.value = true;
}

function openReuploadModal() {
    if (!canUploadReceipt.value || !openInvoicePlan.value) {
        return;
    }
    modalInitialGateway.value = 'bank_transfer';
    selectedPlan.value = openInvoicePlan.value;
    subscribeModalOpen.value = true;
}

async function cancelPendingRequest() {
    if (!canCancelPending.value || cancellingPending.value) {
        return;
    }
    const ok = await confirm({
        title: t('system.billing.page.state.cancel_confirm_title'),
        message: t('system.billing.page.state.cancel_confirm_body'),
        confirmText: t('system.billing.page.state.cancel_request'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (!ok) return;

    cancellingPending.value = true;
    billingActionError.value = '';
    try {
        const res = await fetch('/app/settings/billing/cancel-pending', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.value,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
        });
        if (!res.ok) {
            const payload = await res.json().catch(() => ({}));
            billingActionError.value =
                payload?.error || payload?.message || t('system.billing.page.state.cancel_error');
            return;
        }
        router.reload();
    } catch {
        billingActionError.value = t('system.billing.page.state.cancel_error');
    } finally {
        cancellingPending.value = false;
    }
}

function closeModal() {
    subscribeModalOpen.value = false;
    selectedPlan.value = null;
    modalInitialGateway.value = '';
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
                gradientTo="to-action-primary"
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

            <!-- 0. Unified Billing State Banner (single server source of truth) -->
            <section
                v-if="
                    isUnderReview || isReceiptRejected || isPartiallyPaid || isAwaitingPayment
                "
            >
                <div
                    :class="[
                        'rounded-2xl border p-5 shadow-sm',
                        isUnderReview
                            ? 'border-blue-200 bg-blue-50/90 dark:border-blue-800/60 dark:bg-blue-950/40'
                            : isReceiptRejected
                              ? 'border-rose-200 bg-rose-50/90 dark:border-rose-800/60 dark:bg-rose-950/40'
                              : 'border-amber-200 bg-amber-50/90 dark:border-amber-800/60 dark:bg-amber-950/40',
                    ]"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    :class="[
                                        'text-base font-bold',
                                        isUnderReview
                                            ? 'text-blue-900 dark:text-blue-100'
                                            : isReceiptRejected
                                              ? 'text-rose-900 dark:text-rose-100'
                                              : 'text-amber-900 dark:text-amber-100',
                                    ]"
                                >
                                    {{
                                        isUnderReview
                                            ? t('system.billing.page.state.under_review_title')
                                            : isReceiptRejected
                                              ? t('system.billing.page.state.receipt_rejected_title')
                                              : isPartiallyPaid
                                                ? t('system.billing.page.state.partially_paid_title')
                                                : t('system.billing.page.state.awaiting_payment_title')
                                    }}
                                </h2>
                                <span
                                    v-if="billing_state?.open_invoice?.invoice_number"
                                    class="rounded-md bg-white/80 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700 dark:bg-slate-800/80 dark:text-slate-200"
                                >
                                    {{ t('system.billing.page.state.invoice_number') }}:
                                    {{ billing_state.open_invoice.invoice_number }}
                                </span>
                            </div>

                            <p
                                :class="[
                                    'text-xs leading-relaxed',
                                    isUnderReview
                                        ? 'text-blue-800 dark:text-blue-200'
                                        : isReceiptRejected
                                          ? 'text-rose-800 dark:text-rose-200'
                                          : 'text-amber-800 dark:text-amber-200',
                                ]"
                            >
                                {{
                                    isUnderReview
                                        ? t('system.billing.page.state.under_review_desc')
                                        : isReceiptRejected
                                          ? t('system.billing.page.state.receipt_rejected_desc')
                                          : isPartiallyPaid
                                            ? t('system.billing.page.state.partially_paid_desc')
                                            : t('system.billing.page.state.awaiting_payment_desc')
                                }}
                            </p>

                            <!-- Rejection reason -->
                            <div
                                v-if="
                                    isReceiptRejected &&
                                    billing_state?.latest_rejected_receipt?.review_notes
                                "
                                class="rounded-xl border border-rose-200/80 bg-white/80 px-3 py-2 text-xs text-rose-800 dark:border-rose-800/50 dark:bg-slate-900/50 dark:text-rose-200"
                            >
                                <span class="font-bold">
                                    {{ t('system.billing.page.state.receipt_rejected_reason') }}:
                                </span>
                                {{ billing_state.latest_rejected_receipt.review_notes }}
                            </div>

                            <!-- Partially paid breakdown -->
                            <div
                                v-if="isPartiallyPaid && billing_state?.open_invoice"
                                class="flex flex-wrap items-center gap-4 text-xs"
                            >
                                <span class="font-semibold text-emerald-700 dark:text-emerald-300">
                                    {{ t('system.billing.page.state.paid_amount') }}:
                                    <span class="font-mono">
                                        {{ formatCurrency(billing_state.open_invoice.paid_amount) }}
                                    </span>
                                    {{ t('system.billing.page.currency') }}
                                </span>
                                <span class="font-semibold text-amber-800 dark:text-amber-200">
                                    {{ t('system.billing.page.state.remaining_amount') }}:
                                    <span class="font-mono">
                                        {{
                                            formatCurrency(
                                                billing_state.open_invoice.remaining_amount
                                            )
                                        }}
                                    </span>
                                    {{ t('system.billing.page.currency') }}
                                </span>
                            </div>

                            <p
                                v-if="isPartiallyPaid"
                                class="text-[11px] text-amber-700/90 dark:text-amber-300/90"
                            >
                                {{ t('system.billing.page.state.gateway_locked_partial') }}
                            </p>

                            <p
                                v-if="billing_state?.has_multiple_open_invoices"
                                class="text-[11px] text-slate-600 dark:text-slate-300"
                            >
                                {{ t('system.billing.page.state.multiple_open_invoices_note') }}
                            </p>

                            <p
                                v-if="billingActionError"
                                class="text-xs font-semibold text-rose-700 dark:text-rose-300"
                            >
                                {{ billingActionError }}
                            </p>
                        </div>

                        <!-- Server-governed action buttons -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <AppButton
                                v-if="canUploadReceipt && openInvoicePlan"
                                variant="primary"
                                size="sm"
                                @click="openReuploadModal"
                            >
                                {{
                                    isPartiallyPaid
                                        ? t('system.billing.page.state.complete_remaining')
                                        : t('system.billing.page.state.reupload_receipt')
                                }}
                            </AppButton>
                            <AppButton
                                v-if="canCancelPending"
                                variant="secondary"
                                size="sm"
                                :loading="cancellingPending"
                                :disabled="cancellingPending"
                                @click="cancelPendingRequest"
                            >
                                {{ t('system.billing.page.state.cancel_request') }}
                            </AppButton>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 1. Current subscription (the hero card, Stripe-style) -->
            <section>
                <h2
                    class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                    {{ t('system.billing.page.section_current') }}
                </h2>
                <SubscriptionCard
                    :subscription="current_subscription"
                    :lifecycle="subscription_lifecycle"
                    :billing-state="billing_state"
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
                        :is-partial-open-plan="
                            isPartiallyPaid &&
                            billing_state?.open_invoice?.plan_id === plan.id &&
                            canUploadReceipt
                        "
                        :lock-reason="planLockReason"
                        @subscribe="openSubscribe"
                    />
                </div>
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
                        :billing-state="billing_state"
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
                        <div class="overflow-x-auto">
                            <DataTable structured class="w-full text-sm">
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
                        </DataTable>
                        </div>
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
            :payment-icons="payment_icons"
            :bank-accounts="payment_settings.bank_accounts || []"
            :bank-instructions="payment_settings.bank_transfer_instructions"
            :company-info="company_info"
            :vat-settings="vat_settings"
            :initial-gateway="modalInitialGateway"
            :billing-state="billing_state"
            @close="closeModal"
            @success="onSuccess"
        />
    </AppLayout>
</template>
