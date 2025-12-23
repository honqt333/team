<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('work_orders.title') }}</h1>
                            <p v-if="workOrders" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ workOrders.total }} {{ $t('work_orders.total_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('work_orders.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- View Toggle -->
                        <div class="flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    viewMode === 'grid'
                                        ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    viewMode === 'list'
                                        ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Add Button -->
                        <button
                            @click="showCreateModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('work_orders.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!workOrders" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredWorkOrders.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('work_orders.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('work_orders.empty_hint') }}</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('work_orders.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="order in filteredWorkOrders"
                    :key="order.id"
                    class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md cursor-pointer transition-all"
                >
                    <!-- Header: Code + Status -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ order.code }}</span>
                        <span :class="getStatusClass(order.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">
                            {{ $t(`work_orders.status.${order.status}`) }}
                        </span>
                    </div>

                    <!-- Customer -->
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold text-white flex-shrink-0 bg-gradient-to-br from-emerald-500 to-teal-500">
                            {{ order.customer?.name?.charAt(0)?.toUpperCase() || '?' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ order.customer?.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" dir="ltr">{{ order.customer?.phone }}</p>
                        </div>
                    </div>

                    <!-- Vehicle -->
                    <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 mb-3">
                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <span dir="ltr" class="truncate">{{ order.vehicle?.plate_number }}</span>
                        <span v-if="getVehicleName(order.vehicle)" class="text-gray-400 mx-0.5">•</span>
                        <span class="truncate">{{ getVehicleName(order.vehicle) }}</span>
                    </div>

                    <!-- Footer: Date + Total -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</span>
                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ calculateTotal(order.items) }} ر.س</span>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('work_orders.columns.code') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('work_orders.columns.customer') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('work_orders.columns.vehicle') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('work_orders.columns.status') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('work_orders.columns.total') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('work_orders.columns.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="order in filteredWorkOrders"
                                :key="order.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400">{{ order.code }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold text-white flex-shrink-0 bg-gradient-to-br from-emerald-500 to-teal-500">
                                            {{ order.customer?.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ order.customer?.name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate" dir="ltr">{{ order.customer?.phone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300" dir="ltr">{{ order.vehicle?.plate_number }}</td>
                                <td class="px-5 py-4">
                                    <span :class="getStatusClass(order.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ $t(`work_orders.status.${order.status}`) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ calculateTotal(order.items) }}</td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="workOrders && workOrders.data.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t('customers.showing') }} 
                <span class="font-medium text-gray-900 dark:text-white">{{ workOrders.from }}</span>-<span class="font-medium text-gray-900 dark:text-white">{{ workOrders.to }}</span>
                {{ $t('customers.of') }}
                <span class="font-medium text-gray-900 dark:text-white">{{ workOrders.total }}</span>
            </div>
        </div>

        <!-- Create Modal -->
        <WorkOrderFormModal
            :show="showCreateModal"
            :services="services"
            :customers="customers"
            :makes="makes"
            @close="showCreateModal = false"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkOrderFormModal from '@/Components/WorkOrderFormModal.vue';

const props = defineProps({
    workOrders: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
});

const showCreateModal = ref(false);
const viewMode = ref(localStorage.getItem('workOrdersViewMode') || 'grid');
const searchQuery = ref('');

watch(viewMode, (newMode) => {
    localStorage.setItem('workOrdersViewMode', newMode);
});

const filteredWorkOrders = computed(() => {
    const orders = props.workOrders?.data || [];
    if (!searchQuery.value) return orders;
    
    const query = searchQuery.value.toLowerCase();
    return orders.filter(order => 
        order.code?.toLowerCase().includes(query) ||
        order.customer?.name?.toLowerCase().includes(query) ||
        order.vehicle?.plate_number?.toLowerCase().includes(query)
    );
});

function getStatusClass(status) {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        in_progress: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        done: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    };
    return classes[status] || classes.draft;
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    if (vehicle.make?.name) {
        return `${vehicle.make.name} ${vehicle.model?.name || ''}`.trim();
    }
    return vehicle.make_other || '';
}

function calculateTotal(items) {
    if (!items || !Array.isArray(items)) return '0.00';
    return items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0).toFixed(2);
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('ar-SA');
}

function handleSaved() {
    showCreateModal.value = false;
    router.reload({ only: ['workOrders'] });
}
</script>
