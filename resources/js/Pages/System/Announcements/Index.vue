<template>
    <SystemLayout>
        <Head :title="$t('system.announcements.title')" />

        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.announcements.title')"
                :subtitle="$t('system.announcements.subtitle')"
                :totalCount="announcements?.total != null ? toEnglish(announcements.total) : null"
                :countLabel="$t('system.announcements.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <Link
                        :href="route('system.announcements.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-focus-ring-soft active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.announcements.create') }}
                    </Link>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div class="flex gap-4">
                <SearchableSelect
                    v-model="tableFilters.status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.announcements.all_statuses')"
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
                                {{ $t('system.announcements.col.title') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.announcements.col.type') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.announcements.col.audience') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.tenants.status') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.announcements.col.views') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.announcements.col.date') }}
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
                            v-for="item in announcements.data"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle">
                                <Link
                                    :href="route('system.announcements.show', item.id)"
                                    class="font-medium text-gray-900 dark:text-white hover:text-action-primary"
                                >
                                    {{ item.title }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    :class="getTypeClass(item.type)"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    {{ getTypeLabel(item.type) }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 align-middle"
                            >
                                {{ getTargetLabel(item.target) }}
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <span
                                    :class="
                                        item.is_published
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                    "
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    {{
                                        $t(
                                            item.is_published
                                                ? 'system.status.published'
                                                : 'system.status.draft'
                                        )
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 dark:text-gray-400 align-middle text-center"
                            >
                                {{ item.reads_count }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 align-middle"
                            >
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="route('system.announcements.show', item.id)"
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
                                    </Link>
                                    <button
                                        @click="deleteAnnouncement(item)"
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
                        <tr v-if="!announcements.data?.length">
                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('system.announcements.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
                <AppPagination
                    :links="announcements.links || []"
                    :from="announcements.from"
                    :to="announcements.to"
                    :total="announcements.total"
                />
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormatters } from '@/Composables/useFormatters';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { confirm } = useConfirm();
const { formatDate } = useFormatters();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    announcements: Object,
    filters: Object,
});

const statusOptions = computed(() => [
    { value: '', label: t('system.announcements.all_statuses') },
    { value: 'published', label: t('system.status.published') },
    { value: 'draft', label: t('system.status.draft') },
]);

const { filters: tableFilters, applyFilters } = useServerTable({
    routeUrl: '/system/announcements',
    initialFilters: {
        status: props.filters?.status || '',
    },
});

const getTypeLabel = (type) =>
    ({
        info: t('system.announcements.type.info'),
        warning: t('system.announcements.type.warning'),
        important: t('system.announcements.type.important'),
        maintenance: t('system.announcements.type.maintenance'),
    })[type] || type;

const getTypeClass = (type) =>
    ({
        info: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        important: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        maintenance: 'bg-purple-100 text-purple-700',
    })[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

const getTargetLabel = (target) =>
    ({
        all: t('system.announcements.target.all'),
        active: t('system.announcements.target.active'),
        trial: t('system.announcements.target.trial'),
        expired: t('system.announcements.target.expired'),
        specific: t('system.announcements.target.specific'),
    })[target] || target;

const deleteAnnouncement = async (item) => {
    const confirmed = await confirm({
        title: t('system.announcements.confirm_delete_title'),
        message: t('system.announcements.confirm_delete_message', { title: item.title }),
        confirmText: t('system.announcements.confirm_delete_yes'),
        cancelText: t('system.actions.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(`/system/announcements/${item.id}`);
    }
};
</script>
