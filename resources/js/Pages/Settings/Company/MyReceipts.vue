<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    receipts: { type: Object, required: true }, // paginated
});

const locale = computed(() => document.documentElement.lang || 'ar');

const statusConfig = {
    pending: {
        ar: 'قيد المراجعة',
        en: 'Under review',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    },
    approved: {
        ar: 'موافق عليه',
        en: 'Approved',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    },
    rejected: {
        ar: 'مرفوض',
        en: 'Rejected',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    },
    superseded: {
        ar: 'تم تجاوزه',
        en: 'Superseded',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    },
};

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(amount || 0);
}
</script>

<template>
    <Head :title="locale === 'ar' ? 'إيصالاتي' : 'My receipts'" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                            {{
                                locale === 'ar'
                                    ? 'إيصالات التحويل البنكي'
                                    : 'Bank transfer receipts'
                            }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{
                                locale === 'ar'
                                    ? 'جميع إيصالات التحويل البنكي التي رفعتها لمراجعة الإدارة'
                                    : 'All bank transfer receipts you have uploaded for admin review'
                            }}
                        </p>
                    </div>
                    <Link
                        href="/app/settings/billing"
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
                        {{ locale === 'ar' ? 'العودة للاشتراك' : 'Back to subscription' }}
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="!receipts.data?.length"
                class="rounded-2xl border border-slate-200 bg-white p-12 text-center dark:border-slate-700 dark:bg-slate-800"
            >
                <svg
                    class="mx-auto h-12 w-12 text-slate-400"
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
                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'لا توجد إيصالات' : 'No receipts' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{
                        locale === 'ar'
                            ? 'ستظهر هنا جميع إيصالات التحويل البنكي التي ترفعها'
                            : 'Any bank transfer receipts you upload will appear here'
                    }}
                </p>
            </div>

            <!-- Receipts list -->
            <div v-else class="space-y-3">
                <div
                    v-for="r in receipts.data"
                    :key="r.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{
                                        locale === 'ar'
                                            ? r.subscription?.plan?.name_ar
                                            : r.subscription?.plan?.name_en
                                    }}
                                </p>
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-bold',
                                        statusConfig[r.status]?.class,
                                    ]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                                    ></span>
                                    {{
                                        locale === 'ar'
                                            ? statusConfig[r.status]?.ar
                                            : statusConfig[r.status]?.en
                                    }}
                                </span>
                            </div>
                            <div
                                class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400"
                            >
                                <span class="font-mono">
                                    {{ formatDate(r.transfer_date) }}
                                </span>
                                <span
                                    class="font-mono font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    {{ formatCurrency(r.transfer_amount) }} SAR
                                </span>
                                <span v-if="r.bank_name">
                                    {{ r.bank_name }}
                                </span>
                                <span v-if="r.transfer_reference" class="font-mono">
                                    #{{ r.transfer_reference }}
                                </span>
                            </div>
                            <p
                                v-if="r.review_notes"
                                class="mt-2 rounded bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-900/50 dark:text-slate-300"
                            >
                                <strong class="text-slate-700 dark:text-slate-200">
                                    {{ locale === 'ar' ? 'ملاحظات الإدارة:' : 'Admin notes:' }}
                                </strong>
                                {{ r.review_notes }}
                            </p>
                        </div>
                        <a
                            v-if="r.receipt_path"
                            :href="`/storage/${r.receipt_path}`"
                            target="_blank"
                            class="inline-flex items-center gap-1 self-start rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            {{ locale === 'ar' ? 'عرض الإيصال' : 'View receipt' }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="receipts.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <Link
                    v-for="link in receipts.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'rounded border px-3 py-1 text-xs',
                        link.active
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                        !link.url && 'cursor-not-allowed opacity-50',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
