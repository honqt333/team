<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- Back Button -->
            <Link
                :href="route('vehicles.index')"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ $t('common.back') }}
            </Link>

            <!-- Main Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden relative">
                
                <!-- Background Logo Watermark -->
                <div 
                    v-if="vehicle.make?.logo_path"
                    class="absolute top-0 end-0 p-6 pointer-events-none opacity-[0.05] dark:opacity-[0.1]"
                >
                    <img 
                        :src="`/storage/${vehicle.make.logo_path}`" 
                        class="w-64 h-64 object-contain grayscale"
                        alt=""
                    />
                </div>

                <!-- Header Section -->
                <div class="bg-gradient-to-r from-teal-500/10 via-cyan-500/5 to-transparent dark:from-teal-900/30 dark:via-cyan-900/10 p-6 border-b border-gray-200 dark:border-gray-700 relative z-10">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                        <!-- Vehicle Info -->
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-600 shadow-lg flex items-center justify-center text-white overflow-hidden p-1">
                                <img 
                                    v-if="vehicle.make?.logo_path" 
                                    :src="`/storage/${vehicle.make.logo_path}`" 
                                    class="w-full h-full object-contain brightness-0 invert"
                                    alt=""
                                />
                                <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ getVehicleName(vehicle) }}
                                    </h2>
                                    <span v-if="vehicle.color" class="px-2 py-0.5 text-xs rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full border border-gray-300 dark:border-gray-500" :style="{ backgroundColor: getColorHex(vehicle.color) }"></span>
                                        {{ vehicle.color }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold tracking-wider">
                                        {{ vehicle.plate_number }}
                                    </span>
                                    <span v-if="vehicle.vin" class="flex items-center gap-1">
                                        <span class="text-xs uppercase tracking-widest text-gray-400">VIN:</span>
                                        {{ vehicle.vin }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4">
                            <!-- Customer Link -->
                            <Link 
                                v-if="vehicle.customer"
                                :href="route('customers.show', vehicle.customer.id)"
                                class="flex items-center gap-3 px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl hover:bg-white dark:hover:bg-gray-700 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/50 transition-colors">
                                    <span class="font-bold">{{ vehicle.customer.name.charAt(0).toUpperCase() }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ vehicle.customer.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ vehicle.customer.phone }}</p>
                                </div>
                            </Link>

                            <div class="h-10 w-px bg-gray-200 dark:bg-gray-700 mx-2"></div>

                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ counts.workOrders }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('customers.work_orders') }}</p>
                            </div>
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ counts.quotes }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('customers.quotes') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <button
                        @click="showEditModal = true"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ $t('common.edit') }}
                    </button>

                    <button
                        @click="openWorkOrderModal"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        {{ $t('work_orders.add') }}
                    </button>

                    <button
                        @click="openQuoteModal"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-amber-600 border border-transparent rounded-lg hover:bg-amber-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $t('quotes.add') }}
                    </button>

                    <div class="flex-1"></div>

                    <button
                        @click="confirmDelete"
                        :disabled="!canDelete"
                        :class="[
                            'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                            canDelete
                                ? 'text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                                : 'text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 cursor-not-allowed'
                        ]"
                        :title="canDelete ? '' : $t('customers.cannot_delete_has_data')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ $t('common.delete') }}
                    </button>
                </div>

                <!-- Tabs Navigation -->
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all flex-1 justify-center',
                                activeTab === tab.key
                                    ? 'bg-white dark:bg-gray-800 shadow-sm ' + tab.activeColor
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.label }}
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- ========== WORK ORDERS TAB ========== -->
                    <div v-if="activeTab === 'workOrders'" class="space-y-4">
                        <!-- Toolbar (Search only, Add checks moved to Action Bar) -->
                        <div v-if="filteredWorkOrders.length > 0" class="flex items-center gap-3">
                            <div class="relative flex-1 max-w-md">
                                <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" v-model="workOrderSearch" :placeholder="$t('work_orders.search')" class="w-full ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"/>
                            </div>
                        </div>

                        <!-- Grid/List View Logic (Keeping it Simple: List View preferred for details) -->
                        <div v-if="filteredWorkOrders.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.code') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.status') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.total') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="order in filteredWorkOrders" :key="order.id" @click="router.visit(route('work-orders.show', order.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ order.code }}</td>
                                        <td class="px-4 py-3">
                                            <span :class="getStatusClass(order.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`work_orders.status.${order.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ formatCurrency(order.total) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_work_orders') }}</p>
                            <button @click="openWorkOrderModal" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium shadow-lg hover:bg-indigo-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('work_orders.create') }}
                            </button>
                        </div>
                    </div>

                    <!-- ========== QUOTES TAB ========== -->
                    <div v-if="activeTab === 'quotes'" class="space-y-4">
                        <!-- Toolbar -->
                        <div v-if="filteredQuotes.length > 0" class="flex items-center gap-3">
                             <div class="relative flex-1 max-w-md">
                                <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" v-model="quoteSearch" :placeholder="$t('quotes.search')" class="w-full ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500"/>
                            </div>
                        </div>
                        
                        <!-- List View -->
                        <div v-if="filteredQuotes.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.code') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.status') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.total') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="quote in filteredQuotes" :key="quote.id" @click="router.visit(route('app.quotes.show', quote.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ quote.code }}</td>
                                        <td class="px-4 py-3">
                                            <span :class="getQuoteStatusClass(quote.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`quotes.status.${quote.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ formatCurrency(quote.total) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(quote.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_quotes') }}</p>
                            <button @click="openQuoteModal" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-xl font-medium shadow-lg hover:bg-amber-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('quotes.create') }}
                            </button>
                        </div>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-if="activeTab === 'invoices'" class="space-y-4">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('customers.invoices') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <VehicleFormModal 
            v-if="showEditModal" 
            :show="showEditModal" 
            :vehicle="vehicle" 
            :customers="customersList" 
            :makes="makes" 
            :colors="colors" 
            :modelsByMake="modelsByMake" 
            @close="showEditModal = false" 
            @saved="handleVehicleSaved" 
        />
        
        <WorkOrderFormModal 
            v-if="showWorkOrderModal" 
            :show="showWorkOrderModal" 
            :vehicle="vehicle"
            :customers="customersList" 
            :makes="makes" 
            :colors="colors" 
            :modelsByMake="modelsByMake" 
            :departments="departments" 
            @close="showWorkOrderModal = false" 
            @saved="handleWorkOrderSaved" 
        />
        
        <QuoteFormModal 
            v-if="showQuoteModal" 
            :show="showQuoteModal" 
            :vehicle="vehicle"
            :customers="customersList" 
            :makes="makes" 
            :colors="colors" 
            :modelsByMake="modelsByMake" 
            :departments="departments" 
            :services="services" 
            @close="showQuoteModal = false" 
            @saved="handleQuoteSaved" 
        />
        
        <ConfirmModal />
    </AppLayout>
