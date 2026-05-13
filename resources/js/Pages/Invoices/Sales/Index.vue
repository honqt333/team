<template>
    <AppLayout :title="$t('invoices.sales.title')">
        <div class="space-y-6">

            <!-- Header Section -->
            <PageHeader
                :title="$t('invoices.sales.title')"
                :subtitle="$t('invoices.sales.subtitle')"
                :totalCount="invoices ? toEnglish(invoices.total) : null"
                :countLabel="$t('invoices.sales.tab_invoices')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-600"
                glowFrom="from-blue-600"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #back>
                    <Link
                        :href="route('app.invoices.hub')"
                        :title="$t('common.back')"
                        class="p-2.5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-300 text-blue-600"
                    >
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative group">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="localFilters.search"
                                :placeholder="$t('common.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-100 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-md text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-48">
                            <SearchableSelect
                                v-model="localFilters.payment_status"
                                :options="statusOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('invoices.all_statuses')"
                                class="!rounded-2xl"
                                compact
                            />
                        </div>

                        <!-- Date From -->
                        <CustomDatePicker
                            v-model="localFilters.date_from"
                            :placeholder="$t('work_orders.filters.date_from')"
                        />

                        <!-- Date To -->
                        <CustomDatePicker
                            v-model="localFilters.date_to"
                            :placeholder="$t('work_orders.filters.date_to')"
                        />

                        <!-- View Toggle -->
                        <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'grid'
                                        ? 'bg-blue-600 text-white shadow-blue-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'list'
                                        ? 'bg-blue-600 text-white shadow-blue-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex border-b border-gray-200 dark:border-gray-700 px-2 pt-2 gap-1 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 text-sm font-semibold whitespace-nowrap transition-all duration-200 border-b-2 -mb-px',
                            activeTab === tab.key
                                ? 'border-blue-600 text-blue-700 dark:text-blue-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                        ]"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="tab.iconPath"/>
                        {{ tab.label }}
                        <span
                            v-if="tab.count > 0"
                            :class="activeTab === tab.key
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                            class="min-w-[20px] h-5 px-1.5 text-xs font-bold rounded-full flex items-center justify-center"
                        >{{ toEnglish(tab.count) }}</span>
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="p-0">

                    <!-- INVOICES TAB -->
                    <div v-show="activeTab === 'invoices'">

                        <!-- Empty State -->
                        <div v-if="!invoices.data?.length" class="py-20 text-center">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('common.no_data') }}</p>
                        </div>

                        <!-- Grid View -->
                        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4">
                            <Link
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                                :href="route('app.invoices.show', invoice.id)"
                                class="group relative flex flex-col bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-[0_20px_40px_-10px_rgba(59,130,246,0.2)] transition-all duration-300 cursor-pointer overflow-hidden"
                            >
                                <!-- Top gradient stripe -->
                                <div class="h-1 w-full" :class="stripeClass(invoice.payment_status)"/>

                                <div class="p-5">
                                    <!-- Number + Status -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <span class="text-xs font-black text-blue-600 dark:text-blue-400 tracking-wide">#{{ invoice.invoice_number }}</span>
                                            <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white truncate max-w-[140px]">{{ invoice.customer?.name || '—' }}</p>
                                        </div>
                                        <span :class="statusClass(invoice.payment_status)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold">
                                            {{ $t(`invoices.status.${invoice.payment_status}`) }}
                                        </span>
                                    </div>

                                    <!-- Financials -->
                                    <div class="space-y-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.total') }}</span>
                                            <span class="font-bold text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(invoice.total_incl_tax) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.balance') }}</span>
                                            <span :class="invoice.balance > 0 ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'" class="font-bold" dir="ltr">{{ formatCurrency(invoice.balance) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.issue_date') }}</span>
                                            <span class="text-gray-600 dark:text-gray-300">{{ formatDate(invoice.issue_date) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- List View -->
                        <div v-else class="w-full bg-white dark:bg-gray-800 rounded-b-2xl overflow-hidden">
                            <div class="w-full overflow-x-auto">
                                <table class="w-full min-w-[800px] divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <thead>
                                        <tr class="bg-gray-50/50 dark:bg-gray-900/80">
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.invoice_number') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.issue_date') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('common.customer') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.total') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.total_paid') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.balance') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('common.status') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('common.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                                        <tr
                                            v-for="invoice in invoices.data"
                                            :key="invoice.id"
                                            class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all duration-200"
                                        >
                                            <td class="px-4 py-4">
                                                <Link :href="route('app.invoices.show', invoice.id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                                    {{ invoice.invoice_number }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ formatDate(invoice.issue_date) }}</td>
                                            <td class="px-4 py-4">
                                                <p class="font-medium text-gray-900 dark:text-white truncate max-w-[180px]">{{ invoice.customer?.name || '—' }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-sm font-bold text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(invoice.total_incl_tax) }}</td>
                                            <td class="px-4 py-4 text-sm font-medium text-emerald-600 dark:text-emerald-400" dir="ltr">{{ formatCurrency(invoice.total_paid) }}</td>
                                            <td class="px-4 py-4 text-sm font-bold" dir="ltr" :class="invoice.balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'">{{ formatCurrency(invoice.balance) }}</td>
                                            <td class="px-4 py-4">
                                                <span :class="statusClass(invoice.payment_status)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold">
                                                    {{ $t(`invoices.status.${invoice.payment_status}`) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-end">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Link :href="route('app.invoices.show', invoice.id)" class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    </Link>
                                                    <Link :href="route('app.invoices.print', invoice.id)" target="_blank" class="p-2 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="invoices.links?.length > 3" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-center justify-between flex-wrap gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('common.showing') }} {{ toEnglish(invoices.from) }} - {{ toEnglish(invoices.to) }} {{ $t('common.of') }} {{ toEnglish(invoices.total) }}
                            </span>
                            <div class="flex gap-1">
                                <Link
                                    v-for="link in invoices.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                                        link.active ? 'bg-blue-600 text-white shadow-sm' :
                                        link.url ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' :
                                        'bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed border border-gray-100 dark:border-gray-700'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RETURNS TAB -->
                    <div v-show="activeTab === 'returns'" class="py-20 text-center">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('invoices.sales.no_returns') }}</p>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { debounce } from 'lodash-es';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const statusOptions = [
    { value: 'unpaid', label: t('invoices.status.unpaid') },
    { value: 'partial', label: t('invoices.status.partial') },
    { value: 'paid', label: t('invoices.status.paid') },
];

const props = defineProps({
    invoices: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const activeTab = ref('invoices');
const viewMode = ref(localStorage.getItem('salesInvoicesViewMode') || 'list');

const tabs = computed(() => [
    {
        key: 'invoices',
        label: t('invoices.sales.tab_invoices'),
        count: props.invoices?.total || 0,
        iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    },
    {
        key: 'returns',
        label: t('invoices.sales.tab_returns'),
        count: 0,
        iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>',
    },
]);

const localFilters = ref({
    search: props.filters?.search || '',
    payment_status: props.filters?.payment_status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('app.invoices.sales.index'), {
        search: localFilters.value.search || undefined,
        payment_status: localFilters.value.payment_status || undefined,
        date_from: localFilters.value.date_from || undefined,
        date_to: localFilters.value.date_to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedApplyFilters = debounce(applyFilters, 400);

watch(localFilters, () => {
    debouncedApplyFilters();
}, { deep: true });

// Persist view mode
const setView = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('salesInvoicesViewMode', mode);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'SAR', numberingSystem: 'latn' }).format(amount || 0);

const statusClass = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        partial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};

const stripeClass = (status) => {
    const map = {
        paid: 'bg-emerald-500',
        partial: 'bg-amber-400',
        unpaid: 'bg-red-500',
    };
    return map[status] || 'bg-gray-200 dark:bg-gray-700';
};
</script>
