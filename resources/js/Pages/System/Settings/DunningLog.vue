<template>
    <SystemLayout>
        <Head :title="isAr ? $t('system.settings.dunning_log') : 'Dunning Log'" />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="isAr ? $t('system.settings.dunning_log') : 'Dunning Log'"
                :subtitle="
                    isAr
                        ? $t('system.settings.every_dunning_send_attempt_sent_failed_or_skippe')
                        : 'Every dunning send attempt — sent, failed, or skipped.'
                "
                :totalCount="logs?.total != null ? toEnglish(logs.total) : null"
                :countLabel="isAr ? $t('system.settings.dunning_log') : 'Dunning Log'"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div
                class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex-1 min-w-[150px]">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ isAr ? $t('common.status') : 'Status' }}
                    </label>
                    <SearchableSelect
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :compact="true"
                    />
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ isAr ? $t('system.settings.tier') : 'Tier' }}
                    </label>
                    <SearchableSelect
                        v-model="filters.tier"
                        :options="tierOptions"
                        option-label="label"
                        option-value="value"
                        :compact="true"
                    />
                </div>
                <button
                    @click="applyFilters"
                    class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                >
                    {{ isAr ? $t('common.apply') : 'Apply' }}
                </button>
                <Link
                    :href="'/system/settings/dunning'"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    {{ isAr ? $t('system.settings.settings') : 'Settings' }}
                </Link>
            </div>

            <!-- Log table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <DataTable structured class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('common.date') : 'Date' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('system.settings.tenant') : 'Tenant' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('system.settings.invoice') : 'Invoice' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('system.settings.tier') : 'Tier' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('system.settings.channel') : 'Channel' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('common.status') : 'Status' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? $t('common.error') : 'Error' }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ formatDate(log.sent_at) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ log.tenant?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ log.invoice?.invoice_number || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ log.tier_key.replace('tier_', '') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ log.channel }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="statusClass(log.status)"
                                    class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ statusLabel(log.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-rose-600 dark:text-rose-400">
                                {{ log.error_message || '—' }}
                            </td>
                        </tr>
                        <tr v-if="!logs.data?.length">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    isAr
                                        ? $t('system.settings.no_log_entries_yet')
                                        : 'No log entries yet'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <!-- Pagination -->
                <div
                    v-if="logs.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-gray-700"
                >
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        {{ isAr ? $t('system.settings.page') : 'Page' }} {{ logs.current_page }} /
                        {{ logs.last_page }}
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in logs.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-1 text-sm',
                                link.active
                                    ? 'bg-amber-600 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
                                !link.url && 'pointer-events-none opacity-50',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    tier_definitions: { type: Object, required: true },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const filters = ref({
    status: props.filters.status || '',
    tier: props.filters.tier || '',
});

const statusOptions = computed(() => [
    { value: '', label: isAr.value ? t('common.all') : 'All' },
    { value: 'sent', label: isAr.value ? t('system.settings.sent') : 'Sent' },
    { value: 'failed', label: isAr.value ? t('system.settings.failed') : 'Failed' },
    { value: 'skipped', label: isAr.value ? t('system.settings.skipped') : 'Skipped' },
]);

const tierOptions = computed(() => {
    const opts = [{ value: '', label: isAr.value ? t('common.all') : 'All' }];
    const defs = props.tier_definitions || {};
    for (const key of Object.keys(defs)) {
        opts.push({
            value: key,
            label: (isAr.value ? t('system.settings.tier_2') : 'Tier ') + key.replace('tier_', ''),
        });
    }
    return opts;
});

function applyFilters() {
    router.get('/system/billing/dunning-log', {
        status: filters.value.status || undefined,
        tier: filters.value.tier || undefined,
    });
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString(isAr.value ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClass(s) {
    return (
        {
            sent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            failed: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
            skipped: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        }[s] || 'bg-gray-100 text-gray-700'
    );
}

function statusLabel(s) {
    return (
        {
            sent: isAr.value ? t('system.settings.sent_2') : 'Sent',
            failed: isAr.value ? t('system.settings.failed_2') : 'Failed',
            skipped: isAr.value ? t('system.settings.skipped_2') : 'Skipped',
        }[s] || s
    );
}
</script>
