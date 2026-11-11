<template>
    <SystemLayout>
        <Head
            :title="
                locale === 'ar' ? $t('system.analytics.tenant_analytics') : 'Customer analytics'
            "
        />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.analytics.tenant_analytics')"
                :subtitle="$t('system.analytics.kpis_subtitle')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- KPI grid — 6 cards, 3 columns on desktop -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- MRR -->
                <KpiCard
                    :label="locale === 'ar' ? $t('system.analytics.mrr_label') : 'MRR'"
                    :value="formatCurrency(kpis.mrr) + ' SAR'"
                    :sub="
                        locale === 'ar'
                            ? $t('system.analytics.sum_of_active_subscriptions_yearly_12')
                            : 'Sum of active subscriptions (yearly ÷ 12)'
                    "
                    :trend="mrrTrend"
                    color="emerald"
                />

                <!-- Churn -->
                <KpiCard
                    :label="locale === 'ar' ? $t('system.analytics.churn_rate') : 'Churn rate'"
                    :value="kpis.churn_rate + '%'"
                    :sub="churnSub"
                    :trend="churnTrend"
                    :trend-inverse="true"
                    color="rose"
                />

                <!-- New Tenants -->
                <KpiCard
                    :label="locale === 'ar' ? $t('system.analytics.new_tenants') : 'New tenants'"
                    :value="kpis.new_tenants_30d"
                    :sub="locale === 'ar' ? $t('system.analytics.last_30_days') : 'Last 30 days'"
                    color="indigo"
                />

                <!-- Outstanding Receivables -->
                <KpiCard
                    :label="
                        locale === 'ar'
                            ? $t('system.analytics.outstanding_receivables')
                            : 'Outstanding receivables'
                    "
                    :value="formatCurrency(kpis.outstanding_receivables) + ' SAR'"
                    :sub="
                        locale === 'ar'
                            ? $t('system.analytics.sum_of_unpaid_invoices')
                            : 'Sum of unpaid invoices'
                    "
                    color="amber"
                />

                <!-- Trial Tenants -->
                <KpiCard
                    :label="
                        locale === 'ar' ? $t('system.analytics.trial_tenants') : 'Trial tenants'
                    "
                    :value="kpis.trial_tenants"
                    :sub="
                        locale === 'ar'
                            ? $t('system.analytics.near-term_mrr_signal')
                            : 'Near-term MRR signal'
                    "
                    color="blue"
                />

                <!-- Last Month Churn (for trend) -->
                <KpiCard
                    :label="
                        locale === 'ar'
                            ? $t('system.analytics.last_month_churn')
                            : 'Last month churn'
                    "
                    :value="kpis.churn_last_month + '%'"
                    :sub="
                        locale === 'ar'
                            ? $t('system.analytics.for_comparison_with_current_month')
                            : 'For comparison with current month'
                    "
                    color="slate"
                />
            </div>

            <!-- Top plans + payment mix side-by-side -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Top plans -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                        {{ $t('system.analytics.best_plans') }}
                    </h2>
                    <div
                        v-if="!topPlans.length"
                        class="mt-4 flex flex-col items-center justify-center p-6 text-center rounded-xl bg-gray-50/60 dark:bg-gray-700/30 border border-dashed border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="w-10 h-10 rounded-full bg-selection-bg text-action-primary flex items-center justify-center mb-2"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            {{
                                locale === 'ar'
                                    ? $t('system.analytics.no_active_plans_found')
                                    : 'No active plans found'
                            }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 max-w-xs">
                            {{
                                locale === 'ar'
                                    ? $t(
                                          'system.analytics.create_plans_to_track_their_subscriber_counts'
                                      )
                                    : 'Create plans to track their subscriber counts'
                            }}
                        </p>
                        <Link
                            :href="route('system.plans.index')"
                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-action-primary hover:underline"
                        >
                            <span>
                                {{
                                    locale === 'ar'
                                        ? $t('system.analytics.manage_plans')
                                        : 'Manage Plans'
                                }}
                            </span>
                            <svg
                                class="w-3.5 h-3.5 rtl:rotate-180"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </Link>
                    </div>
                    <ul v-else class="mt-4 space-y-2">
                        <li
                            v-for="(plan, idx) in topPlans"
                            :key="plan.id"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-selection-bg text-xs font-bold text-selection-text"
                                >
                                    {{ idx + 1 }}
                                </span>
                                <span
                                    class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ plan.name }}
                                </span>
                            </div>
                            <div class="text-right">
                                <p
                                    class="font-mono text-sm font-bold text-gray-900 dark:text-white"
                                >
                                    {{ plan.active_subscribers }}
                                </p>
                                <p class="text-[10px] text-gray-500">
                                    {{ formatCurrency(plan.mrr) }} SAR/mo
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Payment mix -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                        {{ $t('system.analytics.payment_methods_distribution') }}
                    </h2>
                    <div
                        v-if="!paymentMix.length"
                        class="mt-4 flex flex-col items-center justify-center p-6 text-center rounded-xl bg-gray-50/60 dark:bg-gray-700/30 border border-dashed border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-2"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            {{
                                locale === 'ar'
                                    ? $t('system.analytics.no_payment_data_yet')
                                    : 'No payment data yet'
                            }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 max-w-xs">
                            {{
                                locale === 'ar'
                                    ? $t(
                                          'system.analytics.payment_breakdown_will_appear_as_tenants_pay_inv'
                                      )
                                    : 'Payment breakdown will appear as tenants pay invoices'
                            }}
                        </p>
                    </div>
                    <ul v-else class="mt-4 space-y-3">
                        <li v-for="row in paymentMix" :key="row.gateway" class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ gatewayLabel(row.gateway) }}
                                </span>
                                <span class="font-mono text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(row.total) }} SAR ({{ row.percentage }}%)
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                <div
                                    :class="gatewayColor(row.gateway)"
                                    class="h-2 rounded-full transition-all"
                                    :style="{ width: row.percentage + '%' }"
                                ></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import KpiCard from '@/Components/System/KpiCard.vue';

