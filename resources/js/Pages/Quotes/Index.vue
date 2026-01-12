<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between sm:flex-wrap gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('quotes.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ toEnglish(quotes?.total || 0) }} {{
                                $t('quotes.total_count') }}</p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="searchQuery" :placeholder="$t('quotes.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" />
                        </div>

                        <!-- View Toggle -->
                        <div class="flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1">
                            <button @click="viewMode = 'grid'" :class="[
                                'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                viewMode === 'grid'
                                    ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            ]">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
                                </svg>
                            </button>
                            <button @click="viewMode = 'list'" :class="[
                                'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                viewMode === 'list'
                                    ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            ]">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Add Button -->
                        <button v-if="can('quotes.create')" @click="openCreateModal"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ $t('quotes.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs - Modern Compact Design -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-3">
                    <button v-for="tab in filterTabs" :key="tab.key" @click="setStatusFilter(tab.key)" :class="[
                        'relative flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200',
                        currentStatus === tab.key
                            ? 'text-white shadow-lg'
                            : 'bg-white dark:bg-gray-800 hover:shadow-md border border-gray-200 dark:border-gray-700'
                    ]"
                        :style="currentStatus === tab.key ? `background: linear-gradient(135deg, ${tab.gradientFrom}, ${tab.gradientTo})` : ''">
                        <!-- Icon -->
                        <div :class="[
                            'w-8 h-8 rounded-lg flex items-center justify-center',
                            currentStatus === tab.key
                                ? 'bg-white/20'
                                : tab.bgColor
                        ]">
                            <component :is="tab.icon" :class="[
                                'w-4 h-4',
                                currentStatus === tab.key ? 'text-white' : tab.iconColor
                            ]" />
                        </div>

                        <!-- Label & Count -->
                        <div class="flex items-center gap-2">
                            <span :class="[
                                'font-medium text-sm',
                                currentStatus === tab.key ? 'text-white' : 'text-gray-700 dark:text-gray-300'
                            ]">
                                {{ tab.label }}
                            </span>
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-xs font-bold',
                                currentStatus === tab.key
                                    ? 'bg-white/20 text-white'
                                    : tab.bgColor + ' ' + tab.iconColor
                            ]">
                                {{ toEnglish(tab.count) }}
                            </span>
                        </div>

                        <!-- Selected Check -->
                        <svg v-if="currentStatus === tab.key" class="w-4 h-4 text-white/80" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>


            <!-- Loading State -->
            <div v-if="!quotes" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-amber-200 dark:border-amber-900 rounded-full"></div>
                    <div
                        class="absolute top-0 left-0 w-12 h-12 border-4 border-amber-600 border-t-transparent rounded-full animate-spin">
                    </div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredQuotes.length === 0"
                class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div
                    class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('quotes.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('quotes.empty_hint') }}</p>
                <button v-if="can('quotes.create')" @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('quotes.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                <div v-for="quote in filteredQuotes" :key="quote.id" @click="navigateToShow(quote)"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden">
                    <!-- Background Logo Watermark -->
                    <div v-if="quote.vehicle?.make?.logo_path"
                        class="absolute inset-x-0 bottom-0 top-12 flex items-center justify-center opacity-[0.06] dark:opacity-[0.2] pointer-events-none select-none z-0 overflow-hidden">
                        <img :src="`/storage/${quote.vehicle.make.logo_path}`"
                            class="w-3/4 h-3/4 object-contain grayscale dark:brightness-150 transform -rotate-12 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-700"
                            alt="" />
                    </div>

                    <!-- Card Content -->
                    <div class="relative z-10 flex flex-col h-full">
                        <!-- Header: Code & Status -->
                        <div
                            class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent dark:from-gray-900/50">
                            <span
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 tracking-widest uppercase">
                                {{ toEnglish(quote.code) }}
                            </span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider"
                                :class="getStatusClass(quote.status)">
                                {{ $t(`quotes.status.${quote.status}`) }}
                            </span>
                        </div>

                        <!-- Body: Quote Info -->
                        <div class="p-5 flex-1 flex flex-col items-center text-center">
                            <!-- Vehicle Plate (Main Title) -->
                            <div class="mb-4">
                                <span
                                    class="inline-block px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-xl font-black tracking-wider rounded-lg border border-gray-200 dark:border-gray-600 font-mono">
                                    {{ toEnglish(quote.vehicle?.plate_number) || $t('common.na') }}
                                </span>
                            </div>

                            <!-- Vehicle Details -->
                            <div class="space-y-1 mb-4">
                                <h3
                                    class="text-base font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                    {{ getVehicleName(quote.vehicle) }}
                                </h3>
                                <div
                                    class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>{{ quote.customer?.name || $t('common.unknown') }}</span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <span
                                    class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold rounded-lg border border-blue-100 dark:border-blue-800/50 shadow-sm">
                                    {{ formatDate(quote.created_at) }}
                                </span>
                            </div>
                        </div>

                        <!-- Footer: Total Section -->
                        <div
                            class="px-5 py-4 bg-gradient-to-t from-gray-50/80 to-transparent dark:from-gray-900/40 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 italic">
                                {{ $t('quotes.form.grand_total') }}
                            </span>
                            <div class="flex flex-col items-end">
                                <span class="text-xl font-black text-amber-600 dark:text-amber-400">
                                    {{ formatCurrency(quote.total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.code') }}
                                </th>
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.vehicle') }}
                                </th>
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.customer') }}
                                </th>
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.status') }}
                                </th>
                                <th
                                    class="px-5 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('quotes.columns.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="quote in filteredQuotes" :key="quote.id" @click="navigateToShow(quote)"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors">
                                <td class="px-5 py-4">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ quote.code }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{
                                            quote.vehicle?.plate_number }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{
                                            getVehicleName(quote.vehicle) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ quote.customer?.name }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider"
                                        :class="getStatusClass(quote.status)">
                                        {{ $t(`quotes.status.${quote.status}`) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-end">
                                    <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                                        {{ formatCurrency(quote.total) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scroll Sentinel -->
            <div ref="loadMoreSentinel" class="py-6 flex justify-center w-full">
                <div v-if="loadingMore" class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="text-sm font-medium">{{ $t('common.loading') }}</span>
                </div>
                <div v-else-if="allQuotes.length >= (quotes?.total || 0) && (quotes?.total || 0) > 0"
                    class="text-sm text-gray-400 dark:text-gray-600">
                    {{ $t('quotes.all_loaded') || 'All quotes loaded' }}
                </div>
            </div>
        </div>

        <!-- Quote Form Modal -->
        <QuoteFormModal :show="showModal" :quote="selectedQuote" :customers="customers" :departments="departments"
            :makes="makes" :colors="colors" :modelsByMake="modelsByMake" @close="closeModal" @saved="handleSaved" />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import { usePermission } from '@/Composables/usePermission';

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
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterCounts: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const { success } = useToast();
const { confirm } = useConfirm();
const { formatCurrency: formatCurrencyEN, toEnglish } = useNumberFormat();
const { can } = usePermission();
const showModal = ref(false);
const selectedQuote = ref(null);
const activeTab = ref(localStorage.getItem('quotesActiveTab') || 'pending');
const viewMode = ref(localStorage.getItem('quotesViewMode') || 'grid');

// Infinite Scroll Refs
const allQuotes = ref(props.quotes?.data || []);
const nextPageUrl = ref(props.quotes?.next_page_url);
const loadingMore = ref(false);
const loadMoreSentinel = ref(null);
let observer = null;

// Watch props change (filters, etc)
watch(() => props.quotes, (newVal) => {
    if (newVal) {
        allQuotes.value = newVal.data;
        nextPageUrl.value = newVal.next_page_url;
    }
});

// Load more data
const loadMore = async () => {
    if (loadingMore.value || !nextPageUrl.value) return;

    loadingMore.value = true;
    try {
        const url = new URL(nextPageUrl.value);
        // Map to API endpoint
        const apiUrl = url.toString().replace('/app/quotes', '/app/api/quotes-index');

        const response = await axios.get(apiUrl);
        const data = response.data;

        allQuotes.value.push(...data.data);
        nextPageUrl.value = data.next_page_url;
    } catch (e) {
        console.error('Failed to load more quotes', e);
    } finally {
        loadingMore.value = false;
    }
};

// Intersection Observer Setup
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && nextPageUrl.value) {
            loadMore();
        }
    }, {
        root: null,
        threshold: 0.1,
    });

    if (loadMoreSentinel.value) {
        observer.observe(loadMoreSentinel.value);
    }

    // Check sentinel ref changes (in case of tab switch/v-if)
    watch(loadMoreSentinel, (el) => {
        if (observer) observer.disconnect();
        if (el) observer.observe(el);
    });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// Icons for filter tabs
const IconAll = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>` };
const IconPending = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };
const IconApproved = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };
const IconConverted = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>` };
const IconRejected = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` };

const filterTabs = computed(() => [
    {
        key: 'pending',
        label: t('quotes.tabs.without_card'),
        icon: IconPending,
        iconColor: 'text-amber-500',
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        gradientFrom: '#f59e0b',
        gradientTo: '#d97706',
        count: props.filterCounts.pending || 0
    },
    {
        key: 'all',
        label: t('quotes.tabs.all'),
        icon: IconAll,
        iconColor: 'text-slate-500',
        bgColor: 'bg-slate-100 dark:bg-slate-900/30',
        gradientFrom: '#64748b',
        gradientTo: '#475569',
        count: props.filterCounts.all || 0
    },
    {
        key: 'approved',
        label: t('quotes.status.approved'),
        icon: IconApproved,
        iconColor: 'text-emerald-500',
        bgColor: 'bg-emerald-100 dark:bg-emerald-900/30',
        gradientFrom: '#10b981',
        gradientTo: '#059669',
        count: props.filterCounts.approved || 0
    },
    {
        key: 'converted',
        label: t('quotes.status.converted'),
        icon: IconConverted,
        iconColor: 'text-blue-500',
        bgColor: 'bg-blue-100 dark:bg-blue-900/30',
        gradientFrom: '#3b82f6',
        gradientTo: '#2563eb',
        count: props.filterCounts.converted || 0
    },
    {
        key: 'rejected',
        label: t('quotes.status.rejected'),
        icon: IconRejected,
        iconColor: 'text-red-500',
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        gradientFrom: '#ef4444',
        gradientTo: '#dc2626',
        count: props.filterCounts.rejected || 0
    },
]);

// Determine current status from URL filters
const currentStatus = computed(() => props.filters.status || 'pending');

const searchQuery = ref(props.filters.search || '');

function setStatusFilter(status) {
    router.get(route('app.quotes.index'), {
        status: status,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const filters = ref({
    search: props.filters.search || '',
    status: '',
    dateRange: 'all',
});

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

watch(searchQuery, debounce((value) => {
    router.get(route('app.quotes.index'), {
        search: value,
        status: currentStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

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

// Computed: Filter quotes - using allQuotes ref for infinite scroll
const filteredQuotes = computed(() => {
    return allQuotes.value;
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
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function getWhatsAppNumber(customer) {
    if (!customer) return null;
    const phone = customer.whatsapp || customer.phone;
    if (!phone) return null;
    return phone.replace(/[^\d+]/g, "").replace(/^\+/, "");
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
</script>
