<template>
    <SystemLayout>
        <div class="space-y-6">
            <PageHeader
                :title="$t('system.admin_users.activity_log')"
                :subtitle="$t('system.activity_log.subtitle')"
            >
                <template #back>
                    <Link
                        href="/system/admin-users"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="$t('common.back')"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                </template>
                <template #icon>
                    <div
                        class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center text-white shadow-md shadow-focus-ring-soft"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </template>
                <template #badge>
                    <span
                        v-if="logs?.total !== undefined"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-selection-bg text-selection-text border border-action-primary/25"
                    >
                        {{ logs.total }}
                    </span>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div class="flex gap-4">
                <div class="flex-1">
                    <SearchableSelect
                        v-model="adminId"
                        :options="admins"
                        option-label="name"
                        option-value="id"
                        :placeholder="$t('system.admin_users.all_administrators')"
                        compact
                    />
                </div>
                <div class="flex-1">
                    <SearchableSelect
                        v-model="action"
                        :options="actionOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.admin_users.all_actions')"
                        compact
                    />
                </div>
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <DataTable structured class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('quotes.form_tabs.responsible') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('company_profile.invoices_tab.table.actions') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.details') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                IP
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.date') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        >
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ log.admin?.name }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="getActionClass(log.action)"
                                    class="px-2 py-1 rounded-full text-xs"
                                >
                                    {{ log.action_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
                                {{ log.model_type || '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs" dir="ltr">
                                {{ log.ip_address }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-sm">
                                {{ formatDate(log.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!logs.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                {{ $t('common.no_records') }}
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
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const actionOptions = computed(() => [
    { value: 'login', label: t('hr.attendance.check_in') },
    { value: 'create', label: t('common.create') },
    { value: 'update', label: t('common.edit') },
    { value: 'delete', label: t('common.delete') },
]);

const props = defineProps({
    logs: Object,
    admins: Array,
    filters: Object,
});

const adminId = ref(props.filters?.admin_id || '');
const action = ref(props.filters?.action || '');

const applyFilters = () => {
    router.get(
        '/system/activity-log',
        {
            admin_id: adminId.value || undefined,
            action: action.value || undefined,
        },
        { preserveState: true }
    );
};

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getActionClass = (action) =>
    ({
        login: 'bg-blue-100 text-blue-700',
        create: 'bg-emerald-100 text-emerald-700',
        update: 'bg-amber-100 text-amber-700',
        delete: 'bg-red-100 text-red-700',
    })[action] || 'bg-gray-100 text-gray-700';
</script>
