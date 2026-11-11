<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();

const props = defineProps({
    receipts: { type: Object, required: true }, // paginated
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const locale = computed(() => document.documentElement.lang || 'ar');

const { filters: tableFilters, applyFilters } = useServerTable({
    routeUrl: '/system/bank-transfer-receipts',
    initialFilters: {
        search: props.filters.search || '',
        status: props.filters.status || 'pending',
    },
});

const statusColors = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
};

const statusOptions = computed(() => [
    {
        value: 'all',
        label: t('system.bank_transfer_receipts.all_statuses'),
    },
    {
        value: 'pending',
        label: t('system.bank_transfer_receipts.pending'),
    },
    {
        value: 'approved',
        label: t('system.bank_transfer_receipts.approved_2'),
    },
    {
        value: 'rejected',
        label: t('system.bank_transfer_receipts.rejected_2'),
    },
]);

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
        return {
            color: 'slate',
            label:
                locale.value === 'ar'
                    ? t('system.bank_transfer_receipts.no_comparison')
                    : 'No comparison',
        };
    }
    const diff = Number(receipt.amount_diff);
    if (Math.abs(diff) < 0.01) {
        return {
            color: 'emerald',
            label: locale.value === 'ar' ? t('system.bank_transfer_receipts.match_2') : '✓ Match',
        };
    }
    if (diff < 0) {
        return {
            color: 'amber',
            label:
                locale.value === 'ar'
                    ? t('system.bank_transfer_receipts.short_by', {
                          amount: formatCurrency(-diff),
                      })
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
        :title="
            locale === 'ar'
                ? $t('system.bank_transfer_receipts.index_title')
                : 'Bank Transfer Receipts Review'
        "
    />

    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.bank_transfer_receipts.index_title')"
                :subtitle="$t('system.bank_transfer_receipts.subtitle')"
            >
                <template #icon>
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                </template>
                <template #badge>
                    <span
                        v-if="receipts?.total !== undefined"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-100 dark:border-amber-800"
                    >
                        {{ receipts.total }}
                    </span>
                </template>
            </PageHeader>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/10"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300"
                    >
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.pending')
                                : 'Pending'
                        }}
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
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.approved')
                                : 'Approved'
                        }}
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
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.rejected')
                                : 'Rejected'
                        }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-300">
                        {{ stats.rejected }}
                    </p>
                </div>
                <div class="rounded-xl border border-action-primary/25 bg-selection-bg p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-selection-text">
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.pending_amount')
                                : 'Pending amount'
                        }}
                    </p>
                    <p class="mt-1 text-xl font-bold text-selection-text">
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
                        v-model="tableFilters.search"
                        type="search"
                        :placeholder="
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.search_by_tenant_name')
                                : 'Search by tenant name...'
                        "
                        @keyup.enter="applyFilters"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-action-primary focus:ring-1 focus:ring-focus-ring dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                </div>
                <div class="w-full sm:w-52">
                    <SearchableSelect
                        v-model="tableFilters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :compact="true"
                        @update:model-value="applyFilters"
                    />
                </div>
            </div>

            <!-- Receipts table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <DataTable structured class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.tenant')
                                        : 'Tenant'
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.plan')
                                        : 'Plan'
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.amount')
                                        : 'Amount'
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.transfer_date')
                                        : 'Transfer date'
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.submitted')
                                        : 'Submitted'
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{ locale === 'ar' ? $t('common.status') : 'Status' }}
                            </th>
                            <th
                                class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-gray-500"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.actions')
                                        : 'Actions'
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-if="!receipts.data?.length">
                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.no_receipts')
                                        : 'No receipts'
                                }}
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
                                            :src="r.receipt_url"
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
                                    class="rounded-lg bg-action-primary px-3 py-1 text-xs font-semibold text-white hover:bg-action-primary-hover"
                                >
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.review')
                                            : 'Review'
                                    }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>

            <!-- Pagination -->
            <AppPagination
                :links="receipts.links || []"
                :from="receipts.from"
                :to="receipts.to"
                :total="receipts.total"
            />
        </div>
    </SystemLayout>
</template>
