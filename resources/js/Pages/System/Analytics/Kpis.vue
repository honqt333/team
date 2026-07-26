<template>
    <SystemLayout>
        <Head :title="locale === 'ar' ? 'تحليلات المستأجرين' : 'Customer analytics'" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ locale === 'ar' ? 'تحليلات المستأجرين' : 'Customer analytics' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        locale === 'ar'
                            ? 'مؤشرات الأداء الرئيسية للدفع والاشتراكات (آخر 30 يوم)'
                            : 'Headline KPIs for billing & subscriptions (last 30 days)'
                    }}
                </p>
            </div>

            <!-- KPI grid — 6 cards, 3 columns on desktop -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- MRR -->
                <KpiCard
                    :label="locale === 'ar' ? 'الإيراد الشهري المتكرر' : 'MRR'"
                    :value="formatCurrency(kpis.mrr) + ' SAR'"
                    :sub="
                        locale === 'ar'
                            ? 'مجموع الاشتراكات النشطة (شهري / 12 سنوياً)'
                            : 'Sum of active subscriptions (yearly ÷ 12)'
                    "
                    :trend="mrrTrend"
                    color="emerald"
                />

                <!-- Churn -->
                <KpiCard
                    :label="locale === 'ar' ? 'معدل الإلغاء' : 'Churn rate'"
                    :value="kpis.churn_rate + '%'"
                    :sub="churnSub"
                    :trend="churnTrend"
                    :trend-inverse="true"
                    color="rose"
                />

                <!-- New Tenants -->
                <KpiCard
                    :label="locale === 'ar' ? 'مستأجرين جدد' : 'New tenants'"
                    :value="kpis.new_tenants_30d"
                    :sub="locale === 'ar' ? 'آخر 30 يوم' : 'Last 30 days'"
                    color="indigo"
                />

                <!-- Outstanding Receivables -->
                <KpiCard
                    :label="locale === 'ar' ? 'المستحقات المعلقة' : 'Outstanding receivables'"
                    :value="formatCurrency(kpis.outstanding_receivables) + ' SAR'"
                    :sub="
                        locale === 'ar' ? 'مجموع الفواتير غير المدفوعة' : 'Sum of unpaid invoices'
                    "
                    color="amber"
                />

                <!-- Trial Tenants -->
                <KpiCard
                    :label="locale === 'ar' ? 'في فترة التجربة' : 'Trial tenants'"
                    :value="kpis.trial_tenants"
                    :sub="locale === 'ar' ? 'إشارة لإيراد قريب' : 'Near-term MRR signal'"
                    color="blue"
                />

                <!-- Last Month Churn (for trend) -->
                <KpiCard
                    :label="locale === 'ar' ? 'إلغاء الشهر الماضي' : 'Last month churn'"
                    :value="kpis.churn_last_month + '%'"
                    :sub="
                        locale === 'ar'
                            ? 'للمقارنة بالشهر الحالي'
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
                        {{ locale === 'ar' ? 'أفضل الباقات' : 'Top plans' }}
                    </h2>
                    <div v-if="!topPlans.length" class="mt-6 text-center text-sm text-gray-500">
                        —
                    </div>
                    <ul v-else class="mt-4 space-y-2">
                        <li
                            v-for="(plan, idx) in topPlans"
                            :key="plan.id"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300"
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
                        {{
                            locale === 'ar'
                                ? 'توزيع طرق الدفع (90 يوم)'
                                : 'Payment method mix (90d)'
                        }}
                    </h2>
                    <div v-if="!paymentMix.length" class="mt-6 text-center text-sm text-gray-500">
                        —
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
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
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
        return locale.value === 'ar' ? 'مستقر مقارنة بالشهر الماضي' : 'Stable vs last month';
    }
    if (d > 0) {
        return locale.value === 'ar' ? `ارتفع ${d}% عن الشهر الماضي` : `Up ${d}% vs last month`;
    }
    return locale.value === 'ar'
        ? `انخفض ${Math.abs(d)}% عن الشهر الماضي`
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
        bank_transfer: locale.value === 'ar' ? 'تحويل بنكي' : 'Bank transfer',
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
