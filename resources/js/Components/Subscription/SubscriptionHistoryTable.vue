<script setup>
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    subscriptions: { type: Array, default: () => [] },
    billingState: { type: Object, default: null },
    canCancel: { type: Boolean, default: true },
    canResume: { type: Boolean, default: true },
});

// Status badge styling. Every terminal status (cancelled, expired,
// refunded, failed) AND the in-flight statuses (pending, pending_payment)
// are listed here so the table renders correctly for any row the
// controller might include. The previous version was missing 'failed',
// 'refunded', and 'past_due', which produced an unstyled gray pill
// for the now-supported 'failed' status.
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
        class: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
    },
    expired: {
        class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    },
    refunded: {
        class: 'bg-purple-100 text-purple-800',
    },
    past_due: {
        class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    },
};

// Fallback to 'cancelled' styling if a status we haven't seen yet
// arrives — better an odd color than an unstyled blank pill.
const statusClass = (status) => {
    return statusConfig[status]?.class || statusConfig.cancelled.class;
};

const statusLabel = (status) => {
    // t() returns the key path itself when missing — wrap in a
    // fallback so the table never renders the raw dot-path
    // ('system.company_subscription.status.unknown_thing') to the
    // tenant. The unknown statuses fall back to the literal status
    // string, which is at least readable.
    const key = `system.company_subscription.status.${status}`;
    const value = t(key);
    return value === key ? status : value;
};

const planName = (sub) => {
    if (locale.value === 'ar') return sub.plan?.name_ar || sub.plan?.name || '—';
    return sub.plan?.name_en || sub.plan?.name || '—';
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

const rowStatus = (sub) => {
    if (
        props.billingState?.pending_subscription_id &&
        Number(sub.id) === Number(props.billingState.pending_subscription_id) &&
        props.billingState.display_status &&
        props.billingState.display_status !== 'none'
    ) {
        if (props.billingState.display_status === 'under_review') {
            return {
                class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                label: t('system.billing.page.state.under_review_title'),
            };
        }
        if (props.billingState.display_status === 'partially_paid') {
            return {
                class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                label: t('system.billing.page.state.partially_paid_title'),
            };
        }
        if (props.billingState.display_status === 'receipt_rejected') {
            return {
                class: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                label: t('system.billing.page.state.receipt_rejected_title'),
            };
        }
    }

    return {
        class: statusClass(sub.status),
        label: statusLabel(sub.status),
    };
};

// Defensive: a single history row is short — a single line. Use
// whitespace-nowrap so the dates and amount stay on one line and
// the table doesn't get too wide on mobile.
const tableClass = 'whitespace-nowrap';
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="!subscriptions.length"
            class="rounded-lg border border-dashed border-slate-200 p-8 text-center dark:border-slate-700"
        >
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ t('system.company_subscription.history.empty') }}
            </p>
        </div>

        <div
            v-else
            class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm" :class="tableClass">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.plan') }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.cycle') }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.from') }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.to') }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.amount') }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr
                            v-for="sub in subscriptions"
                            :key="sub.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                        >
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{ planName(sub) }}
                                </p>
                                <p
                                    v-if="sub.promo_code"
                                    class="mt-0.5 font-mono text-xs text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ sub.promo_code }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                {{ t('system.company_subscription.history.cycle_yearly') }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300"
                            >
                                {{ formatDate(sub.starts_at) }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300"
                            >
                                {{ formatDate(sub.ends_at) }}
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-900 dark:text-white">
                                {{ formatCurrency(sub.price) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold',
                                        rowStatus(sub).class,
                                    ]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                                    ></span>
                                    {{ rowStatus(sub).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
