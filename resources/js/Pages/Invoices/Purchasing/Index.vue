<template>
    <AppLayout :title="$t('invoices.purchases.title')">
        <div class="space-y-6">

            <!-- Header Section -->
            <PageHeader
                :title="$t('invoices.purchases.title')"
                :subtitle="$t('invoices.purchases.subtitle')"
                :totalCount="invoices?.total ? toEnglish(invoices.total) : '0'"
                :countLabel="$t('invoices.purchases.tab_invoices')"
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
                        :href="route('app.invoices.hub')"
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
                    <div class="flex items-center gap-4">
                        <!-- Actions Group -->
                        <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                            <div class="flex gap-1.5">
                                <button
                                    @click="setView('grid')"
                                    :title="$t('common.grid_view')"
                                    :class="[
                                        'p-2.5 rounded-xl transition-all shadow-sm',
                                        viewMode === 'grid'
                                            ? 'bg-amber-600 text-white shadow-amber-200 dark:shadow-none'
                                            : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                    ]"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </button>
                                <button
                                    @click="setView('list')"
                                    :title="$t('common.list_view')"
                                    :class="[
                                        'p-2.5 rounded-xl transition-all shadow-sm',
                                        viewMode === 'list'
                                            ? 'bg-amber-600 text-white shadow-amber-200 dark:shadow-none'
                                            : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                    ]"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <!-- Search -->
                        <div class="relative group lg:w-48">
                            <div class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-amber-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="localFilters.search"
                                :placeholder="$t('common.search')"
                                class="block w-full ps-11 pe-4 py-3 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all outline-none shadow-sm"
                            />
                        </div>

                        <!-- Date From -->
                        <div class="lg:w-36">
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 mb-1 px-1 uppercase tracking-wider">{{ $t('work_orders.filters.date_from') }}</label>
                            <CustomDatePicker
                                v-model="localFilters.date_from"
                                class="!rounded-2xl"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="lg:w-36">
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 mb-1 px-1 uppercase tracking-wider">{{ $t('work_orders.filters.date_to') }}</label>
                            <CustomDatePicker
                                v-model="localFilters.date_to"
                                class="!rounded-2xl"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="lg:w-40">
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 mb-1 px-1 uppercase tracking-wider">{{ $t('common.status') }}</label>
                            <SearchableSelect
                                v-model="localFilters.status"
                                :options="statusOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('invoices.all_statuses')"
                                class="!rounded-2xl"
                                compact
                            />
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Premium Tabs Section -->
            <div class="flex flex-wrap gap-3">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'relative flex items-center gap-3 px-5 py-3 rounded-2xl transition-all duration-300 group',
                        activeTab === tab.key
                            ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25 scale-[1.02]'
                            : 'bg-white dark:bg-gray-800 hover:bg-amber-50 dark:hover:bg-amber-900/10 border border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:shadow-md'
                    ]"
                >
                    <!-- Tab Icon Container -->
                    <div :class="[
                        'w-8 h-8 rounded-xl flex items-center justify-center transition-colors duration-300',
                        activeTab === tab.key
                            ? 'bg-white/20'
                            : 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 group-hover:bg-amber-600 group-hover:text-white'
                    ]">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="tab.key === 'invoices'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>

                    <span class="font-black tracking-tight text-sm uppercase">
                        {{ tab.label }}
                    </span>

                    <!-- Tab Count Badge -->
                    <span v-if="tab.count > 0" :class="[
                        'px-2 py-0.5 rounded-lg text-[10px] font-black min-w-[24px] text-center',
                        activeTab === tab.key
                            ? 'bg-white/20 text-white'
                            : 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'
                    ]">
                        {{ toEnglish(tab.count) }}
                    </span>
                </button>
            </div>

                <!-- Tab Content -->
                <div class="p-0">

                    <!-- PURCHASES TAB -->
                    <div v-show="activeTab === 'invoices'">

                        <!-- Empty State -->
                        <div v-if="!invoices.data?.length" class="py-20 text-center">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('common.no_data') }}</p>
                        </div>

                        <!-- Grid View -->
                        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5 p-5">
                            <Link
                                v-for="invoice in invoices?.data || []"
                                :key="invoice.id"
                                :href="route('app.invoices.purchases.show', invoice.id)"
                                class="group relative flex flex-col bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-500 cursor-pointer overflow-hidden shadow-sm"
                            >
                                <!-- Status Indicator Bar (Vertical) -->
                                <div class="absolute inset-y-0 start-0 w-1.5" :class="stripeClass(invoice.status)"></div>

                                <!-- Header -->
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 pe-6 flex justify-between items-start">
                                    <span class="text-[10px] font-black text-sky-600 dark:text-sky-400 font-mono bg-sky-50 dark:bg-sky-900/20 px-2 py-0.5 rounded-full">{{ formatDate(invoice.issue_date) }}</span>
                                    <div class="text-end">
                                        <div class="flex items-center justify-end gap-1.5 text-slate-800 dark:text-white">
                                            <span class="text-base font-black font-mono tracking-tighter">{{ invoice.code }}</span>
                                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                        </div>
                                        <div class="flex items-center justify-end gap-1 mt-1">
                                            <p class="text-[9px] font-black text-slate-500 font-mono truncate">{{ invoice.invoice_number || '—' }}</p>
                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">مرجع المورد</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 ps-8 flex-1 flex flex-col">
                                    <!-- Body Content -->
                                    <div class="text-center mb-5">
                                        <h4 class="text-[11px] font-black text-slate-800 dark:text-white mb-5 line-clamp-1 opacity-90">{{ $page.props.center?.name || 'مركز فريق الخدمة' }}</h4>
                                        <div class="flex items-center gap-2 justify-end mb-4 group-hover:translate-x-[-4px] transition-transform">
                                            <p class="text-[11px] font-black text-slate-600 dark:text-slate-300">{{ invoice.supplier?.name || '—' }}</p>
                                            <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div>
                                        </div>
                                        <div class="w-full h-[1.5px] bg-gradient-to-r from-transparent via-gray-100 dark:via-gray-700 to-transparent my-5"></div>
                                    </div>

                                    <!-- Bottom Info -->
                                    <div class="mt-auto space-y-4">
                                        <!-- Overdue Alert -->
                                        <div v-if="invoice.balance > 0" class="flex justify-center mb-4">
                                            <div class="px-4 py-1.5 rounded-2xl border border-red-100 bg-red-50/50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[9px] font-black shadow-sm flex items-center gap-1.5">
                                                <span class="relative flex h-2 w-2">
                                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                                </span>
                                                {{ getOverdueLabel(invoice.due_date) }}
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3">
                                            <div class="flex justify-between items-center bg-slate-50/30 dark:bg-slate-800/30 p-2 rounded-xl border border-transparent hover:border-slate-100 dark:hover:border-slate-700 transition-colors">
                                                <span class="text-xs font-black text-slate-800 dark:text-white font-mono" dir="ltr">{{ formatCurrency(invoice.total) }}</span>
                                                <span class="text-[10px] font-black text-slate-400 uppercase">{{ $t('invoices.amount') }}</span>
                                            </div>
                                            
                                            <template v-if="invoice.balance > 0">
                                                <div class="flex justify-between items-center bg-red-50/10 dark:bg-red-900/10 p-2 rounded-xl">
                                                    <span class="text-sm font-black text-red-600 dark:text-red-400 font-mono" dir="ltr">{{ formatCurrency(invoice.balance || 0) }}</span>
                                                    <span class="text-[10px] font-black text-red-400 uppercase">{{ $t('invoices.balance') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center p-2">
                                                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 font-mono" dir="ltr">{{ formatDate(invoice.due_date || invoice.issue_date) }}</span>
                                                    <span class="text-[10px] font-black text-slate-400 uppercase">موعد الدفع</span>
                                                </div>
                                            </template>
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
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">#</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.invoice_number') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.issue_date') }}</th>
                                            <th class="px-4 py-4 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('common.supplier') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('common.total') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.discount') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.subtotal') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.tax') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.amount') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('invoices.balance') }}</th>
                                            <th class="px-4 py-4 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em]">{{ $t('common.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                                        <tr
                                            v-for="(invoice, index) in invoices?.data || []"
                                            :key="invoice.id"
                                            class="group hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-all duration-200"
                                        >
                                            <td class="px-4 py-4 text-xs text-gray-400 font-mono">{{ toEnglish(index + 1) }}</td>
                                            <td class="px-4 py-4">
                                                <Link :href="route().has('app.invoices.purchases.show') ? route('app.invoices.purchases.show', invoice.id) : '#'" class="font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                                    #{{ invoice.invoice_number || invoice.code }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-gray-600 dark:text-gray-300 font-mono">{{ formatDate(invoice.issue_date) }}</td>
                                            <td class="px-4 py-4">
                                                <p class="font-medium text-gray-900 dark:text-white truncate max-w-[150px]">{{ invoice.supplier?.name || '—' }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-end text-xs font-mono text-gray-600 dark:text-gray-400">{{ formatCurrency(invoice.total) }}</td>
                                            <td class="px-4 py-4 text-end text-xs font-mono text-red-600 dark:text-red-400">{{ formatCurrency(invoice.discount_amount || 0) }}</td>
                                            <td class="px-4 py-4 text-end text-xs font-mono text-gray-600 dark:text-gray-400">{{ formatCurrency(invoice.subtotal) }}</td>
                                            <td class="px-4 py-4 text-end text-xs font-mono text-gray-500 dark:text-gray-500">{{ formatCurrency(invoice.tax_amount) }}</td>
                                            <td class="px-4 py-4 text-end text-sm font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(invoice.total) }}</td>
                                            <td class="px-4 py-4 text-end text-sm font-black text-amber-600 dark:text-amber-400 font-mono">{{ formatCurrency(invoice.balance || 0) }}</td>
                                            <td class="px-4 py-4 text-end">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Link :href="route('app.invoices.purchases.show', invoice.id)" class="p-2 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="invoices?.links?.length > 3" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-center justify-between flex-wrap gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('common.showing') }} {{ toEnglish(invoices.from || 0) }} - {{ toEnglish(invoices.to || 0) }} {{ $t('common.of') }} {{ toEnglish(invoices.total || 0) }}
                            </span>
                            <div class="flex gap-1">
                                <Link
                                    v-for="link in invoices.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                                        link.active ? 'bg-amber-600 text-white shadow-sm' :
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
                        <p class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('invoices.purchases.no_returns') }}</p>
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
    { value: 'draft', label: t('invoices.purchases.statuses.draft') },
    { value: 'open', label: t('invoices.purchases.statuses.open') },
    { value: 'paid', label: t('invoices.purchases.statuses.paid') },
    { value: 'cancelled', label: t('invoices.purchases.statuses.cancelled') },
];

const props = defineProps({
    invoices: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const activeTab = ref('invoices');
const viewMode = ref(localStorage.getItem('purchaseInvoicesViewMode') || 'list');

const tabs = computed(() => [
    {
        key: 'invoices',
        label: t('invoices.purchases.tab_invoices'),
        count: props.invoices?.total || 0,
    },
    {
        key: 'returns',
        label: t('invoices.purchases.tab_returns'),
        count: 0,
    },
]);

const localFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('app.invoices.purchases.index'), {
        search: localFilters.value.search || undefined,
        status: localFilters.value.status || undefined,
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
    localStorage.setItem('purchaseInvoicesViewMode', mode);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
};

const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'SAR', numberingSystem: 'latn' }).format(amount || 0);

const invoiceStatusClass = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};

const getOverdueLabel = (dueDate) => {
    if (!dueDate) return t('invoices.due_amount');
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = today - due;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 0) {
        return toEnglish(diffDays) + ' ايام تجاوز تاريخ الاستحقاق';
    }
    return 'المبالغ المستحقة للدفع';
};

const stripeClass = (status) => {
    const map = {
        paid: 'bg-emerald-500',
        open: 'bg-blue-500',
        draft: 'bg-gray-300 dark:bg-gray-600',
        cancelled: 'bg-red-500',
    };
    return map[status] || 'bg-gray-200 dark:bg-gray-700';
};
</script>