</template>

<script setup>
import { ref, computed, h, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    vehicle: Object,
    customer: Object,
    counts: Object,
    canDelete: Boolean,
    workOrders: Array,
    quotes: Array,
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const { t, locale } = useI18n();
const { confirm } = useConfirm();

// Compute customers list compatible with modals (must include current customer)
const customersList = computed(() => {
    return props.customer ? [props.customer] : [];
});

// Modal states
const showEditModal = ref(false);
const showWorkOrderModal = ref(false);
const showQuoteModal = ref(false);

// Tab & view states
const activeTab = ref('workOrders');
const workOrderSearch = ref('');
const quoteSearch = ref('');

// Icons
const ClipboardIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })]);
const DocumentIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })]);
const InvoiceIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z' })]);

const tabs = computed(() => [
    { key: 'workOrders', label: t('customers.work_orders'), count: props.counts.workOrders, icon: ClipboardIcon, activeColor: 'text-indigo-600 dark:text-indigo-400' },
    { key: 'quotes', label: t('customers.quotes'), count: props.counts.quotes, icon: DocumentIcon, activeColor: 'text-amber-600 dark:text-amber-400' },
    { key: 'invoices', label: t('customers.invoices'), count: props.counts.invoices || 0, icon: InvoiceIcon, activeColor: 'text-green-600 dark:text-green-400' },
]);

// Filtered data
const filteredWorkOrders = computed(() => {
    if (!props.workOrders) return [];
    if (!workOrderSearch.value) return props.workOrders;
    const q = workOrderSearch.value.toLowerCase();
    return props.workOrders.filter(w => w.code?.toLowerCase().includes(q));
});

const filteredQuotes = computed(() => {
    if (!props.quotes) return [];
    if (!quoteSearch.value) return props.quotes;
    const q = quoteSearch.value.toLowerCase();
    return props.quotes.filter(qu => qu.code?.toLowerCase().includes(q));
});

// Helpers
function getName(item) {
    if (!item) return '';
    return locale.value === 'ar' ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
}

function getMakeName(vehicle) {
    if (!vehicle) return '';
    return getName(vehicle.make) || vehicle.make_other || '';
}

function getModelName(vehicle) {
    if (!vehicle) return '';
    return getName(vehicle.model) || vehicle.model_other || '';
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    return [getMakeName(vehicle), getModelName(vehicle), vehicle.year].filter(Boolean).join(' ');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US');
}

// Currency formatter
// This should probably be a global helper used everywhere
function formatCurrency(value) {
    if (!value) return '';
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0
    }).format(value);
}

function getStatusClass(status) {
    const classes = { open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    return classes[status] || classes.open;
}

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    // Simplified map (should match what is used in other components)
    const colorMap = {
        'أبيض': '#ffffff', 'أسود': '#1f2937', 'فضي': '#9ca3af', 'رمادي': '#6b7280', 'أحمر': '#ef4444',
        'أزرق': '#3b82f6', 'أخضر': '#22c55e', 'ذهبي': '#eab308', 'بني': '#92400e', 'برتقالي': '#f97316',
        'white': '#ffffff', 'black': '#1f2937', 'silver': '#9ca3af', 'gray': '#6b7280', 'red': '#ef4444',
        'blue': '#3b82f6', 'green': '#22c55e', 'gold': '#eab308', 'brown': '#92400e', 'orange': '#f97316',
    };
    return colorMap[colorName.toLowerCase()] || colorMap[colorName] || '#9ca3af';
}

function getQuoteStatusClass(status) {
    const classes = { draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', converted: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' };
    return classes[status] || classes.draft;
}

// Modal handlers
function openWorkOrderModal() { showWorkOrderModal.value = true; }
function openQuoteModal() { showQuoteModal.value = true; }
async function confirmDelete() { 
    if (!props.canDelete) return; 
    const confirmed = await confirm({ title: t("vehicles.delete_confirm_title") || t("common.delete_confirm"), message: t("vehicles.delete_confirm_message") || t("common.delete_confirm_message"), confirmText: t("common.delete"), cancelText: t("common.cancel"), type: "danger" }); 
    if (confirmed) deleteVehicle(); 
}

function deleteVehicle() { router.delete(route('vehicles.destroy', props.vehicle.id)); }
function handleVehicleSaved() { showEditModal.value = false; router.reload(); }
function handleWorkOrderSaved() { showWorkOrderModal.value = false; router.reload(); }
function handleQuoteSaved() { showQuoteModal.value = false; router.reload(); }
</script>
