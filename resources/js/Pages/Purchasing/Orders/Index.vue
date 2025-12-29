<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('purchasing.orders.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.orders.subtitle') }}</p>
                        </div>
                    </div>
                    <Link
                        v-if="can('purchasing.pos.create')"
                        :href="route('app.purchasing.orders.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('purchasing.orders.add') }}
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            :placeholder="$t('purchasing.orders.search_placeholder')"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="localFilters.status"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">{{ $t('purchasing.orders.all_statuses') }}</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ getStatusLabel(status) }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('purchasing.orders.code') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('purchasing.orders.supplier') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('purchasing.orders.date') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('purchasing.orders.total') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.status') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-mono text-blue-600 dark:text-blue-400">
                                    <Link :href="route('app.purchasing.orders.show', order.id)" class="hover:underline">
                                        {{ order.code }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ order.supplier?.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300" dir="ltr">
                                    {{ formatDate(order.order_date) }}
                                </td>
                                <td class="px-4 py-3 text-end text-sm font-medium text-gray-900 dark:text-white font-mono" dir="ltr">
                                    {{ formatCurrency(order.total) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getStatusBadgeClass(order.status)">
                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <Link
                                        :href="route('app.purchasing.orders.show', order.id)"
                                        class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!orders.data.length">
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('purchasing.orders.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;

const props = defineProps({
    orders: Object,
    suppliers: Array,
    statuses: Array,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const applyFilters = () => {
    router.get(route('app.purchasing.orders.index'), {
        search: localFilters.value.search || undefined,
        status: localFilters.value.status || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const debouncedSearch = debounce(applyFilters, 300);

const formatDate = (date) => new Date(date).toLocaleDateString('ar-SA');

const formatCurrency = (value) => new Intl.NumberFormat('ar-SA', {
    style: 'currency',
    currency: 'SAR',
}).format(value || 0);

const getStatusLabel = (status) => {
    const labels = {
        'draft': t('purchasing.orders.statuses.draft'),
        'sent': t('purchasing.orders.statuses.sent'),
        'partial': t('purchasing.orders.statuses.partial'),
        'received': t('purchasing.orders.statuses.received'),
        'cancelled': t('purchasing.orders.statuses.cancelled'),
    };
    return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'draft': 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300',
        'sent': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'partial': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'received': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'cancelled': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return `inline-flex px-2 py-1 rounded-full text-xs font-medium ${classes[status] || 'bg-gray-100'}`;
};
</script>
