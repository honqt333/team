<template>
    <AppLayout>
        <Head :title="`${taxPeriod.name} - ${$t('reports.tax_periods_report.title')}`" />

        <div class="space-y-6">
            <!-- Header Section (Screen Only) -->
            <div
                class="print:hidden flex flex-col lg:flex-row lg:items-center justify-between gap-4"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <Link
                            :href="route('app.reports.tax-periods.index')"
                            class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                        >
                            {{ $t('reports.tax_periods_report.back_to_tax_periods') }}
                        </Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ taxPeriod.name }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ taxPeriod.name }}
                        </h1>
                        <span
                            class="text-xs font-mono px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold"
                        >
                            {{ taxPeriod.period_key }}
                        </span>
                        <!-- Status Badge -->
                        <span
                            v-if="taxPeriod.status === 'posted'"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            {{ $t('reports.tax_periods_report.status_posted') }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            {{ $t('reports.tax_periods_report.status_draft') }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                        {{ formatDate(taxPeriod.start_date) }} &larr;
                        {{ formatDate(taxPeriod.end_date) }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Post / Lock Button (Draft Only, uses useConfirm) -->
                    <button
                        v-if="taxPeriod.status === 'draft'"
                        type="button"
                        @click="handlePostPeriod"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-500/20 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>{{ $t('reports.tax_periods_report.post_period_btn') }}</span>
                    </button>

                    <!-- Recreate / Refresh Button (Draft Only, uses useConfirm) -->
                    <button
                        v-if="taxPeriod.status === 'draft'"
                        type="button"
                        @click="handleRecreatePeriod"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>{{ $t('reports.tax_periods_report.recreate_period_btn') }}</span>
                    </button>

                    <!-- Export Excel -->
                    <a
                        :href="route('app.reports.tax-periods.export-excel', taxPeriod.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 text-xs font-bold rounded-xl transition-all"
                    >
                        <svg
                            class="w-4 h-4 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <span>{{ $t('reports.tax_periods_report.export_excel') }}</span>
                    </a>

                    <!-- Export ZATCA -->
                    <a
                        :href="route('app.reports.tax-periods.export-zatca', taxPeriod.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 text-xs font-bold rounded-xl transition-all"
                    >
                        <svg
                            class="w-4 h-4 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <span>{{ $t('reports.tax_periods_report.export_zatca') }}</span>
                    </a>

                    <!-- Print Button (uses usePrint composable per Constitution) -->
                    <AppPrintButton
                        @click="handlePrint"
                        :label="$t('reports.tax_periods_report.print_declaration')"
                    />
                </div>
            </div>

            <!-- Posted Notice Banner (If Locked) -->
            <div
                v-if="taxPeriod.status === 'posted'"
                class="print:hidden bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 flex items-center gap-4"
            >
                <div
                    class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
                <div class="text-xs text-emerald-900 dark:text-emerald-200">
                    <p class="font-bold text-sm">
                        {{ $t('reports.tax_periods_report.posted_notice') }}
                    </p>
                    <p class="mt-0.5 text-emerald-700 dark:text-emerald-300">
                        {{ $t('reports.tax_periods_report.posted_at') }}:
                        <span class="font-mono font-bold">
                            {{ formatDate(taxPeriod.posted_at) }}
                        </span>
                        <span v-if="taxPeriod.poster">
                            &bull; {{ $t('reports.tax_periods_report.posted_by') }}:
                            <span class="font-bold">{{ taxPeriod.poster.name }}</span>
                        </span>
                        <span v-if="taxPeriod.creator">
                            &bull; {{ $t('reports.tax_periods_report.created_by') }}:
                            <span class="font-bold">{{ taxPeriod.creator.name }}</span>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Printable Declaration Document Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-6"
            >
                <!-- ZATCA Form Header -->
                <div
                    class="border-b border-gray-200 dark:border-gray-700 pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-emerald-500/20"
                            >
                                Z
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $t('reports.tax_periods_report.zatca_official_header') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('reports.tax_periods_report.zatca_summary_subtitle') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-right space-y-1">
                        <div>
                            <span class="text-gray-500">
                                {{ $t('reports.tax_periods_report.facility_name') }}:
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ tenant?.name }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">
                                {{ $t('reports.tax_periods_report.tax_number_label') }}:
                            </span>
                            <span
                                class="font-mono font-bold text-emerald-700 dark:text-emerald-400"
                            >
                                {{ tenant?.vat_number || '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">
                                {{ $t('reports.tax_periods_report.tax_period_label') }}:
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ taxPeriod.name }} ({{ taxPeriod.period_key }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ZATCA Official Return Table (Boxes 1-13) -->
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                        {{ $t('reports.tax_periods_report.zatca_summary_title') }}
                    </h3>
                    <div
                        class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700"
                    >
                        <DataTable structured class="w-full text-right text-xs">
                            <thead
                                class="bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 font-bold"
                            >
                                <tr>
                                    <th class="p-3 w-14 text-center">
                                        {{ $t('reports.tax_periods_report.box_number') }}
                                    </th>
                                    <th class="p-3">
                                        {{ $t('reports.tax_periods_report.box_description') }}
                                    </th>
                                    <th class="p-3 text-center">
                                        {{ $t('reports.tax_periods_report.transactions_count') }}
                                    </th>
                                    <th class="p-3 text-left">
                                        {{ $t('reports.tax_periods_report.taxable_amount_sar') }}
                                    </th>
                                    <th class="p-3 text-left">
                                        {{ $t('reports.tax_periods_report.adjustment_amount_sar') }}
                                    </th>
                                    <th class="p-3 text-left">
                                        {{ $t('reports.tax_periods_report.vat_amount_sar') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <!-- Box 1: Sales 15% -->
                                <tr class="bg-emerald-50/30 dark:bg-emerald-950/10 font-medium">
                                    <td
                                        class="p-3 text-center font-bold font-mono text-emerald-700 dark:text-emerald-400"
                                    >
                                        1
                                    </td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.zatca_box_sales') }}
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold">
                                        {{ summary.income.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono font-bold">
                                        {{ formatCurrency(summary.income.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        0.00 {{ $t('common.currency') }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(summary.income.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 6: Total Sales -->
                                <tr
                                    class="bg-gray-50/60 dark:bg-gray-900/20 font-bold border-b-2 border-gray-200 dark:border-gray-600"
                                >
                                    <td class="p-3 text-center font-mono">6</td>
                                    <td class="p-3 text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.total_sales_label') }}
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        {{ summary.income.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono">
                                        {{ formatCurrency(summary.income.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        0.00 {{ $t('common.currency') }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(summary.income.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 7: Purchases 15% -->
                                <tr class="bg-rose-50/30 dark:bg-rose-950/10 font-medium">
                                    <td
                                        class="p-3 text-center font-bold font-mono text-rose-700 dark:text-rose-400"
                                    >
                                        7
                                    </td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.zatca_box_purchases') }}
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold">
                                        {{ summary.expense.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono font-bold">
                                        {{ formatCurrency(summary.expense.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        0.00 {{ $t('common.currency') }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono font-bold text-rose-600 dark:text-rose-400"
                                    >
                                        {{ formatCurrency(summary.expense.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 12: Total Purchases -->
                                <tr
                                    class="bg-gray-50/60 dark:bg-gray-900/20 font-bold border-b-2 border-gray-200 dark:border-gray-600"
                                >
                                    <td class="p-3 text-center font-mono">12</td>
                                    <td class="p-3 text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.total_purchases_label') }}
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        {{ summary.expense.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono">
                                        {{ formatCurrency(summary.expense.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        0.00 {{ $t('common.currency') }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono text-rose-600 dark:text-rose-400"
                                    >
                                        {{ formatCurrency(summary.expense.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 13: Net VAT Due / Refund -->
                                <tr class="bg-gray-900 text-white font-bold text-sm">
                                    <td
                                        class="p-4 text-center font-mono text-emerald-400 text-base"
                                    >
                                        13
                                    </td>
                                    <td class="p-4" colspan="4">
                                        <div class="font-bold">
                                            {{ $t('reports.tax_periods_report.zatca_box_net') }}
                                        </div>
                                    </td>
                                    <td
                                        class="p-4 text-left font-mono text-base font-black"
                                        :class="
                                            summary.total.net_vat_amount >= 0
                                                ? 'text-emerald-400'
                                                : 'text-cyan-400'
                                        "
                                    >
                                        {{ formatCurrency(summary.total.net_vat_amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>

                <!-- Transactions Detail Table -->
                <div class="pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4"
                    >
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ $t('reports.tax_periods_report.detailed_transactions_title') }}
                                ({{ filteredTransactions.length }})
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{
                                    $t('reports.tax_periods_report.detailed_transactions_subtitle')
                                }}
                            </p>
                        </div>

                        <!-- Filters for Detail Table (Using SearchableSelect) -->
                        <div class="flex flex-wrap items-center gap-3">
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('reports.tax_periods_report.search_placeholder')"
                                class="text-xs bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 w-60 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />

                            <div class="w-52">
                                <SearchableSelect
                                    v-model="filterType"
                                    :options="typeFilterOptions"
                                    :placeholder="$t('reports.tax_periods_report.all_types')"
                                    class="w-full text-xs"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700"
                    >
                        <DataTable structured class="w-full text-right text-xs">
                            <thead
                                class="bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300 font-semibold uppercase"
                            >
                                <tr>
                                    <th class="px-3 py-3 w-10">#</th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.ref_no') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.date') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.type') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.party') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.tax_number') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.taxable_subtotal') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.vat_amount') }}
                                    </th>
                                    <th class="px-3 py-3">
                                        {{ $t('reports.tax_periods_report.total_with_vat') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="(tx, idx) in paginatedTransactions"
                                    :key="tx.id || idx"
                                    class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors"
                                >
                                    <td class="px-3 py-3 text-gray-500">
                                        {{ (currentPage - 1) * pageSize + idx + 1 }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-bold font-mono text-gray-900 dark:text-white"
                                    >
                                        {{ tx.ref_no }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-gray-500 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatDate(tx.transaction_date) }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold"
                                            :class="
                                                getTypeBadgeClass(tx.transaction_type, tx.is_income)
                                            "
                                        >
                                            {{
                                                $t(
                                                    `reports.tax_periods_report.types.${tx.transaction_type}`
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-gray-800 dark:text-gray-200">
                                        {{ tx.party_name || '-' }}
                                    </td>
                                    <td class="px-3 py-3 font-mono text-gray-500">
                                        {{ tx.tax_number || '-' }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-mono text-gray-700 dark:text-gray-300"
                                    >
                                        {{ formatCurrency(tx.subtotal) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-bold font-mono"
                                        :class="tx.is_income ? 'text-emerald-600' : 'text-rose-600'"
                                    >
                                        {{ formatCurrency(tx.vat_amount) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-bold font-mono text-gray-900 dark:text-white"
                                    >
                                        {{ formatCurrency(tx.total_amount) }}
                                    </td>
                                </tr>

                                <tr v-if="filteredTransactions.length === 0">
                                    <td colspan="9" class="py-10 text-center text-gray-400">
                                        {{ $t('reports.tax_periods_report.no_transactions') }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>

                    <!-- Client-Side Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="mt-4 flex items-center justify-between text-xs text-gray-500"
                    >
                        <span>
                            {{
                                $t('reports.tax_periods_report.showing_records', {
                                    from: (currentPage - 1) * pageSize + 1,
                                    to: Math.min(
                                        currentPage * pageSize,
                                        filteredTransactions.length
                                    ),
                                    total: filteredTransactions.length,
                                })
                            }}
                        </span>
                        <div class="flex gap-1">
                            <button
                                type="button"
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                                class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40"
                            >
                                {{ $t('common.previous') }}
                            </button>
                            <span class="px-3 py-1 font-bold">
                                {{ currentPage }} / {{ totalPages }}
                            </span>
                            <button
                                type="button"
                                :disabled="currentPage === totalPages"
                                @click="currentPage++"
                                class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40"
                            >
                                {{ $t('common.next') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Standard Constitutional ConfirmModal -->
            <ConfirmModal />
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { usePrint } from '@/Composables/usePrint';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { confirm } = useConfirm();
const { printDocument, formatCurrency: printFormatCurrency } = usePrint();

const props = defineProps({
    taxPeriod: Object,
    summary: Object,
    transactions: Array,
    tenant: Object,
});

const searchQuery = ref('');
const filterType = ref('all');

const typeFilterOptions = computed(() => [
    { value: 'all', label: t('reports.tax_periods_report.all_types') },
    { value: 'sales_invoice', label: t('reports.tax_periods_report.types.sales_invoice') },
    { value: 'credit_note', label: t('reports.tax_periods_report.types.credit_note') },
    { value: 'purchase_invoice', label: t('reports.tax_periods_report.types.purchase_invoice') },
    { value: 'purchase_return', label: t('reports.tax_periods_report.types.purchase_return') },
    {
        value: 'company_transaction',
        label: t('reports.tax_periods_report.types.company_transaction'),
    },
]);

const currentPage = ref(1);
const pageSize = 50;

const filteredTransactions = computed(() => {
    let list = props.transactions || [];

    if (filterType.value !== 'all') {
        list = list.filter((tr) => tr.transaction_type === filterType.value);
    }

    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (tr) =>
                (tr.ref_no && tr.ref_no.toLowerCase().includes(q)) ||
                (tr.party_name && tr.party_name.toLowerCase().includes(q)) ||
                (tr.tax_number && tr.tax_number.toLowerCase().includes(q))
        );
    }

    return list;
});

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / pageSize));

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredTransactions.value.slice(start, start + pageSize);
});

const getTypeBadgeClass = (type, isIncome) => {
    if (type === 'sales_invoice')
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
    if (type === 'credit_note')
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
    if (type === 'purchase_invoice')
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
    if (type === 'purchase_return')
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300';
    return isIncome ? 'bg-teal-100 text-teal-800' : 'bg-rose-100 text-rose-800';
};

const handlePostPeriod = async () => {
    const confirmed = await confirm({
        title: t('reports.tax_periods_report.confirm_post_title'),
        message: t('reports.tax_periods_report.confirm_post_message'),
        confirmText: t('reports.tax_periods_report.confirm_post_action'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });

    if (confirmed) {
        router.post(route('app.reports.tax-periods.post', props.taxPeriod.id));
    }
};

const handleRecreatePeriod = async () => {
    const confirmed = await confirm({
        title: t('reports.tax_periods_report.confirm_recreate_title'),
        message: t('reports.tax_periods_report.confirm_recreate_message'),
        confirmText: t('reports.tax_periods_report.confirm_recreate_action'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });

    if (confirmed) {
        router.post(route('app.reports.tax-periods.recreate', props.taxPeriod.id));
    }
};

const handlePrint = () => {
    printDocument({
        title: `${props.taxPeriod.name} - ${t('reports.tax_periods_report.title')}`,
    });
};

const formatCurrency = (val) => {
    if (printFormatCurrency) {
        return printFormatCurrency(Number(val) || 0);
    }
    const num = Number(val || 0);
    return (
        num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
        ' ' +
        t('common.currency')
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return String(dateStr).split('T')[0];
};
</script>
