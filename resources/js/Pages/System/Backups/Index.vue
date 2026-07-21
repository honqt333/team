<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('system.backups.title') }}
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ $t('system.backups.subtitle') }}
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.backups.stats.total') }}
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.backups.stats.completed') }}
                    </p>
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.completed?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.backups.stats.failed') }}
                    </p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ stats.failed?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.backups.stats.total_size') }}
                    </p>
                    <p class="text-2xl font-bold text-indigo-600">
                        {{ stats.total_size_mb?.toLocaleString() }} MB
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
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.col.name') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.col.tenant') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.col.type') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.col.status') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.col.size') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.col.created') }}
                                </th>
                                <th class="px-4 py-3 align-middle text-center">
                                    {{ $t('system.backups.col.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="backup in backups.data"
                                :key="backup.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ backup.name }}
                                    </p>
                                    <p
                                        v-if="backup.error_message"
                                        class="text-xs text-red-600 dark:text-red-400 mt-1"
                                    >
                                        {{ backup.error_message }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm align-middle"
                                >
                                    {{ backup.tenant?.trade_name || backup.tenant?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        :class="getTypeClass(backup.type)"
                                        class="px-2 py-1 rounded-full text-xs"
                                    >
                                        {{ getTypeLabel(backup.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        :class="getStatusClass(backup.status)"
                                        class="px-2 py-1 rounded-full text-xs"
                                    >
                                        {{ getStatusLabel(backup.status) }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm align-middle"
                                >
                                    {{ humanSize(backup.size_bytes) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm align-middle"
                                >
                                    {{ formatDate(backup.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-end align-middle">
                                    <div class="flex gap-2 justify-end">
                                        <a
                                            v-if="backup.status === 'completed'"
                                            :href="`/system/backups/${backup.id}/download`"
                                            class="text-indigo-600 hover:text-indigo-700 text-sm"
                                        >
                                            {{ $t('system.actions.download') }}
                                        </a>
                                        <button
                                            @click="confirmDelete(backup)"
                                            class="text-red-600 hover:text-red-800 text-sm"
                                        >
                                            {{ $t('system.actions.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!backups.data?.length">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.backups.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

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

const statusFilterOptions = [
    { value: 'all', label: t('system.common.all') },
    { value: 'pending', label: t('system.status.pending') },
    { value: 'running', label: t('system.status.pending') },
    { value: 'completed', label: t('system.backups.status.completed') },
    { value: 'failed', label: t('system.backups.status.failed') },
];

const typeFilterOptions = [
    { value: 'all', label: t('system.common.all') },
    { value: 'manual', label: t('system.backups.type.manual') },
    { value: 'scheduled', label: t('system.backups.type.scheduled') },
    { value: 'pre_delete', label: t('system.backups.type.pre_delete') },
];

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

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '—');

const humanSize = (bytes) => {
    if (!bytes) return '—';
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
        manual: 'bg-blue-100 text-blue-700',
        scheduled: 'bg-amber-100 text-amber-700',
        pre_delete: 'bg-red-100 text-red-700',
    })[type] || 'bg-gray-100 text-gray-700';

const getTypeLabel = (type) =>
    ({
        manual: t('system.backups.type.manual'),
        scheduled: t('system.backups.type.scheduled'),
        pre_delete: t('system.backups.type.pre_delete'),
    })[type] || type;

const getStatusClass = (status) =>
    ({
        pending: 'bg-amber-100 text-amber-700',
        running: 'bg-blue-100 text-blue-700',
        completed: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
    })[status] || 'bg-gray-100 text-gray-700';

const getStatusLabel = (status) =>
    ({
        pending: t('system.status.pending'),
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
