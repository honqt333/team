<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Back + Title + Count -->
                    <div class="flex items-center gap-4">
                        <!-- Back Button -->
                        <Link
                            :href="route('work-orders.hub')"
                            class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-300 transition-all"
                        >
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" :class="statusFilter === 'closed' ? 'bg-gradient-to-br from-slate-500 to-gray-600 shadow-slate-500/30' : 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/30'">
                            <svg v-if="statusFilter === 'closed'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ pageTitle }}</h1>
                            <p v-if="workOrders" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ toEnglish(workOrders.total) }} {{ $t('work_orders.total_count') }}
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

            <!-- Filter Tabs (only for open status) - Modern Compact Design -->
            <div v-if="statusFilter === 'open'" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-3">
                <button
                    v-for="tab in filterTabs"
                    :key="tab.key"
                    @click="setSubFilter(tab.key)"
                    :class="[
                        'relative flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200',
                        currentSubFilter === tab.key
                            ? 'text-white shadow-lg'
                            : 'bg-white dark:bg-gray-800 hover:shadow-md border border-gray-200 dark:border-gray-700'
                    ]"
                    :style="currentSubFilter === tab.key ? `background: linear-gradient(135deg, ${tab.gradientFrom}, ${tab.gradientTo})` : ''"
                >
                    <!-- Icon -->
                    <div :class="[
                        'w-8 h-8 rounded-lg flex items-center justify-center',
                        currentSubFilter === tab.key
                            ? 'bg-white/20'
                            : tab.bgColor
                    ]">
                        <component 
                            :is="tab.icon" 
                            :class="[
                                'w-4 h-4',
                                currentSubFilter === tab.key ? 'text-white' : tab.iconColor
                            ]" 
                        />
                    </div>
                    
                    <!-- Label & Count -->
                    <div class="flex items-center gap-2">
                        <span :class="[
                            'font-medium text-sm',
                            currentSubFilter === tab.key ? 'text-white' : 'text-gray-700 dark:text-gray-300'
                        ]">
                            {{ tab.label }}
                        </span>
                        <span :class="[
                            'px-2 py-0.5 rounded-full text-xs font-bold',
                            currentSubFilter === tab.key 
                                ? 'bg-white/20 text-white' 
                                : tab.bgColor + ' ' + tab.iconColor
                        ]">
                            {{ toEnglish(tab.count) }}
                        </span>
                    </div>
                    
                    <!-- Selected Check -->
                    <svg v-if="currentSubFilter === tab.key" class="w-4 h-4 text-white/80" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
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
            <div v-else-if="workOrders.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('work_orders.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('work_orders.empty_hint') }}</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('work_orders.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                <div
                    v-for="order in workOrders.data"
                    :key="order.id"
                    @click="router.visit(route('work-orders.show', order.id))"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden"
                >
                    <!-- Background Logo Watermark -->
                    <div 
                        v-if="order.vehicle?.make?.logo_path"
                        class="absolute inset-x-0 bottom-0 top-12 flex items-center justify-center opacity-[0.06] dark:opacity-[0.2] pointer-events-none select-none z-0 overflow-hidden"
                    >
                        <img 
                            :src="`/storage/${order.vehicle.make.logo_path}`" 
                            class="w-3/4 h-3/4 object-contain grayscale dark:brightness-150 transform -rotate-12 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-700"
                            alt=""
                        />
                    </div>

                    <!-- Card Content -->
                    <div class="relative z-10 flex flex-col h-full">
                        <!-- Header: Code & Status -->
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent dark:from-gray-900/50">
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 tracking-widest uppercase">
                                {{ toEnglish(order.code) }}
                            </span>
                            <span
                                class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider"
                                :class="getStatusClass(order.status)"
                            >
                                {{ $t(`work_orders.status.${order.status}`) }}
                            </span>
                        </div>

                        <!-- Body: Order Info -->
                        <div class="p-5 flex-1 flex flex-col items-center text-center">
                            <!-- Vehicle Plate (Main Title) -->
                            <div class="mb-4">
                                <span class="inline-block px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-xl font-black tracking-wider rounded-lg border border-gray-200 dark:border-gray-600 font-mono" dir="ltr">
                                    {{ toEnglish(order.vehicle?.plate_number) || $t('common.na') }}
                                </span>
                            </div>

                            <!-- Vehicle Details -->
                            <div class="space-y-1 mb-4">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ getVehicleName(order.vehicle) }}
                                </h3>
                                <div class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>{{ order.customer?.name || $t('common.unknown') }}</span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <!-- Due Date / Closed Date -->
                                <span 
                                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg border shadow-sm"
                                    :class="getDateClass(order)"
                                >
                                    {{ getDateLabel(order) }}: {{ formatDate(getDisplayDate(order)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Footer: Invoice Amounts -->
                        <div class="px-5 py-4 bg-gradient-to-t from-gray-50/80 to-transparent dark:from-gray-900/40 border-t border-gray-100 dark:border-gray-700/50">
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <span class="text-[10px] text-gray-400 block">{{ $t('work_orders.invoice_total') }}</span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(order.total || 0) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 block">{{ $t('work_orders.paid') }}</span>
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ formatCurrency(order.paid_amount || 0) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 block">{{ $t('work_orders.remaining') }}</span>
                                    <span class="text-sm font-bold" :class="(order.total - (order.paid_amount || 0)) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500'">
                                        {{ formatCurrency((order.total || 0) - (order.paid_amount || 0)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
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
                                v-for="order in workOrders.data"
                                :key="order.id"
                                @click="router.visit(route('work-orders.show', order.id))"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400">#{{ order.code || order.id }}</span>
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
            <div v-if="workOrders && workOrders.data.length > 0" class="flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('customers.showing') }} 
                    <span class="font-medium text-gray-900 dark:text-white">{{ workOrders.from }}</span>-<span class="font-medium text-gray-900 dark:text-white">{{ workOrders.to }}</span>
                    {{ $t('customers.of') }}
                    <span class="font-medium text-gray-900 dark:text-white">{{ workOrders.total }}</span>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-for="link in workOrders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-sm',
                            link.active ? 'bg-indigo-600 text-white shadow-indigo-500/20' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <WorkOrderFormModal
            :show="showCreateModal"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="showCreateModal = false"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { success } = useToast();
const { toEnglish } = useNumberFormat();

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
    colors: {
        type: Array,
        default: () => [],
    },
    modelsByMake: {
        type: Object,
        default: () => ({}),
    },
    departments: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statusFilter: {
        type: String,
        default: null,
    },
    subFilter: {
        type: String,
        default: null,
    },
    filterCounts: {
        type: Object,
        default: () => ({}),
    },
});

// Icons for filter tabs
const IconOpen = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>` };
const IconDraft = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>` };
const IconOverdue = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };
const IconPending = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };

const filterTabs = computed(() => [
    { 
        key: 'in_progress', 
        label: t('work_orders.filters.open'), 
        icon: IconOpen, 
        iconColor: 'text-emerald-500', 
        bgColor: 'bg-emerald-100 dark:bg-emerald-900/30',
        gradientFrom: '#10b981',
        gradientTo: '#059669',
        count: props.filterCounts.open || 0 
    },
    { 
        key: 'draft', 
        label: t('work_orders.filters.draft'), 
        icon: IconDraft, 
        iconColor: 'text-slate-500', 
        bgColor: 'bg-slate-100 dark:bg-slate-900/30',
        gradientFrom: '#64748b',
        gradientTo: '#475569',
        count: props.filterCounts.draft || 0 
    },
    { 
        key: 'overdue', 
        label: t('work_orders.filters.overdue'), 
        icon: IconOverdue, 
        iconColor: 'text-red-500', 
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        gradientFrom: '#ef4444',
        gradientTo: '#dc2626',
        count: props.filterCounts.overdue || 0 
    },
    { 
        key: 'pending_payment', 
        label: t('work_orders.filters.pending_payment'), 
        icon: IconPending, 
        iconColor: 'text-amber-500', 
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        gradientFrom: '#f59e0b',
        gradientTo: '#d97706',
        count: props.filterCounts.pending_payment || 0 
    },
]);

const currentSubFilter = computed(() => props.subFilter || 'in_progress');

const pageTitle = computed(() => {
    if (props.statusFilter === 'open') return t('work_orders.list.open_title');
    if (props.statusFilter === 'closed') return t('work_orders.list.closed_title');
    return t('work_orders.list.all_title');
});

const showCreateModal = ref(false);
const viewMode = ref(localStorage.getItem('workOrdersViewMode') || 'grid');
const searchQuery = ref(props.filters.search || '');

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

function setSubFilter(filter) {
    router.get(route('work-orders.index'), { 
        status: props.statusFilter,
        sub_filter: filter,
        search: searchQuery.value 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(searchQuery, debounce((value) => {
    router.get(route('work-orders.index'), { 
        status: props.statusFilter,
        sub_filter: props.subFilter,
        search: value 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

watch(viewMode, (newMode) => {
    localStorage.setItem('workOrdersViewMode', newMode);
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
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { 
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Check if order is overdue (expected_end_date passed and still open)
function isOverdue(order) {
    if (!order.expected_end_date) return false;
    if (['done', 'cancelled'].includes(order.status)) return false;
    const dueDate = new Date(order.expected_end_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return dueDate < today;
}

// Get date to display: closed_at for closed orders, expected_end_date for open
function getDisplayDate(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return order.closed_at || order.updated_at;
    }
    return order.expected_end_date || order.entry_date;
}

// Get date label based on status
function getDateLabel(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return t('work_orders.closed_date');
    }
    return t('work_orders.due_date');
}

// Get date styling class based on status and overdue
function getDateClass(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return 'bg-gray-50 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700';
    }
    if (isOverdue(order)) {
        return 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-200 dark:border-red-700';
    }
    return 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-800/50';
}

function formatCurrency(value) {
    return parseFloat(value || 0).toFixed(2);
}

function handleSaved() {
    showCreateModal.value = false;
    success(t('common.saved_success'));
    router.reload({ only: ['workOrders'] });
}
</script>
