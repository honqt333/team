<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    receipts: { type: Object, required: true }, // paginated
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const locale = computed(() => document.documentElement.lang || 'ar');
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'pending');

const statusColors = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
};

const statusLabels = {
    pending: { ar: 'قيد المراجعة', en: 'Pending' },
    approved: { ar: 'موافق عليه', en: 'Approved' },
    rejected: { ar: 'مرفوض', en: 'Rejected' },
};

const statusOptions = computed(() => [
    { value: 'all', label: locale.value === 'ar' ? 'جميع الحالات' : 'All statuses' },
    { value: 'pending', label: locale.value === 'ar' ? 'قيد المراجعة' : 'Pending' },
    { value: 'approved', label: locale.value === 'ar' ? 'موافق عليه' : 'Approved' },
    { value: 'rejected', label: locale.value === 'ar' ? 'مرفوض' : 'Rejected' },
]);

function applyFilters() {
    router.get(
        '/system/bank-transfer-receipts',
        {
            search: search.value,
            status: status.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}

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

// Heuristic: receipt_path has no extension info, but the original
// Laravel upload keeps the original filename's extension in the
// storage path. Check if the extension is one of the image formats.
function isImage(receipt) {
    if (!receipt?.receipt_path) return false;
    const path = receipt.receipt_path.toLowerCase();
    return /\.(jpe?g|png|webp|gif|bmp)$/.test(path);
}

// Returns the visual state for the amount-comparison badge shown next to
// each receipt in the list. Three outcomes: match (green), short (amber),
// over (orange), or unknown when the receipt has no payment to compare
// against (legacy / orphaned data — kept visible so the admin can spot
// them but rendered neutrally).
function getAmountBadge(receipt) {
    if (receipt.amount_diff === null || receipt.amount_diff === undefined) {
        return { color: 'slate', label: locale.value === 'ar' ? 'لا مقارنة' : 'No comparison' };
    }
    const diff = Number(receipt.amount_diff);
    if (Math.abs(diff) < 0.01) {
        return {
            color: 'emerald',
            label: locale.value === 'ar' ? '✓ مطابق' : '✓ Match',
        };
    }
    if (diff < 0) {
        return {
            color: 'amber',
            label:
                locale.value === 'ar'
                    ? `⚠️ ناقص ${formatCurrency(-diff)}`
                    : `⚠️ Short ${formatCurrency(-diff)}`,
        };
    }
    return {
        color: 'orange',
        label: locale.value === 'ar' ? `+ ${formatCurrency(diff)}` : `+ ${formatCurrency(diff)}`,
    };
}
</script>

<template>
    <Head
        :title="locale === 'ar' ? 'مراجعة إيصالات التحويل البنكي' : 'Bank Transfer Receipts Review'"
    />

    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{
                            locale === 'ar'
                                ? 'مراجعة إيصالات التحويل البنكي'
                                : 'Bank Transfer Receipts Review'
                        }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{
                            locale === 'ar'
                                ? 'مراجعة وتأكيد إيصالات التحويل البنكي للمستأجرين'
                                : 'Review and verify bank transfer receipts from tenants'
                        }}
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300"
                    >
                        {{ locale === 'ar' ? 'قيد المراجعة' : 'Pending' }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">
                        {{ stats.pending }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/50 dark:bg-emerald-900/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
                    >
                        {{ locale === 'ar' ? 'موافق عليها' : 'Approved' }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                        {{ stats.approved }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/50 dark:bg-rose-900/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300"
                    >
                        {{ locale === 'ar' ? 'مرفوضة' : 'Rejected' }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-300">
                        {{ stats.rejected }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800/50 dark:bg-indigo-900/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"
                    >
                        {{ locale === 'ar' ? 'مبالغ قيد المراجعة' : 'Pending amount' }}
                    </p>
                    <p class="mt-1 text-xl font-bold text-indigo-700 dark:text-indigo-300">
                        {{ formatCurrency(stats.total_amount_pending) }}
                        <span class="text-sm font-normal">SAR</span>
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center"
            >
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="
                            locale === 'ar' ? 'ابحث باسم المستأجر...' : 'Search by tenant name...'
                        "
                        @keyup.enter="applyFilters"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                </div>
                <div class="w-full sm:w-52">
                    <SearchableSelect
                        v-model="status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :compact="true"
                        @change="applyFilters"
                    />
                </div>
            </div>

            <!-- Receipts table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'المستأجر' : 'Tenant' }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'الباقة' : 'Plan' }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'المبلغ' : 'Amount' }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'تاريخ التحويل' : 'Transfer date' }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'مرفوع' : 'Submitted' }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'الحالة' : 'Status' }}
                            </th>
                            <th
                                class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? 'إجراءات' : 'Actions' }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-if="!receipts.data?.length">
                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{ locale === 'ar' ? 'لا توجد إيصالات' : 'No receipts' }}
                            </td>
                        </tr>
                        <tr
                            v-for="r in receipts.data"
                            :key="r.id"
                            class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <!-- Thumbnail of the uploaded receipt -->
                                    <div
                                        v-if="r.receipt_path"
                                        class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <img
                                            v-if="isImage(r)"
                                            :src="`/storage/${r.receipt_path}`"
                                            :alt="`receipt-${r.id}`"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-gray-400"
                                        >
                                            <svg
                                                class="h-5 w-5"
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
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            {{ r.tenant?.trade_name || r.tenant?.name || '—' }}
                                        </p>
                                        <p
                                            v-if="r.transfer_reference"
                                            class="mt-0.5 font-mono text-xs text-gray-500"
                                        >
                                            {{ r.transfer_reference }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {{
                                    locale === 'ar'
                                        ? r.subscription?.plan?.name_ar
                                        : r.subscription?.plan?.name_en
                                }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white"
                            >
                                {{ formatCurrency(r.transfer_amount) }} SAR
                                <div
                                    v-if="getAmountBadge(r).color !== 'slate'"
                                    :class="`mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-bold ${
                                        getAmountBadge(r).color === 'emerald'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                            : getAmountBadge(r).color === 'amber'
                                              ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                              : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                    }`"
                                >
                                    {{ getAmountBadge(r).label }}
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300"
                            >
                                {{ formatDate(r.transfer_date) }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300"
                            >
                                {{ formatDate(r.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold',
                                        statusColors[r.status],
                                    ]"
                                >
                                    {{
                                        locale === 'ar'
                                            ? statusLabels[r.status]?.ar
                                            : statusLabels[r.status]?.en
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <Link
                                    :href="`/system/bank-transfer-receipts/${r.id}`"
                                    class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                                >
                                    {{ locale === 'ar' ? 'مراجعة' : 'Review' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="receipts.links" class="flex flex-wrap items-center justify-center gap-1">
                <Link
                    v-for="link in receipts.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'rounded border px-3 py-1 text-xs',
                        link.active
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        !link.url && 'cursor-not-allowed opacity-50',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </SystemLayout>
</template>
