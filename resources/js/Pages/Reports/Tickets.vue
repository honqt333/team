<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section -->
            <PageHeader
                :title="$t('reports.tickets_report.title')"
                :subtitle="$t('reports.tickets_report.subtitle')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-600"
                glowFrom="from-blue-500"
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
                            stroke-width="2.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
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
                            <span>{{ $t('reports.tickets_report.print_report') }}</span>
                        </button>

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold transition-all shadow-lg shadow-blue-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.tickets_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- UNIFIED FINANCIAL & OPERATIONAL SUMMARY STRIP -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700/80 shadow-md flex flex-wrap items-center justify-between gap-3 text-xs"
            >
                <div class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                    <span class="text-sm font-black">
                        {{ $t('reports.tickets_report.total_summary') }}:
                    </span>
                </div>

                <div
                    class="flex flex-wrap items-center gap-3 sm:gap-6 divide-x rtl:divide-x-reverse divide-gray-200 dark:divide-gray-700"
                >
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $t('reports.tickets_report.total_records') }}:
                        </span>
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ kpis.total_records }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">
                            {{ $t('reports.tickets_report.total_amount') }}:
                        </span>
                        <span class="font-mono font-bold text-blue-700 dark:text-blue-300">
                            {{ formatMoney(kpis.total_amount) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-gray-600 dark:text-gray-400 font-semibold">
                            {{ $t('reports.tickets_report.total_tax') }}:
                        </span>
                        <span class="font-mono font-bold text-gray-700 dark:text-gray-300">
                            {{ formatMoney(kpis.total_tax) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">
                            {{ $t('reports.tickets_report.parts_cost') }}:
                        </span>
                        <span class="font-mono font-bold text-amber-700 dark:text-amber-300">
                            {{ formatMoney(kpis.total_parts_cost) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                            {{ $t('reports.tickets_report.net_amount') }}:
                        </span>
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-300">
                            {{ formatMoney(kpis.net_amount) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-teal-600 dark:text-teal-400 font-semibold">
                            {{ $t('reports.tickets_report.amount_paid') }}:
                        </span>
                        <span class="font-mono font-bold text-teal-700 dark:text-teal-300">
                            {{ formatMoney(kpis.total_paid) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-rose-600 dark:text-rose-400 font-semibold">
                            {{ $t('reports.tickets_report.remaining_balance') }}:
                        </span>
                        <span class="font-mono font-bold text-rose-700 dark:text-rose-300">
                            {{ formatMoney(kpis.total_remaining) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-purple-600 dark:text-purple-400 font-semibold">
                            {{ $t('reports.tickets_report.bad_debt') }}:
                        </span>
                        <span
                            :class="[
                                'font-mono font-bold',
                                kpis.total_bad_debt > 0
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-gray-700 dark:text-gray-300',
                            ]"
                        >
                            {{ formatMoney(kpis.total_bad_debt) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Standard Official Print Section (Screen Hidden) -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.tickets_report.official_report')"
                    :subtitle="
                        filterForm.start_date || filterForm.end_date
                            ? `${filterForm.start_date || '—'} / ${filterForm.end_date || '—'}`
                            : ''
                    "
                >
                    <template #extra>
                        <div v-if="filterForm.date_type" class="text-xs text-gray-600">
                            {{ $t('reports.tickets_report.date_type') }}:
                            <span class="font-bold">
                                {{ getDateTypeLabel(filterForm.date_type) }}
                            </span>
                        </div>
                    </template>
                </StandardReportHeader>

                <!-- Print Financial Summary Box -->
                <div
                    class="grid grid-cols-6 gap-2 p-3 mb-4 bg-gray-100 rounded-lg text-center text-xs font-bold border border-gray-300"
                >
                    <div>
                        <div class="text-gray-500">
                            {{ $t('reports.tickets_report.total_records') }}
                        </div>
                        <div class="font-mono text-sm">{{ kpis.total_records }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">
                            {{ $t('reports.tickets_report.total_amount') }}
                        </div>
                        <div class="font-mono text-sm">{{ formatMoney(kpis.total_amount) }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">
                            {{ $t('reports.tickets_report.parts_cost') }}
                        </div>
                        <div class="font-mono text-sm">
                            {{ formatMoney(kpis.total_parts_cost) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">
                            {{ $t('reports.tickets_report.net_amount') }}
                        </div>
                        <div class="font-mono text-sm">{{ formatMoney(kpis.net_amount) }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">
                            {{ $t('reports.tickets_report.amount_paid') }}
                        </div>
                        <div class="font-mono text-sm">{{ formatMoney(kpis.total_paid) }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">
                            {{ $t('reports.tickets_report.remaining_balance') }}
                        </div>
                        <div class="font-mono text-sm text-rose-700">
                            {{ formatMoney(kpis.total_remaining) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADVANCED FILTERS BAR (Screen Only) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4 print:hidden"
            >
                <!-- Row 1: Date Range Presets -->
                <div
                    class="flex items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700/60"
                >
                    <!-- Presets Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-for="p in presets"
                            :key="p.id"
                            type="button"
                            @click="selectPreset(p.id)"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-200',
                                filterForm.range === p.id
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20'
                                    : 'bg-gray-100 dark:bg-gray-700/70 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                            ]"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                </div>

                <!-- Row 2: Date Type & Pickers & Direct Search -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                    <!-- Date Type Selection Dropdown (SearchableSelect) -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.date_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.date_type"
                            :options="dateTypeOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Date From -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.date_from') }}
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
                            {{ $t('reports.tickets_report.date_to') }}
                        </label>
                        <CustomDatePicker
                            v-model="filterForm.end_date"
                            @update:modelValue="onCustomDateInput"
                            placeholder="YYYY-MM-DD"
                            class="!rounded-2xl"
                        />
                    </div>

                    <!-- Direct Search Input -->
                    <div>
                        <label
                            class="block text-[11px] font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.direct_search') }}
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="filterForm.search"
                                @keyup.enter="applyFilters"
                                :placeholder="$t('reports.tickets_report.search_placeholder')"
                                class="w-full ps-9 pe-9 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                            />
                            <svg
                                class="w-4 h-4 text-gray-400 absolute start-3 top-2.5"
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
                                v-if="filterForm.search"
                                type="button"
                                @click="
                                    filterForm.search = '';
                                    applyFilters();
                                "
                                class="absolute end-2.5 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs font-bold"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Group By Selector & Detailed Filters -->
                <div
                    class="p-3 bg-blue-50/50 dark:bg-blue-950/20 rounded-2xl border border-blue-100 dark:border-blue-900/40 flex flex-col md:flex-row md:items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2">
                        <svg
                            class="w-5 h-5 text-blue-600 dark:text-blue-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                        <div>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">
                                {{ $t('reports.tickets_report.group_by') }}
                            </span>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                تجميع السجلات في بطاقات قابلة للطي بمجاميع مستقلة
                            </p>
                        </div>
                    </div>
                    <div class="w-full md:w-64">
                        <SearchableSelect
                            v-model="filterForm.group_by"
                            :options="groupByOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Row 4: SearchableSelect Filters Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.status"
                            :options="statusOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.payment_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.payment_status"
                            :options="paymentStatusOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Customer Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.customer') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_id"
                            :options="customerOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            :search-placeholder="$t('reports.tickets_report.search_customer')"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Customer Type Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.customer_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_type"
                            :options="customerTypeOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Vehicle Make Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.vehicle_make') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.make_id"
                            :options="makeOptions"
                            option-label="name"
                            option-value="id"
                            compact
                            :search-placeholder="$t('reports.tickets_report.search_make')"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Center Filter (if multiple) -->
                    <div v-if="centers && centers.length > 1">
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tickets_report.center') }}
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
                </div>

                <!-- Row 5: Overdue Checkbox & Action Buttons -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <label
                        class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 px-3 py-1.5 rounded-xl border border-amber-200 dark:border-amber-900/50"
                    >
                        <input
                            type="checkbox"
                            v-model="filterForm.is_overdue"
                            @change="applyFilters"
                            class="rounded text-amber-600 focus:ring-amber-500"
                        />
                        <span>⚠️ {{ $t('reports.tickets_report.overdue_only') }}</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 transition-colors"
                        >
                            {{ $t('reports.payments_report.reset_filters') }}
                        </button>
                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all"
                        >
                            {{ $t('reports.payments_report.apply_filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- GROUPED ACCORDION VIEW (عند تفعيل التجميع GroupBy) -->
            <div v-if="filterForm.group_by !== 'none' && groupedTickets" class="space-y-4">
                <div class="flex items-center justify-between px-1 print:hidden">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                        {{ groupedTickets.length }} {{ $t('reports.tickets_report.group_summary') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAll"
                            class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-650 text-gray-700 dark:text-gray-200 rounded-lg font-semibold"
                        >
                            {{ $t('reports.tickets_report.expand_all') }}
                        </button>
                        <button
                            type="button"
                            @click="collapseAll"
                            class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-650 text-gray-700 dark:text-gray-200 rounded-lg font-semibold"
                        >
                            {{ $t('reports.tickets_report.collapse_all') }}
                        </button>
                    </div>
                </div>

                <!-- Group Accordion Card -->
                <div
                    v-for="group in groupedTickets"
                    :key="group.group_key"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
                >
                    <!-- Accordion Header -->
                    <div
                        @click="toggleGroup(group.group_key)"
                        class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-3 bg-gray-50/50 dark:bg-gray-900/30"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs"
                            >
                                {{ group.total_records }}
                            </span>
                            <div>
                                <h3
                                    class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    {{ formatGroupTitle(group.group_name) }}
                                </h3>
                                <p class="text-[11px] text-gray-500">
                                    {{ group.total_records }}
                                    {{ $t('reports.tickets_report.total_records') }}
                                </p>
                            </div>
                        </div>

                        <!-- Group KPI Chips -->
                        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs">
                            <div
                                class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300"
                            >
                                <span class="text-gray-500 me-1">
                                    {{ $t('reports.tickets_report.total_amount') }}:
                                </span>
                                <span class="font-mono font-bold">
                                    {{ formatMoney(group.total_amount) }}
                                </span>
                            </div>

                            <div
                                class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300"
                            >
                                <span class="text-gray-500 me-1">
                                    {{ $t('reports.tickets_report.amount_paid') }}:
                                </span>
                                <span class="font-mono font-bold">
                                    {{ formatMoney(group.total_paid) }}
                                </span>
                            </div>

                            <div
                                class="px-2.5 py-1 rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300"
                            >
                                <span class="text-gray-500 me-1">
                                    {{ $t('reports.tickets_report.remaining_balance') }}:
                                </span>
                                <span class="font-mono font-bold">
                                    {{ formatMoney(group.total_remaining) }}
                                </span>
                            </div>

                            <div
                                v-if="group.total_bad_debt > 0"
                                class="px-2.5 py-1 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 font-bold"
                            >
                                <span class="text-gray-500 me-1">
                                    {{ $t('reports.tickets_report.bad_debt') }}:
                                </span>
                                <span class="font-mono">
                                    {{ formatMoney(group.total_bad_debt) }}
                                </span>
                            </div>

                            <svg
                                :class="[
                                    'w-5 h-5 text-gray-400 transition-transform duration-200',
                                    expandedGroups[group.group_key] ? 'rotate-180' : '',
                                ]"
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

                    <!-- Inner Table -->
                    <div
                        v-show="expandedGroups[group.group_key]"
                        class="p-0 border-t border-gray-100 dark:border-gray-700"
                    >
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-start">
                                <thead
                                    class="bg-gray-50 dark:bg-gray-850 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700"
                                >
                                    <tr>
                                        <th class="py-3 px-3 text-center">#</th>
                                        <th class="py-3 px-3 text-start">
                                            {{ $t('reports.tickets_report.work_order_no') }}
                                        </th>
                                        <th class="py-3 px-3 text-start">
                                            {{ getDateTypeLabel(filterForm.date_type) }}
                                        </th>
                                        <th class="py-3 px-3 text-start">
                                            {{ $t('reports.tickets_report.vehicle') }}
                                        </th>
                                        <th class="py-3 px-3 text-start">
                                            {{ $t('reports.tickets_report.customer_party') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.tickets_report.total_amount') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.tickets_report.parts_cost') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.tickets_report.net_amount') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.tickets_report.amount_paid') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.tickets_report.remaining_balance') }}
                                        </th>
                                        <th class="py-3 px-3 text-center print:hidden">
                                            {{ $t('reports.payments_report.actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                    <tr
                                        v-for="(tkt, idx) in group.tickets"
                                        :key="tkt.id"
                                        class="hover:bg-gray-50/70 dark:hover:bg-gray-750/50 transition-colors"
                                    >
                                        <td class="py-3 px-3 text-center text-gray-400 font-mono">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="py-3 px-3 font-mono font-bold">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a
                                                    :href="'/app/work-orders/' + tkt.id"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    {{ tkt.code }}
                                                </a>
                                                <span
                                                    :class="[
                                                        'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                        getStatusBadgeClass(tkt.status),
                                                    ]"
                                                >
                                                    {{ getStatusLabel(tkt.status) }}
                                                </span>
                                                <span
                                                    v-if="tkt.is_overdue"
                                                    class="text-amber-600 dark:text-amber-400"
                                                    title="متأخر عن موعد التسليم"
                                                >
                                                    ⏱️
                                                </span>
                                            </div>
                                            <div
                                                v-if="tkt.invoice"
                                                class="text-[10px] text-gray-400 mt-0.5"
                                            >
                                                {{ $t('reports.payments_report.invoice') }}:
                                                {{ tkt.invoice.invoice_number }}
                                            </div>
                                        </td>
                                        <td
                                            class="py-3 px-3 font-mono text-gray-600 dark:text-gray-300"
                                        >
                                            {{ getTicketDateValue(tkt) }}
                                        </td>
                                        <td class="py-3 px-3">
                                            <div v-if="tkt.vehicle" class="space-y-0.5">
                                                <div
                                                    class="font-bold text-gray-900 dark:text-gray-100"
                                                >
                                                    {{ tkt.vehicle.make_name }}
                                                    {{ tkt.vehicle.model_name }}
                                                    {{ tkt.vehicle.year }}
                                                </div>
                                                <div
                                                    class="font-mono text-xs text-blue-600 dark:text-blue-400"
                                                >
                                                    {{ tkt.vehicle.plate_number }}
                                                    <span
                                                        v-if="tkt.odometer"
                                                        class="text-gray-400 text-[10px] ms-1"
                                                    >
                                                        ({{ formatNumber(tkt.odometer) }}
                                                        {{ $t('reports.tickets_report.km') }})
                                                    </span>
                                                </div>
                                            </div>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div v-if="tkt.customer">
                                                <div
                                                    class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5"
                                                >
                                                    <span>{{ tkt.customer.name }}</span>
                                                    <span
                                                        :class="[
                                                            'px-1.5 py-0.5 rounded text-[9px] font-semibold',
                                                            getCustomerTypeBadgeClass(
                                                                tkt.customer.type
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            getCustomerTypeLabel(tkt.customer.type)
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    v-if="tkt.customer.phone"
                                                    class="font-mono text-[10px] text-gray-500"
                                                >
                                                    {{ tkt.customer.phone }}
                                                </div>
                                            </div>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-bold text-gray-900 dark:text-gray-100"
                                        >
                                            {{ formatMoney(tkt.total_incl_tax) }}
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono text-amber-600 dark:text-amber-400"
                                        >
                                            {{ formatMoney(tkt.parts_cost) }}
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ formatMoney(tkt.net_amount) }}
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-bold text-teal-600 dark:text-teal-400"
                                        >
                                            {{ formatMoney(tkt.total_paid) }}
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-bold text-rose-600 dark:text-rose-400"
                                        >
                                            {{ formatMoney(tkt.balance) }}
                                        </td>
                                        <td class="py-3 px-3 text-center print:hidden">
                                            <a
                                                :href="'/app/work-orders/' + tkt.id"
                                                class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 text-[11px] font-bold inline-block"
                                            >
                                                {{ $t('reports.tickets_report.view_work_order') }}
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FLAT TABLE VIEW (بدون تجميع GroupBy) -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead
                            class="bg-gray-50 dark:bg-gray-850 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700"
                        >
                            <tr>
                                <th class="py-3 px-3 text-center">#</th>
                                <th
                                    v-if="centers && centers.length > 1"
                                    class="py-3 px-3 text-start"
                                >
                                    {{ $t('reports.tickets_report.center') }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.tickets_report.work_order_no') }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ getDateTypeLabel(filterForm.date_type) }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.tickets_report.vehicle') }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.tickets_report.customer_party') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.tickets_report.total_amount') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.tickets_report.parts_cost') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.tickets_report.net_amount') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.tickets_report.amount_paid') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.tickets_report.remaining_balance') }}
                                </th>
                                <th class="py-3 px-3 text-center print:hidden">
                                    {{ $t('reports.payments_report.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr v-if="!tickets?.data || tickets.data.length === 0">
                                <td
                                    :colspan="centers && centers.length > 1 ? 12 : 11"
                                    class="py-12 text-center"
                                >
                                    <div class="max-w-xs mx-auto text-center space-y-2">
                                        <div
                                            class="w-12 h-12 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400"
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
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                        </div>
                                        <p
                                            class="text-sm font-bold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ $t('reports.tickets_report.no_records') }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $t('reports.tickets_report.no_records_hint') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="(tkt, idx) in tickets?.data"
                                :key="tkt.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-750/50 transition-colors"
                            >
                                <td class="py-3 px-3 text-center text-gray-400 font-mono">
                                    {{ (tickets.current_page - 1) * tickets.per_page + idx + 1 }}
                                </td>
                                <td
                                    v-if="centers && centers.length > 1"
                                    class="py-3 px-3 text-gray-600 dark:text-gray-300"
                                >
                                    {{ tkt.center?.name || '—' }}
                                </td>
                                <td class="py-3 px-3 font-mono font-bold">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <a
                                            :href="'/app/work-orders/' + tkt.id"
                                            class="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            {{ tkt.code }}
                                        </a>
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                getStatusBadgeClass(tkt.status),
                                            ]"
                                        >
                                            {{ getStatusLabel(tkt.status) }}
                                        </span>
                                        <span
                                            v-if="tkt.is_overdue"
                                            class="text-amber-600 dark:text-amber-400"
                                            title="متأخر عن موعد التسليم"
                                        >
                                            ⏱️
                                        </span>
                                    </div>
                                    <div
                                        v-if="tkt.invoice"
                                        class="text-[10px] text-gray-400 mt-0.5"
                                    >
                                        {{ $t('reports.payments_report.invoice') }}:
                                        {{ tkt.invoice.invoice_number }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 font-mono text-gray-600 dark:text-gray-300">
                                    {{ getTicketDateValue(tkt) }}
                                </td>
                                <td class="py-3 px-3">
                                    <div v-if="tkt.vehicle" class="space-y-0.5">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ tkt.vehicle.make_name }} {{ tkt.vehicle.model_name }}
                                            {{ tkt.vehicle.year }}
                                        </div>
                                        <div
                                            class="font-mono text-xs text-blue-600 dark:text-blue-400"
                                        >
                                            {{ tkt.vehicle.plate_number }}
                                            <span
                                                v-if="tkt.odometer"
                                                class="text-gray-400 text-[10px] ms-1"
                                            >
                                                ({{ formatNumber(tkt.odometer) }}
                                                {{ $t('reports.tickets_report.km') }})
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="py-3 px-3">
                                    <div v-if="tkt.customer">
                                        <div
                                            class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5"
                                        >
                                            <span>{{ tkt.customer.name }}</span>
                                            <span
                                                :class="[
                                                    'px-1.5 py-0.5 rounded text-[9px] font-semibold',
                                                    getCustomerTypeBadgeClass(tkt.customer.type),
                                                ]"
                                            >
                                                {{ getCustomerTypeLabel(tkt.customer.type) }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="tkt.customer.phone"
                                            class="font-mono text-[10px] text-gray-500"
                                        >
                                            {{ tkt.customer.phone }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-gray-900 dark:text-gray-100"
                                >
                                    {{ formatMoney(tkt.total_incl_tax) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono text-amber-600 dark:text-amber-400"
                                >
                                    {{ formatMoney(tkt.parts_cost) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatMoney(tkt.net_amount) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-teal-600 dark:text-teal-400"
                                >
                                    {{ formatMoney(tkt.total_paid) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-rose-600 dark:text-rose-400"
                                >
                                    {{ formatMoney(tkt.balance) }}
                                </td>
                                <td class="py-3 px-3 text-center print:hidden">
                                    <a
                                        :href="'/app/work-orders/' + tkt.id"
                                        class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 text-[11px] font-bold inline-block"
                                    >
                                        {{ $t('reports.tickets_report.view_work_order') }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="tickets?.links && tickets.links.length > 3"
                    class="p-4 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs print:hidden"
                >
                    <div class="text-gray-500 dark:text-gray-400">
                        {{ $t('reports.payments_report.showing') }}
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ tickets.from || 0 }}
                        </span>
                        {{ $t('reports.payments_report.to') }}
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ tickets.to || 0 }}
                        </span>
                        {{ $t('reports.payments_report.of') }}
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ tickets.total || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in tickets.links" :key="i">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-bold transition-all',
                                    link.active
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200',
                                ]"
                                v-html="link.label"
                            />
                            <span v-else class="px-2 py-1 text-gray-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Unified Print Footer (no signatures/stamps) -->
            <StandardReportFooter class="hidden print:block pt-8 mt-8 border-t-2 border-gray-300" />
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const { t, te } = useI18n();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    kpis: {
        type: Object,
        default: () => ({
            total_records: 0,
            total_amount: 0,
            total_tax: 0,
            total_parts_cost: 0,
            net_amount: 0,
            total_paid: 0,
            total_remaining: 0,
            total_discount: 0,
            total_bad_debt: 0,
        }),
    },
    statusBreakdown: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
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
            tax_number: '',
            commercial_register: '',
            address: '',
            phone: '',
            center_name: '',
        }),
    },
    tickets: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    groupedTickets: {
        type: Array,
        default: () => null,
    },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    date_type: props.filters.date_type || 'entry_date',
    status: props.filters.status || 'all',
    payment_status: props.filters.payment_status || 'all',
    customer_id: props.filters.customer_id || 'all',
    customer_type: props.filters.customer_type || 'all',
    make_id: props.filters.make_id || 'all',
    center_id: props.filters.center_id || 'all',
    is_overdue: Boolean(props.filters.is_overdue),
    group_by: props.filters.group_by || 'none',
    search: props.filters.search || '',
});

const expandedGroups = reactive({});

watch(
    () => props.groupedTickets,
    (val) => {
        if (val) {
            val.forEach((g) => {
                if (expandedGroups[g.group_key] === undefined) {
                    expandedGroups[g.group_key] = true;
                }
            });
        }
    },
    { immediate: true }
);

const toggleGroup = (key) => {
    expandedGroups[key] = !expandedGroups[key];
};

const expandAll = () => {
    if (props.groupedTickets) {
        props.groupedTickets.forEach((g) => {
            expandedGroups[g.group_key] = true;
        });
    }
};

const collapseAll = () => {
    if (props.groupedTickets) {
        props.groupedTickets.forEach((g) => {
            expandedGroups[g.group_key] = false;
        });
    }
};

const presets = computed(() => [
    { id: 'today', label: t('reports.tickets_report.today') },
    { id: 'yesterday', label: t('reports.tickets_report.yesterday') },
    { id: 'this_week', label: t('reports.tickets_report.this_week') },
    { id: 'this_month', label: t('reports.tickets_report.this_month') },
    { id: 'this_year', label: t('reports.tickets_report.this_year') },
    { id: 'all', label: t('reports.tickets_report.all_time') },
]);

const selectPreset = (presetId) => {
    filterForm.range = presetId;
    if (presetId !== 'custom') {
        filterForm.start_date = '';
        filterForm.end_date = '';
    }
    applyFilters();
};

const onCustomDateInput = () => {
    filterForm.range = 'custom';
    applyFilters();
};

const applyFilters = () => {
    router.get(
        '/app/reports/tickets',
        {
            range: filterForm.range,
            start_date: filterForm.start_date || undefined,
            end_date: filterForm.end_date || undefined,
            date_type: filterForm.date_type || undefined,
            status: filterForm.status !== 'all' ? filterForm.status : undefined,
            payment_status:
                filterForm.payment_status !== 'all' ? filterForm.payment_status : undefined,
            customer_id: filterForm.customer_id !== 'all' ? filterForm.customer_id : undefined,
            customer_type:
                filterForm.customer_type !== 'all' ? filterForm.customer_type : undefined,
            make_id: filterForm.make_id !== 'all' ? filterForm.make_id : undefined,
            center_id: filterForm.center_id || 'all',
            is_overdue: filterForm.is_overdue ? 1 : undefined,
            group_by: filterForm.group_by !== 'none' ? filterForm.group_by : undefined,
            search: filterForm.search ? filterForm.search.trim() : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const resetFilters = () => {
    filterForm.range = 'this_month';
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.date_type = 'entry_date';
    filterForm.status = 'all';
    filterForm.payment_status = 'all';
    filterForm.customer_id = 'all';
    filterForm.customer_type = 'all';
    filterForm.make_id = 'all';
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
    if (filterForm.status && filterForm.status !== 'all')
        params.append('status', filterForm.status);
    if (filterForm.payment_status && filterForm.payment_status !== 'all')
        params.append('payment_status', filterForm.payment_status);
    if (filterForm.customer_id && filterForm.customer_id !== 'all')
        params.append('customer_id', filterForm.customer_id);
    if (filterForm.customer_type && filterForm.customer_type !== 'all')
        params.append('customer_type', filterForm.customer_type);
    if (filterForm.make_id && filterForm.make_id !== 'all')
        params.append('make_id', filterForm.make_id);
    if (filterForm.center_id && filterForm.center_id !== 'all')
        params.append('center_id', filterForm.center_id);
    if (filterForm.is_overdue) params.append('is_overdue', '1');
    if (filterForm.search) params.append('search', filterForm.search.trim());
    return `/app/reports/tickets/export?${params.toString()}`;
});

const groupByOptions = computed(() => [
    { id: 'none', name: t('reports.tickets_report.group_none') },
    { id: 'center', name: t('reports.tickets_report.group_center') },
    { id: 'status', name: t('reports.tickets_report.group_status') },
    { id: 'customer_type', name: t('reports.tickets_report.group_customer_type') },
    { id: 'make', name: t('reports.tickets_report.group_make') },
]);

const dateTypeOptions = computed(() => [
    { id: 'entry_date', name: t('reports.tickets_report.date_entry') },
    { id: 'opened_at', name: t('reports.tickets_report.date_opened') },
    { id: 'expected_end_date', name: t('reports.tickets_report.date_due') },
    { id: 'closed_at', name: t('reports.tickets_report.date_closed') },
    { id: 'created_at', name: t('reports.tickets_report.date_created') },
]);

const statusOptions = computed(() => [
    { id: 'all', name: t('reports.tickets_report.all_statuses') },
    { id: 'draft', name: getStatusLabel('draft') },
    { id: 'open', name: getStatusLabel('open') },
    { id: 'in_progress', name: getStatusLabel('in_progress') },
    { id: 'ready_for_qc', name: getStatusLabel('ready_for_qc') },
    { id: 'on_hold', name: getStatusLabel('on_hold') },
    { id: 'done', name: getStatusLabel('done') },
    { id: 'cancelled', name: getStatusLabel('cancelled') },
]);

const paymentStatusOptions = computed(() => [
    { id: 'all', name: t('reports.tickets_report.all_payment_statuses') },
    { id: 'paid', name: t('reports.tickets_report.paid') },
    { id: 'partial', name: t('reports.tickets_report.partial') },
    { id: 'unpaid', name: t('reports.tickets_report.unpaid') },
]);

const customerOptions = computed(() => [
    { id: 'all', name: t('reports.tickets_report.all_customers') },
    ...props.customers.map((c) => ({
        id: String(c.id),
        name: c.phone ? `${c.name} (${c.phone})` : c.name,
    })),
]);

const customerTypeOptions = computed(() => [
    { id: 'all', name: t('reports.tickets_report.all_customer_types') },
    { id: 'individual', name: t('reports.tickets_report.type_individual') },
    { id: 'company', name: t('reports.tickets_report.type_company') },
    { id: 'government', name: t('reports.tickets_report.type_government') },
    { id: 'vip', name: t('reports.tickets_report.type_vip') },
]);

const makeOptions = computed(() => [
    { id: 'all', name: t('reports.tickets_report.all_makes') },
    ...props.makes.map((m) => ({
        id: String(m.id),
        name: m.name_ar || m.name_en,
    })),
]);

const centerOptions = computed(() => [
    { id: 'all', name: t('reports.tickets_report.all_centers') },
    ...props.centers.map((c) => ({
        id: String(c.id),
        name: c.name,
    })),
]);

const printDate = computed(() => {
    const d = new Date();
    return d.toISOString().slice(0, 10) + ' ' + d.toTimeString().slice(0, 5);
});

const handlePrint = () => {
    window.print();
};

const formatMoney = (val) => {
    const num = parseFloat(val) || 0;
    return `${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${t('reports.tickets_report.currency')}`;
};

const formatNumber = (val) => {
    const num = parseFloat(val) || 0;
    return num.toLocaleString('en-US');
};

const getDateTypeLabel = (dateType) => {
    switch (dateType) {
        case 'opened_at':
            return t('reports.tickets_report.date_opened');
        case 'expected_end_date':
            return t('reports.tickets_report.date_due');
        case 'closed_at':
            return t('reports.tickets_report.date_closed');
        case 'created_at':
            return t('reports.tickets_report.date_created');
        default:
            return t('reports.tickets_report.date_entry');
    }
};

const getTicketDateValue = (tkt) => {
    switch (filterForm.date_type) {
        case 'opened_at':
            return tkt.opened_at || '—';
        case 'expected_end_date':
            return tkt.expected_end_date || '—';
        case 'closed_at':
            return tkt.closed_at || '—';
        case 'created_at':
            return tkt.created_at || '—';
        default:
            return tkt.entry_date || '—';
    }
};

const getStatusLabel = (st) => {
    if (!st) return '—';
    if (te(`work_orders.work_order_statuses.${st}`)) {
        return t(`work_orders.work_order_statuses.${st}`);
    }
    if (te(`work_orders.status.${st}`)) {
        return t(`work_orders.status.${st}`);
    }
    const fallback = {
        draft: 'مسودة',
        open: 'مفتوح',
        in_progress: 'قيد التنفيذ',
        ready_for_qc: 'بانتظار الفحص',
        on_hold: 'متوقف مؤقتاً',
        done: 'جاهز للتسليم',
        completed: 'مكتمل',
        cancelled: 'ملغى',
    };
    return fallback[st] || st;
};

const getStatusBadgeClass = (st) => {
    switch (st) {
        case 'done':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'ready_for_qc':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
        case 'on_hold':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
        case 'open':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getCustomerTypeLabel = (type) => {
    switch (type) {
        case 'company':
            return t('reports.tickets_report.type_company');
        case 'government':
            return t('reports.tickets_report.type_government');
        case 'vip':
            return t('reports.tickets_report.type_vip');
        default:
            return t('reports.tickets_report.type_individual');
    }
};

const getCustomerTypeBadgeClass = (type) => {
    switch (type) {
        case 'company':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
        case 'government':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'vip':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }
};

const formatGroupTitle = (val) => {
    if (filterForm.group_by === 'status') {
        return getStatusLabel(val);
    }
    if (filterForm.group_by === 'customer_type') {
        return getCustomerTypeLabel(val);
    }
    return val || '—';
};
</script>

<style scoped>
@media print {
    body {
        background: white !important;
        color: black !important;
        font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif !important;
    }

    .print-section {
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
