<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.backups.title')"
                :subtitle="$t('system.backups.subtitle')"
                :totalCount="backups?.total != null ? toEnglish(backups.total) : null"
                :countLabel="$t('system.backups.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21 3.582 4 8 4s8-1.79 8-4"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="stat in statCards"
                    :key="stat.key"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 transition-shadow hover:shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ stat.label }}
                        </p>
                        <div
                            :class="stat.iconBg"
                            class="flex items-center justify-center w-9 h-9 rounded-lg"
                        >
                            <svg
                                class="w-5 h-5"
                                :class="stat.iconColor"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="stat.iconPath"
                                />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold" :class="stat.valueColor">
                        {{ stat.value }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <SearchableSelect
                            v-model="tenantFilter"
                            :options="tenantFilterOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('system.backups.filter_tenant')"
                            compact
                        />
                    </div>
                    <SearchableSelect
                        v-model="statusFilter"
                        :options="statusFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.backups.filter_status')"
                        compact
                    />
                    <SearchableSelect
                        v-model="typeFilter"
                        :options="typeFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.backups.filter_type')"
                        compact
                    />
                </div>
            </div>

            <!-- Backups Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.name') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.tenant') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.type') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.status') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.size') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.created') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.backups.col.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="backup in backups.data"
                                :key="backup.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ backup.name }}
                                    </p>
                                    <p
                                        v-if="backup.error_message"
                                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                                    >
                                        {{ backup.error_message }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 align-middle"
                                >
                                    {{
                                        backup.tenant?.trade_name ||
                                        backup.tenant?.name ||
                                        $t('common.na')
                                    }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        :class="getTypeClass(backup.type)"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ getTypeLabel(backup.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        :class="getStatusClass(backup.status)"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ getStatusLabel(backup.status) }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 align-middle"
                                >
                                    {{ humanSize(backup.size_bytes) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 align-middle"
                                >
                                    {{ formatDate(backup.created_at) }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-center gap-2">
                                        <a
                                            v-if="backup.status === 'completed'"
                                            :href="`/system/backups/${backup.id}/download`"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
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
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                                />
                                            </svg>
                                            {{ $t('system.actions.download') }}
                                        </a>
                                        <button
                                            @click="confirmDelete(backup)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                            {{ $t('system.actions.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!backups.data?.length">
                                <td colspan="7" class="px-4 py-16 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400"
                                    >
                                        <svg
                                            class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                            />
                                        </svg>
                                        <p class="text-sm font-medium">
                                            {{ $t('system.backups.empty') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination -->
                <div
                    v-if="backups.links"
                    class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{
                            $t('pagination.showing_range', {
                                from: backups.from || 0,
                                to: backups.to || 0,
                                total: backups.total || 0,
                            })
                        }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in backups.links" :key="link.label">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1 rounded text-sm bg-gray-50 dark:bg-gray-800 text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    backups: Object,
    stats: Object,
    tenants: Array,
    filters: Object,
});

const tenantFilter = ref(props.filters?.tenant_id || '');
const statusFilter = ref(props.filters?.status || 'all');
const typeFilter = ref(props.filters?.type || 'all');

const tenantFilterOptions = computed(() => [
    { value: '', label: t('system.backups.filter_all_tenants') },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.trade_name || t.name })),
]);

const statusFilterOptions = computed(() => [
    { value: 'all', label: t('system.common.all') },
    { value: 'pending', label: t('system.backups.status.pending') },
    { value: 'running', label: t('system.backups.status.running') },
    { value: 'completed', label: t('system.backups.status.completed') },
    { value: 'failed', label: t('system.backups.status.failed') },
]);

const typeFilterOptions = computed(() => [
    { value: 'all', label: t('system.common.all') },
    { value: 'manual', label: t('system.backups.type.manual') },
    { value: 'scheduled', label: t('system.backups.type.scheduled') },
    { value: 'pre_delete', label: t('system.backups.type.pre_delete') },
]);

// Stat cards: extracted to a computed list so the template doesn't
// repeat the same card 4× with only label/value/color swapped.
const statCards = computed(() => [
    {
        key: 'total',
        label: t('system.backups.stats.total'),
        value: (props.stats?.total || 0).toLocaleString(),
        valueColor: 'text-gray-900 dark:text-white',
        iconBg: 'bg-gray-100 dark:bg-gray-700',
        iconColor: 'text-gray-500 dark:text-gray-400',
        iconPath:
            'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4',
    },
    {
        key: 'completed',
        label: t('system.backups.stats.completed'),
        value: (props.stats?.completed || 0).toLocaleString(),
        valueColor: 'text-emerald-600 dark:text-emerald-400',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/30',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'failed',
        label: t('system.backups.stats.failed'),
        value: (props.stats?.failed || 0).toLocaleString(),
        valueColor: 'text-red-600 dark:text-red-400',
        iconBg: 'bg-red-100 dark:bg-red-900/30',
        iconColor: 'text-red-600 dark:text-red-400',
        iconPath: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'total_size',
        label: t('system.backups.stats.total_size'),
        value: `${(props.stats?.total_size_mb || 0).toLocaleString()} MB`,
        valueColor: 'text-indigo-600 dark:text-indigo-400',
        iconBg: 'bg-indigo-100 dark:bg-indigo-900/30',
        iconColor: 'text-indigo-600 dark:text-indigo-400',
        iconPath:
            'M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z',
    },
]);

// Auto-apply filters; debounce tenant/status changes
let filterTimeout = null;
watch([tenantFilter, statusFilter, typeFilter], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFilters, 200);
});

const applyFilters = () => {
    router.get(
        '/system/backups',
        {
            tenant_id: tenantFilter.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        },
        { preserveState: true }
    );
};

const formatDate = (date) =>
    date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : t('common.na');

const humanSize = (bytes) => {
    if (!bytes) return t('common.na');
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    let size = Number(bytes);
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    return `${size.toFixed(2)} ${units[i]}`;
};

const getTypeClass = (type) =>
    ({
        manual: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        pre_delete: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    })[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

const getTypeLabel = (type) =>
    ({
        manual: t('system.backups.type.manual'),
        scheduled: t('system.backups.type.scheduled'),
        pre_delete: t('system.backups.type.pre_delete'),
    })[type] || type;

const getStatusClass = (status) =>
    ({
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        running: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    })[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

const getStatusLabel = (status) =>
    ({
        pending: t('system.backups.status.pending'),
        running: t('system.backups.status.running'),
        completed: t('system.backups.status.completed'),
        failed: t('system.backups.status.failed'),
    })[status] || status;

const confirmDelete = (backup) => {
    if (confirm(t('system.backups.confirm_delete', { name: backup.name }))) {
        router.delete(`/system/backups/${backup.id}`);
    }
};
</script>
