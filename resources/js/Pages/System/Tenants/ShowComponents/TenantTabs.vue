<template>
    <div
        class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <!-- Tab bar — uses a simple inline state instead of a router tab
             so the admin can flip between tabs without losing scroll
             position or triggering a server request. -->
        <div class="flex overflow-x-auto border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="active = tab.key"
                :class="[
                    'flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition-colors',
                    active === tab.key
                        ? 'border-action-primary text-action-primary'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
            >
                <span aria-hidden="true">{{ tab.icon }}</span>
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    class="rounded-full bg-gray-200 px-1.5 py-0.5 text-[10px] font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </div>

        <!-- Tab panels. Each panel is a self-contained sub-component
             so the parent doesn't grow into a god-component. -->
        <div class="p-5">
            <!-- Overview -->
            <div v-if="active === 'overview'" class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                    {{ $t('system.tenants.tabs.basic_info') }}
                </h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        v-for="(value, key) in overviewFields"
                        :key="key"
                        class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ key }}
                        </p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ value || $t('common.na') }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="recentWorkOrders && recentWorkOrders.length"
                    class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                >
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">
                        {{ $t('system.tenants.show.recent_work_orders') }}
                    </h4>
                    <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700">
                        <li
                            v-for="wo in recentWorkOrders"
                            :key="wo.id"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="font-mono text-gray-700 dark:text-gray-200">
                                {{ wo.code }}
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="statusBadge(wo.status)"
                            >
                                {{ getWorkOrderStatusLabel(wo.status) }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Phase 5 — Health score trend (last 6 months) -->
                <div
                    v-if="healthTrend && healthTrend.length"
                    class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">
                            {{ $t('system.tenants.tabs.health_trend') }}
                        </h4>
                        <span
                            v-if="healthTrend.length"
                            class="text-xs font-bold"
                            :class="
                                healthTrend[healthTrend.length - 1].score >= 80
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : healthTrend[healthTrend.length - 1].score >= 60
                                      ? 'text-amber-600 dark:text-amber-400'
                                      : 'text-rose-600 dark:text-rose-400'
                            "
                        >
                            {{ healthTrend[healthTrend.length - 1].score }}/100
                        </span>
                    </div>
                    <!-- Sparkline: 6 dots, one per month, colored by score band -->
                    <div class="mt-3 flex items-end gap-1.5">
                        <div
                            v-for="point in healthTrend"
                            :key="point.month"
                            class="flex flex-1 flex-col items-center gap-1"
                        >
                            <div
                                class="w-full rounded-t"
                                :class="
                                    point.score >= 80
                                        ? 'bg-emerald-500'
                                        : point.score >= 60
                                          ? 'bg-amber-500'
                                          : 'bg-rose-500'
                                "
                                :style="{
                                    height: Math.max(4, (point.score / 100) * 40) + 'px',
                                }"
                                :title="`${point.month}: ${point.score}/100`"
                            ></div>
                            <span class="text-[9px] text-gray-400">
                                {{ point.month.slice(5) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscriptions -->
            <div v-else-if="active === 'subscriptions'" class="space-y-3">
                <div
                    class="flex items-center justify-between pb-1 border-b border-gray-100 dark:border-gray-800"
                >
                    <p class="text-xs text-gray-500 font-medium">
                        {{ $t('system.tenants.subscriptions_log') }}
                    </p>
                    <button
                        type="button"
                        @click="$emit('assign-plan')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-action-primary hover:bg-action-primary-hover text-white transition-colors shadow-sm"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.tenants.assign_new_plan') }}
                    </button>
                </div>
                <div
                    v-if="!tenant.subscriptions || tenant.subscriptions.length === 0"
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                >
                    {{ $t('system.tenants.tabs.no_subscriptions') }}
                </div>
                <div
                    v-for="sub in tenant.subscriptions || []"
                    :key="sub.id"
                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ sub.plan?.name_ar || sub.plan?.name_en || $t('common.na') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $t('system.tenants.show.yearly') }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="statusBadge(sub.status)"
                        >
                            {{ getStatusLabel(sub.status) }}
                        </span>
                    </div>
                    <div
                        class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300"
                    >
                        <div>
                            <span class="text-gray-500">{{ $t('system.tenants.tabs.from') }}:</span>
                            {{ formatDate(sub.starts_at) }}
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('system.tenants.tabs.to') }}:</span>
                            {{ formatDate(sub.ends_at) }}
                        </div>
                    </div>
                    <div
                        class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex flex-wrap items-center gap-2"
                    >
                        <Link
                            :href="`/system/subscriptions/${sub.id}`"
                            class="px-3 py-1 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            {{ $t('system.actions.view') }}
                        </Link>
                        <button
                            v-if="
                                ['cancelled', 'expired', 'pending', 'pending_payment'].includes(
                                    sub.status
                                )
                            "
                            type="button"
                            @click="activateSub(sub)"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors"
                        >
                            {{ $t('system.subscriptions.activate_subscription') }}
                        </button>
                        <button
                            v-if="['active', 'trial', 'trialing'].includes(sub.status)"
                            type="button"
                            @click="cancelSub(sub)"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors"
                        >
                            {{ $t('system.subscriptions.cancel_subscription') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invoices -->
            <div v-else-if="active === 'invoices'" class="space-y-3">
                <div v-for="sub in tenant.subscriptions || []" :key="sub.id" class="space-y-2">
                    <div
                        v-for="inv in sub.invoices || []"
                        :key="inv.id"
                        class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div>
                            <p
                                class="font-mono text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ inv.invoice_number }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatDate(inv.issued_at) }}
                            </p>
                        </div>
                        <div class="text-end">
                            <p class="font-mono text-sm font-bold text-gray-900 dark:text-white">
                                {{ Number(inv.total).toFixed(2) }} SAR
                            </p>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="statusBadge(inv.status)"
                            >
                                {{ getStatusLabel(inv.status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div
                    v-if="!hasInvoices"
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                >
                    {{ $t('system.tenants.tabs.no_invoices') }}
                </div>
            </div>

            <!-- Payments -->
            <div v-else-if="active === 'payments'" class="space-y-2">
                <div v-for="sub in tenant.subscriptions || []" :key="sub.id">
                    <div v-for="inv in sub.invoices || []" :key="`pay-${inv.id}`">
                        <div
                            v-for="pay in inv.payments || []"
                            :key="pay.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ Number(pay.amount).toFixed(2) }} SAR
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ pay.gateway }} ·
                                    {{ formatDate(pay.paid_at || pay.created_at) }}
                                </p>
                            </div>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="statusBadge(pay.status)"
                            >
                                {{ getStatusLabel(pay.status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div
                    v-if="!hasPayments"
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                >
                    {{ $t('system.tenants.tabs.no_payments') }}
                </div>
            </div>

            <!-- Bank Transfer Receipts -->
            <div v-else-if="active === 'receipts'" class="space-y-2">
                <div
                    v-for="r in bankTransferReceipts || []"
                    :key="r.id"
                    class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div>
                            <p
                                class="font-mono text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ r.transfer_reference }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatDate(r.transfer_date) }} ·
                                {{ Number(r.transfer_amount).toFixed(2) }} SAR
                            </p>
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="statusBadge(r.status)"
                        >
                            {{ getStatusLabel(r.status) }}
                        </span>
                    </div>
                </div>
                <div
                    v-if="!bankTransferReceipts || bankTransferReceipts.length === 0"
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                >
                    {{ $t('system.tenants.tabs.no_receipts') }}
                </div>
            </div>

            <!-- Activity log -->
            <div v-else-if="active === 'activity'" class="space-y-2">
                <div
                    v-for="log in activityLog || []"
                    :key="log.id"
                    class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ log.description || log.action || $t('common.na') }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ formatDateTime(log.created_at) }}
                    </p>
                </div>
                <div
                    v-if="!activityLog || activityLog.length === 0"
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                >
                    {{ $t('system.tenants.tabs.no_activity') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    tenant: { type: Object, required: true },
    bankTransferReceipts: { type: Array, default: () => [] },
    activityLog: { type: Array, default: () => [] },
    recentWorkOrders: { type: Array, default: () => [] },
});

// Phase 5 — health trend. Fetched lazily on mount and cached for the
// page session. The trend is a small array of {month, score} pairs
// the sparkline renders.
const healthTrend = ref([]);

onMounted(async () => {
    try {
        const res = await fetch(
            `/system/analytics/tenants/${props.tenant.id}/health-trend?months=6`,
            { headers: { Accept: 'application/json' } }
        );
        if (res.ok) {
            const data = await res.json();
            healthTrend.value = data.points || [];
        }
    } catch (_) {
        // silent — the chart simply won't render
    }
});

defineEmits(['security', 'assign-plan']);

const { t, locale } = useI18n();
const { confirm } = useConfirm();
const active = ref('overview');

async function activateSub(sub) {
    const ok = await confirm({
        title: t('system.subscriptions.activate_subscription'),
        message: t('system.subscriptions.confirm_activate_message'),
        confirmText: t('system.subscriptions.activate_subscription'),
        cancelText: t('common.cancel'),
        type: 'success',
    });
    if (ok) {
        router.post(`/system/subscriptions/${sub.id}/activate`, {}, { preserveScroll: true });
    }
}

async function cancelSub(sub) {
    const ok = await confirm({
        title: t('system.subscriptions.cancel_subscription'),
        message: t('system.subscriptions.confirm_cancel_message'),
        confirmText: t('system.subscriptions.cancel_subscription'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.post(`/system/subscriptions/${sub.id}/cancel`, {}, { preserveScroll: true });
    }
}

// Tabs config — count-driven badges so the admin sees at a glance how
// many subscriptions/invoices/etc. are on the tenant without clicking.
// Internal key values remain completely unchanged.
const tabs = computed(() => [
    { key: 'overview', label: t('system.tenants.tabs.overview'), icon: '👁' },
    {
        key: 'subscriptions',
        label: t('system.tenants.tabs.subscriptions'),
        icon: '◐',
        badge: (props.tenant.subscriptions || []).length || null,
    },
    {
        key: 'invoices',
        label: t('system.tenants.tabs.invoices'),
        icon: '🧾',
        badge: invoiceCount.value || null,
    },
    {
        key: 'payments',
        label: t('system.tenants.tabs.payments'),
        icon: '$',
        badge: paymentCount.value || null,
    },
    {
        key: 'receipts',
        label: t('system.tenants.tabs.receipts'),
        icon: '📎',
        badge: (props.bankTransferReceipts || []).length || null,
    },
    {
        key: 'activity',
        label: t('system.tenants.tabs.activity'),
        icon: '⏱',
        badge: (props.activityLog || []).length || null,
    },
]);

const invoiceCount = computed(() => {
    let count = 0;
    for (const sub of props.tenant.subscriptions || []) {
        count += (sub.invoices || []).length;
    }
    return count;
});

const paymentCount = computed(() => {
    let count = 0;
    for (const sub of props.tenant.subscriptions || []) {
        for (const inv of sub.invoices || []) {
            count += (inv.payments || []).length;
        }
    }
    return count;
});

const hasInvoices = computed(() => invoiceCount.value > 0);
const hasPayments = computed(() => paymentCount.value > 0);

const overviewFields = computed(() => ({
    [t('system.tenants.name')]: props.tenant.trade_name || props.tenant.name || t('common.na'),
    [t('system.tenants.status')]: getStatusLabel(props.tenant.status),
    [t('system.tenants.email')]: props.tenant.email || t('common.na'),
    [t('system.tenants.phone')]: props.tenant.phone || t('common.na'),
    [t('system.tenants.legal_name')]: props.tenant.legal_name || t('common.na'),
    [t('system.tenants.show.cr_number')]: props.tenant.cr_number || t('common.na'),
}));

function getStatusLabel(status) {
    if (!status) return t('common.na');
    const key = `system.status.${status}`;
    return t(key) !== key ? t(key) : status;
}

function getWorkOrderStatusLabel(status) {
    if (!status) return t('common.na');
    const key = `system.work_orders.status.${status}`;
    return t(key) !== key ? t(key) : status;
}

function formatDate(dateStr) {
    if (!dateStr) return t('common.na');
    const loc = locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US';
    return new Date(dateStr).toLocaleDateString(loc, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(dateStr) {
    if (!dateStr) return t('common.na');
    const loc = locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US';
    return new Date(dateStr).toLocaleString(loc, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Color-coded status badge. Keeps the visual language consistent with
// the rest of the system (emerald = paid, amber = pending, rose = bad).
function statusBadge(status) {
    const s = String(status).toLowerCase();
    if (['paid', 'approved', 'active', 'completed'].includes(s))
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    if (['pending', 'pending_payment', 'pending_review', 'trialing', 'expiring'].includes(s))
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    if (['rejected', 'cancelled', 'expired', 'overdue', 'failed', 'suspended'].includes(s))
        return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
    return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}
</script>
