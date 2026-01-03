<template>
    <AppLayout :title="$t('inventory.transfers.title')">
        <div class="p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('inventory.transfers.title') }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.transfers.subtitle') }}
                    </p>
                </div>
                <a
                    :href="route('app.inventory.transfers.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('inventory.transfers.add') }}
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('inventory.transfers.search_placeholder')"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        @input="debouncedSearch"
                    />
                    <div class="w-48">
                        <SearchableSelect
                            v-model="statusFilter"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('inventory.transfers.all_statuses')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('inventory.transfers.code') }}
                            </th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('inventory.transfers.from') }}
                            </th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('inventory.transfers.to') }}
                            </th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('common.status') }}
                            </th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('common.created_at') }}
                            </th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="transfer in transfers.data"
                            :key="transfer.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a 
                                    :href="route('app.inventory.transfers.show', transfer.id)"
                                    class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                                >
                                    {{ transfer.code }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                {{ transfer.from_warehouse?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                {{ transfer.to_warehouse?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="statusClass(transfer.status)">
                                    {{ $t('inventory.transfers.statuses.' + transfer.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-sm">
                                {{ formatDate(transfer.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end">
                                <a
                                    :href="route('app.inventory.transfers.show', transfer.id)"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="transfers.data.length === 0" class="p-12 text-center text-gray-500 dark:text-gray-400">
                    {{ $t('inventory.transfers.empty') }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    transfers: Object,
    filters: Object,
});

const { t } = useI18n();
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

const statusOptions = computed(() => [
    { value: '', label: t('inventory.transfers.all_statuses') },
    { value: 'draft', label: t('inventory.transfers.statuses.draft') },
    { value: 'sent', label: t('inventory.transfers.statuses.sent') },
    { value: 'received', label: t('inventory.transfers.statuses.received') },
    { value: 'cancelled', label: t('inventory.transfers.statuses.cancelled') },
]);

const applyFilters = () => {
    router.get(route('app.inventory.transfers.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const debouncedSearch = debounce(applyFilters, 300);

const statusClass = (status) => {
    const classes = {
        draft: 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        sent: 'px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        received: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        cancelled: 'px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    };
    return classes[status] || classes.draft;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>
