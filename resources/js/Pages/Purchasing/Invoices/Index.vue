<template>
    <AppLayout :title="$t('purchasing.invoices.title')">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-xl shadow-violet-500/30 flex items-center justify-center text-white">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ $t('purchasing.invoices.title') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            <span class="font-bold text-violet-600 animate-pulse">{{ invoices.total }}</span>
                            {{ $t('common.record') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-500/20 relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative">
                        <div class="text-[10px] font-black uppercase tracking-widest text-violet-100 mb-1">{{ $t('common.total') }}</div>
                        <div class="text-3xl font-black font-mono">{{ toEnglish(stats.total) }}</div>
                    </div>
                </div>
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-amber-200 transition-all">
                    <div class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">{{ $t('purchasing.invoices.statuses.open') }}</div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white font-mono">{{ toEnglish(stats.open) }}</div>
                </div>
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-emerald-200 transition-all">
                    <div class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">{{ $t('purchasing.invoices.statuses.paid') }}</div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white font-mono">{{ toEnglish(stats.paid) }}</div>
                </div>
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-gray-300 transition-all">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('purchasing.invoices.statuses.draft') }}</div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white font-mono">{{ toEnglish(stats.draft) }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" v-model="filters.search" @input="search"
                            :placeholder="$t('common.search')"
                            class="w-full ps-9 pe-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-sm focus:ring-2 focus:ring-violet-500 transition-all" />
                    </div>
                    <select v-model="filters.status" @change="search"
                        class="px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-sm focus:ring-2 focus:ring-violet-500 transition-all">
                        <option value="">{{ $t('common.all_statuses') }}</option>
                        <option value="draft">{{ $t('purchasing.invoices.statuses.draft') }}</option>
                        <option value="open">{{ $t('purchasing.invoices.statuses.open') }}</option>
                        <option value="paid">{{ $t('purchasing.invoices.statuses.paid') }}</option>
                        <option value="cancelled">{{ $t('purchasing.invoices.statuses.cancelled') }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('purchasing.invoices.code') }}</th>
                                <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('purchasing.orders.supplier') }}</th>
                                <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('purchasing.invoices.po_ref') }}</th>
                                <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('purchasing.orders.date') }}</th>
                                <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('purchasing.orders.expected_date') }}</th>
                                <th class="px-6 py-4 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('common.total') }}</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <tr v-for="inv in invoices.data" :key="inv.id"
                                @click="$inertia.visit(route('app.purchasing.invoices.show', inv.id))"
                                class="hover:bg-violet-50/40 dark:hover:bg-violet-900/10 cursor-pointer transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="font-black font-mono text-violet-600 dark:text-violet-400 group-hover:underline">{{ inv.code }}</span>
                                    <div v-if="inv.invoice_number" class="text-xs text-gray-400 font-mono">{{ inv.invoice_number }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ inv.supplier?.name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ inv.supplier?.code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="inv.purchase_order" class="text-xs font-mono text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-lg">
                                        {{ inv.purchase_order.code }}
                                    </span>
                                    <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-600 dark:text-gray-400 text-sm">{{ formatDate(inv.issue_date) }}</td>
                                <td class="px-6 py-4 font-mono text-sm" :class="isOverdue(inv) ? 'text-red-500 font-bold' : 'text-gray-600 dark:text-gray-400'">
                                    {{ inv.due_date ? formatDate(inv.due_date) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-end font-black font-mono text-gray-900 dark:text-white">{{ formatCurrency(inv.total) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="statusClass(inv.status)" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider">
                                        {{ $t(`purchasing.invoices.statuses.${inv.status}`) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data?.length">
                                <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                    </svg>
                                    <p class="font-bold">{{ $t('purchasing.invoices.empty') }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        {{ invoices.from }}–{{ invoices.to }} {{ $t('common.of') }} {{ invoices.total }}
                    </span>
                    <div class="flex gap-2">
                        <Link v-if="invoices.prev_page_url" :href="invoices.prev_page_url"
                            class="px-3 py-1.5 text-sm font-bold rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                            {{ $t('common.prev') }}
                        </Link>
                        <Link v-if="invoices.next_page_url" :href="invoices.next_page_url"
                            class="px-3 py-1.5 text-sm font-bold rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                            {{ $t('common.next') }}
                        </Link>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePage } from '@inertiajs/vue3';

const { formatCurrency, toEnglish } = useNumberFormat();

const props = defineProps({
    invoices: Object,
    filters: Object,
    stats: Object,
});

const filters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) : '—';

const isOverdue = (inv) => {
    if (!inv.due_date || inv.status === 'paid' || inv.status === 'cancelled') return false;
    return new Date(inv.due_date) < new Date();
};

const statusClass = (status) => ({
    draft:     'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    open:      'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    paid:      'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
}[status] || 'bg-gray-100 text-gray-600');

let searchTimer;
const search = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('app.purchasing.invoices.index'), filters, { preserveState: true, replace: true });
    }, 350);
};
</script>
