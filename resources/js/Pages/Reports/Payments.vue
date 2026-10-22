<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-gray-800 print:hidden"
            >
                <div class="flex items-center gap-4">
                    <!-- Premium Back Button -->
                    <a
                        href="/app/reports"
                        class="p-2.5 rounded-2xl backdrop-blur-md bg-white/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/80 text-indigo-600 dark:text-indigo-400 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-300 group shrink-0"
                        :title="$t('reports.payments_report.back_to_reports')"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180 group-hover:-translate-x-0.5 rtl:group-hover:translate-x-0.5 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </a>

                    <div>
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1"
                        >
                            <span>{{ $t('reports.title') }}</span>
                            <span>/</span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold">
                                {{ $t('reports.payments_report.title') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold shadow-lg shadow-indigo-500/25 shrink-0"
                            >
                                <svg
                                    class="w-6 h-6"
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
                            <div>
                                <h1
                                    class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white"
                                >
                                    {{ $t('reports.payments_report.title') }}
                                </h1>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('reports.payments_report.subtitle') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export & Print Actions -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="handlePrint"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-750 text-xs font-bold transition-all shadow-sm"
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
                        <span>{{ $t('reports.payments_report.print_report') }}</span>
                    </button>

                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold transition-all shadow-lg shadow-indigo-500/25 hover:-translate-y-0.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        <span>{{ $t('reports.payments_report.export_excel') }}</span>
                    </a>
                </div>
            </div>

            <!-- AUDIT_STANDARDS.md Section 5: Standard Official Print Section -->
            <div class="print-section hidden">
                <div class="grid grid-cols-3 items-center pb-4 mb-4 border-b-2 border-gray-800">
                    <!-- Column 1: Company Info -->
                    <div class="text-start space-y-1 text-xs">
                        <h2 class="text-base font-black text-black">{{ companyInfo.name }}</h2>
                        <p
                            v-if="companyInfo.center_name && centers?.length > 1"
                            class="font-bold text-gray-700"
                        >
                            {{ $t('reports.payments_report.center') }}:
                            {{ companyInfo.center_name }}
                        </p>
                        <p v-if="companyInfo.vat_number" class="text-gray-600">
                            {{ $t('common.vat') }}:
                            <span class="font-mono font-bold">{{ companyInfo.vat_number }}</span>
                        </p>
                        <p v-if="companyInfo.cr_number" class="text-gray-600">
                            CR:
                            <span class="font-mono">{{ companyInfo.cr_number }}</span>
                        </p>
                    </div>

                    <!-- Column 2 (Center): Official Logo -->
                    <div class="text-center flex flex-col items-center justify-center">
                        <img
                            v-if="companyInfo.logo_path"
                            :src="'/storage/' + companyInfo.logo_path"
                            alt="Logo"
                            class="h-14 w-auto object-contain mx-auto mb-1"
                        />
                        <div v-else class="text-center">
                            <span class="text-lg font-black tracking-wider text-black">CARAG</span>
                        </div>
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ $t('reports.payments_report.title') }}
                        </span>
                    </div>

                    <!-- Column 3: Report Metadata -->
                    <div class="text-end space-y-1 text-xs text-gray-600">
                        <p class="font-bold text-black">
                            {{ $t('reports.payments_report.official_report') }}
                        </p>
                        <p>
                            {{ $t('reports.payments_report.report_period') }}:
                            <span class="font-mono">{{ filters.start_date || 'All' }}</span>
                            {{ $t('reports.payments_report.to') }}
                            <span class="font-mono">{{ filters.end_date || 'Now' }}</span>
                        </p>
                        <p>
                            {{ $t('reports.payments_report.printed_at') }}:
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                        <p>
                            {{ $t('reports.payments_report.transaction_count') }}:
                            <span class="font-mono font-bold text-black">
                                {{ kpis.total_count }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Print Financial Summary Banner -->
                <div
                    class="grid grid-cols-3 gap-2 mb-4 p-3 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.payments_report.total_collected') }}:
                        </span>
                        <strong class="font-mono text-sm">
                            {{ formatMoney(kpis.total_collected) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.payments_report.total_refunded') }}:
                        </span>
                        <strong class="font-mono text-sm text-red-700">
                            {{ formatMoney(kpis.total_refunded) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.payments_report.net_collected') }}:
                        </span>
                        <strong class="font-mono text-sm text-black">
                            {{ formatMoney(kpis.net_collected) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards (Screen View) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                <!-- Net Collections -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-indigo-100 dark:border-indigo-900/40 shadow-xl shadow-indigo-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                            >
                                {{ $t('reports.payments_report.net_collected') }}
                            </span>
                            <div
                                class="text-2xl font-black text-gray-900 dark:text-white mt-1 font-mono"
                            >
                                {{ formatMoney(kpis.net_collected) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner"
                        >
                            <svg
                                class="w-6 h-6"
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
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.payments_report.total_collected') }} -
                        {{ $t('reports.payments_report.total_refunded') }}
                    </div>
                </div>

                <!-- Total Collected -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-blue-100 dark:border-blue-900/40 shadow-xl shadow-blue-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                            >
                                {{ $t('reports.payments_report.total_collected') }}
                            </span>
                            <div
                                class="text-2xl font-black text-gray-900 dark:text-white mt-1 font-mono"
                            >
                                {{ formatMoney(kpis.total_collected) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-inner"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 11l5-5m0 0l5 5m-5-5v12"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.payments_report.receipts') }}
                    </div>
                </div>

                <!-- Total Refunded -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-rose-100 dark:border-rose-900/40 shadow-xl shadow-rose-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400"
                            >
                                {{ $t('reports.payments_report.total_refunded') }}
                            </span>
                            <div
                                class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 font-mono"
                            >
                                {{ formatMoney(kpis.total_refunded) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-inner"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 13l-5 5m0 0l-5-5m5 5V6"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.payments_report.refunds') }}
                    </div>
                </div>

                <!-- Transaction Count -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-purple-100 dark:border-purple-900/40 shadow-xl shadow-purple-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400"
                            >
                                {{ $t('reports.payments_report.transaction_count') }}
                            </span>
                            <div
                                class="text-2xl font-black text-gray-900 dark:text-white mt-1 font-mono"
                            >
                                {{ kpis.total_count }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-inner"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.payments_report.report_period') }}
                    </div>
                </div>
            </div>

            <!-- Methods Breakdown Strip -->
            <div
                v-if="methodsBreakdown && methodsBreakdown.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm print:hidden"
            >
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 block mb-3">
                    {{ $t('reports.payments_report.methods_breakdown') }}:
                </span>
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        v-for="item in methodsBreakdown"
                        :key="item.method"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 text-xs"
                    >
                        <span class="font-bold text-gray-700 dark:text-gray-300">
                            {{ getMethodLabel(item.method) }}:
                        </span>
                        <span class="font-black text-indigo-600 dark:text-indigo-400 font-mono">
                            {{ formatMoney(item.total) }}
                        </span>
                        <span
                            class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 font-mono"
                        >
                            {{ item.count }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- DEDICATED DATE RANGE BOX (مربع الفترة الزمنية) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-indigo-100/80 dark:border-gray-700/80 shadow-xl shadow-indigo-500/5 space-y-4 print:hidden"
            >
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/60"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner"
                        >
                            <svg
                                class="w-5 h-5"
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
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $t('reports.payments_report.date_range') }}
                            </h2>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ activeDateRangeLabel }}
                            </p>
                        </div>
                    </div>

                    <!-- Quick Preset Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            v-for="preset in presets"
                            :key="preset.id"
                            @click="selectPreset(preset.id)"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                                filterForm.range === preset.id
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <!-- Date Inputs Side-by-Side & Action Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <!-- Date From -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.payments_report.date_from') }}
                        </label>
                        <CustomDatePicker
                            v-model="filterForm.start_date"
                            @update:modelValue="onCustomDateInput"
                            placeholder="YYYY-MM-DD"
                            class="!rounded-2xl"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.payments_report.date_to') }}
                        </label>
                        <CustomDatePicker
                            v-model="filterForm.end_date"
                            @update:modelValue="onCustomDateInput"
                            placeholder="YYYY-MM-DD"
                            class="!rounded-2xl"
                        />
                    </div>

                    <!-- Filter Actions: Apply & Reset -->
                    <div class="sm:col-span-2 flex items-center gap-2">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold shadow-lg shadow-indigo-500/25 transition-all hover:-translate-y-0.5"
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
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                />
                            </svg>
                            <span>{{ $t('reports.payments_report.apply_filters') }}</span>
                        </button>

                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 text-xs font-bold transition-all shadow-sm"
                        >
                            {{ $t('reports.payments_report.reset_filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- SECONDARY FILTERS BAR (SearchableSelect for Methods & Customers) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4 print:hidden"
            >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Direct Search Input -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.payments_report.direct_search') }}
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="filterForm.search"
                                @keyup.enter="applyFilters"
                                :placeholder="$t('reports.payments_report.search_placeholder')"
                                class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 pe-8 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                            />
                            <button
                                v-if="filterForm.search"
                                @click="
                                    filterForm.search = '';
                                    applyFilters();
                                "
                                class="absolute end-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                            >
                                ×
                            </button>
                        </div>
                    </div>

                    <!-- SearchableSelect: Payment Method -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.payments_report.payment_method') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.method"
                            :options="methodOptions"
                            optionLabel="name"
                            optionValue="id"
                            :placeholder="$t('reports.payments_report.select_method')"
                            :searchPlaceholder="$t('reports.payments_report.search_method')"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- SearchableSelect: Customer -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.payments_report.customer') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_id"
                            :options="customerOptions"
                            optionLabel="name"
                            optionValue="id"
                            :placeholder="$t('reports.payments_report.select_customer')"
                            :searchPlaceholder="$t('reports.payments_report.search_customer')"
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Voucher Type Filter Radios -->
                <div
                    class="flex items-center gap-4 text-xs pt-2 border-t border-gray-100 dark:border-gray-750"
                >
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input
                            type="radio"
                            v-model="filterForm.type"
                            value="all"
                            @change="applyFilters"
                            class="text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-gray-700 dark:text-gray-300 font-medium">
                            {{ $t('reports.payments_report.all_types') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input
                            type="radio"
                            v-model="filterForm.type"
                            value="payment"
                            @change="applyFilters"
                            class="text-emerald-600 focus:ring-emerald-500"
                        />
                        <span class="text-emerald-700 dark:text-emerald-400 font-bold">
                            {{ $t('reports.payments_report.receipts') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input
                            type="radio"
                            v-model="filterForm.type"
                            value="refund"
                            @change="applyFilters"
                            class="text-rose-600 focus:ring-rose-500"
                        />
                        <span class="text-rose-700 dark:text-rose-400 font-bold">
                            {{ $t('reports.payments_report.refunds') }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Table of Payments (Screen + Print) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-xl shadow-indigo-500/5 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs text-gray-700 dark:text-gray-300">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 font-bold border-b border-gray-200 dark:border-gray-800 text-[11px]"
                        >
                            <tr>
                                <th class="px-4 py-3.5 text-center">#</th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.voucher_no') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.datetime') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.party') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.reference') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.vehicles') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.payment_method') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.voucher_type') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.amount') }}
                                </th>
                                <th class="px-4 py-3.5 text-center">
                                    {{ $t('reports.payments_report.received_by') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                            <tr
                                v-for="(payment, index) in payments.data"
                                :key="payment.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors text-center"
                            >
                                <!-- Row number -->
                                <td class="px-4 py-3 font-mono text-gray-400 text-xs">
                                    {{ (payments.from || 1) + index }}
                                </td>

                                <!-- Ref / ID -->
                                <td
                                    class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ payment.reference || 'REC-' + payment.id }}
                                </td>

                                <!-- Date (AUDIT_STANDARDS.md Section 3: English numbers) -->
                                <td
                                    class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400 text-[11px]"
                                >
                                    {{ formatDate(payment.payment_date) }}
                                </td>

                                <!-- Customer / Party -->
                                <td class="px-4 py-3 text-start">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ getPartyName(payment) }}
                                    </div>
                                    <div
                                        v-if="getPartyPhone(payment)"
                                        class="text-[10px] text-gray-400 font-mono"
                                    >
                                        {{ getPartyPhone(payment) }}
                                    </div>
                                </td>

                                <!-- Links (Work Order / Invoice) -->
                                <td class="px-4 py-3 text-center">
                                    <div
                                        v-if="payment.work_order"
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <a
                                            :href="'/app/work-orders/' + payment.work_order.id"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono text-xs font-bold"
                                        >
                                            {{ payment.work_order.work_order_number }}
                                        </a>
                                    </div>
                                    <div v-if="payment.invoice" class="text-[11px] text-gray-500">
                                        <span>{{ $t('reports.invoices') }}:</span>
                                        <a
                                            :href="'/app/invoices/' + payment.invoice.id"
                                            class="hover:underline font-mono text-gray-700 dark:text-gray-300 font-semibold"
                                        >
                                            {{ payment.invoice.invoice_number }}
                                        </a>
                                    </div>
                                    <div
                                        v-if="!payment.work_order && !payment.invoice"
                                        class="text-gray-400 text-xs"
                                    >
                                        —
                                    </div>
                                </td>

                                <!-- Vehicle -->
                                <td class="px-4 py-3 text-center">
                                    <div v-if="payment.work_order?.vehicle" class="space-y-0.5">
                                        <span
                                            class="inline-block px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 font-mono text-[10px] font-bold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ payment.work_order.vehicle.plate_number }}
                                        </span>
                                        <div class="text-[10px] text-gray-500">
                                            {{ payment.work_order.vehicle.make }}
                                            {{ payment.work_order.vehicle.model }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </td>

                                <!-- Payment Method -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="getMethodBadgeClass(payment.payment_method)"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    >
                                        {{ getMethodLabel(payment.payment_method) }}
                                    </span>
                                </td>

                                <!-- Type -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="payment.type === 'refund'"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300"
                                    >
                                        {{ $t('reports.payments_report.refund_voucher') }}
                                    </span>
                                    <span
                                        v-else-if="payment.type === 'bad_debt'"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ $t('payments.types.bad_debt') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                                    >
                                        {{ $t('reports.payments_report.receipt_voucher') }}
                                    </span>
                                </td>

                                <!-- Amount (AUDIT_STANDARDS.md Section 3: English numbers) -->
                                <td
                                    class="px-4 py-3 font-mono font-black text-sm text-center"
                                    :class="
                                        payment.type === 'refund'
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-gray-900 dark:text-white'
                                    "
                                >
                                    {{ payment.type === 'refund' ? '-' : ''
                                    }}{{ formatMoney(payment.amount) }}
                                </td>

                                <!-- Received By -->
                                <td
                                    class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 text-center"
                                >
                                    {{ payment.received_by?.name || '—' }}
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center">
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
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                        {{ $t('reports.payments_report.no_records') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $t('reports.payments_report.no_records_hint') }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="payments.links && payments.links.length > 3"
                    class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs print:hidden"
                >
                    <span class="text-gray-500">
                        {{ $t('reports.payments_report.showing') }}
                        <span class="font-mono">{{ payments.from || 0 }}</span>
                        {{ $t('reports.payments_report.to') }}
                        <span class="font-mono">{{ payments.to || 0 }}</span>
                        {{ $t('reports.payments_report.of') }}
                        <span class="font-mono font-bold">{{ payments.total }}</span>
                        {{ $t('reports.payments_report.transaction') }}
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, idx) in payments.links"
                            :key="idx"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-xl transition-all text-xs font-mono font-bold',
                                link.active
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- AUDIT_STANDARDS.md Section 5: Standard Print Footer -->
            <div
                class="print-footer hidden print:block pt-4 mt-6 border-t border-gray-300 text-center text-xs text-gray-600"
            >
                <p>
                    {{ $t('reports.payments_report.prepared_by') }}:
                    <span class="font-bold">
                        {{ $page.props.auth?.user?.name || 'Administrator' }}
                    </span>
                    | {{ $t('reports.payments_report.printed_at') }}:
                    <span class="font-mono">{{ printDateFormatted }}</span>
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';

const { t } = useI18n();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            range: 'this_month',
            start_date: '',
            end_date: '',
            method: 'all',
            type: 'all',
            customer_id: 'all',
            search: '',
        }),
    },
    kpis: {
        type: Object,
        default: () => ({
            total_collected: 0,
            total_refunded: 0,
            net_collected: 0,
            total_count: 0,
        }),
    },
    methodsBreakdown: {
        type: Array,
        default: () => [],
    },
    customers: {
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
    payments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    method: props.filters.method || 'all',
    type: props.filters.type || 'all',
    customer_id: props.filters.customer_id || 'all',
    search: props.filters.search || '',
});

const presets = computed(() => [
    { id: 'today', label: t('reports.payments_report.today') },
    { id: 'yesterday', label: t('reports.payments_report.yesterday') },
    { id: 'this_week', label: t('reports.payments_report.this_week') },
    { id: 'this_month', label: t('reports.payments_report.this_month') },
    { id: 'this_year', label: t('reports.payments_report.this_year') },
    { id: 'all', label: t('reports.payments_report.all_time') },
]);

const activeDateRangeLabel = computed(() => {
    if (filterForm.start_date && filterForm.end_date) {
        return `${filterForm.start_date} ${t('reports.payments_report.to')} ${filterForm.end_date}`;
    }
    const matched = presets.value.find((p) => p.id === filterForm.range);
    return matched ? matched.label : t('reports.payments_report.all_time');
});

const selectPreset = (presetId) => {
    filterForm.range = presetId;
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const toYmd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (presetId === 'today') {
        const todayStr = toYmd(now);
        filterForm.start_date = todayStr;
        filterForm.end_date = todayStr;
    } else if (presetId === 'yesterday') {
        const yest = new Date(now);
        yest.setDate(yest.getDate() - 1);
        const yestStr = toYmd(yest);
        filterForm.start_date = yestStr;
        filterForm.end_date = yestStr;
    } else if (presetId === 'this_week') {
        const day = now.getDay();
        const diff = now.getDate() - day + (day === 0 ? -6 : 1); // Monday/Sunday
        const start = new Date(now.setDate(diff));
        filterForm.start_date = toYmd(start);
        filterForm.end_date = toYmd(new Date());
    } else if (presetId === 'this_month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        filterForm.start_date = toYmd(start);
        filterForm.end_date = toYmd(end);
    } else if (presetId === 'this_year') {
        filterForm.start_date = `${now.getFullYear()}-01-01`;
        filterForm.end_date = `${now.getFullYear()}-12-31`;
    } else if (presetId === 'all') {
        filterForm.start_date = '';
        filterForm.end_date = '';
    }

    applyFilters();
};

const onCustomDateInput = () => {
    filterForm.range = 'custom';
};

const methodOptions = computed(() => [
    { id: 'all', name: t('reports.payments_report.all_methods') },
    { id: 'mada', name: `${t('payments.methods.mada')} (Mada)` },
    { id: 'cash', name: `${t('payments.methods.cash')} (Cash)` },
    { id: 'transfer', name: `${t('payments.methods.transfer')} (Transfer)` },
    { id: 'apple_pay', name: 'Apple Pay' },
    { id: 'visa', name: 'Visa' },
    { id: 'mastercard', name: 'MasterCard' },
    { id: 'stc_pay', name: 'STC Pay' },
    { id: 'tabby', name: 'Tabby' },
    { id: 'tamara', name: 'Tamara' },
    { id: 'credit', name: t('payments.methods.credit') },
]);

const customerOptions = computed(() => [
    { id: 'all', name: t('reports.payments_report.all_customers') },
    ...props.customers.map((c) => ({
        id: c.id,
        name: c.name + (c.phone ? ` (${c.phone})` : ''),
    })),
]);

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        range: filterForm.range || 'this_month',
        start_date: filterForm.start_date || '',
        end_date: filterForm.end_date || '',
        method: filterForm.method || 'all',
        type: filterForm.type || 'all',
        customer_id: filterForm.customer_id || 'all',
        search: filterForm.search || '',
    });
    return `/app/reports/payments/export?${params.toString()}`;
});

const applyFilters = () => {
    router.get(
        '/app/reports/payments',
        {
            range: filterForm.range || 'this_month',
            start_date: filterForm.start_date || '',
            end_date: filterForm.end_date || '',
            method: filterForm.method || 'all',
            type: filterForm.type || 'all',
            customer_id: filterForm.customer_id || 'all',
            search: filterForm.search || '',
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
    filterForm.method = 'all';
    filterForm.type = 'all';
    filterForm.customer_id = 'all';
    filterForm.search = '';
    applyFilters();
};

const handlePrint = () => {
    window.print();
};

// AUDIT_STANDARDS.md Section 3: All numbers & prices formatted in English digits (0-9)
const formatMoney = (val) => {
    const num = Number(val) || 0;
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) +
        ' ' +
        t('reports.payments_report.currency')
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const printDateFormatted = computed(() => {
    return new Date().toLocaleString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

const getPartyName = (p) => {
    return (
        p.work_order?.customer?.name ||
        p.invoice?.customer?.name ||
        p.purchase_invoice?.supplier?.name ||
        t('reports.payments_report.unknown_customer')
    );
};

const getPartyPhone = (p) => {
    return p.work_order?.customer?.phone || p.invoice?.customer?.phone || '';
};

const getMethodLabel = (m) => {
    const map = {
        mada: t('payments.methods.mada'),
        cash: t('payments.methods.cash'),
        transfer: t('payments.methods.transfer'),
        apple_pay: 'Apple Pay',
        visa: 'Visa',
        mastercard: 'MasterCard',
        stc_pay: 'STC Pay',
        tabby: 'Tabby',
        tamara: 'Tamara',
        credit: t('payments.methods.credit'),
        other: t('payments.methods.other'),
    };
    return map[m] || m || '—';
};

const getMethodBadgeClass = (m) => {
    switch (m) {
        case 'mada':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'cash':
            return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        case 'apple_pay':
            return 'bg-gray-900 text-white dark:bg-gray-700 dark:text-gray-100';
        case 'transfer':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'visa':
        case 'mastercard':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    }
};
</script>

<style scoped>
/* AUDIT_STANDARDS.md Section 5: Standard Printing */
@media print {
    body {
        background: white !important;
        color: black !important;
        font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif !important;
    }

    .print-section {
        display: block !important;
    }

    .print-footer {
        display: block !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }

    th,
    td {
        border: 1px solid #d1d5db !important;
        padding: 6px 4px !important;
        text-align: center !important;
    }

    th {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: bold !important;
    }
}
</style>
