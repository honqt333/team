<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.total_messages') }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ stats.total_sent || 0 }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-emerald-600 font-medium">
                    {{ $t('system.credits.delivered') }}
                </p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.delivered || 0 }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-blue-600 font-medium">{{ $t('system.credits.read') }}</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.read || 0 }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-rose-600 font-medium">{{ $t('system.credits.failed') }}</p>
                <p class="text-2xl font-bold text-rose-600 mt-1">{{ stats.failed || 0 }}</p>
            </div>
        </div>

        <!-- Filter bar -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between gap-4"
        >
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-medium">{{ $t('common.status') }}:</span>
                <SearchableSelect
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    compact
                    @update:model-value="applyFilter"
                />
            </div>
        </div>

        <!-- Logs Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
        >
            <div class="overflow-x-auto">
                <DataTable structured class="w-full text-right text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3.5 font-bold">{{ $t('system.credits.tenant') }}</th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.mobile_number') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.template') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">{{ $t('common.status') }}</th>
                            <th class="px-6 py-3.5 font-bold">{{ $t('system.credits.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="log in logs?.data || []"
                            :key="log.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                        >
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{
                                    log.tenant?.trade_name ||
                                    log.tenant?.name ||
                                    '#' + log.tenant_id
                                }}
                            </td>
                            <td
                                class="px-6 py-4 font-mono text-xs text-gray-600 dark:text-gray-400 dir-ltr text-right"
                            >
                                {{ log.phone || '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ log.template_name || log.type || '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="getStatusBadgeClass(log.status)"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                >
                                    {{ getStatusLabel(log.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(log.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!logs?.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                {{ $t('common.no_records') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
                <AppPagination
                    :links="logs?.links || []"
                    :from="logs?.from"
                    :to="logs?.to"
                    :total="logs?.total"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    logs: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const status = ref(props.filters?.status || '');

const statusOptions = computed(() => [
    { value: '', label: t('system.credits.all_statuses') },
    { value: 'sent', label: t('system.credits.sent') },
    { value: 'delivered', label: t('system.credits.delivered') },
    { value: 'read', label: t('system.credits.read') },
    { value: 'failed', label: t('system.credits.failed') },
]);

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getStatusBadgeClass = (s) =>
    ({
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
        delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        read: 'bg-purple-100 text-purple-700',
        failed: 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
    })[s] || 'bg-gray-100 text-gray-700';

const getStatusLabel = (s) =>
    ({
        sent: t('system.credits.sent'),
        delivered: t('system.credits.delivered'),
        read: t('system.credits.read'),
        failed: t('system.credits.failed'),
    })[s] || s;

const applyFilter = () => {
    router.get(
        route('system.whatsapp.index'),
        {
            tab: 'usage',
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};
</script>
