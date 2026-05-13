<template>
    <AppLayout :title="$t('inventory.transfers.title')">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.transfers.title')"
                :subtitle="$t('inventory.transfers.subtitle')"
                :totalCount="transfers.total"
                :countLabel="$t('inventory.transfers.title')"
                gradientFrom="from-indigo-600"
                gradientTo="to-violet-700"
                glowFrom="from-indigo-500"
                badgeBg="bg-indigo-50/50 dark:bg-indigo-900/30"
                badgeText="text-indigo-600 dark:text-indigo-400"
                badgeBorder="border-indigo-100/50 dark:border-indigo-800/30"
                badgeDot="bg-indigo-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </template>

                <template #actions>
                    <Link
                        v-if="can('inventory.transfers.create') || isAnyAdmin()"
                        :href="route('app.inventory.transfers.create')"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-2xl font-black shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all group/add"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('inventory.transfers.add') }}</span>
                    </Link>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="$t('inventory.transfers.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Status Filter -->
                            <div class="w-full md:w-56">
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

                            <!-- Reset Button -->
                            <button 
                                @click="resetFilters"
                                class="p-3.5 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all shadow-sm"
                                :title="$t('common.reset')"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <div class="space-y-6">
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
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { debounce } from 'lodash-es';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;
const isAnyAdmin = () => page.props.auth?.roles?.some(role => ['super_admin', 'admin'].includes(role)) ?? false;

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

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    applyFilters();
};

const debouncedSearch = debounce(applyFilters, 300);

const statusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200/50 dark:border-gray-600/30',
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200/50 dark:border-blue-800/30',
        received: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200/50 dark:border-green-800/30',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200/50 dark:border-red-800/30',
    };
    return `inline-flex px-2.5 py-1 rounded-full text-xs font-bold shadow-sm ${classes[status] || classes.draft}`;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>
