<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-600 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('quotes.title') }}</h1>
                    </div>

                    <!-- Add Button -->
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 text-sm sm:text-base w-full sm:w-auto bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>{{ $t('quotes.add') }}</span>
                    </button>
                </div>

                <!-- Tabs Section -->
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <button
                        @click="activeTab = 'pending'"
                        :class="[
                            'flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all',
                            activeTab === 'pending'
                                ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 shadow-sm'
                                : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $t('quotes.tabs.without_card') }}
                        <span class="px-1.5 py-0.5 text-xs rounded-full bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200">
                            {{ pendingCount }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'all'"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'all'
                                ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 shadow-sm'
                                : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        {{ $t('quotes.tabs.all') }}
                        <span class="px-1.5 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            {{ allCount }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row gap-3 md:gap-4">
                    <!-- Comprehensive Search -->
                    <div class="relative w-full lg:flex-1">
                        <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="filters.search"
                            :placeholder="$t('quotes.filters.search_placeholder')"
                            class="w-full ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                        />
                    </div>

                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-3">
                        <!-- Status Filter -->
                        <SelectInput
                            v-model="filters.status"
                            color="amber"
                            class="w-full sm:w-auto sm:min-w-[150px]"
                        >
                            <option value="">{{ $t('quotes.filters.all_statuses') }}</option>
                            <option value="draft">{{ $t('quotes.status.draft') }}</option>
                            <option value="sent">{{ $t('quotes.status.sent') }}</option>
                            <option value="approved">{{ $t('quotes.status.approved') }}</option>
                            <option value="rejected">{{ $t('quotes.status.rejected') }}</option>
                            <option value="converted">{{ $t('quotes.status.converted') }}</option>
                        </SelectInput>

                        <!-- Date Range Filter -->
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap hidden sm:inline">{{ $t('quotes.filters.date_range') }}</span>
                            <SelectInput
                                v-model="filters.dateRange"
                                color="amber"
                                class="w-full sm:w-auto min-w-[140px]"
                            >
                                <option value="all">{{ $t('quotes.filters.all_time') }}</option>
                                <option value="today">{{ $t('quotes.filters.today') }}</option>
                                <option value="week">{{ $t('quotes.filters.last_week') }}</option>
                                <option value="month">{{ $t('quotes.filters.last_month') }}</option>
                                <option value="30days">{{ $t('quotes.filters.last_30_days') }}</option>
                            </SelectInput>
                        </div>

                        <!-- Actions Group -->
                        <div class="flex items-center gap-2 ms-auto w-auto">
                            <!-- Clear Filters -->
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="px-3 py-2 text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors whitespace-nowrap"
                            >
                                {{ $t('quotes.filters.clear') }}
                            </button>
        
                            <!-- View Toggle -->
                            <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                <button
                                    @click="viewMode = 'grid'"
                                    :class="[
                                        'p-2 rounded-md transition-all',
                                        viewMode === 'grid'
                                            ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm'
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
                                        'p-2 rounded-md transition-all',
                                        viewMode === 'list'
                                            ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm'
                                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!quotes" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-amber-200 dark:border-amber-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-amber-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredQuotes.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('quotes.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('quotes.empty_hint') }}</p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('quotes.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                <div
                    v-for="quote in filteredQuotes"
                    :key="quote.id"
                    class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-lg cursor-pointer transition-all overflow-hidden"
                >
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 dark:text-white">{{ quote.code }}</span>
                                <span class="text-xs text-gray-400">{{ formatDate(quote.created_at) }}</span>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="getStatusClass(quote.status)"
                            >
                                {{ $t(`quotes.status.${quote.status}`) }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 space-y-3" @click="navigateToShow(quote)">
                        <!-- Vehicle Info -->
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ quote.vehicle?.plate_number }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ getVehicleName(quote.vehicle) }}</p>
                            </div>
                        </div>

                        <!-- Customer Info -->
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="truncate">{{ quote.customer?.name }}</span>
                        </div>

                        <!-- Total -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('quotes.form.grand_total') }}</span>
                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ formatCurrency(quote.total) }}</span>
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
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.code') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.vehicle') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.customer') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.total') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.status') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="quote in filteredQuotes"
                                :key="quote.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ quote.code }}</span>
                                        <p class="text-xs text-gray-400">{{ formatDate(quote.created_at) }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ quote.vehicle?.plate_number }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ getVehicleName(quote.vehicle) }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ quote.customer?.name }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ formatCurrency(quote.total) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                        :class="getStatusClass(quote.status)"
                                    >
                                        {{ $t(`quotes.status.${quote.status}`) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="navigateToShow(quote)"
                                            class="p-1.5 text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                            :title="$t('common.view')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Info -->
            <div v-if="quotes && filteredQuotes.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t('quotes.showing') }} 
                <span class="font-medium text-gray-900 dark:text-white">{{ filteredQuotes.length }}</span>
                {{ $t('quotes.of') }}
                <span class="font-medium text-gray-900 dark:text-white">{{ quotes.total }}</span>
            </div>
        </div>

        <!-- Quote Form Modal -->
        <QuoteFormModal
            :show="showModal"
            :quote="selectedQuote"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="closeModal"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import AppLayout from '@/Layouts/AppLayout.vue';
import SelectInput from '@/Components/SelectInput.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';

const props = defineProps({
    quotes: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    departments: {
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
});

const { t } = useI18n();
const { success } = useToast();
const { confirm } = useConfirm();
const { formatCurrency: formatCurrencyEN } = useNumberFormat();
const showModal = ref(false);
const selectedQuote = ref(null);
const activeTab = ref(localStorage.getItem('quotesActiveTab') || 'pending');
const viewMode = ref(localStorage.getItem('quotesViewMode') || 'grid');

const filters = ref({
    search: '',
    status: '',
    dateRange: 'all',
});

watch(viewMode, (newMode) => {
    localStorage.setItem('quotesViewMode', newMode);
});

watch(activeTab, (newTab) => {
    localStorage.setItem('quotesActiveTab', newTab);
});

// Computed: Count of quotes without work card (not converted and not rejected)
const pendingCount = computed(() => {
    if (!props.quotes?.data) return 0;
    return props.quotes.data.filter(q => q.status !== 'converted' && q.status !== 'rejected').length;
});

// Computed: Total count
const allCount = computed(() => {
    return props.quotes?.total || 0;
});

// Computed: Check if any filters are active
const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.status || filters.value.dateRange !== 'all';
});

// Computed: Filter quotes based on tab and filters
const filteredQuotes = computed(() => {
    if (!props.quotes?.data) return [];
    
    let result = [...props.quotes.data];
    
    // Tab filter
    if (activeTab.value === 'pending') {
        // Pending = quotes that need action (not converted and not rejected)
        result = result.filter(q => q.status !== 'converted' && q.status !== 'rejected');
    }
    
    // Comprehensive Search Filter (Code, Customer Name, Phone, Vehicle Plate)
    if (filters.value.search) {
        const searchTerm = filters.value.search.toLowerCase();
        result = result.filter(q => {
            const matchesCode = q.code?.toLowerCase().includes(searchTerm);
            const matchesCustomer = q.customer?.name?.toLowerCase().includes(searchTerm) || 
                                  q.customer?.phone?.includes(searchTerm);
            const matchesVehicle = q.vehicle?.plate_number?.toLowerCase().includes(searchTerm);
            
            return matchesCode || matchesCustomer || matchesVehicle;
        });
    }
    
    // Status filter
    if (filters.value.status) {
        result = result.filter(q => q.status === filters.value.status);
    }
    
    // Date range filter
    if (filters.value.dateRange !== 'all') {
        const now = new Date();
        let startDate;
        
        switch (filters.value.dateRange) {
            case 'today':
                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                break;
            case 'week':
                startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                break;
            case 'month':
                startDate = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                break;
            case '30days':
                startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                break;
        }
        
        if (startDate) {
            result = result.filter(q => new Date(q.created_at) >= startDate);
        }
    }
    
    return result;
});

function clearFilters() {
    filters.value = {
        search: '',
        status: '',
        dateRange: 'all',
    };
}

function getStatusClass(status) {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        converted: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    };
    return classes[status] || classes.draft;
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    const parts = [];
    if (vehicle.make?.name) parts.push(vehicle.make.name);
    if (vehicle.model?.name) parts.push(vehicle.model.name);
    if (vehicle.year) parts.push(vehicle.year);
    return parts.join(' ') || '';
}

