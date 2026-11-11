<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.purchase_invoices_report.title')"
                :subtitle="$t('reports.purchase_invoices_report.subtitle')"
                gradientFrom="from-violet-600"
                gradientTo="to-purple-700"
                glowFrom="from-violet-500"
                class="print:hidden"
            >
                <template #back>
                    <BackButton href="/app/reports" />
                </template>

                <template #icon>
                    <svg
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            @click="handlePrint"
                            :label="$t('reports.purchase_invoices_report.print_report')"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white text-xs font-bold transition-all shadow-lg shadow-violet-500/25 hover:-translate-y-0.5"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            <span>{{ $t('reports.purchase_invoices_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.purchase_invoices_report.official_report')"
                    :subtitle="
                        filters.start_date || filters.end_date
                            ? `${$t('reports.purchase_invoices_report.date_range')}: ${filters.start_date || 'All'} ${$t('reports.purchase_invoices_report.to')} ${filters.end_date || 'Now'}`
                            : ''
                    "
                >
                    <template #extra>
                        <div class="text-xs text-gray-600">
                            {{ $t('reports.purchase_invoices_report.total_records') }}:
                            <span class="font-mono font-bold text-black">
                                {{ kpis.total_records }}
                            </span>
                        </div>
                    </template>
                </StandardReportHeader>

                <!-- Print Financial Summary Banner -->
                <div
                    class="grid grid-cols-6 gap-2 mb-4 p-3 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.purchase_invoices_report.total_subtotal') }}:
                        </span>
                        <strong class="font-mono text-xs">
                            {{ formatMoney(kpis.total_subtotal) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.purchase_invoices_report.total_discount') }}:
                        </span>
                        <strong class="font-mono text-xs text-amber-700">
                            {{ formatMoney(kpis.total_discount) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.purchase_invoices_report.total_vat') }}:
                        </span>
                        <strong class="font-mono text-xs">{{ formatMoney(kpis.total_vat) }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.purchase_invoices_report.total_amount') }}:
                        </span>
                        <strong class="font-mono text-xs text-purple-800">
                            {{ formatMoney(kpis.total_invoice_amount) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.purchase_invoices_report.total_paid') }}:
                        </span>
                        <strong class="font-mono text-xs text-emerald-700">
                            {{ formatMoney(kpis.total_paid) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.purchase_invoices_report.total_remaining') }}:
                        </span>
                        <strong class="font-mono text-xs text-rose-700">
                            {{ formatMoney(kpis.total_payment_remaining) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- FILTERS PANEL -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 space-y-4 print:hidden"
            >
                <!-- Row 1: Presets + Date Range + Date Type -->
                <div
                    class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4"
                >
                    <div class="flex flex-wrap items-center gap-1.5 w-full lg:w-auto">
                        <button
                            v-for="preset in presets"
                            :key="preset.id"
                            type="button"
                            @click="selectPreset(preset.id)"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-xs font-bold transition-all',
                                filterForm.range === preset.id
                                    ? 'bg-gradient-to-r from-violet-600 to-purple-600 text-white shadow-md shadow-violet-500/20'
                                    : 'bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <!-- Date Type Toggle & Custom Date Pickers -->
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <!-- Date Type Pill Toggle -->
                        <div
                            class="inline-flex rounded-xl p-1 bg-gray-100 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600"
                        >
                            <button
                                type="button"
                                @click="setDateType('issue_date')"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                    filterForm.date_type === 'issue_date'
                                        ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900',
                                ]"
                            >
                                {{ $t('reports.purchase_invoices_report.issue_date') }}
                            </button>
                            <button
                                type="button"
                                @click="setDateType('due_date')"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                    filterForm.date_type === 'due_date'
                                        ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900',
                                ]"
                            >
                                {{ $t('reports.purchase_invoices_report.due_date') }}
                            </button>
                        </div>

                        <!-- Start & End Date Pickers -->
                        <ReportDateRange
                            v-model:start="filterForm.start_date"
                            v-model:end="filterForm.end_date"
                            @change="onCustomDateChange"
                        />
                    </div>
                </div>

                <!-- Row 2: Detailed Filters -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60"
                >
                    <!-- Branch / Center -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.purchase_invoices_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.center_id"
                            :options="centerOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.purchase_invoices_report.supplier') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.supplier_id"
                            :options="supplierOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.purchase_invoices_report.payment_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.payment_status"
                            :options="paymentStatusOptions"
                            option-label="label"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Invoice Status -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.purchase_invoices_report.status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.status"
                            :options="statusOptions"
                            option-label="label"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Group By -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.purchase_invoices_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.group_by"
                            :options="groupByOptions"
                            option-label="label"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Row 3: Search Bar + Overdue Toggle + Action Buttons -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60"
                >
                    <div class="relative flex-1">
                        <input
                            v-model="filterForm.search"
                            type="text"
                            :placeholder="$t('reports.purchase_invoices_report.search_placeholder')"
                            class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            @keyup.enter="applyFilters"
                        />
                        <svg
                            class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Overdue Only Toggle -->
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <AppToggle v-model="filterForm.is_overdue" @change="applyFilters" />
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('reports.purchase_invoices_report.overdue_only') }}
                            </span>
                        </label>

                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-4 py-2 rounded-xl bg-gray-900 dark:bg-white hover:bg-black dark:hover:bg-gray-100 text-white dark:text-gray-900 text-xs font-bold transition-all shadow-sm"
                        >
                            {{ $t('reports.purchase_invoices_report.apply_filters') }}
                        </button>

                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750 text-xs font-bold transition-all"
                        >
                            {{ $t('reports.purchase_invoices_report.reset_filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI CARDS SUMMARY -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 print:hidden">
                <!-- Total Records -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span>{{ $t('reports.purchase_invoices_report.total_records') }}</span>
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300"
                        >
                            <svg
                                class="w-4 h-4"
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
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-gray-900 dark:text-white">
                        {{ formatNumber(kpis.total_records) }}
                    </div>
                    <span class="text-[10px] text-gray-400">{{ activeDateRangeLabel }}</span>
                </div>

                <!-- Subtotal (Excl. VAT) -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span>{{ $t('reports.purchase_invoices_report.total_subtotal') }}</span>
                        <div
                            class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-blue-600 dark:text-blue-400">
                        {{ formatMoney(kpis.total_subtotal) }}
                    </div>
                    <span class="text-[10px] text-gray-400">
                        {{ $t('reports.purchase_invoices_report.currency') }}
                    </span>
                </div>

                <!-- Total Discount -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span>{{ $t('reports.purchase_invoices_report.total_discount') }}</span>
                        <div
                            class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-amber-600 dark:text-amber-400">
                        {{ formatMoney(kpis.total_discount) }}
                    </div>
                    <span class="text-[10px] text-gray-400">
                        {{ $t('reports.purchase_invoices_report.currency') }}
                    </span>
                </div>

                <!-- Total VAT -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span>{{ $t('reports.purchase_invoices_report.total_vat') }}</span>
                        <div
                            class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-purple-600 dark:text-purple-400">
                        {{ formatMoney(kpis.total_vat) }}
                    </div>
                    <span class="text-[10px] text-gray-400">15%</span>
                </div>

                <!-- Total Invoiced Amount -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm bg-gradient-to-br from-violet-50/40 dark:from-violet-950/20 to-transparent"
                >
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="font-bold text-violet-800 dark:text-violet-400">
                            {{ $t('reports.purchase_invoices_report.total_amount') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 flex items-center justify-center"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-violet-700 dark:text-violet-300">
                        {{ formatMoney(kpis.total_invoice_amount) }}
                    </div>
                    <span class="text-[10px] text-gray-400">
                        {{ $t('reports.purchase_invoices_report.currency') }}
                    </span>
                </div>

                <!-- Remaining to Suppliers -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm bg-gradient-to-br from-rose-50/40 dark:from-rose-950/20 to-transparent"
                >
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="font-bold text-rose-800 dark:text-rose-400">
                            {{ $t('reports.purchase_invoices_report.total_remaining') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 flex items-center justify-center"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-rose-700 dark:text-rose-300">
                        {{ formatMoney(kpis.total_payment_remaining) }}
                    </div>
                    <span class="text-[10px] text-gray-400">
                        {{ $t('reports.purchase_invoices_report.currency') }}
                    </span>
                </div>
            </div>

            <!-- GROUPED VIEW (When group_by !== 'none') -->
            <div v-if="filterForm.group_by !== 'none' && groupedInvoices" class="space-y-4">
                <div class="flex items-center justify-between px-1 print:hidden">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                        {{ groupedInvoices.length }}
                        {{ $t('reports.purchase_invoices_report.group_summary') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAllGroups"
                            class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline"
                        >
                            {{ $t('reports.purchase_invoices_report.expand_all') }}
                        </button>
                        <span class="text-gray-300">|</span>
                        <button
                            type="button"
                            @click="collapseAllGroups"
                            class="text-xs font-bold text-gray-500 hover:underline"
                        >
                            {{ $t('reports.purchase_invoices_report.collapse_all') }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(grp, idx) in groupedInvoices"
                    :key="idx"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden"
                >
                    <!-- Group Header Accordion Bar -->
                    <button
                        type="button"
                        @click="toggleGroup(grp.group_key)"
                        class="w-full px-5 py-4 flex flex-wrap items-center justify-between gap-3 text-right bg-gradient-to-r from-gray-50/80 to-transparent dark:from-gray-750 hover:bg-gray-100/50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <svg
                                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-90': isGroupExpanded(grp.group_key) }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ grp.group_name }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300"
                            >
                                {{ grp.total_records }}
                                {{ $t('reports.purchase_invoices_report.record') }}
                            </span>
                        </div>

                        <!-- Mini Subtotals inside Header -->
                        <div class="flex items-center gap-4 text-xs font-mono">
                            <div>
                                <span class="text-[10px] text-gray-400 block">
                                    {{ $t('reports.purchase_invoices_report.total') }}
                                </span>
                                <strong class="text-gray-900 dark:text-white">
                                    {{ formatMoney(grp.total_invoice_amount) }}
                                </strong>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 block">
                                    {{ $t('reports.purchase_invoices_report.paid') }}
                                </span>
                                <strong class="text-emerald-600 dark:text-emerald-400">
                                    {{ formatMoney(grp.total_paid) }}
                                </strong>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 block">
                                    {{ $t('reports.purchase_invoices_report.remaining') }}
                                </span>
                                <strong class="text-rose-600 dark:text-rose-400">
                                    {{ formatMoney(grp.total_payment_remaining) }}
                                </strong>
                            </div>
                        </div>
                    </button>

                    <!-- Group Table -->
                    <div
                        v-show="isGroupExpanded(grp.group_key)"
                        class="border-t border-gray-100 dark:border-gray-700/60"
                    >
                        <div class="overflow-x-auto">
                            <DataTable structured class="w-full text-right text-xs">
                                <thead>
                                    <tr
                                        class="bg-gray-50/70 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-[11px] font-bold border-b border-gray-100 dark:border-gray-700/60"
                                    >
                                        <th class="px-4 py-3 text-center">#</th>
                                        <th class="px-4 py-3">
                                            {{ $t('reports.purchase_invoices_report.center') }}
                                        </th>
                                        <th class="px-4 py-3">
                                            {{ $t('reports.purchase_invoices_report.code') }}
                                        </th>
                                        <th class="px-4 py-3">
                                            {{ $t('reports.purchase_invoices_report.issue_date') }}
                                        </th>
                                        <th class="px-4 py-3">
                                            {{ $t('reports.purchase_invoices_report.supplier') }}
                                        </th>
                                        <th class="px-4 py-3 font-mono text-center">
                                            {{ $t('reports.purchase_invoices_report.subtotal') }}
                                        </th>
                                        <th class="px-4 py-3 font-mono text-center">
                                            {{ $t('reports.purchase_invoices_report.discount') }}
                                        </th>
                                        <th class="px-4 py-3 font-mono text-center">
                                            {{ $t('reports.purchase_invoices_report.vat') }}
                                        </th>
                                        <th class="px-4 py-3 font-mono text-center">
                                            {{ $t('reports.purchase_invoices_report.total') }}
                                        </th>
                                        <th class="px-4 py-3 font-mono text-center">
                                            {{ $t('reports.purchase_invoices_report.paid') }}
                                        </th>
                                        <th class="px-4 py-3 font-mono text-center">
                                            {{ $t('reports.purchase_invoices_report.remaining') }}
                                        </th>
                                        <th class="px-4 py-3 text-center">
                                            {{ $t('reports.purchase_invoices_report.status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr
                                        v-for="(inv, iIdx) in grp.items"
                                        :key="inv.id"
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition-colors"
                                    >
                                        <td
                                            class="px-4 py-3 text-center font-mono text-gray-400 text-xs"
                                        >
                                            {{ iIdx + 1 }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200"
                                        >
                                            {{ inv.center?.name || $t('common.main_center') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a
                                                :href="route('app.invoices.purchases.show', inv.id)"
                                                class="font-mono font-bold text-violet-600 dark:text-violet-400 hover:underline block"
                                            >
                                                {{ inv.code }}
                                            </a>
                                            <span
                                                v-if="inv.invoice_number"
                                                class="text-[10px] text-gray-400 font-mono"
                                            >
                                                {{
                                                    $t(
                                                        'reports.purchase_invoices_report.supplier_doc_no'
                                                    )
                                                }}: {{ inv.invoice_number }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs"
                                        >
                                            <div>{{ inv.issue_date }}</div>
                                            <div
                                                v-if="inv.due_date"
                                                class="text-[10px] text-gray-400"
                                            >
                                                {{
                                                    $t('reports.purchase_invoices_report.due_date')
                                                }}: {{ inv.due_date }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div
                                                class="font-bold text-gray-900 dark:text-white text-xs"
                                            >
                                                {{ inv.supplier?.name || '—' }}
                                            </div>
                                            <div
                                                v-if="inv.supplier?.phone"
                                                class="text-[10px] text-gray-400 font-mono"
                                            >
                                                {{ inv.supplier.phone }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-center text-xs text-gray-700 dark:text-gray-300"
                                        >
                                            {{ formatMoney(inv.subtotal) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-center text-xs text-amber-600 dark:text-amber-400"
                                        >
                                            {{ formatMoney(inv.discount_amount) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-center text-xs text-purple-600 dark:text-purple-400"
                                        >
                                            {{ formatMoney(inv.tax_amount) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-center text-xs font-bold text-violet-700 dark:text-violet-300"
                                        >
                                            {{ formatMoney(inv.total) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-center text-xs text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ formatMoney(inv.paid) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-center text-xs font-bold"
                                            :class="
                                                inv.balance > 0
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            {{ formatMoney(inv.balance) }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <PaymentStatusBadge
                                                :status="inv.payment_status"
                                                :label="getPaymentStatusLabel(inv.payment_status)"
                                            />
                                            <span
                                                v-if="inv.is_overdue"
                                                class="block text-[9px] text-rose-600 font-bold mt-0.5"
                                            >
                                                {{ $t('reports.purchase_invoices_report.overdue') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REGULAR FLAT TABLE (When group_by === 'none') -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-right text-xs">
                        <thead>
                            <tr
                                class="bg-gray-50/70 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-[11px] font-bold border-b border-gray-100 dark:border-gray-700/60"
                            >
                                <th class="px-4 py-3.5 text-center">#</th>
                                <th class="px-4 py-3.5">
                                    {{ $t('reports.purchase_invoices_report.center') }}
                                </th>
                                <th class="px-4 py-3.5">
                                    {{ $t('reports.purchase_invoices_report.code') }}
                                </th>
                                <th class="px-4 py-3.5">
                                    {{ $t('reports.purchase_invoices_report.issue_date') }}
                                </th>
                                <th class="px-4 py-3.5">
                                    {{ $t('reports.purchase_invoices_report.supplier') }}
                                </th>
                                <th class="px-4 py-3.5 font-mono text-center">
                                    {{ $t('reports.purchase_invoices_report.subtotal') }}
                                </th>
                                <th class="px-4 py-3.5 font-mono text-center">
                                    {{ $t('reports.purchase_invoices_report.discount') }}
                                </th>
                                <th class="px-4 py-3.5 font-mono text-center">
                                    {{ $t('reports.purchase_invoices_report.vat') }}
                                </th>
                                <th class="px-4 py-3.5 font-mono text-center">
                                    {{ $t('reports.purchase_invoices_report.total') }}
                                </th>
                                <th class="px-4 py-3.5 font-mono text-center">
                                    {{ $t('reports.purchase_invoices_report.paid') }}
                                </th>
                                <th class="px-4 py-3.5 font-mono text-center">
                                    {{ $t('reports.purchase_invoices_report.remaining') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.purchase_invoices_report.status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="(inv, idx) in invoices?.data || []"
                                :key="inv.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition-colors"
                            >
                                <td class="px-4 py-3 text-center font-mono text-gray-400 text-xs">
                                    {{ (invoices.from || 1) + idx }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">
                                    {{ inv.center?.name || $t('common.main_center') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a
                                            :href="route('app.invoices.purchases.show', inv.id)"
                                            class="font-mono font-bold text-violet-600 dark:text-violet-400 hover:underline"
                                        >
                                            {{ inv.code }}
                                        </a>
                                        <span
                                            v-if="inv.is_overdue"
                                            class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300"
                                        >
                                            {{ $t('reports.purchase_invoices_report.overdue') }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="inv.invoice_number"
                                        class="text-[10px] text-gray-400 font-mono mt-0.5"
                                    >
                                        {{
                                            $t('reports.purchase_invoices_report.supplier_doc_no')
                                        }}: {{ inv.invoice_number }}
                                    </div>
                                    <div
                                        v-if="inv.purchase_order"
                                        class="text-[10px] text-gray-400 font-mono"
                                    >
                                        {{ $t('reports.purchase_invoices_report.purchase_order') }}:
                                        {{ inv.purchase_order.code }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs"
                                >
                                    <div>{{ inv.issue_date }}</div>
                                    <div v-if="inv.due_date" class="text-[10px] text-gray-400">
                                        {{ $t('reports.purchase_invoices_report.due_date') }}:
                                        {{ inv.due_date }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-900 dark:text-white text-xs">
                                        {{ inv.supplier?.name || '—' }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span
                                            v-if="inv.supplier?.phone"
                                            class="text-[10px] text-gray-400 font-mono"
                                        >
                                            {{ inv.supplier.phone }}
                                        </span>
                                        <span
                                            v-if="inv.supplier?.type"
                                            class="text-[9px] px-1.5 py-0.2 rounded font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            {{ inv.supplier.type }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-center text-xs text-gray-700 dark:text-gray-300"
                                >
                                    {{ formatMoney(inv.subtotal) }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-center text-xs text-amber-600 dark:text-amber-400"
                                >
                                    {{ formatMoney(inv.discount_amount) }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-center text-xs text-purple-600 dark:text-purple-400"
                                >
                                    {{ formatMoney(inv.tax_amount) }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-center text-xs font-bold text-violet-700 dark:text-violet-300"
                                >
                                    {{ formatMoney(inv.total) }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-center text-xs text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatMoney(inv.paid) }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-center text-xs font-bold"
                                    :class="
                                        inv.balance > 0
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{ formatMoney(inv.balance) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <PaymentStatusBadge
                                        :status="inv.payment_status"
                                        :label="getPaymentStatusLabel(inv.payment_status)"
                                    />
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!invoices?.data || invoices.data.length === 0">
                                <td colspan="12" class="px-6 py-12 text-center">
                                    <div
                                        class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-700 text-gray-400 flex items-center justify-center mb-3"
                                    >
                                        <svg
                                            class="w-7 h-7"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                        {{ $t('reports.purchase_invoices_report.no_records') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $t('reports.purchase_invoices_report.no_records_hint') }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="invoices?.links && invoices.links.length > 3"
                    class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs print:hidden"
                >
                    <span class="text-gray-500">
                        {{ $t('reports.purchase_invoices_report.showing') }}
                        <span class="font-mono">{{ invoices.from || 0 }}</span>
                        {{ $t('reports.purchase_invoices_report.to') }}
                        <span class="font-mono">{{ invoices.to || 0 }}</span>
                        {{ $t('reports.purchase_invoices_report.of') }}
                        <span class="font-mono font-bold">{{ invoices.total }}</span>
                        {{ $t('reports.purchase_invoices_report.record') }}
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, lIdx) in invoices.links"
                            :key="lIdx"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-xl transition-all text-xs font-mono font-bold',
                                link.active
                                    ? 'bg-gradient-to-r from-violet-600 to-purple-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Standard Print Footer -->
            <StandardReportFooter class="hidden print:block pt-4 mt-6 border-t border-gray-300" />
        </PrintEngine>
    </AppLayout>
</template>

<script setup>
import PaymentStatusBadge from '@/Components/Common/PaymentStatusBadge.vue';
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { printDocument } from '@/Composables/usePrint';
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ReportDateRange from '@/Components/Common/ReportDateRange.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const { t } = useI18n();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            range: 'this_month',
            start_date: '',
            end_date: '',
            date_type: 'issue_date',
            status: 'all',
            payment_status: 'all',
            supplier_id: 'all',
            supplier_type: 'all',
            center_id: 'all',
            is_overdue: false,
            group_by: 'none',
            search: '',
        }),
    },
    kpis: {
        type: Object,
        default: () => ({
            total_records: 0,
            total_subtotal: 0,
            total_discount: 0,
            total_vat: 0,
            total_invoice_amount: 0,
            total_paid: 0,
            total_payment_remaining: 0,
        }),
    },
    invoices: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    groupedInvoices: {
        type: Array,
        default: () => null,
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    centers: {
        type: Array,
        default: () => [],
    },
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            vat_number: '',
            cr_number: '',
            logo_path: '',
            center_name: '',
        }),
    },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    date_type: props.filters.date_type || 'issue_date',
    status: props.filters.status || 'all',
    payment_status: props.filters.payment_status || 'all',
    supplier_id: String(props.filters.supplier_id || 'all'),
    supplier_type: props.filters.supplier_type || 'all',
    center_id: String(props.filters.center_id || 'all'),
    is_overdue: Boolean(props.filters.is_overdue),
    group_by: props.filters.group_by || 'none',
    search: props.filters.search || '',
});

const presets = computed(() => [
    { id: 'today', label: t('reports.purchase_invoices_report.today') },
    { id: 'yesterday', label: t('reports.purchase_invoices_report.yesterday') },
    { id: 'this_week', label: t('reports.purchase_invoices_report.this_week') },
    { id: 'this_month', label: t('reports.purchase_invoices_report.this_month') },
    { id: 'this_year', label: t('reports.purchase_invoices_report.this_year') },
    { id: 'all', label: t('reports.purchase_invoices_report.all_time') },
]);

const activeDateRangeLabel = computed(() => {
    if (filterForm.start_date && filterForm.end_date) {
        return `${filterForm.start_date} ${t('reports.purchase_invoices_report.to')} ${filterForm.end_date}`;
    }
    const matched = presets.value.find((p) => p.id === filterForm.range);
    return matched ? matched.label : t('reports.purchase_invoices_report.all_time');
});

const centerOptions = computed(() => [
    { id: 'all', name: t('reports.purchase_invoices_report.all_centers') },
    ...props.centers.map((c) => ({ id: String(c.id), name: c.name })),
]);

const supplierOptions = computed(() => [
    { id: 'all', name: t('reports.purchase_invoices_report.all_suppliers') },
    ...props.suppliers.map((s) => ({
        id: String(s.id),
        name: s.phone ? `${s.name} (${s.phone})` : s.name,
    })),
]);

const paymentStatusOptions = computed(() => [
    { id: 'all', label: t('reports.purchase_invoices_report.all_payment_statuses') },
    { id: 'paid', label: t('reports.purchase_invoices_report.status_paid') },
    { id: 'partial', label: t('reports.purchase_invoices_report.status_partial') },
    { id: 'unpaid', label: t('reports.purchase_invoices_report.status_unpaid') },
]);

const statusOptions = computed(() => [
    { id: 'all', label: t('reports.purchase_invoices_report.all_payment_statuses') },
    { id: 'paid', label: t('reports.purchase_invoices_report.status_paid') },
    { id: 'open', label: t('reports.purchase_invoices_report.status_partial') },
    { id: 'draft', label: t('reports.purchase_invoices_report.status_draft') },
    { id: 'cancelled', label: t('reports.purchase_invoices_report.status_cancelled') },
]);

const groupByOptions = computed(() => [
    { id: 'none', label: t('reports.purchase_invoices_report.group_none') },
    { id: 'center', label: t('reports.purchase_invoices_report.group_center') },
    { id: 'supplier', label: t('reports.purchase_invoices_report.group_supplier') },
    { id: 'payment_status', label: t('reports.purchase_invoices_report.group_payment_status') },
]);

const selectPreset = (presetId) => {
    filterForm.range = presetId;
    filterForm.start_date = '';
    filterForm.end_date = '';
    applyFilters();
};

const onCustomDateChange = () => {
    if (filterForm.start_date || filterForm.end_date) {
        filterForm.range = 'custom';
    }
    applyFilters();
};

const setDateType = (type) => {
    filterForm.date_type = type;
    applyFilters();
};

const applyFilters = () => {
    router.get(
        route('app.reports.purchase-invoices'),
        {
            range: filterForm.range,
            start_date: filterForm.start_date || undefined,
            end_date: filterForm.end_date || undefined,
            date_type: filterForm.date_type !== 'issue_date' ? filterForm.date_type : undefined,
            center_id: filterForm.center_id !== 'all' ? filterForm.center_id : undefined,
            supplier_id: filterForm.supplier_id !== 'all' ? filterForm.supplier_id : undefined,
            supplier_type:
                filterForm.supplier_type !== 'all' ? filterForm.supplier_type : undefined,
            status: filterForm.status !== 'all' ? filterForm.status : undefined,
            payment_status:
                filterForm.payment_status !== 'all' ? filterForm.payment_status : undefined,
            is_overdue: filterForm.is_overdue ? 1 : undefined,
            group_by: filterForm.group_by !== 'none' ? filterForm.group_by : undefined,
            search: filterForm.search ? filterForm.search.trim() : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const resetFilters = () => {
    filterForm.range = 'this_month';
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.date_type = 'issue_date';
    filterForm.status = 'all';
    filterForm.payment_status = 'all';
    filterForm.supplier_id = 'all';
    filterForm.supplier_type = 'all';
    filterForm.center_id = 'all';
    filterForm.is_overdue = false;
    filterForm.group_by = 'none';
    filterForm.search = '';
    applyFilters();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filterForm.range) params.append('range', filterForm.range);
    if (filterForm.start_date) params.append('start_date', filterForm.start_date);
    if (filterForm.end_date) params.append('end_date', filterForm.end_date);
    if (filterForm.date_type) params.append('date_type', filterForm.date_type);
    if (filterForm.center_id && filterForm.center_id !== 'all')
        params.append('center_id', filterForm.center_id);
    if (filterForm.supplier_id && filterForm.supplier_id !== 'all')
        params.append('supplier_id', filterForm.supplier_id);
    if (filterForm.supplier_type && filterForm.supplier_type !== 'all')
        params.append('supplier_type', filterForm.supplier_type);
    if (filterForm.status && filterForm.status !== 'all')
        params.append('status', filterForm.status);
    if (filterForm.payment_status && filterForm.payment_status !== 'all')
        params.append('payment_status', filterForm.payment_status);
    if (filterForm.is_overdue) params.append('is_overdue', '1');
    if (filterForm.search) params.append('search', filterForm.search.trim());

    return `${route('app.reports.purchase-invoices.export')}?${params.toString()}`;
});

// Accordion open/close state for grouped view
const expandedGroups = reactive({});

const isGroupExpanded = (key) => {
    if (expandedGroups[key] === undefined) {
        return true; // expanded by default
    }
    return expandedGroups[key];
};

const toggleGroup = (key) => {
    expandedGroups[key] = !isGroupExpanded(key);
};

const expandAllGroups = () => {
    if (props.groupedInvoices) {
        props.groupedInvoices.forEach((g) => {
            expandedGroups[g.group_key] = true;
        });
    }
};

const collapseAllGroups = () => {
    if (props.groupedInvoices) {
        props.groupedInvoices.forEach((g) => {
            expandedGroups[g.group_key] = false;
        });
    }
};

const formatMoney = (val) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatNumber = (val) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US');
};

const getPaymentStatusLabel = (status) => {
    switch (status) {
        case 'paid':
            return t('reports.purchase_invoices_report.status_paid');
        case 'partial':
            return t('reports.purchase_invoices_report.status_partial');
        case 'unpaid':
            return t('reports.purchase_invoices_report.status_unpaid');
        default:
            return status || '—';
    }
};

const handlePrint = () => {
    printDocument();
};
</script>

<style>
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print-section {
        display: block !important;
    }
}
</style>
