<template>
    <SystemLayout>
        <Head :title="$t('system.credits.whatsapp_purchases_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.whatsapp_purchases_title')"
                :subtitle="$t('system.credits.whatsapp_purchases_subtitle')"
                :totalCount="purchases?.total != null ? toEnglish(purchases.total) : null"
                :countLabel="$t('system.credits.whatsapp_purchases_title')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-600"
                glowFrom="from-emerald-500"
                glowTo="to-teal-600"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Navigation Tabs (mirrors WhatsappUsage) -->
            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <Link
                    :href="route('system.whatsapp.packages')"
                    :class="
                        route().current('system.whatsapp.packages')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.packages') }}
                </Link>
                <Link
                    :href="route('system.whatsapp.balances')"
                    :class="
                        route().current('system.whatsapp.balances')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.balances') }}
                </Link>
                <Link
                    :href="route('system.whatsapp.purchases')"
                    :class="
                        route().current('system.whatsapp.purchases')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.purchases') }}
                </Link>
                <Link
                    :href="route('system.whatsapp.usage')"
                    :class="
                        route().current('system.whatsapp.usage')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.usage') }}
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.subscriptions.stats.total') }}
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.status.paid') }}
                    </p>
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.paid?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.status.pending') }}
                    </p>
                    <p class="text-2xl font-bold text-amber-600">
                        {{ stats.pending?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.status.failed') }}
                    </p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ stats.failed?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.credits.revenue') }}
                    </p>
                    <p class="text-2xl font-bold text-action-primary">
                        {{ formatCurrency(stats.total_revenue) }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.credits.credits_sold') }}
                    </p>
                    <p class="text-2xl font-bold text-purple-600">
                        {{ stats.total_credits_sold?.toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="tableFilters.search"
                            type="text"
                            :placeholder="$t('system.credits.search_purchases')"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <SearchableSelect
                        v-model="tableFilters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.common.all')"
                        compact
                        @update:model-value="applyFilters"
                    />
                </div>
            </div>

            <!-- Purchases Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.tenants.trade_name') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.package') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.credits') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.amount') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.tenants.status') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.gateway') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.reference') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.date') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="purchase in purchases.data"
                                :key="purchase.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">
                                    {{ purchase.tenant?.trade_name || '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ purchase.package?.name_ar || $t('system.credits.custom') }}
                                </td>
                                <td class="px-4 py-3 text-action-primary font-semibold">
                                    {{ purchase.credits?.toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ formatCurrency(purchase.amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="getStatusClass(purchase.status)"
                                        class="px-2 py-1 rounded-full text-xs"
                                    >
                                        {{ getStatusLabel(purchase.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
                                    {{ purchase.payment_gateway || '-' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono"
                                    dir="ltr"
                                >
                                    {{ purchase.payment_reference || '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                                    {{ formatDate(purchase.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="!purchases.data?.length">
                                <td
                                    colspan="8"
                                    class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.credits.no_purchases') }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination -->
                <AppPagination
                    :links="purchases.links || []"
                    :from="purchases.from"
                    :to="purchases.to"
                    :total="purchases.total"
                />
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { useI18n } from 'vue-i18n';
import { Head, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';

const { toEnglish } = useNumberFormat();

const { t } = useI18n();

const statusOptions = [
    { value: 'all', label: t('system.common.all') },
    { value: 'pending', label: t('system.status.pending') },
    { value: 'paid', label: t('system.status.paid') },
    { value: 'failed', label: t('system.status.failed') },
    { value: 'refunded', label: t('system.status.refunded') },
];

const props = defineProps({
    purchases: Object,
    stats: Object,
    filters: Object,
});

const { filters: tableFilters, applyFilters } = useServerTable({
    routeUrl: '/system/whatsapp/purchases',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || 'all',
    },
});

const formatCurrency = (amount) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount || 0);

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getStatusLabel = (status) => {
    const known = ['pending', 'paid', 'failed', 'refunded'];
    if (known.includes(status)) {
        return t(`system.status.${status}`);
    }
    return status;
};

const getStatusClass = (status) =>
    ({
        pending: 'bg-amber-100 text-amber-700',
        paid: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
        refunded: 'bg-gray-100 text-gray-700',
    })[status] || 'bg-gray-100 text-gray-700';
</script>
