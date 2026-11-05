<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.suppliers_report.title')"
                :subtitle="$t('reports.suppliers_report.subtitle')"
                gradientFrom="from-rose-600"
                gradientTo="to-red-700"
                glowFrom="from-rose-500"
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
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <button
                            type="button"
                            @click="handlePrint"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold transition-all shadow-sm"
                        >
                            <svg
                                class="w-4 h-4 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            <span>{{ $t('reports.suppliers_report.print_report') }}</span>
                        </button>

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white text-xs font-bold transition-all shadow-lg shadow-rose-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.suppliers_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.suppliers_report.official_report')"
                    :subtitle="
                        filters.start_date || filters.end_date
                            ? `${$t('reports.suppliers_report.date_range')}: ${filters.start_date || 'All'} — ${filters.end_date || 'Now'}`
                            : ''
                    "
                >
                    <template #extra>
                        <div class="text-xs text-gray-600">
                            {{ $t('reports.suppliers_report.total_records') }}:
                            <span class="font-mono font-bold text-black">
                                {{ kpis.total_records }}
                            </span>
                        </div>
                    </template>
                </StandardReportHeader>

                <!-- Print KPI Summary Banner -->
                <div
                    class="grid grid-cols-6 gap-2 mb-4 p-3 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.suppliers_report.total_records') }}:
                        </span>
                        <strong class="font-mono text-xs">{{ kpis.total_records }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.suppliers_report.total_subtotal') }}:
                        </span>
                        <strong class="font-mono text-xs">
                            {{ formatMoney(kpis.total_subtotal) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.suppliers_report.total_vat') }}:
                        </span>
                        <strong class="font-mono text-xs">{{ formatMoney(kpis.total_vat) }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.suppliers_report.total_amount') }}:
                        </span>
                        <strong class="font-mono text-xs text-rose-800">
                            {{ formatMoney(kpis.total_invoice_amount) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.suppliers_report.total_paid') }}:
                        </span>
                        <strong class="font-mono text-xs text-emerald-800">
                            {{ formatMoney(kpis.total_paid) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.suppliers_report.total_remaining') }}:
                        </span>
                        <strong class="font-mono text-xs text-amber-800">
                            {{ formatMoney(kpis.total_payment_remaining) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Screen: Interactive Filter Bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 p-5 space-y-4 print:hidden"
            >
                <!-- Quick Date Range Presets -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 ms-1 me-2 flex items-center gap-1.5"
                    >
                        <svg
                            class="w-4 h-4 text-rose-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        {{ $t('reports.suppliers_report.date_range') }}:
                    </span>

                    <button
                        v-for="preset in datePresets"
                        :key="preset.id"
                        type="button"
                        @click="selectPreset(preset.id)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                            localFilters.range === preset.id
                                ? 'bg-rose-500 text-white shadow-sm shadow-rose-500/30'
                                : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Custom Date Pickers (when custom range is chosen) -->
                <div
                    v-if="localFilters.range === 'custom'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50"
                >
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.date_from') }}
                        </label>
                        <CustomDatePicker
                            v-model="localFilters.start_date"
                            @update:modelValue="applyFilters"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.date_to') }}
                        </label>
                        <CustomDatePicker
                            v-model="localFilters.end_date"
                            @update:modelValue="applyFilters"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Filters Grid: Center, Supplier, Document Type, Supplier Type, Payment Status, Group By -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50"
                >
                    <!-- Branch / Center Filter (for multi-branch users) -->
                    <div v-if="centers && centers.length > 1">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.center_id"
                            :options="centerOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.suppliers_report.all_centers')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Supplier Filter -->
                    <div :class="centers && centers.length > 1 ? '' : 'lg:col-span-2'">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.supplier') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.supplier_id"
                            :options="supplierOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.suppliers_report.all_suppliers')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Document Type Filter (All, Invoices, Returns, Debit Notes) -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.document_type') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.document_type"
                            :options="documentTypeOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.suppliers_report.all_documents')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Supplier Type Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.supplier_type') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.supplier_type"
                            :options="supplierTypeOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.suppliers_report.all_supplier_types')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.payment_status') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.payment_status"
                            :options="paymentStatusOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.suppliers_report.all_payment_statuses')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Group By -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.suppliers_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.group_by"
                            :options="groupByOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Row 3: Search Bar + Action Buttons (Apply & Reset) -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60"
                >
                    <div class="relative flex-1">
                        <input
                            type="text"
                            v-model="localFilters.search"
                            @keyup.enter="applyFilters"
                            :placeholder="$t('reports.suppliers_report.search_placeholder')"
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/50 focus:bg-white dark:focus:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-rose-500 focus:border-transparent pe-8 ps-9 py-2 transition-all"
                        />
                        <svg
                            class="w-4 h-4 text-gray-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none"
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
                        <button
                            v-if="localFilters.search"
                            type="button"
                            @click="
                                localFilters.search = '';
                                applyFilters();
                            "
                            class="absolute end-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-4 py-2 rounded-xl bg-gray-900 dark:bg-white hover:bg-black dark:hover:bg-gray-100 text-white dark:text-gray-900 text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                />
                            </svg>
                            <span>{{ $t('reports.suppliers_report.apply_filters') }}</span>
                        </button>

                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750 text-xs font-bold transition-all flex items-center gap-1.5"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            <span>{{ $t('reports.suppliers_report.reset_filters') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards (Screen Only) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3.5 print:hidden">
                <!-- 1. Total Invoices & Returns -->
                <div
                    class="bg-gradient-to-br from-rose-500/10 to-red-500/5 dark:from-rose-500/20 dark:to-red-500/10 rounded-2xl p-4 border border-rose-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.total_records') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400"
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
                    <div
                        class="mt-2 text-2xl font-black text-rose-700 dark:text-rose-400 font-mono"
                    >
                        {{ kpis.total_records }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ kpis.total_invoices_count || kpis.total_records }} فواتير
                        <span v-if="kpis.total_returns_count">
                            | {{ kpis.total_returns_count }} مرتجع
                        </span>
                    </div>
                </div>

                <!-- 2. Net Subtotal -->
                <div
                    class="bg-gradient-to-br from-slate-500/10 to-gray-500/5 dark:from-slate-500/20 dark:to-gray-500/10 rounded-2xl p-4 border border-slate-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.subtotal') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-slate-500/20 flex items-center justify-center text-slate-600 dark:text-slate-400"
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
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black text-slate-800 dark:text-slate-200 font-mono"
                    >
                        {{ formatMoney(kpis.total_subtotal) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.suppliers_report.net_subtotal') }}
                    </div>
                </div>

                <!-- 3. Net VAT -->
                <div
                    class="bg-gradient-to-br from-indigo-500/10 to-blue-500/5 dark:from-indigo-500/20 dark:to-blue-500/10 rounded-2xl p-4 border border-indigo-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.total_vat') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400"
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
                    <div
                        class="mt-2 text-2xl font-black text-indigo-700 dark:text-indigo-400 font-mono"
                    >
                        {{ formatMoney(kpis.total_vat) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.suppliers_report.net_vat') }}
                    </div>
                </div>

                <!-- 4. Net Due (Invoice Amount) -->
                <div
                    class="bg-gradient-to-br from-red-500/10 to-rose-500/5 dark:from-red-500/20 dark:to-rose-500/10 rounded-2xl p-4 border border-red-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.total_amount') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-red-500/20 flex items-center justify-center text-red-600 dark:text-red-400"
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
                    <div class="mt-2 text-2xl font-black text-red-700 dark:text-red-400 font-mono">
                        {{ formatMoney(kpis.total_invoice_amount) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.suppliers_report.net_due') }}
                    </div>
                </div>

                <!-- 5. Total Paid -->
                <div
                    class="bg-gradient-to-br from-emerald-500/10 to-teal-500/5 dark:from-emerald-500/20 dark:to-teal-500/10 rounded-2xl p-4 border border-emerald-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.total_paid') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400"
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black text-emerald-700 dark:text-emerald-400 font-mono"
                    >
                        {{ formatMoney(kpis.total_paid) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        المسدد للموردين
                    </div>
                </div>

                <!-- 6. Total Adjustments & Returns -->
                <div
                    class="bg-gradient-to-br from-purple-500/10 to-pink-500/5 dark:from-purple-500/20 dark:to-pink-500/10 rounded-2xl p-4 border border-purple-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.amount_adjusted') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400"
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
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black text-purple-700 dark:text-purple-400 font-mono"
                    >
                        {{ formatMoney(kpis.total_discount) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.suppliers_report.adjusted_and_returns') }}
                    </div>
                </div>

                <!-- 7. Net Remaining -->
                <div
                    class="bg-gradient-to-br from-amber-500/10 to-orange-500/5 dark:from-amber-500/20 dark:to-orange-500/10 rounded-2xl p-4 border border-amber-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.suppliers_report.total_remaining') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400"
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
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black text-amber-700 dark:text-amber-400 font-mono"
                    >
                        {{ formatMoney(kpis.total_payment_remaining) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        صافي المتبقي للسداد
                    </div>
                </div>
            </div>

            <!-- Returns & Debit Notes Info Banner (if returns exist) -->
            <div
                v-if="kpis.total_returns_amount && kpis.total_returns_amount > 0"
                class="flex items-center justify-between p-3.5 rounded-2xl bg-purple-50/80 dark:bg-purple-900/20 border border-purple-200/70 dark:border-purple-800/50 text-xs text-purple-900 dark:text-purple-200 shadow-sm print:hidden"
            >
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600 animate-pulse"></span>
                    <span class="font-bold">
                        {{ $t('reports.suppliers_report.total_returns') }}:
                    </span>
                    <span class="font-mono font-black text-purple-800 dark:text-purple-300 text-sm">
                        {{ formatMoney(kpis.total_returns_amount) }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        ({{ kpis.total_returns_count }} مرتجع مسجل منها
                        {{ kpis.total_debit_notes_count }} إشعار مدين للمورد تم احتسابها وخصمها من
                        صافي المستحق)
                    </span>
                </div>
                <div
                    v-if="kpis.total_debit_notes_amount > 0"
                    class="font-mono text-purple-800 dark:text-purple-300 font-bold hidden sm:block text-xs"
                >
                    {{ $t('reports.suppliers_report.total_debit_notes') }}:
                    {{ formatMoney(kpis.total_debit_notes_amount) }}
                </div>
            </div>

            <!-- Grouped View (When group_by != 'none') -->
            <div v-if="groupedInvoices && groupedInvoices.length > 0" class="space-y-4">
                <div class="flex items-center justify-between px-1 print:hidden">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                        {{ groupedInvoices.length }}
                        {{ $t('reports.suppliers_report.group_summary') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAllGroups"
                            class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                            {{ $t('reports.suppliers_report.expand_all') }}
                        </button>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <button
                            type="button"
                            @click="collapseAllGroups"
                            class="text-xs font-bold text-gray-500 hover:underline flex items-center gap-1"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 15l7-7 7 7"
                                />
                            </svg>
                            {{ $t('reports.suppliers_report.collapse_all') }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(grp, idx) in groupedInvoices"
                    :key="grp.group_key"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 overflow-hidden"
                >
                    <!-- Group Header Accordion Bar -->
                    <div
                        @click="toggleGroup(grp.group_key)"
                        class="p-4 bg-gray-50 dark:bg-gray-750/70 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-7 h-7 rounded-lg bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xs"
                            >
                                {{ idx + 1 }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ grp.group_name }}
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ grp.total_records }} فواتير
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-gray-400 block">قبل الضريبة</span>
                                <span
                                    class="font-mono font-bold text-xs text-slate-700 dark:text-slate-300"
                                >
                                    {{ formatMoney(grp.total_subtotal) }}
                                </span>
                            </div>
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-gray-400 block">الضريبة</span>
                                <span class="font-mono font-bold text-xs text-indigo-600">
                                    {{ formatMoney(grp.total_vat) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-gray-400 block">المستحق</span>
                                <span
                                    class="font-mono font-bold text-xs text-rose-700 dark:text-rose-400"
                                >
                                    {{ formatMoney(grp.total_invoice_amount) }}
                                </span>
                            </div>
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-gray-400 block">المسدد</span>
                                <span class="font-mono font-bold text-xs text-emerald-600">
                                    {{ formatMoney(grp.total_paid) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-gray-400 block">المتبقي</span>
                                <span
                                    class="font-mono font-bold text-xs text-amber-700 dark:text-amber-400"
                                >
                                    {{ formatMoney(grp.total_payment_remaining) }}
                                </span>
                            </div>
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': isGroupOpen(grp.group_key) }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Group Items Table -->
                    <div v-show="isGroupOpen(grp.group_key)" class="overflow-x-auto">
                        <table class="w-full text-xs text-right">
                            <thead
                                class="bg-gray-50/75 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-700"
                            >
                                <tr>
                                    <th class="py-2.5 px-3 w-12 text-center">#</th>
                                    <th class="py-2.5 px-3">النوع</th>
                                    <th class="py-2.5 px-3">الفرع</th>
                                    <th class="py-2.5 px-3">رقم المعاملة</th>
                                    <th class="py-2.5 px-3">مستند المورد / البيان</th>
                                    <th class="py-2.5 px-3">المورد</th>
                                    <th class="py-2.5 px-3">التاريخ</th>
                                    <th class="py-2.5 px-3 text-center">قبل الضريبة</th>
                                    <th class="py-2.5 px-3 text-center">الضريبة</th>
                                    <th class="py-2.5 px-3 text-center">المستحق</th>
                                    <th class="py-2.5 px-3 text-center">المسدد</th>
                                    <th class="py-2.5 px-3 text-center">التسويات / الإرجاع</th>
                                    <th class="py-2.5 px-3 text-center">المتبقي</th>
                                    <th class="py-2.5 px-3 text-center">حالة المعاملة</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium"
                            >
                                <tr
                                    v-for="(inv, itemIdx) in grp.items"
                                    :key="inv.id"
                                    class="hover:bg-rose-50/30 dark:hover:bg-rose-900/10 transition-colors"
                                >
                                    <td class="py-2.5 px-3 text-center text-gray-400">
                                        {{ itemIdx + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="getDocumentTypeBadgeClasses(inv.record_type)"
                                        >
                                            {{ getDocumentTypeLabel(inv.record_type) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">
                                        {{ inv.center?.name || '—' }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <a
                                            v-if="inv.show_url"
                                            :href="inv.show_url"
                                            class="font-mono font-bold text-rose-600 hover:text-rose-800 hover:underline flex items-center gap-1"
                                        >
                                            {{ inv.code }}
                                        </a>
                                        <span
                                            v-else
                                            class="font-mono font-bold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ inv.code }}
                                        </span>

                                        <!-- Returns attached to this invoice -->
                                        <div
                                            v-if="inv.has_returns && inv.return_invoices?.length"
                                            class="mt-1 flex flex-col gap-0.5"
                                        >
                                            <a
                                                v-for="ret in inv.return_invoices"
                                                :key="ret.id"
                                                :href="ret.show_url"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:underline border border-amber-200 dark:border-amber-800/50"
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full bg-amber-500"
                                                ></span>
                                                {{
                                                    ret.create_debit_note ? 'إشعار مدين' : 'مرتجع'
                                                }}: {{ ret.code }} ({{ formatMoney(ret.total) }})
                                            </a>
                                        </div>

                                        <!-- Parent invoice for return/debit note -->
                                        <div
                                            v-if="inv.parent_invoice_code"
                                            class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400"
                                        >
                                            {{ $t('reports.suppliers_report.parent_invoice') }}:
                                            <a
                                                v-if="inv.parent_invoice_url"
                                                :href="inv.parent_invoice_url"
                                                class="font-mono font-bold text-rose-600 hover:underline"
                                            >
                                                {{ inv.parent_invoice_code }}
                                            </a>
                                            <span v-else class="font-mono font-bold">
                                                {{ inv.parent_invoice_code }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="py-2.5 px-3 font-mono text-gray-600 dark:text-gray-400"
                                    >
                                        {{ inv.invoice_number || '—' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-900 dark:text-white font-bold">
                                        {{ inv.supplier?.name || '—' }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 font-mono text-gray-600 dark:text-gray-300"
                                    >
                                        {{ inv.issue_date || '—' }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono"
                                        :class="{ 'text-rose-600 font-bold': inv.subtotal < 0 }"
                                    >
                                        {{ formatMoney(inv.subtotal) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono"
                                        :class="
                                            inv.tax_amount < 0
                                                ? 'text-rose-600 font-bold'
                                                : 'text-indigo-600'
                                        "
                                    >
                                        {{ formatMoney(inv.tax_amount) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono font-bold"
                                        :class="
                                            inv.total < 0
                                                ? 'text-rose-600 dark:text-rose-400'
                                                : 'text-rose-700 dark:text-rose-400'
                                        "
                                    >
                                        {{ formatMoney(inv.total) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono font-bold text-emerald-600"
                                    >
                                        {{ formatMoney(inv.paid) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono"
                                        :class="
                                            inv.discount_amount > 0
                                                ? 'text-purple-600 dark:text-purple-400 font-bold'
                                                : 'text-gray-500'
                                        "
                                    >
                                        {{ formatMoney(inv.discount_amount) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono font-bold"
                                        :class="
                                            inv.balance < 0
                                                ? 'text-purple-600'
                                                : inv.balance > 0
                                                  ? 'text-amber-600'
                                                  : 'text-gray-400'
                                        "
                                    >
                                        {{ formatMoney(inv.balance) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold"
                                            :class="getPaymentBadgeClasses(inv.payment_status)"
                                        >
                                            {{ getPaymentStatusLabel(inv.payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detailed Table View (When group_by == 'none') -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead
                            class="bg-gray-50/75 dark:bg-gray-750/50 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-200/80 dark:border-gray-700"
                        >
                            <tr>
                                <th class="py-3 px-3 w-12 text-center">#</th>
                                <th class="py-3 px-3">النوع</th>
                                <th class="py-3 px-3">
                                    {{ $t('reports.suppliers_report.center') }}
                                </th>
                                <th class="py-3 px-3">
                                    {{ $t('reports.suppliers_report.invoice_no') }}
                                </th>
                                <th class="py-3 px-3">
                                    {{ $t('reports.suppliers_report.supplier_doc_no') }}
                                </th>
                                <th class="py-3 px-3">
                                    {{ $t('reports.suppliers_report.supplier') }}
                                </th>
                                <th class="py-3 px-3">
                                    {{ $t('reports.suppliers_report.invoice_date') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.subtotal') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.vat') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.payment_due') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.amount_paid') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.amount_adjusted') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.payment_remaining') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.suppliers_report.payment_status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            v-if="invoices && invoices.data && invoices.data.length > 0"
                            class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium"
                        >
                            <tr
                                v-for="(inv, idx) in invoices.data"
                                :key="inv.id"
                                class="hover:bg-rose-50/30 dark:hover:bg-rose-900/10 transition-colors"
                            >
                                <td class="py-3 px-3 text-center text-gray-400">
                                    {{ (invoices.current_page - 1) * invoices.per_page + idx + 1 }}
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="getDocumentTypeBadgeClasses(inv.record_type)"
                                    >
                                        {{ getDocumentTypeLabel(inv.record_type) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-gray-700 dark:text-gray-300">
                                    {{ inv.center?.name || '—' }}
                                </td>
                                <td class="py-3 px-3">
                                    <a
                                        v-if="inv.show_url"
                                        :href="inv.show_url"
                                        class="font-mono font-bold text-rose-600 hover:text-rose-800 hover:underline flex items-center gap-1"
                                    >
                                        {{ inv.code }}
                                    </a>
                                    <span
                                        v-else
                                        class="font-mono font-bold text-gray-800 dark:text-gray-200"
                                    >
                                        {{ inv.code }}
                                    </span>

                                    <!-- Returns attached to this invoice -->
                                    <div
                                        v-if="inv.has_returns && inv.return_invoices?.length"
                                        class="mt-1 flex flex-col gap-0.5"
                                    >
                                        <a
                                            v-for="ret in inv.return_invoices"
                                            :key="ret.id"
                                            :href="ret.show_url"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:underline border border-amber-200 dark:border-amber-800/50"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full bg-amber-500"
                                            ></span>
                                            {{ ret.create_debit_note ? 'إشعار مدين' : 'مرتجع' }}:
                                            {{ ret.code }} ({{ formatMoney(ret.total) }})
                                        </a>
                                    </div>

                                    <!-- Parent invoice for return/debit note -->
                                    <div
                                        v-if="inv.parent_invoice_code"
                                        class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $t('reports.suppliers_report.parent_invoice') }}:
                                        <a
                                            v-if="inv.parent_invoice_url"
                                            :href="inv.parent_invoice_url"
                                            class="font-mono font-bold text-rose-600 hover:underline"
                                        >
                                            {{ inv.parent_invoice_code }}
                                        </a>
                                        <span v-else class="font-mono font-bold">
                                            {{ inv.parent_invoice_code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 font-mono text-gray-600 dark:text-gray-400">
                                    {{ inv.invoice_number || '—' }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ inv.supplier?.name || '—' }}
                                    </div>
                                    <div
                                        v-if="inv.supplier?.phone"
                                        class="text-[11px] text-gray-400 font-mono"
                                    >
                                        {{ inv.supplier.phone }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 font-mono text-gray-600 dark:text-gray-300">
                                    {{ inv.issue_date || '—' }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono"
                                    :class="{ 'text-rose-600 font-bold': inv.subtotal < 0 }"
                                >
                                    {{ formatMoney(inv.subtotal) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono"
                                    :class="
                                        inv.tax_amount < 0
                                            ? 'text-rose-600 font-bold'
                                            : 'text-indigo-600'
                                    "
                                >
                                    {{ formatMoney(inv.tax_amount) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono font-bold"
                                    :class="
                                        inv.total < 0
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-rose-700 dark:text-rose-400'
                                    "
                                >
                                    {{ formatMoney(inv.total) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono font-bold text-emerald-600"
                                >
                                    {{ formatMoney(inv.paid) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono"
                                    :class="
                                        inv.discount_amount > 0
                                            ? 'text-purple-600 dark:text-purple-400 font-bold'
                                            : 'text-gray-500'
                                    "
                                >
                                    {{ formatMoney(inv.discount_amount) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono font-bold"
                                    :class="
                                        inv.balance < 0
                                            ? 'text-purple-600'
                                            : inv.balance > 0
                                              ? 'text-amber-600'
                                              : 'text-gray-400'
                                    "
                                >
                                    {{ formatMoney(inv.balance) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        :class="getPaymentBadgeClasses(inv.payment_status)"
                                    >
                                        {{ getPaymentStatusLabel(inv.payment_status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td
                                    colspan="14"
                                    class="py-12 text-center text-gray-400 dark:text-gray-500"
                                >
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg
                                            class="w-10 h-10 text-gray-300 dark:text-gray-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                            />
                                        </svg>
                                        <span
                                            class="text-sm font-bold text-gray-700 dark:text-gray-300"
                                        >
                                            {{ $t('reports.suppliers_report.no_records') }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $t('reports.suppliers_report.no_records_hint') }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="invoices && invoices.links && invoices.links.length > 3"
                    class="p-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs"
                >
                    <div class="text-gray-500">
                        {{ $t('reports.suppliers_report.showing') }}
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ invoices.from || 0 }}
                        </span>
                        {{ $t('reports.suppliers_report.to') }}
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ invoices.to || 0 }}
                        </span>
                        {{ $t('reports.suppliers_report.of') }}
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ invoices.total || 0 }}
                        </span>
                        {{ $t('reports.suppliers_report.record') }}
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in invoices.links" :key="i">
                            <button
                                v-if="link.url"
                                type="button"
                                @click="
                                    router.get(
                                        link.url,
                                        {},
                                        { preserveState: true, preserveScroll: true }
                                    )
                                "
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                    link.active
                                        ? 'bg-rose-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-2 py-1 text-gray-400 text-xs"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Standard Print Footer -->
            <div class="print-section hidden mt-8">
                <StandardReportFooter :company="companyInfo" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const { t } = useI18n();

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    kpis: {
        type: Object,
        required: true,
    },
    invoices: {
        type: Object,
        default: null,
    },
    groupedInvoices: {
        type: Array,
        default: null,
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
        default: () => ({}),
    },
});

// Local reactive filters matching props
const localFilters = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.range === 'custom' ? props.filters.start_date || '' : '',
    end_date: props.filters.range === 'custom' ? props.filters.end_date || '' : '',
    payment_status: String(props.filters.payment_status || 'all'),
    document_type: String(props.filters.document_type || 'all'),
    supplier_id: String(props.filters.supplier_id || 'all'),
    supplier_type: String(props.filters.supplier_type || 'all'),
    center_id: String(props.filters.center_id || 'all'),
    group_by: String(props.filters.group_by || 'none'),
    search: props.filters.search || '',
});

// Sync local filters on Inertia page navigation
watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        localFilters.range = newFilters.range || 'this_month';
        localFilters.start_date = newFilters.range === 'custom' ? newFilters.start_date || '' : '';
        localFilters.end_date = newFilters.range === 'custom' ? newFilters.end_date || '' : '';
        localFilters.payment_status = String(newFilters.payment_status || 'all');
        localFilters.document_type = String(newFilters.document_type || 'all');
        localFilters.supplier_id = String(newFilters.supplier_id || 'all');
        localFilters.supplier_type = String(newFilters.supplier_type || 'all');
        localFilters.center_id = String(newFilters.center_id || 'all');
        localFilters.group_by = String(newFilters.group_by || 'none');
        localFilters.search = newFilters.search || '';
    },
    { deep: true }
);

// Accordion open/close state for grouped view
const openGroups = reactive({});

const isGroupOpen = (key) => {
    if (openGroups[key] === undefined) {
        return true; // open by default
    }
    return openGroups[key];
};

const toggleGroup = (key) => {
    openGroups[key] = !isGroupOpen(key);
};

const expandAllGroups = () => {
    if (props.groupedInvoices) {
        props.groupedInvoices.forEach((g) => {
            openGroups[g.group_key] = true;
        });
    }
};

const collapseAllGroups = () => {
    if (props.groupedInvoices) {
        props.groupedInvoices.forEach((g) => {
            openGroups[g.group_key] = false;
        });
    }
};

// Date Presets
const datePresets = computed(() => [
    { id: 'today', label: t('reports.suppliers_report.today') },
    { id: 'yesterday', label: t('reports.suppliers_report.yesterday') },
    { id: 'this_week', label: t('reports.suppliers_report.this_week') },
    { id: 'this_month', label: t('reports.suppliers_report.this_month') },
    { id: 'this_year', label: t('reports.suppliers_report.this_year') },
    { id: 'all', label: t('reports.suppliers_report.all_time') },
    { id: 'custom', label: t('reports.suppliers_report.custom') },
]);

const selectPreset = (presetId) => {
    localFilters.range = presetId;
    if (presetId !== 'custom') {
        localFilters.start_date = '';
        localFilters.end_date = '';
    }
    applyFilters();
};

// Select Dropdown Options (providing id, value, name, label for full compatibility)
const centerOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.suppliers_report.all_centers'),
        label: t('reports.suppliers_report.all_centers'),
    },
    ...(props.centers || []).map((c) => ({
        id: String(c.id),
        value: String(c.id),
        name: c.name,
        label: c.name,
    })),
]);

const supplierOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.suppliers_report.all_suppliers'),
        label: t('reports.suppliers_report.all_suppliers'),
    },
    ...(props.suppliers || []).map((s) => ({
        id: String(s.id),
        value: String(s.id),
        name: s.phone ? `${s.name} (${s.phone})` : s.name,
        label: s.phone ? `${s.name} (${s.phone})` : s.name,
    })),
]);

const supplierTypeOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.suppliers_report.all_supplier_types'),
        label: t('reports.suppliers_report.all_supplier_types'),
    },
    {
        id: 'individual',
        value: 'individual',
        name: t('reports.suppliers_report.type_individual'),
        label: t('reports.suppliers_report.type_individual'),
    },
    {
        id: 'company',
        value: 'company',
        name: t('reports.suppliers_report.type_company'),
        label: t('reports.suppliers_report.type_company'),
    },
]);

const documentTypeOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.suppliers_report.all_documents'),
        label: t('reports.suppliers_report.all_documents'),
    },
    {
        id: 'invoices',
        value: 'invoices',
        name: t('reports.suppliers_report.purchase_invoices_only'),
        label: t('reports.suppliers_report.purchase_invoices_only'),
    },
    {
        id: 'returns',
        value: 'returns',
        name: t('reports.suppliers_report.returns_only'),
        label: t('reports.suppliers_report.returns_only'),
    },
    {
        id: 'debit_notes',
        value: 'debit_notes',
        name: t('reports.suppliers_report.debit_notes_only'),
        label: t('reports.suppliers_report.debit_notes_only'),
    },
]);

const paymentStatusOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.suppliers_report.all_payment_statuses'),
        label: t('reports.suppliers_report.all_payment_statuses'),
    },
    {
        id: 'paid',
        value: 'paid',
        name: t('reports.suppliers_report.status_paid'),
        label: t('reports.suppliers_report.status_paid'),
    },
    {
        id: 'partial',
        value: 'partial',
        name: t('reports.suppliers_report.status_partial'),
        label: t('reports.suppliers_report.status_partial'),
    },
    {
        id: 'unpaid',
        value: 'unpaid',
        name: t('reports.suppliers_report.status_unpaid'),
        label: t('reports.suppliers_report.status_unpaid'),
    },
]);

const groupByOptions = computed(() => [
    {
        id: 'none',
        value: 'none',
        name: t('reports.suppliers_report.group_none'),
        label: t('reports.suppliers_report.group_none'),
    },
    {
        id: 'supplier',
        value: 'supplier',
        name: t('reports.suppliers_report.group_supplier'),
        label: t('reports.suppliers_report.group_supplier'),
    },
    {
        id: 'center',
        value: 'center',
        name: t('reports.suppliers_report.group_center'),
        label: t('reports.suppliers_report.group_center'),
    },
    {
        id: 'payment_status',
        value: 'payment_status',
        name: t('reports.suppliers_report.group_payment_status'),
        label: t('reports.suppliers_report.group_payment_status'),
    },
]);

// Apply Filters through Inertia
const applyFilters = () => {
    const isCustom = localFilters.range === 'custom';
    const params = {
        range: localFilters.range,
        start_date: isCustom ? localFilters.start_date || undefined : undefined,
        end_date: isCustom ? localFilters.end_date || undefined : undefined,
        payment_status:
            localFilters.payment_status !== 'all' ? localFilters.payment_status : undefined,
        document_type:
            localFilters.document_type !== 'all' ? localFilters.document_type : undefined,
        supplier_id: localFilters.supplier_id !== 'all' ? localFilters.supplier_id : undefined,
        supplier_type:
            localFilters.supplier_type !== 'all' ? localFilters.supplier_type : undefined,
        center_id: localFilters.center_id !== 'all' ? localFilters.center_id : undefined,
        group_by: localFilters.group_by !== 'none' ? localFilters.group_by : undefined,
        search: localFilters.search ? localFilters.search.trim() : undefined,
    };

    router.get(route('app.reports.suppliers'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset Filters
const resetFilters = () => {
    localFilters.range = 'this_month';
    localFilters.start_date = '';
    localFilters.end_date = '';
    localFilters.payment_status = 'all';
    localFilters.document_type = 'all';
    localFilters.supplier_id = 'all';
    localFilters.supplier_type = 'all';
    localFilters.center_id = 'all';
    localFilters.group_by = 'none';
    localFilters.search = '';
    applyFilters();
};

// Export URL
const exportUrl = computed(() => {
    const query = new URLSearchParams();
    if (localFilters.range) query.set('range', localFilters.range);
    if (localFilters.range === 'custom') {
        if (localFilters.start_date) query.set('start_date', localFilters.start_date);
        if (localFilters.end_date) query.set('end_date', localFilters.end_date);
    }
    if (localFilters.payment_status && localFilters.payment_status !== 'all')
        query.set('payment_status', localFilters.payment_status);
    if (localFilters.document_type && localFilters.document_type !== 'all')
        query.set('document_type', localFilters.document_type);
    if (localFilters.supplier_id && localFilters.supplier_id !== 'all')
        query.set('supplier_id', localFilters.supplier_id);
    if (localFilters.supplier_type && localFilters.supplier_type !== 'all')
        query.set('supplier_type', localFilters.supplier_type);
    if (localFilters.center_id && localFilters.center_id !== 'all')
        query.set('center_id', localFilters.center_id);
    if (localFilters.search) query.set('search', localFilters.search);

    return route('app.reports.suppliers.export') + '?' + query.toString();
});

// Printing
const handlePrint = () => {
    window.print();
};

// Badge helpers
const getPaymentBadgeClasses = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300';
        case 'partial':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300';
        case 'debit_note':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300';
        case 'returned':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300';
        case 'unpaid':
        default:
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300';
    }
};

const getPaymentStatusLabel = (status) => {
    switch (status) {
        case 'paid':
            return t('reports.suppliers_report.status_paid');
        case 'partial':
            return t('reports.suppliers_report.status_partial');
        case 'debit_note':
            return t('reports.suppliers_report.doc_debit_note');
        case 'returned':
            return t('reports.suppliers_report.doc_return_invoice');
        case 'unpaid':
        default:
            return t('reports.suppliers_report.status_unpaid');
    }
};

const getDocumentTypeBadgeClasses = (recordType) => {
    switch (recordType) {
        case 'debit_note':
            return 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-300';
        case 'return_invoice':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300';
        case 'purchase_invoice':
        default:
            return 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20 dark:bg-sky-900/30 dark:text-sky-300';
    }
};

const getDocumentTypeLabel = (recordType) => {
    switch (recordType) {
        case 'debit_note':
            return t('reports.suppliers_report.doc_debit_note');
        case 'return_invoice':
            return t('reports.suppliers_report.doc_return_invoice');
        case 'purchase_invoice':
        default:
            return t('reports.suppliers_report.doc_purchase_invoice');
    }
};

// Formatter Helpers
const formatMoney = (val) => {
    const num = Number(val || 0);
    return `${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ر.س`;
};

const formatNumber = (val) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};
</script>

<style>
@media print {
    body {
        background: white !important;
        color: black !important;
    }

    .print\:hidden {
        display: none !important;
    }

    .print-section {
        display: block !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 9pt !important;
    }

    th,
    td {
        border: 1px solid #d1d5db !important;
        padding: 4px 6px !important;
    }

    thead {
        display: table-header-group !important;
        background-color: #f3f4f6 !important;
    }

    tr {
        page-break-inside: avoid !important;
    }
}
</style>