function formatCurrency(amount) {
    return formatCurrencyEN(amount) + ' ' + t('common.currency');
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('ar-SA');
}

function canShowActions(quote) {
    return quote.status === 'draft' || quote.status === 'sent';
}

function openCreateModal() {
    selectedQuote.value = null;
    showModal.value = true;
}

function openEditModal(quote) {
    if (quote.status !== 'draft' && quote.status !== 'sent') {
        return;
    }
    selectedQuote.value = quote;
    showModal.value = true;
}

function navigateToShow(quote) {
    router.visit(route('app.quotes.show', quote.id));
}

function closeModal() {
    showModal.value = false;
    selectedQuote.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['quotes'] });
}

async function approveQuote(quote) {
    const confirmed = await confirm({
        title: t('quotes.actions.approve'),
        message: t('quotes.messages.confirm_approve'),
        confirmText: t('quotes.actions.approve'),
        cancelText: t('common.cancel'),
        type: 'success',
    });

    if (confirmed) {
        router.post(`/app/quotes/${quote.id}/approve`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                success(t('quotes.messages.approved_success'));
                router.reload({ only: ['quotes'] });
            },
        });
    }
}

async function rejectQuote(quote) {
    const confirmed = await confirm({
        title: t('quotes.actions.reject'),
        message: t('quotes.messages.confirm_reject'),
        confirmText: t('quotes.actions.reject'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.post(`/app/quotes/${quote.id}/reject`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                success(t('quotes.messages.rejected_success'));
                router.reload({ only: ['quotes'] });
            },
        });
    }
}
</script>
