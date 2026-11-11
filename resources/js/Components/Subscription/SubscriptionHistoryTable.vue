<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

const { t } = useI18n();

const props = defineProps({
    subscriptions: { type: Array, default: () => [] },
    canCancel: { type: Boolean, default: true },
    canResume: { type: Boolean, default: true },
});

const emit = defineEmits(['refresh']);

const locale = computed(() => document.documentElement.lang || 'ar');
const processing = ref(null);
const confirmCancel = ref(null);

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

async function cancel(sub) {
    processing.value = sub.id;
    try {
        await fetch(`/app/settings/billing/cancel/${sub.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        confirmCancel.value = null;
        emit('refresh');
    } catch (e) {
        // silent
    } finally {
        processing.value = null;
    }
}

async function resume(sub) {
    processing.value = sub.id;
    try {
        await fetch(`/app/settings/billing/resume/${sub.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        emit('refresh');
    } catch (e) {
        // silent
    } finally {
        processing.value = null;
    }
}

async function toggleAutoRenew(sub) {
    processing.value = sub.id;
    try {
        await fetch(`/app/settings/billing/auto-renew/${sub.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        emit('refresh');
    } catch (e) {
        // silent
    } finally {
        processing.value = null;
    }
}

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
                            <th
                                class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                {{ t('system.company_subscription.history.table.actions') }}
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
                                        statusClass(sub.status),
                                    ]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                                    ></span>
                                    {{ statusLabel(sub.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Auto-renew toggle for active -->
                                    <button
                                        v-if="sub.status === 'active'"
                                        type="button"
                                        @click="toggleAutoRenew(sub)"
                                        :disabled="processing === sub.id"
                                        :title="
                                            sub.auto_renew
                                                ? t(
                                                      'system.company_subscription.history.actions.disable_auto_renew'
                                                  )
                                                : t(
                                                      'system.company_subscription.history.actions.enable_auto_renew'
                                                  )
                                        "
                                        :class="[
                                            'rounded p-1.5 transition-colors disabled:opacity-50',
                                            sub.auto_renew
                                                ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30'
                                                : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700',
                                        ]"
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
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Cancel for active/trialing — the history list
                                         normally doesn't show these (the controller
                                         excludes the current subscription), but a
                                         tenant might see one if the controller
                                         filter changes in the future. Defensive. -->
                                    <button
                                        v-if="
                                            canCancel &&
                                            ['active', 'trialing', 'trial'].includes(sub.status)
                                        "
                                        type="button"
                                        @click="confirmCancel = sub"
                                        :disabled="processing === sub.id"
                                        class="rounded p-1.5 text-rose-600 hover:bg-rose-50 disabled:opacity-50 dark:hover:bg-rose-900/30"
                                        :title="
                                            t(
                                                'system.company_subscription.history.actions.cancel_subscription'
                                            )
                                        "
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
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Resume for cancelled — only shown if the
                                         controller returned it (history usually
                                         includes only the most recent cancelled). -->
                                    <button
                                        v-if="canResume && sub.status === 'cancelled'"
                                        type="button"
                                        @click="resume(sub)"
                                        :disabled="processing === sub.id"
                                        class="rounded p-1.5 text-emerald-600 hover:bg-emerald-50 disabled:opacity-50 dark:hover:bg-emerald-900/30"
                                        :title="
                                            t(
                                                'system.company_subscription.history.actions.resume_subscription'
                                            )
                                        "
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
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 0 01-15.357-2m15.357 2H15"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cancel confirmation modal -->
        <Teleport to="body">
            <div
                v-if="confirmCancel"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="confirmCancel = null"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-800">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ t('system.company_subscription.history.confirm_modal.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ t('system.company_subscription.history.confirm_modal.body') }}
                    </p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="confirmCancel = null"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ t('system.company_subscription.history.confirm_modal.go_back') }}
                        </button>
                        <button
                            type="button"
                            @click="cancel(confirmCancel)"
                            :disabled="processing === confirmCancel.id"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                        >
                            {{ t('system.company_subscription.history.confirm_modal.yes_cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
