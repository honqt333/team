<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('purchasing.orders.title')"
                :subtitle="$t('purchasing.orders.subtitle')"
                :totalCount="orders.total"
                :countLabel="$t('purchasing.orders.title')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-700"
                glowFrom="from-blue-500"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <!-- Export/Print Group -->
                        <div class="flex items-center gap-1">
                            <button @click="exportOrders"
                                class="p-2.5 text-gray-500 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md"
                                :title="$t('common.export')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </button>
                            <button @click="printOrders"
                                class="p-2.5 text-gray-500 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md"
                                :title="$t('common.print')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Create Order Button -->
                    <button
                        v-if="can('purchasing.pos.create') || isAnyAdmin()"
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl font-black shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all group/add"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('purchasing.orders.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('purchasing.orders.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @input="debouncedSearch"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Status Filter -->
                            <div class="w-full md:w-56">
                                <SearchableSelect
                                    v-model="localFilters.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('purchasing.orders.all_statuses')"
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

            <!-- Orders Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.code') }}</th>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.supplier') }}</th>
                                <th
                                    class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.date') }}</th>
                                <th
                                    class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.total') }}</th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('common.status') }}</th>
                                <th
                                    class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="order in orders.data" :key="order.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-mono text-blue-600 dark:text-blue-400">
                                    <Link :href="route('app.purchasing.orders.show', order.id)" class="hover:underline">
                                        {{ order.code }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ order.supplier?.name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300" dir="ltr">
                                    {{ formatDate(order.order_date) }}
                                </td>
                                <td class="px-4 py-3 text-end text-sm font-medium text-gray-900 dark:text-white font-mono"
                                    dir="ltr">
                                    {{ formatCurrency(order.total) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getStatusBadgeClass(order.status)">
                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <Link :href="route('app.purchasing.orders.show', order.id)"
                                        class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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

        <!-- Create/Edit Modal -->
        <PurchaseOrderFormModal :show="showCreateModal" :suppliers="suppliers" :warehouses="warehouses"
            :default-warehouse="defaultWarehouse" @close="showCreateModal = false" @saved="onSaved" />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PurchaseOrderFormModal from '@/Components/Purchasing/PurchaseOrderFormModal.vue';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const page = usePage();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    orders: Object,
    suppliers: Array,
    statuses: Array,
    filters: Object,
    warehouses: Array,
    defaultWarehouse: Object,
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

const resetFilters = () => {
    localFilters.value.search = '';
    localFilters.value.status = '';
    applyFilters();
};

const exportOrders = () => {
    // Implement export if needed
};

const printOrders = () => {
    window.print();
};

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
        'draft': 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800/50 dark:text-gray-400 dark:border-gray-700',
        'sent': 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/30',
        'partial': 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/30',
        'received': 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30',
        'cancelled': 'bg-red-50 text-red-700 border-red-100 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/30',
    };
    return `inline-flex px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-sm ${classes[status] || 'bg-gray-100'}`;
};

const statusOptions = computed(() => {
    const allOption = { value: '', label: t('purchasing.orders.all_statuses') };
    const mappedStatuses = props.statuses.map(status => ({
        value: status,
        label: getStatusLabel(status)
    }));
    return [allOption, ...mappedStatuses];
});

// Modal Logic
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingOrder = ref(null);

const openCreateModal = () => {
    editingOrder.value = null;
    showCreateModal.value = true;
};

const onSaved = () => {
    // Refresh page or list handled by Inertia automatically if using visit/reload, 
    // but the modal submit does onSuccess so we might just need to ensure data is fresh.
    // The prop `orders` will update if the controller redirects back or returns fresh data.
    // Usually Inertia handles this.
};
</script>
