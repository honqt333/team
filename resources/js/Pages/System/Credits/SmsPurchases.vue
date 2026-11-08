<template>
    <SystemLayout>
        <Head :title="$t('system.credits.sms_purchases_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.sms_purchases_title')"
                :subtitle="$t('system.credits.sms_purchases_subtitle')"
                :totalCount="purchases?.total != null ? toEnglish(purchases.total) : null"
                :countLabel="$t('system.credits.sms_purchases_title')"
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

            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <Link
                    :href="route('system.sms.packages')"
                    :class="
                        route().current('system.sms.packages')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.packages') }}
                </Link>
                <Link
                    :href="route('system.sms.balances')"
                    :class="
                        route().current('system.sms.balances')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.balances') }}
                </Link>
                <Link
                    :href="route('system.sms.purchases')"
                    :class="
                        route().current('system.sms.purchases')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.purchases') }}
                </Link>
                <Link
                    :href="route('system.sms.usage')"
                    :class="
                        route().current('system.sms.usage')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.usage') }}
                </Link>
            </div>

            <!-- Filters -->
            <div>
                <SearchableSelect
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.credits.all_statuses')"
                    compact
                />
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.tenant_2') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.package') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.credit') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.amount') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.status') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.payment_method') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
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
                            <td class="px-4 py-3 text-indigo-600 font-semibold">
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
                            <td class="px-4 py-3 text-gray-500 text-sm">
                                {{ formatDate(purchase.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!purchases.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                {{ $t('system.credits.no_purchases') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

const statusOptions = computed(() => [
    { value: 'pending', label: t('common.pending') },
    { value: 'paid', label: t('common.status_paid') },
    { value: 'failed', label: t('system.credits.failed') },
    { value: 'refunded', label: t('system.credits.refunded') },
]);

const props = defineProps({
    purchases: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get(
        '/system/sms/purchases',
        { status: status.value || undefined },
        { preserveState: true }
    );
};

const formatCurrency = (amount) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency: 'SAR' }).format(
        amount || 0
    );
const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getStatusLabel = (status) =>
    ({
        pending: t('common.pending'),
        paid: t('common.status_paid'),
        failed: t('system.credits.failed'),
        refunded: t('system.credits.refunded'),
    })[status] || status;

const getStatusClass = (status) =>
    ({
        pending: 'bg-amber-100 text-amber-700',
        paid: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
        refunded: 'bg-gray-100 text-gray-700',
    })[status] || 'bg-gray-100 text-gray-700';
</script>
