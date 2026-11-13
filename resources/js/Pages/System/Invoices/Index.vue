<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.invoices.title')"
                :subtitle="$t('system.invoices.subtitle')"
                :totalCount="invoices?.total != null ? toEnglish(invoices.total) : null"
                :countLabel="$t('system.invoices.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div
                    v-for="stat in statCards"
                    :key="stat.key"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 transition-shadow hover:shadow-sm"
                >
                    <p class="text-2xl font-bold" :class="stat.valueColor">
                        {{ stat.value }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ stat.label }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <AppInput
                        v-model="tableFilters.search"
                        type="search"
                        :placeholder="$t('system.invoices.search_placeholder')"
                        @enter="applyFilters"
                    />
                </div>
                <SearchableSelect
                    v-model="tableFilters.status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.invoices.all_statuses')"
                    compact
                    @update:model-value="applyFilters"
                />
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <DataTable structured class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.invoices.col.invoice_number') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.invoices.col.tenant') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.invoices.col.amount') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.invoices.col.due_at') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.tenants.status') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.backups.col.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle">
                                <span class="font-mono text-sm text-gray-900 dark:text-white">
                                    {{ invoice.invoice_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span class="text-gray-900 dark:text-white">
                                    {{
                                        invoice.tenant?.trade_name ||
                                        invoice.tenant?.name ||
                                        $t('common.na')
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(invoice.total) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    :class="
                                        isOverdue(invoice)
                                            ? 'text-red-600'
                                            : 'text-gray-500 dark:text-gray-400'
                                    "
                                >
                                    {{ formatDate(invoice.due_at) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <span
                                    :class="getStatusClass(invoice.status)"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    {{ getStatusLabel(invoice.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center justify-center gap-2">
                                    <a
                                        :href="`/system/invoices/${invoice.id}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-action-primary hover:text-selection-text hover:bg-selection-bg rounded-lg transition-colors"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        {{ $t('system.actions.view') }}
                                    </a>
                                    <a
                                        :href="`/system/invoices/${invoice.id}/download`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                    >
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data?.length">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('system.invoices.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>

            <!-- Pagination -->
            <AppPagination
                :links="invoices.links || []"
                :from="invoices.from"
                :to="invoices.to"
                :total="invoices.total"
            />
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';
import { useFormatters } from '@/Composables/useFormatters';

const { formatDate, formatCurrency } = useFormatters();

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const statusOptions = computed(() => [
    { value: '', label: t('system.invoices.all_statuses') },
    { value: 'pending', label: t('system.invoices.status.pending') },
    { value: 'overdue', label: t('system.invoices.status.overdue') },
    { value: 'paid', label: t('system.invoices.status.paid') },
    { value: 'failed', label: t('system.invoices.status.failed') },
    { value: 'cancelled', label: t('system.invoices.status.cancelled') },
]);

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
});

const statCards = computed(() => [
    {
        key: 'total',
        label: t('system.invoices.stats.total'),
        value: props.stats?.total ?? 0,
        valueColor: 'text-gray-900 dark:text-white',
    },
    {
        key: 'pending',
        label: t('system.invoices.stats.pending'),
        value: props.stats?.pending ?? 0,
        valueColor: 'text-amber-600 dark:text-amber-400',
    },
    {
        key: 'paid',
        label: t('system.invoices.stats.paid'),
        value: props.stats?.paid ?? 0,
        valueColor: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        key: 'overdue',
        label: t('system.invoices.stats.overdue'),
        value: props.stats?.overdue ?? 0,
        valueColor: 'text-red-600 dark:text-red-400',
    },
    {
        key: 'revenue',
        label: t('system.invoices.stats.revenue'),
        value: formatCurrency(props.stats?.total_revenue ?? 0),
        valueColor: 'text-action-primary',
    },
]);

const { filters: tableFilters, applyFilters } = useServerTable({
    routeUrl: '/system/invoices',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
    },
});

const isOverdue = (invoice) => {
    if (invoice.status !== 'pending' || !invoice.due_at) return false;
    const startOfToday = new Date();
    startOfToday.setHours(0, 0, 0, 0);
    return new Date(invoice.due_at) < startOfToday;
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: t('system.invoices.status.pending'),
        paid: t('system.invoices.status.paid'),
        failed: t('system.invoices.status.failed'),
        cancelled: t('system.invoices.status.cancelled'),
        refunded: t('system.invoices.status.refunded'),
    };
    return labels[status] || status || t('common.na');
};
</script>
