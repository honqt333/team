<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.employees_report.title')"
                :subtitle="$t('reports.employees_report.subtitle')"
                gradientFrom="from-orange-600"
                gradientTo="to-amber-700"
                glowFrom="from-orange-500"
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
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
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
                            <span>{{ $t('reports.employees_report.print_report') }}</span>
                        </button>

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white text-xs font-bold transition-all shadow-lg shadow-orange-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.employees_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.employees_report.official_report')"
                    :subtitle="
                        filters.start_date || filters.end_date
                            ? `${$t('reports.employees_report.date_range')}: ${filters.start_date || 'All'} — ${filters.end_date || 'Now'}`
                            : ''
                    "
                >
                    <template #extra>
                        <div class="text-xs text-gray-600">
                            {{ $t('reports.employees_report.total_records') }}:
                            <span class="font-mono font-bold text-black">
                                {{ kpis.total_records }}
                            </span>
                        </div>
                    </template>
                </StandardReportHeader>

                <!-- Print KPI Summary Banner -->
                <div
                    class="grid grid-cols-5 gap-2 mb-4 p-3 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.employees_report.total_records') }}:
                        </span>
                        <strong class="font-mono text-xs">{{ kpis.total_records }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.employees_report.total_service_hours') }}:
                        </span>
                        <strong class="font-mono text-xs">
                            {{ formatNumber(kpis.total_service_hours) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.employees_report.total_avg_percent_contribution') }}:
                        </span>
                        <strong class="font-mono text-xs">
                            {{ formatMoney(kpis.total_avg_percent_contribution) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{
                                $t(
                                    'reports.employees_report.total_avg_contribution_by_service_hours'
                                )
                            }}:
                        </span>
                        <strong class="font-mono text-xs">
                            {{ formatMoney(kpis.total_avg_contribution_by_service_hours) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.employees_report.total_weighted_avg_contribution') }}:
                        </span>
                        <strong class="font-mono text-xs">
                            {{ formatMoney(kpis.total_weighted_avg_contribution) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards (Wrshah chunk 72 parity: 7 summary cards) -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3.5 print:hidden">
                <!-- Total Records -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.employees_report.total_records') }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400"
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
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-gray-900 dark:text-white font-mono">
                        {{ formatNumber(kpis.total_records) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.record') }}
                    </div>
                </div>

                <!-- Total Service Hours -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.employees_report.total_service_hours') }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
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
                    <div class="text-xl font-black text-blue-600 dark:text-blue-400 font-mono">
                        {{ formatNumber(kpis.total_service_hours) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.service_hours') }}
                    </div>
                </div>

                <!-- Total Avg Percent Contribution -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.employees_report.total_avg_percent_contribution') }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400"
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
                    <div
                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono"
                    >
                        {{ formatMoney(kpis.total_avg_percent_contribution) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.currency') }}
                    </div>
                </div>

                <!-- Avg Contribution / Hour -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{
                                $t(
                                    'reports.employees_report.total_avg_contribution_by_service_hours'
                                )
                            }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400"
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
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                        {{ formatMoney(kpis.total_avg_contribution_by_service_hours) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.currency') }} / ساعة
                    </div>
                </div>

                <!-- Weighted Avg Contribution -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.employees_report.total_weighted_avg_contribution') }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400"
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
                                    d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-purple-600 dark:text-purple-400 font-mono">
                        {{ formatMoney(kpis.total_weighted_avg_contribution) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.currency') }}
                    </div>
                </div>

                <!-- Total Service Price -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.employees_report.total_service_price') }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400"
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
                    <div class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono">
                        {{ formatMoney(kpis.total_service_price) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.currency') }}
                    </div>
                </div>

                <!-- Total Service Cost (Net) -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-200/80 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.employees_report.total_service_cost') }}
                        </span>
                        <div
                            class="p-2 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400"
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
                    <div class="text-xl font-black text-teal-600 dark:text-teal-400 font-mono">
                        {{ formatMoney(kpis.total_service_cost) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        الصافي بعد الخصم
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div
                class="rounded-2xl bg-white dark:bg-gray-800 p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm print:hidden"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Date Range Selection -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.date_range') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.range"
                            :options="rangeOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Custom Date From -->
                    <div v-if="localFilters.range === 'custom'">
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.date_from') }}
                        </label>
                        <CustomDatePicker
                            v-model="localFilters.start_date"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Custom Date To -->
                    <div v-if="localFilters.range === 'custom'">
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.date_to') }}
                        </label>
                        <CustomDatePicker v-model="localFilters.end_date" @change="applyFilters" />
                    </div>

                    <!-- Date Search Type (service_date vs invoice_date) -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.date_type') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.date_type"
                            :options="dateTypeOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Branch / Center -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.center_id"
                            :options="centerOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Employee -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.employee') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.employee_id"
                            :options="employeeOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Job Title -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.job_title') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.job_title_id"
                            :options="jobTitleOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Department -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.section') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.department_id"
                            :options="departmentOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Service -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.service') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.service_id"
                            :options="serviceOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Service Status -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.status') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.service_status"
                            :options="serviceStatusOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Vehicle Make -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.all_makes') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.make_id"
                            :options="makeOptions"
                            @change="handleMakeChange"
                        />
                    </div>

                    <!-- Vehicle Model -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.all_models') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.model_id"
                            :options="filteredModelOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Work Order Code -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.ticket_no') }}
                        </label>
                        <input
                            v-model="localFilters.ticket_no"
                            type="text"
                            placeholder="WO-000001"
                            @keyup.enter="applyFilters"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                        />
                    </div>

                    <!-- Group By -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.group_by"
                            :options="groupByOptions"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Direct Search -->
                    <div class="md:col-span-2">
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.employees_report.direct_search') }}
                        </label>
                        <div class="relative">
                            <input
                                v-model="localFilters.search"
                                type="text"
                                :placeholder="$t('reports.employees_report.search_placeholder')"
                                @keyup.enter="applyFilters"
                                class="w-full pr-10 pl-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                            />
                            <div
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400"
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
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toggles & Action Buttons Row -->
                <div
                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 flex flex-wrap items-center justify-between gap-3"
                >
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Overdue Only Toggle -->
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="localFilters.is_overdue"
                                @change="applyFilters"
                                class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500"
                            />
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $t('reports.employees_report.overdue') }}
                            </span>
                        </label>

                        <!-- Deduct Ticket Discount Toggle -->
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="localFilters.deduct_ticket_discount"
                                @change="applyFilters"
                                class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500"
                            />
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $t('reports.employees_report.deduct_ticket_discount') }}
                            </span>
                        </label>

                        <!-- Remove Inactive Employees Toggle -->
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="localFilters.remove_inactive_employees"
                                @change="applyFilters"
                                class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500"
                            />
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $t('reports.employees_report.remove_inactive_employees') }}
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Reset Filters Button -->
                        <button
                            type="button"
                            @click="resetFilters"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold transition-all"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-gray-500"
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
                            <span>{{ $t('reports.employees_report.reset_filters') }}</span>
                        </button>

                        <!-- Apply Filters Button -->
                        <button
                            type="button"
                            @click="applyFilters"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold transition-all shadow-md shadow-orange-500/20"
                        >
                            <span>{{ $t('reports.employees_report.apply_filters') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Group View Expand/Collapse Control Bar -->
            <div
                v-if="filters.group_by !== 'none' && groupedEmployees && groupedEmployees.length"
                class="flex items-center justify-between bg-orange-50/50 dark:bg-orange-950/20 px-4 py-2.5 rounded-xl border border-orange-200/50 dark:border-orange-800/40 print:hidden"
            >
                <div class="text-xs font-bold text-orange-900 dark:text-orange-200">
                    {{ $t('reports.employees_report.group_summary') }}:
                    <span class="font-mono text-orange-600 dark:text-orange-400 font-black">
                        {{ groupedEmployees.length }}
                    </span>
                    مجموعات
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="expandAllGroups"
                        class="px-3 py-1 text-xs font-bold text-orange-700 dark:text-orange-300 hover:bg-orange-100/60 dark:hover:bg-orange-900/40 rounded-lg transition-colors"
                    >
                        {{ $t('reports.employees_report.expand_all') }}
                    </button>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <button
                        type="button"
                        @click="collapseAllGroups"
                        class="px-3 py-1 text-xs font-bold text-orange-700 dark:text-orange-300 hover:bg-orange-100/60 dark:hover:bg-orange-900/40 rounded-lg transition-colors"
                    >
                        {{ $t('reports.employees_report.collapse_all') }}
                    </button>
                </div>
            </div>

            <!-- Grouped Data Display (Accordion) -->
            <div
                v-if="filters.group_by !== 'none' && groupedEmployees && groupedEmployees.length"
                class="space-y-4"
            >
                <div
                    v-for="group in groupedEmployees"
                    :key="group.group_id"
                    class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm overflow-hidden"
                >
                    <!-- Group Header Accordion Button -->
                    <button
                        type="button"
                        @click="toggleGroup(group.group_id)"
                        class="w-full flex flex-wrap items-center justify-between p-4 bg-gray-50/70 dark:bg-gray-700/40 hover:bg-gray-100/70 dark:hover:bg-gray-700/70 transition-colors text-right"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-sm text-gray-900 dark:text-white">
                                {{ group.group_name }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 font-mono"
                            >
                                {{ group.summary.total_records }} عمليات
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">ساعات العمل:</span>
                                <span class="font-mono text-blue-600 dark:text-blue-400 font-bold">
                                    {{ formatNumber(group.summary.total_service_hours) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">صافي التكلفة:</span>
                                <span class="font-mono text-gray-900 dark:text-white font-bold">
                                    {{ formatMoney(group.summary.total_service_cost) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    متوسط المساهمة:
                                </span>
                                <span
                                    class="font-mono text-emerald-600 dark:text-emerald-400 font-bold"
                                >
                                    {{ formatMoney(group.summary.total_avg_percent_contribution) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">المساهمة/ساعة:</span>
                                <span
                                    class="font-mono text-indigo-600 dark:text-indigo-400 font-bold"
                                >
                                    {{
                                        formatMoney(
                                            group.summary.total_avg_contribution_by_service_hours
                                        )
                                    }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    المساهمة المرجحة:
                                </span>
                                <span
                                    class="font-mono text-purple-600 dark:text-purple-400 font-bold"
                                >
                                    {{ formatMoney(group.summary.total_weighted_avg_contribution) }}
                                </span>
                            </div>
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': openGroups.has(group.group_id) }"
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
                    </button>

                    <!-- Group Table Container -->
                    <div
                        v-show="openGroups.has(group.group_id)"
                        class="overflow-x-auto border-t border-gray-200 dark:border-gray-700"
                    >
                        <table class="w-full text-right text-xs">
                            <thead
                                class="bg-gray-100/60 dark:bg-gray-750 text-gray-600 dark:text-gray-300 font-bold uppercase tracking-wider text-[11px]"
                            >
                                <tr>
                                    <th class="py-3 px-3.5 text-center">#</th>
                                    <th class="py-3 px-3.5">
                                        {{ $t('reports.employees_report.center') }}
                                    </th>
                                    <th class="py-3 px-3.5">
                                        {{ $t('reports.employees_report.employee') }}
                                    </th>
                                    <th class="py-3 px-3.5">
                                        {{ $t('reports.employees_report.service') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-center">
                                        {{ $t('reports.employees_report.service_date') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-center">
                                        {{ $t('reports.employees_report.invoice_date') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-left">
                                        {{ $t('reports.employees_report.service_price') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-left">
                                        {{ $t('reports.employees_report.service_discount') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-left">
                                        {{ $t('reports.employees_report.service_cost') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-center">
                                        {{ $t('reports.employees_report.service_contribution') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-center">
                                        {{ $t('reports.employees_report.service_hours') }}
                                    </th>
                                    <th class="py-3 px-3.5 text-center">
                                        {{ $t('reports.employees_report.working_technicians') }}
                                    </th>
                                    <th
                                        class="py-3 px-3.5 text-left font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{
                                            $t('reports.employees_report.avg_percent_contribution')
                                        }}
                                    </th>
                                    <th
                                        class="py-3 px-3.5 text-left font-black text-indigo-600 dark:text-indigo-400"
                                    >
                                        {{
                                            $t(
                                                'reports.employees_report.avg_contribution_by_service_hours'
                                            )
                                        }}
                                    </th>
                                    <th
                                        class="py-3 px-3.5 text-left font-black text-purple-600 dark:text-purple-400"
                                    >
                                        {{
                                            $t('reports.employees_report.weighted_avg_contribution')
                                        }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <tr
                                    v-for="(row, rIdx) in group.items"
                                    :key="row.id"
                                    class="hover:bg-gray-50/60 dark:hover:bg-gray-750/50 transition-colors"
                                >
                                    <td
                                        class="py-3 px-3.5 text-center font-mono text-gray-500 font-bold"
                                    >
                                        {{ rIdx + 1 }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ row.center_name }}
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ row.employee_name }}
                                        </div>
                                        <div
                                            class="text-[11px] text-gray-500 dark:text-gray-400 font-medium"
                                        >
                                            {{ row.job_title_name }}
                                        </div>
                                        <div
                                            class="text-[10px] text-orange-600 dark:text-orange-400 font-bold"
                                        >
                                            ({{ row.technician_service_role_name }})
                                        </div>
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                :class="getStatusBadgeClasses(row.service_status)"
                                            >
                                                {{ getStatusLabel(row.service_status) }}
                                            </span>
                                            <a
                                                :href="getWorkOrderUrl(row.work_order_id)"
                                                class="font-mono font-bold text-orange-600 dark:text-orange-400 hover:underline"
                                            >
                                                {{ row.ticket_no }}
                                            </a>
                                            <span
                                                v-if="row.is_overdue"
                                                class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold"
                                                title="متأخرة"
                                            >
                                                متأخرة
                                            </span>
                                        </div>
                                        <div
                                            class="font-semibold text-gray-900 dark:text-white mt-1"
                                        >
                                            {{ row.service_name }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ row.section_name }}
                                        </div>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-[11px] text-gray-600 dark:text-gray-300"
                                        >
                                            <span>{{ row.vehicle.display_name }}</span>
                                            <SaudiPlateDisplay
                                                v-if="row.vehicle.plate_number"
                                                :plate="row.vehicle.plate_number"
                                                size="xs"
                                            />
                                        </div>
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-center font-mono text-gray-700 dark:text-gray-300"
                                    >
                                        {{ row.service_date || '-' }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-center font-mono text-gray-700 dark:text-gray-300"
                                    >
                                        <div>{{ row.invoice_date || '-' }}</div>
                                        <div
                                            v-if="row.invoice_number"
                                            class="text-[10px] text-gray-400"
                                        >
                                            {{ row.invoice_number }}
                                        </div>
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-left font-mono font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ formatMoney(row.service_price) }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-left font-mono text-rose-600 dark:text-rose-400"
                                    >
                                        {{ formatMoney(row.service_discount) }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-left font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ formatMoney(row.service_cost) }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-center font-mono font-bold text-orange-600 dark:text-orange-400"
                                    >
                                        {{ formatNumber(row.service_contribution) }}%
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-center font-mono font-bold text-blue-600 dark:text-blue-400"
                                    >
                                        {{ formatNumber(row.service_hours) }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-center font-mono font-bold text-gray-700 dark:text-gray-300"
                                    >
                                        {{ row.working_technicians }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-left font-mono font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/20"
                                    >
                                        {{ formatMoney(row.avg_percent_contribution) }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-left font-mono font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/20"
                                    >
                                        {{ formatMoney(row.avg_contribution_by_service_hours) }}
                                    </td>
                                    <td
                                        class="py-3 px-3.5 text-left font-mono font-black text-purple-600 dark:text-purple-400 bg-purple-50/30 dark:bg-purple-950/20"
                                    >
                                        {{ formatMoney(row.weighted_avg_contribution) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Flat Table View (Direct List, group_by === 'none') -->
            <div
                v-else
                class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead
                            class="bg-gray-100/70 dark:bg-gray-750 text-gray-600 dark:text-gray-300 font-bold uppercase tracking-wider text-[11px] border-b border-gray-200 dark:border-gray-700"
                        >
                            <tr>
                                <th class="py-3 px-3.5 text-center">#</th>
                                <th class="py-3 px-3.5">
                                    {{ $t('reports.employees_report.center') }}
                                </th>
                                <th class="py-3 px-3.5">
                                    {{ $t('reports.employees_report.employee') }}
                                </th>
                                <th class="py-3 px-3.5">
                                    {{ $t('reports.employees_report.service') }}
                                </th>
                                <th class="py-3 px-3.5 text-center">
                                    {{ $t('reports.employees_report.service_date') }}
                                </th>
                                <th class="py-3 px-3.5 text-center">
                                    {{ $t('reports.employees_report.invoice_date') }}
                                </th>
                                <th class="py-3 px-3.5 text-left">
                                    {{ $t('reports.employees_report.service_price') }}
                                </th>
                                <th class="py-3 px-3.5 text-left">
                                    {{ $t('reports.employees_report.service_discount') }}
                                </th>
                                <th class="py-3 px-3.5 text-left">
                                    {{ $t('reports.employees_report.service_cost') }}
                                </th>
                                <th class="py-3 px-3.5 text-center">
                                    {{ $t('reports.employees_report.service_contribution') }}
                                </th>
                                <th class="py-3 px-3.5 text-center">
                                    {{ $t('reports.employees_report.service_hours') }}
                                </th>
                                <th class="py-3 px-3.5 text-center">
                                    {{ $t('reports.employees_report.working_technicians') }}
                                </th>
                                <th
                                    class="py-3 px-3.5 text-left font-black text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ $t('reports.employees_report.avg_percent_contribution') }}
                                </th>
                                <th
                                    class="py-3 px-3.5 text-left font-black text-indigo-600 dark:text-indigo-400"
                                >
                                    {{
                                        $t(
                                            'reports.employees_report.avg_contribution_by_service_hours'
                                        )
                                    }}
                                </th>
                                <th
                                    class="py-3 px-3.5 text-left font-black text-purple-600 dark:text-purple-400"
                                >
                                    {{ $t('reports.employees_report.weighted_avg_contribution') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr v-if="!paginatedItems.length" class="text-center py-12">
                                <td
                                    colspan="15"
                                    class="py-12 text-center text-gray-500 dark:text-gray-400"
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
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                        <span class="font-bold text-sm">
                                            {{ $t('reports.employees_report.no_records') }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $t('reports.employees_report.no_records_hint') }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="(row, idx) in paginatedItems"
                                :key="row.id"
                                class="hover:bg-gray-50/60 dark:hover:bg-gray-750/50 transition-colors"
                            >
                                <td
                                    class="py-3 px-3.5 text-center font-mono text-gray-500 font-bold"
                                >
                                    {{ (employees?.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-3.5 font-medium text-gray-900 dark:text-white">
                                    {{ row.center_name }}
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ row.employee_name }}
                                    </div>
                                    <div
                                        class="text-[11px] text-gray-500 dark:text-gray-400 font-medium"
                                    >
                                        {{ row.job_title_name }}
                                    </div>
                                    <div
                                        class="text-[10px] text-orange-600 dark:text-orange-400 font-bold"
                                    >
                                        ({{ row.technician_service_role_name }})
                                    </div>
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span :class="getStatusBadgeClasses(row.service_status)">
                                            {{ getStatusLabel(row.service_status) }}
                                        </span>
                                        <a
                                            :href="getWorkOrderUrl(row.work_order_id)"
                                            class="font-mono font-bold text-orange-600 dark:text-orange-400 hover:underline"
                                        >
                                            {{ row.ticket_no }}
                                        </a>
                                        <span
                                            v-if="row.is_overdue"
                                            class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold"
                                            title="متأخرة"
                                        >
                                            متأخرة
                                        </span>
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                        {{ row.service_name }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ row.section_name }}
                                    </div>
                                    <div
                                        class="mt-1 flex items-center gap-2 text-[11px] text-gray-600 dark:text-gray-300"
                                    >
                                        <span>{{ row.vehicle.display_name }}</span>
                                        <SaudiPlateDisplay
                                            v-if="row.vehicle.plate_number"
                                            :plate="row.vehicle.plate_number"
                                            size="xs"
                                        />
                                    </div>
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono text-gray-700 dark:text-gray-300"
                                >
                                    {{ row.service_date || '-' }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono text-gray-700 dark:text-gray-300"
                                >
                                    <div>{{ row.invoice_date || '-' }}</div>
                                    <div
                                        v-if="row.invoice_number"
                                        class="text-[10px] text-gray-400"
                                    >
                                        {{ row.invoice_number }}
                                    </div>
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ formatMoney(row.service_price) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-rose-600 dark:text-rose-400"
                                >
                                    {{ formatMoney(row.service_discount) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ formatMoney(row.service_cost) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono font-bold text-orange-600 dark:text-orange-400"
                                >
                                    {{ formatNumber(row.service_contribution) }}%
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono font-bold text-blue-600 dark:text-blue-400"
                                >
                                    {{ formatNumber(row.service_hours) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono font-bold text-gray-700 dark:text-gray-300"
                                >
                                    {{ row.working_technicians }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/20"
                                >
                                    {{ formatMoney(row.avg_percent_contribution) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/20"
                                >
                                    {{ formatMoney(row.avg_contribution_by_service_hours) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono font-black text-purple-600 dark:text-purple-400 bg-purple-50/30 dark:bg-purple-950/20"
                                >
                                    {{ formatMoney(row.weighted_avg_contribution) }}
                                </td>
                            </tr>
                        </tbody>

                        <!-- Overall Table Footer -->
                        <tfoot
                            class="bg-gray-100/90 dark:bg-gray-750 font-bold border-t-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white"
                        >
                            <tr>
                                <td colspan="6" class="py-3 px-3.5 text-right font-black text-xs">
                                    المجموع الإجمالي ({{ kpis.total_records }} سجلات)
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-amber-600 dark:text-amber-400"
                                >
                                    {{ formatMoney(kpis.total_service_price) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-rose-600 dark:text-rose-400"
                                >
                                    {{ formatMoney(kpis.total_service_discount) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-teal-600 dark:text-teal-400"
                                >
                                    {{ formatMoney(kpis.total_service_cost) }}
                                </td>
                                <td class="py-3 px-3.5 text-center font-mono text-gray-500">-</td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono text-blue-600 dark:text-blue-400"
                                >
                                    {{ formatNumber(kpis.total_service_hours) }}
                                </td>
                                <td class="py-3 px-3.5 text-center font-mono text-gray-500">-</td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatMoney(kpis.total_avg_percent_contribution) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ formatMoney(kpis.total_avg_contribution_by_service_hours) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-left font-mono text-purple-600 dark:text-purple-400"
                                >
                                    {{ formatMoney(kpis.total_weighted_avg_contribution) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div
                    v-if="employees && employees.links && employees.links.length > 3"
                    class="p-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 text-xs"
                >
                    <div class="text-gray-500 dark:text-gray-400">
                        {{ $t('reports.employees_report.showing') }}
                        <span class="font-bold text-gray-900 dark:text-white font-mono">
                            {{ employees.from || 0 }}
                        </span>
                        {{ $t('reports.employees_report.to') }}
                        <span class="font-bold text-gray-900 dark:text-white font-mono">
                            {{ employees.to || 0 }}
                        </span>
                        {{ $t('reports.employees_report.of') }}
                        <span class="font-bold text-gray-900 dark:text-white font-mono">
                            {{ employees.total || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, lIdx) in employees.links" :key="lIdx">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg border font-mono transition-colors"
                                :class="
                                    link.active
                                        ? 'bg-orange-600 text-white border-orange-600 font-bold'
                                        : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                "
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 rounded-lg border border-transparent text-gray-400 dark:text-gray-600 font-mono"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Standard Official Print Footer -->
            <div class="print-section hidden">
                <StandardReportFooter />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { usePrint } from '@/Composables/usePrint';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    kpis: {
        type: Object,
        required: true,
    },
    employees: {
        type: Object,
        default: null,
    },
    groupedEmployees: {
        type: Array,
        default: null,
    },
    centers: {
        type: Array,
        default: () => [],
    },
    employeesList: {
        type: Array,
        default: () => [],
    },
    jobTitles: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    models: {
        type: Array,
        default: () => [],
    },
});

const { print } = usePrint();

const handlePrint = () => {
    print();
};

const localFilters = ref({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    date_type: props.filters.date_type || 'service_date',
    center_id: String(props.filters.center_id || 'all'),
    employee_id: String(props.filters.employee_id || 'all'),
    job_title_id: String(props.filters.job_title_id || 'all'),
    department_id: String(props.filters.department_id || 'all'),
    service_id: String(props.filters.service_id || 'all'),
    service_status: String(props.filters.service_status || 'all'),
    make_id: String(props.filters.make_id || 'all'),
    model_id: String(props.filters.model_id || 'all'),
    ticket_no: props.filters.ticket_no || '',
    is_overdue: Boolean(props.filters.is_overdue),
    deduct_ticket_discount: Boolean(props.filters.deduct_ticket_discount),
    remove_inactive_employees: Boolean(props.filters.remove_inactive_employees),
    group_by: props.filters.group_by || 'none',
    search: props.filters.search || '',
});

// Group Accordion open tracking
const openGroups = ref(new Set());
if (props.groupedEmployees && props.groupedEmployees.length) {
    props.groupedEmployees.forEach((g) => openGroups.value.add(g.group_id));
}

const toggleGroup = (groupId) => {
    if (openGroups.value.has(groupId)) {
        openGroups.value.delete(groupId);
    } else {
        openGroups.value.add(groupId);
    }
};

const expandAllGroups = () => {
    if (props.groupedEmployees) {
        props.groupedEmployees.forEach((g) => openGroups.value.add(g.group_id));
    }
};

const collapseAllGroups = () => {
    openGroups.value.clear();
};

const paginatedItems = computed(() => {
    return props.employees?.data || [];
});

// Options for selects
const rangeOptions = [
    { value: 'today', label: 'اليوم' },
    { value: 'yesterday', label: 'أمس' },
    { value: 'this_week', label: 'هذا الأسبوع' },
    { value: 'this_month', label: 'هذا الشهر' },
    { value: 'this_year', label: 'هذا العام' },
    { value: 'all_time', label: 'كل الفترات' },
    { value: 'custom', label: 'تاريخ مخصص' },
];

const dateTypeOptions = [
    { value: 'service_date', label: 'تاريخ الخدمة' },
    { value: 'invoice_date', label: 'تاريخ الفاتورة' },
];

const centerOptions = computed(() => [
    { value: 'all', label: 'كافة الفروع' },
    ...props.centers.map((c) => ({ value: String(c.id), label: c.name })),
]);

const employeeOptions = computed(() => [
    { value: 'all', label: 'كافة الموظفين' },
    ...props.employeesList.map((e) => ({
        value: String(e.id),
        label: e.name_ar || e.name_en || 'موظف #' + e.id,
    })),
]);

const jobTitleOptions = computed(() => [
    { value: 'all', label: 'كافة المسميات الوظيفية' },
    ...props.jobTitles.map((j) => ({
        value: String(j.id),
        label: j.name_ar || j.name_en,
    })),
]);

const departmentOptions = computed(() => [
    { value: 'all', label: 'كافة الأقسام' },
    ...props.departments.map((d) => ({
        value: String(d.id),
        label: d.name_ar || d.name_en,
    })),
]);

const serviceOptions = computed(() => [
    { value: 'all', label: 'كافة الخدمات' },
    ...props.services.map((s) => ({
        value: String(s.id),
        label: s.name_ar || s.name_en,
    })),
]);

const serviceStatusOptions = [
    { value: 'all', label: 'كافة حالات الخدمات' },
    { value: 'pending', label: 'قيد الانتظار' },
    { value: 'in_progress', label: 'جاري العمل' },
    { value: 'paused', label: 'متوقف مؤقتاً' },
    { value: 'completed', label: 'مكتمل' },
    { value: 'cancelled', label: 'ملغي' },
];

const makeOptions = computed(() => [
    { value: 'all', label: 'كافة الشركات الصانعة' },
    ...props.makes.map((m) => ({
        value: String(m.id),
        label: m.name_ar || m.name_en,
    })),
]);

const filteredModelOptions = computed(() => {
    let list = props.models;
    if (localFilters.value.make_id && localFilters.value.make_id !== 'all') {
        list = list.filter((m) => String(m.make_id) === String(localFilters.value.make_id));
    }
    return [
        { value: 'all', label: 'كافة الموديلات' },
        ...list.map((m) => ({
            value: String(m.id),
            label: m.name_ar || m.name_en,
        })),
    ];
});

const groupByOptions = [
    { value: 'none', label: 'بدون تجميع (جدول مباشر)' },
    { value: 'job_title', label: 'تجميع حسب المسمى الوظيفي' },
    { value: 'employee', label: 'تجميع حسب الموظف' },
    { value: 'service_status', label: 'تجميع حسب حالة الخدمة' },
    { value: 'ticket_no', label: 'تجميع حسب كارت العمل' },
    { value: 'center', label: 'تجميع حسب الفرع' },
];

const handleMakeChange = () => {
    localFilters.value.model_id = 'all';
    applyFilters();
};

const applyFilters = () => {
    const params = {};
    Object.keys(localFilters.value).forEach((key) => {
        const val = localFilters.value[key];
        if (val !== null && val !== undefined && val !== '' && val !== 'all') {
            params[key] = val;
        }
    });

    router.get(route('app.reports.employees'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        range: 'this_month',
        start_date: '',
        end_date: '',
        date_type: 'service_date',
        center_id: 'all',
        employee_id: 'all',
        job_title_id: 'all',
        department_id: 'all',
        service_id: 'all',
        service_status: 'all',
        make_id: 'all',
        model_id: 'all',
        ticket_no: '',
        is_overdue: false,
        deduct_ticket_discount: false,
        remove_inactive_employees: false,
        group_by: 'none',
        search: '',
    };
    router.get(
        route('app.reports.employees'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.keys(localFilters.value).forEach((key) => {
        const val = localFilters.value[key];
        if (val !== null && val !== undefined && val !== '' && val !== 'all') {
            params.append(key, val);
        }
    });
    return route('app.reports.employees.export') + '?' + params.toString();
});

const formatMoney = (val) => {
    const num = Number(val || 0);
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const formatNumber = (val) => {
    const num = Number(val || 0);
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: Number.isInteger(num) ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const getStatusBadgeClasses = (status) => {
    switch (status) {
        case 'completed':
            return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'in_progress':
            return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'paused':
            return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'cancelled':
            return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
        default:
            return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'completed':
            return 'مكتمل';
        case 'in_progress':
            return 'جاري العمل';
        case 'paused':
            return 'متوقف مؤقتاً';
        case 'cancelled':
            return 'ملغي';
        case 'pending':
            return 'قيد الانتظار';
        default:
            return status || 'غير محدد';
    }
};

const getWorkOrderUrl = (id) => {
    try {
        if (typeof route === 'function' && route().has('work-orders.show')) {
            return route('work-orders.show', id);
        }
    } catch (e) {}
    return `/app/work-orders/${id}`;
};
</script>
