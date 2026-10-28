<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    subscription: { type: Object, default: null },
});

const emit = defineEmits(['renew']);

const locale = computed(() => document.documentElement.lang || 'ar');

const statusConfig = {
    active: {
        class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    },
    trialing: {
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    },
    trial: {
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    },
    pending: {
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    },
    pending_payment: {
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    },
    payment_required: {
        class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    },
    failed: {
        class: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
    },
    cancelled: {
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    },
    expired: {
        class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    },
    refunded: {
        class: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
    },
};

const status = computed(() => statusConfig[props.subscription?.status] || statusConfig.pending);
const statusLabel = computed(() => {
    const key = `system.company_subscription.status.${props.subscription?.status || 'pending'}`;
    return t(key);
});

const planName = computed(() => {
    if (!props.subscription) return '';
    if (locale.value === 'ar') {
        return props.subscription.plan?.name_ar || props.subscription.plan?.name || '';
    }
    return props.subscription.plan?.name_en || props.subscription.plan?.name || '';
});

const daysLeft = computed(() => {
    if (!props.subscription?.ends_at) return null;
    const end = new Date(props.subscription.ends_at);
    const now = new Date();
    const diff = Math.ceil((end - now) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
});

// Subscription price fallback: legacy subscriptions created before
// the 'price' column was added in 2026_07_21 (and backfilled in
// 2026_07_25_150000) may have price=0 even though the plan has a real
// price. If the subscription's own price is 0 (or missing), look up
// the plan's price_monthly/price_yearly. The plan is eager-loaded by
// the controller, so no extra request is needed.
const effectivePrice = computed(() => {
    const sub = props.subscription;
    if (!sub) return 0;
    if (sub.price && Number(sub.price) > 0) return Number(sub.price);
    const plan = sub.plan;
    if (!plan) return 0;
    return sub.billing_cycle === 'yearly'
        ? Number(plan.price_yearly || 0)
        : Number(plan.price_monthly || 0);
});

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

const canRenew = computed(() => {
    if (!props.subscription) return false;
    const s = props.subscription.status;
    if (['expired', 'cancelled', 'failed', 'trial', 'trialing'].includes(s)) {
        return true;
    }
    if (s === 'active' && daysLeft.value !== null && daysLeft.value <= 30) {
        return true;
    }
    return false;
});
</script>

<template>
    <div
        v-if="subscription"
        class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-6 dark:border-slate-700 dark:from-indigo-900/20 dark:via-slate-800 dark:to-violet-900/20"
    >
        <!-- Decorative blobs -->
        <div
            class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-indigo-200/20 blur-2xl dark:bg-indigo-900/20"
        ></div>
        <div
            class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-violet-200/20 blur-2xl dark:bg-violet-900/20"
        ></div>

        <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                    >
                        {{ t('system.company_subscription.card.current_label') }}
                    </span>
                    <span :class="['rounded-full px-2 py-0.5 text-[10px] font-bold', status.class]">
                        {{ statusLabel }}
                    </span>
                </div>
                <h3 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                    {{ planName }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    <span v-if="subscription.ends_at">
                        {{ t('system.company_subscription.card.ends') }}:
                        <span class="font-mono">{{ formatDate(subscription.ends_at) }}</span>
                        <span v-if="daysLeft !== null" class="ms-2 text-xs">
                            ({{
                                t('system.company_subscription.card.days_left', {
                                    days: daysLeft,
                                })
                            }})
                        </span>
                    </span>
                </p>
            </div>

            <div class="text-start md:text-end">
                <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {{ t('system.company_subscription.card.price') }}
                </p>
                <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">
                    {{ formatCurrency(effectivePrice) }}
                    <span class="text-base font-normal text-slate-500">SAR</span>
                </p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    /
                    {{
                        subscription.billing_cycle === 'yearly'
                            ? t('system.company_subscription.card.billing_yearly')
                            : t('system.company_subscription.card.billing_monthly')
                    }}
                </p>
            </div>
        </div>

        <!-- Expired / Expiring Soon / Renewal Action Banner -->
        <div
            v-if="canRenew"
            class="relative mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 dark:border-amber-800/60 dark:bg-amber-900/20"
        >
            <div class="flex items-center gap-2.5 text-xs text-amber-800 dark:text-amber-300">
                <svg
                    class="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                <span>
                    <strong v-if="subscription.status === 'expired'">
                        {{ $t('subscription.expired_notice') }}
                    </strong>
                    <strong v-else-if="daysLeft !== null && daysLeft <= 30">
                        {{ $t('subscription.expiring_soon') }} {{ daysLeft }}
                        {{ $t('subscription.days_suffix') }}
                    </strong>
                    <strong v-else-if="['trial', 'trialing'].includes(subscription.status)">
                        {{ $t('subscription.on_trial_notice') }}
                    </strong>
                    {{ $t('subscription.renew_now_hint') }}
                </span>
            </div>
            <button
                type="button"
                @click="emit('renew')"
                class="w-full sm:w-auto flex-shrink-0 inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                {{ $t('subscription.renew_now') }}
            </button>
        </div>

        <!-- Auto-renew status -->
        <div
            v-if="subscription.status === 'active'"
            class="relative mt-4 flex items-center gap-2 border-t border-slate-200/50 pt-3 text-xs text-slate-600 dark:border-slate-700/50 dark:text-slate-400"
        >
            <svg
                class="h-3.5 w-3.5"
                :class="subscription.auto_renew ? 'text-emerald-500' : 'text-slate-400'"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
            </svg>
            <span>
                {{
                    subscription.auto_renew
                        ? t('system.company_subscription.card.auto_renew_enabled')
                        : t('system.company_subscription.card.auto_renew_disabled')
                }}
            </span>
        </div>
    </div>
</template>
