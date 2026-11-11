<template>
    <SystemLayout>
        <Head :title="$t('system.credits.sms_usage_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.sms_usage_title')"
                :subtitle="$t('system.credits.track_every_message_sent')"
                :totalCount="logs?.total != null ? toEnglish(logs.total) : null"
                :countLabel="$t('system.credits.sms_usage_title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
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

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-indigo-600">
                        {{ stats.total_sent?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.total_messages') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.delivered?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.delivered') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-red-600">
                        {{ stats.failed?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.failed_2') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total_credits_used?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.credit_used') }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-4">
                <SearchableSelect
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.credits.all_statuses_2')"
                    compact
                />
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <DataTable structured class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.tenant_3') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.mobile_number') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.type') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.credit_2') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.status') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.date') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        >
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                {{ log.tenant?.trade_name || '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">
                                {{ log.phone_number }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300"
                                >
                                    {{ getTypeLabel(log.message_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ log.credits_used }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="getStatusClass(log.status)"
                                    class="px-2 py-1 rounded-full text-xs"
                                >
                                    {{ getStatusLabel(log.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-sm">
                                {{ formatDate(log.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!logs.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
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
    { value: 'sent', label: t('system.credits.sent') },
    { value: 'delivered', label: t('system.credits.delivered') },
    { value: 'failed', label: t('system.credits.failed_2') },
    { value: 'pending', label: t('system.credits.pending') },
]);

const props = defineProps({
    logs: Object,
    stats: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/system/sms/usage', { status: status.value || undefined }, { preserveState: true });
};

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getTypeLabel = (type) =>
    ({
        reminder: t('system.credits.reminder'),
        notification: t('system.credits.notification'),
        otp: t('system.credits.verification_code'),
        marketing: t('system.credits.marketing'),
        invoice: t('system.credits.invoice'),
        appointment: t('system.credits.appointment'),
    })[type] || type;

const getStatusLabel = (status) =>
    ({
        sent: t('system.credits.sent'),
        delivered: t('system.credits.delivered'),
        failed: t('system.credits.failed_2'),
        pending: t('system.credits.pending'),
    })[status] || status;

const getStatusClass = (status) =>
    ({
        sent: 'bg-blue-100 text-blue-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
        pending: 'bg-amber-100 text-amber-700',
    })[status] || 'bg-gray-100 text-gray-700';
</script>