const props = defineProps({
    kpis: {
        type: Object,
        required: true,
    },
});

// Read the locale inside `onMounted` rather than at setup time, so
// `document.documentElement` is guaranteed to exist. The `ref` is
// used (not `computed`) because the script also reads `locale.value`
// directly in `gatewayLabel`, and Vue 3 templates auto-unwrap a ref
// inside `===` and ternary expressions (the rule that bites us is
// `||`/`&&` short-circuits — those need `locale.value`).
const locale = ref('ar');
onMounted(() => {
    locale.value = document?.documentElement?.lang || 'ar';
});

const topPlans = ref([]);
const paymentMix = ref([]);

// ── Trend computation ─────────────────────────────────────────────
// Churn is "lower is better" so a positive delta (current > last)
// is bad — flagged with trend-inverse to flip the color.
const churnDelta = computed(() => {
    return Number((props.kpis.churn_rate - props.kpis.churn_last_month).toFixed(1));
});

const churnTrend = computed(() => churnDelta.value);

const churnSub = computed(() => {
    const d = churnDelta.value;
    if (d === 0) {
        return t('system.analytics.stable_vs_last_month');
    }
    if (d > 0) {
        return locale.value === 'ar' ? t('system.analytics.rose') : `Up ${d}% vs last month`;
    }
    return locale.value === 'ar'
        ? t('system.analytics.fell')
        : `Down ${Math.abs(d)}% vs last month`;
});

// MRR trend — only show a number if we have a historical signal
// (Phase 5.4 will add proper time-series; for now we leave it null
// to avoid showing misleading fake arrows).
const mrrTrend = ref(null);

// ── Fetch the side panels lazily after the KPI cards render ──────
onMounted(async () => {
    try {
        const [plansRes, mixRes] = await Promise.all([
            fetch('/system/analytics/top-plans', { headers: { Accept: 'application/json' } }),
            fetch('/system/analytics/payment-mix', { headers: { Accept: 'application/json' } }),
        ]);
        if (plansRes.ok) topPlans.value = (await plansRes.json()).plans || [];
        if (mixRes.ok) paymentMix.value = (await mixRes.json()).mix || [];
    } catch (_) {
        // Charts will just show empty states — the KPI cards above
        // are the critical content and they came via Inertia props.
    }
});

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        maximumFractionDigits: 0,
    }).format(amount || 0);
}

function gatewayLabel(g) {
    const map = {
        moyasar: 'Moyasar',
        tap: 'Tap',
        paytabs: 'PayTabs',
        tabby: 'Tabby',
        tamara: 'Tamara',
        bank_transfer: t('invoices.credit_note.methods.bank_transfer'),
    };
    return map[g] || g;
}

function gatewayColor(g) {
    const map = {
        moyasar: 'bg-action-primary',
        tap: 'bg-blue-500',
        paytabs: 'bg-cyan-500',
        tabby: 'bg-emerald-500',
        tamara: 'bg-pink-500',
        bank_transfer: 'bg-amber-500',
    };
    return map[g] || 'bg-gray-500';
}
</script>
