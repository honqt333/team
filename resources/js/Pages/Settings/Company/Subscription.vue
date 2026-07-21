<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SubscriptionCard from '@/Components/Subscription/SubscriptionCard.vue';
import PlanCard from '@/Components/Subscription/PlanCard.vue';
import SubscribeModal from '@/Components/Subscription/SubscribeModal.vue';
import SubscriptionHistoryTable from '@/Components/Subscription/SubscriptionHistoryTable.vue';

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
    return props.current_subscription?.plan_id;
});

function openSubscribe(plan) {
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

const statusConfig = {
    paid: {
        label_ar: 'مدفوع',
        label_en: 'Paid',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    },
    pending: {
        label_ar: 'معلق',
        label_en: 'Pending',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    },
    failed: {
        label_ar: 'فشل',
        label_en: 'Failed',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    },
    refunded: {
        label_ar: 'مسترجع',
        label_en: 'Refunded',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    },
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

const gatewayLabels = {
    moyasar: { ar: 'مُيَسَّر', en: 'Moyasar' },
    tap: { ar: 'تاب', en: 'Tap' },
    tabby: { ar: 'تابي', en: 'Tabby' },
    tamara: { ar: 'تمارا', en: 'Tamara' },
    bank_transfer: { ar: 'تحويل بنكي', en: 'Bank Transfer' },
};
</script>

<template>
    <Head :title="locale === 'ar' ? 'إدارة الاشتراك' : 'Subscription Management'" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ locale === 'ar' ? 'إدارة الاشتراك' : 'Subscription Management' }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{
                                locale === 'ar'
                                    ? 'إدارة باقتك، طرق الدفع، وسجل الفواتير'
                                    : 'Manage your plan, payment methods, and invoice history'
                            }}
                        </p>
                    </div>
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
                        {{ locale === 'ar' ? 'العودة للإعدادات' : 'Back to settings' }}
                    </a>
                </div>
            </div>

            <!-- Current subscription card -->
            <SubscriptionCard :subscription="current_subscription" />

            <!-- Available plans -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'الباقات المتاحة' : 'Available plans' }}
                </h2>
                <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                    {{
                        locale === 'ar'
                            ? 'اختر الباقة المناسبة لاحتياجات مركزك'
                            : "Choose the plan that fits your center's needs"
                    }}
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <PlanCard
                        v-for="plan in available_plans"
                        :key="plan.id"
                        :plan="plan"
                        :is-current="currentPlanId === plan.id"
                        @subscribe="openSubscribe"
                    />
                </div>
            </div>

            <!-- Subscription history -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <h2 class="mb-1 text-base font-bold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'سجل الاشتراكات' : 'Subscription history' }}
                </h2>
                <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                    {{
                        locale === 'ar'
                            ? 'جميع اشتراكاتك السابقة والحالية'
                            : 'All your past and current subscriptions'
                    }}
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
                    {{ locale === 'ar' ? 'إيصالات قيد المراجعة' : 'Receipts under review' }}
                </h2>
                <p class="mb-5 text-xs text-amber-700/80 dark:text-amber-300/80">
                    {{
                        locale === 'ar'
                            ? 'هذه الإيصالات تم رفعها وفي انتظار موافقة الإدارة'
                            : 'These receipts have been uploaded and are awaiting admin approval'
                    }}
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
                            {{ locale === 'ar' ? 'قيد المراجعة' : 'Pending' }}
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
                    {{ locale === 'ar' ? 'آخر محاولات الدفع' : 'Recent payment attempts' }}
                </h2>
                <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">
                    {{
                        locale === 'ar'
                            ? 'سجل بآخر 10 محاولات دفع للاشتراك'
                            : 'Last 10 payment attempts for your subscription'
                    }}
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
                                    {{ locale === 'ar' ? 'التاريخ' : 'Date' }}
                                </th>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ locale === 'ar' ? 'البوابة' : 'Gateway' }}
                                </th>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ locale === 'ar' ? 'المبلغ' : 'Amount' }}
                                </th>
                                <th
                                    class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ locale === 'ar' ? 'الحالة' : 'Status' }}
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
                                    {{
                                        gatewayLabels[payment.gateway]?.[
                                            locale === 'ar' ? 'ar' : 'en'
                                        ] || payment.gateway
                                    }}
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
                                            statusConfig[payment.status]?.class,
                                        ]"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                                        ></span>
                                        {{
                                            locale === 'ar'
                                                ? statusConfig[payment.status]?.label_ar
                                                : statusConfig[payment.status]?.label_en
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
