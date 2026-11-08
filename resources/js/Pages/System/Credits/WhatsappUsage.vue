<template>
    <SystemLayout>
        <Head :title="$t('system.credits.whatsapp_usage_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.whatsapp_usage_title')"
                :subtitle="$t('system.credits.whatsapp_usage_subtitle')"
                :totalCount="logs?.total != null ? toEnglish(logs.total) : null"
                :countLabel="$t('system.credits.whatsapp_usage_title')"
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
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                </template>
            </PageHeader>

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

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.total_sent?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.total_messages_2') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-blue-600">
                        {{ stats.delivered?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.delivered_2') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-indigo-600">
                        {{ stats.read?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.read') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-red-600">
                        {{ stats.failed?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.failed_3') }}</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.tenant_5') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.mobile_number_2') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.template') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.credit_3') }}
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
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
                                {{ log.template_name || '-' }}
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
                </table>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { Head, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

const props = defineProps({ logs: Object, stats: Object, filters: Object });

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');
const getStatusLabel = (s) =>
    ({
        sent: t('system.credits.sent_2'),
        delivered: t('system.credits.delivered_2'),
        read: t('system.credits.read'),
        failed: t('system.credits.failed_3'),
        pending: t('system.credits.pending_2'),
    })[s] || s;
const getStatusClass = (s) =>
    ({
        sent: 'bg-blue-100 text-blue-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        read: 'bg-indigo-100 text-indigo-700',
        failed: 'bg-red-100 text-red-700',
        pending: 'bg-amber-100 text-amber-700',
    })[s] || 'bg-gray-100 text-gray-700';
</script>
