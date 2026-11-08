<template>
    <div class="space-y-6">
        <!-- KPI grid — 6 cards, 3 columns on desktop -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- MRR -->
            <KpiCard
                :label="locale === 'ar' ? $t('system.analytics.mrr_label') : 'MRR'"
                :value="formatCurrency(kpis?.mrr) + ' SAR'"
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
                :value="(kpis?.churn_rate || 0) + '%'"
                :sub="churnSub"
                :trend="churnTrend"
                :trend-inverse="true"
                color="rose"
            />

            <!-- New Tenants -->
            <KpiCard
                :label="locale === 'ar' ? $t('system.analytics.new_tenants') : 'New tenants'"
                :value="kpis?.new_tenants_30d || 0"
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
                :value="formatCurrency(kpis?.outstanding_receivables) + ' SAR'"
                :sub="
                    locale === 'ar'
                        ? $t('system.analytics.sum_of_unpaid_invoices')
                        : 'Sum of unpaid invoices'
                "
                color="amber"
            />

            <!-- Trial Tenants -->
            <KpiCard
                :label="locale === 'ar' ? $t('system.analytics.trial_tenants') : 'Trial tenants'"
                :value="kpis?.trial_tenants || 0"
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
                    locale === 'ar' ? $t('system.analytics.last_month_churn') : 'Last month churn'
                "
                :value="(kpis?.churn_last_month || 0) + '%'"
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
                        class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
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
                        v-for="plan in topPlans"
                        :key="plan.id"
                        class="flex items-center justify-between rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400"
                            >
                                #{{ plan.rank }}
                            </span>
                            <span
                                class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ plan.name }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="font-mono text-sm font-bold text-gray-900 dark:text-white">
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import KpiCard from '@/Components/System/KpiCard.vue';

const { t } = useI18n();

const props = defineProps({
    kpis: {
        type: Object,
        default: () => ({}),
    },
});

const locale = ref('ar');
onMounted(() => {
    locale.value = document?.documentElement?.lang || 'ar';
});

const topPlans = ref([]);
const paymentMix = ref([]);

const churnDelta = computed(() => {
    return Number(((props.kpis?.churn_rate || 0) - (props.kpis?.churn_last_month || 0)).toFixed(1));
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

const mrrTrend = ref(null);

onMounted(async () => {
    try {
        const [plansRes, mixRes] = await Promise.all([
            fetch('/system/analytics/top-plans', { headers: { Accept: 'application/json' } }),
            fetch('/system/analytics/payment-mix', { headers: { Accept: 'application/json' } }),
        ]);
        if (plansRes.ok) topPlans.value = (await plansRes.json()).plans || [];
        if (mixRes.ok) paymentMix.value = (await mixRes.json()).mix || [];
    } catch (_) {
        // Handled silently
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
        moyasar: 'bg-indigo-500',
        tap: 'bg-blue-500',
        paytabs: 'bg-cyan-500',
        tabby: 'bg-emerald-500',
        tamara: 'bg-pink-500',
        bank_transfer: 'bg-amber-500',
    };
    return map[g] || 'bg-gray-500';
}
</script>
