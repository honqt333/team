<template>
    <AppLayout :title="`${$t('invoices.invoice')} #${invoice.code}`">
        <div class="space-y-6 pb-20">
            <!-- Glassmorphic Header -->
            <PageHeader
                :title="`${$t('invoices.invoice')} #${invoice.code}`"
                :subtitle="invoice.supplier?.name"
                :totalCount="formatCurrency(invoice.total)"
                :countLabel="$t('invoices.grand_total')"
                gradientFrom="from-amber-600"
                gradientTo="to-orange-600"
                glowFrom="from-amber-500"
                badgeBg="bg-amber-50/50 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-100/50 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
            >
                <template #back>
                    <Link
                        :href="route('app.invoices.purchases.index')"
                        :title="$t('common.back')"
                        class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-300 text-amber-600 group"
                    >
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <span :class="statusClass(invoice.status)" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm">
                            {{ $t(`invoices.purchases.statuses.${invoice.status}`) }}
                        </span>
                        
                        <div v-if="invoice.invoice_number" class="px-4 py-1.5 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-100 dark:border-gray-700 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            REF: {{ invoice.invoice_number }}
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Financial Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Subtotal -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700/50 shadow-sm group hover:shadow-md transition-all">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.15em] mb-1">{{ $t('invoices.subtotal') }}</p>
                    <p class="text-base font-black text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrency(invoice.subtotal) }}</p>
                </div>

                <!-- Discount -->
                <div v-if="invoice.discount > 0" class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700/50 shadow-sm group hover:shadow-md transition-all border-dashed border-amber-200">
                    <p class="text-[9px] font-black text-amber-500 uppercase tracking-[0.15em] mb-1">{{ $t('invoices.discount') }}</p>
                    <p class="text-base font-black text-amber-600 font-mono" dir="ltr">-{{ formatCurrency(invoice.discount) }}</p>
                </div>

                <!-- Tax Total -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700/50 shadow-sm group hover:shadow-md transition-all">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.15em] mb-1">{{ $t('invoices.tax') }}</p>
                    <p class="text-base font-black text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrency(invoice.tax_total) }}</p>
                </div>

                <!-- Grand Total -->
                <div class="bg-gradient-to-br from-amber-600 to-orange-600 rounded-3xl p-5 shadow-lg shadow-amber-500/20 group hover:shadow-xl transition-all">
                    <p class="text-[9px] font-black text-white/70 uppercase tracking-[0.15em] mb-1">{{ $t('invoices.grand_total') }}</p>
                    <p class="text-lg font-black text-white font-mono" dir="ltr">{{ formatCurrency(invoice.total) }}</p>
                </div>

                <!-- Remaining Balance -->
                <div v-if="invoice.balance > 0" class="bg-red-50 dark:bg-red-900/10 rounded-3xl p-5 shadow-sm border border-red-100 dark:border-red-900/30 group hover:shadow-md transition-all">
                    <p class="text-[9px] font-black text-red-600 dark:text-red-400 uppercase tracking-[0.15em] mb-1">{{ $t('invoices.balance') }}</p>
                    <p class="text-base font-black text-red-700 dark:text-red-300 font-mono" dir="ltr">{{ formatCurrency(invoice.balance) }}</p>
                </div>
                <div v-else class="bg-emerald-50 dark:bg-emerald-900/10 rounded-3xl p-5 shadow-sm border border-emerald-100 dark:border-emerald-900/30 group hover:shadow-md transition-all">
                    <p class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.15em] mb-1">{{ $t('common.status') }}</p>
                    <p class="text-base font-black text-emerald-700 dark:text-emerald-300 uppercase tracking-widest">{{ $t('invoices.purchases.statuses.paid') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content Column -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Items Table Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/30">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                </div>
                                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">{{ $t('common.details') }}</h3>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50/30 dark:bg-gray-900/50">
                                        <th class="px-8 py-5 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('inventory.parts.title_singular') }}</th>
                                        <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.qty') }}</th>
                                        <th class="px-8 py-5 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.unit_price') }}</th>
                                        <th class="px-8 py-5 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('invoices.tax') }}</th>
                                        <th class="px-8 py-5 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr v-for="line in invoice.lines" :key="line.id" class="group hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
                                        <td class="px-8 py-6">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-amber-600 transition-colors">{{ line.part?.name }}</span>
                                                <span class="text-[10px] text-gray-400 font-mono mt-1 uppercase tracking-wider">{{ line.part?.sku }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ toEnglish(line.qty) }}</span>
                                        </td>
                                        <td class="px-8 py-6 text-end">
                                            <span class="text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_price) }}</span>
                                        </td>
                                        <td class="px-8 py-6 text-end text-xs text-sky-600 dark:text-sky-400 font-mono">
                                            {{ formatCurrency(line.tax_amount) }}
                                        </td>
                                        <td class="px-8 py-6 text-end">
                                            <span class="text-base font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(line.total) }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Cards Column -->
                <div class="space-y-8">
                    <!-- Supplier Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-700/50 shadow-sm relative overflow-hidden group">
                        <!-- Card Decoration -->
                        <div class="absolute -top-12 -end-12 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-colors"></div>
                        
                        <div class="relative">
                            <div class="flex items-center gap-3 text-amber-600 mb-6">
                                <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('purchasing.orders.supplier') }}</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xl font-black text-gray-900 dark:text-white">{{ invoice.supplier?.name }}</p>
                                    <p v-if="invoice.supplier?.phone" class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-mono" dir="ltr">{{ invoice.supplier?.phone }}</p>
                                </div>
                                <div v-if="invoice.supplier?.tax_number" class="pt-4 border-t border-gray-50 dark:border-gray-700">
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">VAT NUMBER</p>
                                    <p class="text-xs font-black text-gray-700 dark:text-gray-300 font-mono">{{ invoice.supplier?.tax_number }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Purchase Order -->
                    <div v-if="invoice.purchase_order" class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-700/50 shadow-sm relative overflow-hidden group">
                        <!-- Card Decoration -->
                        <div class="absolute -top-12 -end-12 w-32 h-32 bg-sky-500/5 rounded-full blur-3xl group-hover:bg-sky-500/10 transition-colors"></div>
                        
                        <div class="flex items-center gap-3 text-sky-600 mb-6 relative">
                            <div class="p-2 bg-sky-50 dark:bg-sky-900/30 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('purchasing.orders.title_singular') }}</h3>
                        </div>
                        <Link :href="route('app.purchasing.orders.show', invoice.purchase_order.id)" class="block p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-gray-100 dark:border-gray-700 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition-colors">
                            <p class="text-lg font-black text-gray-900 dark:text-white group-hover:text-amber-600 transition-colors">{{ invoice.purchase_order.code }}</p>
                            <p class="text-[10px] text-gray-500 mt-1 font-mono uppercase tracking-widest">{{ formatDate(invoice.purchase_order.order_date) }}</p>
                        </Link>
                    </div>

                    <!-- Additional Notes -->
                    <div v-if="invoice.notes" class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-700/50 shadow-sm">
                        <div class="flex items-center gap-3 text-slate-400 mb-6">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            <h3 class="text-xs font-black uppercase tracking-[0.15em]">{{ $t('common.notes') }}</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-medium italic">{{ invoice.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

const props = defineProps({
    invoice: Object,
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'SAR', numberingSystem: 'latn' }).format(amount || 0);

const statusClass = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};
</script>

