<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Screen Only Header Section -->
            <PageHeader
                :title="$t('reports.services_report.title')"
                :subtitle="$t('reports.services_report.subtitle')"
                gradientFrom="from-teal-500"
                gradientTo="to-cyan-600"
                glowFrom="from-teal-500"
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
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            @click="handlePrint"
                            :label="$t('reports.services_report.print_report')"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white text-xs font-bold transition-all shadow-lg shadow-teal-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.services_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Print Header -->
            <StandardReportHeader
                class="hidden print:block mb-6"
                :title="$t('reports.services_report.official_report')"
            />

            <!-- Horizontal KPI Strip (matching wrshah /reports/services) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Total Records -->
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="text-xs font-medium">
                            {{ $t('reports.services_report.total_records') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-600 flex items-center justify-center"
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
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-gray-900 dark:text-white font-mono">
                        {{ kpis?.total_records || 0 }}
                    </div>
                </div>

                <!-- Total Service Price -->
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="text-xs font-medium">
                            {{ $t('reports.services_report.service_price') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center"
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
                    <div class="text-xl font-black text-gray-900 dark:text-white font-mono">
                        {{ formatMoney(kpis?.total_service_price) }}
                        <span class="text-[10px] font-sans font-normal text-gray-400">
                            {{ $t('reports.services_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Total Discount -->
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="text-xs font-medium">
                            {{ $t('reports.services_report.service_discount') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center"
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
                        {{ formatMoney(kpis?.total_service_discount) }}
                        <span class="text-[10px] font-sans font-normal text-gray-400">
                            {{ $t('reports.services_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Net / Service Cost -->
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="text-xs font-medium">
                            {{ $t('reports.services_report.service_cost') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center"
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
                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono"
                    >
                        {{ formatMoney(kpis?.total_service_cost) }}
                        <span class="text-[10px] font-sans font-normal text-gray-400">
                            {{ $t('reports.services_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- VAT -->
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="text-xs font-medium">
                            {{ $t('reports.services_report.service_vat') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center"
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
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-purple-600 dark:text-purple-400 font-mono">
                        {{ formatMoney(kpis?.total_service_vat) }}
                        <span class="text-[10px] font-sans font-normal text-gray-400">
                            {{ $t('reports.services_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Total Amount (Incl. Tax) -->
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <span class="text-xs font-medium">
                            {{ $t('reports.services_report.total_amount') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-600 flex items-center justify-center"
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
                    <div class="text-xl font-black text-teal-600 dark:text-teal-400 font-mono">
                        {{ formatMoney(kpis?.total_amount) }}
                        <span class="text-[10px] font-sans font-normal text-gray-400">
                            {{ $t('reports.services_report.currency') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-sm p-5 space-y-4 print:hidden"
            >
                <!-- Quick Date Presets -->
                <div
                    class="flex flex-wrap items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700/60"
                >
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 ml-2">
                        {{ $t('reports.services_report.date_range') }}:
                    </span>
                    <button
                        v-for="preset in datePresets"
                        :key="preset.id"
                        type="button"
                        @click="setPreset(preset.id)"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-semibold transition-all',
                            filterForm.range === preset.id
                                ? 'bg-teal-600 text-white shadow-sm'
                                : 'bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Date Inputs Row -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Date Type Selection -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.date_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.date_type"
                            :options="dateTypeOptions"
                            :placeholder="$t('reports.services_report.date_type')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Date From -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.date_from') }}
                        </label>
                        <CustomDatePicker v-model="filterForm.start_date" class="w-full text-xs" />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.date_to') }}
                        </label>
                        <CustomDatePicker v-model="filterForm.end_date" class="w-full text-xs" />
                    </div>
                </div>

                <!-- Search & Filters Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                    <!-- Direct Search -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.direct_search') }}
                        </label>
                        <div class="relative">
                            <input
                                v-model="filterForm.search"
                                type="text"
                                :placeholder="$t('reports.services_report.search_placeholder')"
                                class="w-full h-10 px-3.5 pe-8 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-teal-500"
                                @keyup.enter="applyFilters"
                            />
                            <button
                                v-if="filterForm.search"
                                type="button"
                                @click="
                                    filterForm.search = '';
                                    applyFilters();
                                "
                                class="absolute end-2.5 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-base leading-none"
                            >
                                ×
                            </button>
                        </div>
                    </div>

                    <!-- Center / Branch Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.center_id"
                            :options="centerOptions"
                            :placeholder="$t('reports.services_report.all_centers')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Department / Section Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.department') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.department_id"
                            :options="departmentOptions"
                            :placeholder="$t('reports.services_report.all_departments')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Service Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.service') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.service_id"
                            :options="filteredServiceOptions"
                            :placeholder="$t('reports.services_report.all_services')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Customer Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.customer') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_id"
                            :options="customerOptions"
                            :placeholder="$t('reports.services_report.all_customers')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Customer Type Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.customer_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_type"
                            :options="customerTypeOptions"
                            :placeholder="$t('reports.services_report.all_customer_types')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Vehicle Make Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.vehicle_make') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.make_id"
                            :options="makeOptions"
                            :placeholder="$t('reports.services_report.all_makes')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Vehicle Model Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.vehicle_model') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.model_id"
                            :options="filteredModelOptions"
                            :placeholder="$t('reports.services_report.all_models')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Ticket / Work Order Status -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.ticket_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.ticket_status"
                            :options="ticketStatusOptions"
                            :placeholder="$t('reports.services_report.all_ticket_statuses')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Service Status -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.services_report.service_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.service_status"
                            :options="serviceStatusOptions"
                            :placeholder="$t('reports.services_report.all_service_statuses')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Group By (التجميع حسب) -->
                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-bold text-teal-600 dark:text-teal-400 mb-1.5"
                        >
                            {{ $t('reports.services_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.group_by"
                            :options="groupByOptions"
                            :placeholder="$t('reports.services_report.group_none')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Toggles & Action Buttons Row -->
                <div
                    class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                >
                    <!-- Checkbox Switches -->
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Under Warranty Checkbox -->
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <AppToggle v-model="filterForm.is_warranty" @change="applyFilters" />
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 dark:text-teal-400"
                            >
                                <svg
                                    class="w-4 h-4 text-teal-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                                {{ $t('reports.services_report.under_warranty_only') }}
                            </span>
                        </label>

                        <!-- Overdue Only Checkbox -->
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <AppToggle v-model="filterForm.is_overdue" @change="applyFilters" />
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 dark:text-amber-400"
                            >
                                <svg
                                    class="w-4 h-4 text-amber-500"
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
                                {{ $t('reports.services_report.overdue_only') }}
                            </span>
                        </label>

                        <!-- Deduct Ticket Discount Toggle -->
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <AppToggle
                                v-model="filterForm.deduct_ticket_discount"
                                @change="applyFilters"
                            />
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('reports.services_report.deduct_ticket_discount') }}
                            </span>
                        </label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            {{ $t('reports.payments_report.reset_filters') }}
                        </button>
                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-6 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-md shadow-teal-500/20"
                        >
                            {{ $t('reports.payments_report.apply_filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grouped Accordions View -->
            <div v-if="filterForm.group_by !== 'none' && groupedServices?.length" class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                        {{ $t('reports.services_report.group_summary') }} ({{
                            groupedServices.length
                        }})
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAllGroups"
                            class="text-[11px] font-bold text-teal-600 hover:underline"
                        >
                            {{ $t('reports.services_report.expand_all') }}
                        </button>
                        <span class="text-gray-300">|</span>
                        <button
                            type="button"
                            @click="collapseAllGroups"
                            class="text-[11px] font-bold text-gray-500 hover:underline"
                        >
                            {{ $t('reports.services_report.collapse_all') }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(grp, idx) in groupedServices"
                    :key="idx"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-sm overflow-hidden"
                >
                    <!-- Accordion Header -->
                    <div
                        @click="toggleGroup(grp.group_key)"
                        class="p-4 bg-gray-50/70 dark:bg-gray-750 flex flex-col md:flex-row md:items-center justify-between gap-3 cursor-pointer hover:bg-teal-50/40 dark:hover:bg-teal-900/10 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="w-6 h-6 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 flex items-center justify-center text-xs font-bold transition-transform duration-200"
                                :class="{ 'rotate-180': openGroups[grp.group_key] }"
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
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ grp.group_name }}
                                </h3>
                                <p class="text-[11px] text-gray-500">
                                    {{ grp.total_records }}
                                    {{ $t('reports.services_report.records_count') }}
                                </p>
                            </div>
                        </div>

                        <!-- Subtotal Metrics -->
                        <div class="flex flex-wrap items-center gap-4 text-xs font-mono">
                            <div>
                                <span class="text-gray-400 text-[10px] block font-sans">
                                    {{ $t('reports.services_report.service_price') }}
                                </span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatMoney(grp.total_service_price) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block font-sans">
                                    {{ $t('reports.services_report.service_discount') }}
                                </span>
                                <span class="font-bold text-amber-600 dark:text-amber-400">
                                    {{ formatMoney(grp.total_service_discount) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block font-sans">
                                    {{ $t('reports.services_report.service_cost') }}
                                </span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatMoney(grp.total_service_cost) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block font-sans">
                                    {{ $t('reports.services_report.service_vat') }}
                                </span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">
                                    {{ formatMoney(grp.total_service_vat) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[10px] block font-sans">
                                    {{ $t('reports.services_report.total_amount') }}
                                </span>
                                <span class="font-bold text-teal-600 dark:text-teal-400">
                                    {{ formatMoney(grp.total_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Body -->
                    <div
                        v-show="openGroups[grp.group_key]"
                        class="overflow-x-auto border-t border-gray-100 dark:border-gray-700"
                    >
                        <DataTable structured class="w-full text-xs text-start">
                            <thead
                                class="bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700"
                            >
                                <tr>
                                    <th class="py-2.5 px-3 text-center">#</th>
                                    <th class="py-2.5 px-3 text-start">
                                        {{ $t('reports.services_report.center') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-start">
                                        {{ $t('reports.services_report.vehicle') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-start">
                                        {{ $t('reports.services_report.service') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-start">
                                        {{ $t('reports.services_report.customer') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-center">
                                        {{ $t('reports.services_report.start_date') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-center">
                                        {{ $t('reports.services_report.due_date') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-end">
                                        {{ $t('reports.services_report.service_price') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-end">
                                        {{ $t('reports.services_report.service_discount') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-end">
                                        {{ $t('reports.services_report.service_cost') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-end">
                                        {{ $t('reports.services_report.service_vat') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-end">
                                        {{ $t('reports.services_report.total_amount') }}
                                    </th>
                                    <th class="py-2.5 px-3 text-center print:hidden"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <tr
                                    v-for="(srv, i) in grp.services"
                                    :key="srv.id"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50"
                                >
                                    <td class="py-2.5 px-3 text-center text-gray-400">
                                        {{ i + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">
                                        {{ srv.center_name }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-gray-800 dark:text-gray-200">
                                            {{ srv.vehicle.display_name }}
                                        </div>
                                        <div
                                            class="font-mono text-[11px] text-gray-500 dir-ltr inline-block"
                                        >
                                            {{ srv.vehicle.plate_number }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <a
                                                :href="'/app/work-orders/' + srv.work_order_id"
                                                class="font-mono font-bold text-teal-600 dark:text-teal-400 hover:underline"
                                            >
                                                {{ srv.ticket_no }}
                                            </a>
                                            <!-- Overdue badge -->
                                            <span
                                                v-if="srv.is_overdue"
                                                class="inline-flex items-center text-amber-500"
                                                :title="$t('reports.services_report.overdue_badge')"
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
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </span>
                                            <!-- Warranty Badge -->
                                            <span
                                                v-if="srv.is_warranty"
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800"
                                                :title="
                                                    srv.warranty_remaining_days > 0
                                                        ? srv.warranty_remaining_days +
                                                          ' ' +
                                                          $t('reports.services_report.days') +
                                                          ' ' +
                                                          $t(
                                                              'reports.services_report.warranty_remaining'
                                                          )
                                                        : $t(
                                                              'reports.services_report.under_warranty'
                                                          )
                                                "
                                            >
                                                🛡️
                                                {{
                                                    srv.warranty_remaining_days > 0
                                                        ? srv.warranty_remaining_days +
                                                          ' ' +
                                                          $t('reports.services_report.days')
                                                        : $t(
                                                              'reports.services_report.under_warranty'
                                                          )
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="font-semibold text-gray-900 dark:text-white mt-0.5"
                                        >
                                            {{ srv.service_name }}
                                            <span class="text-gray-400 text-[10px] font-normal">
                                                ({{ srv.department_name }})
                                            </span>
                                        </div>
                                        <span
                                            :class="[
                                                'inline-block px-1.5 py-0.2 rounded text-[10px] font-bold mt-1',
                                                getServiceStatusBadgeClass(srv.status),
                                            ]"
                                        >
                                            {{ getServiceStatusLabel(srv.status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-gray-800 dark:text-gray-200">
                                            {{ srv.customer.name }}
                                        </div>
                                        <div class="text-[11px] text-gray-400 font-mono">
                                            {{ srv.customer.phone }}
                                        </div>
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono text-[11px] text-gray-600 dark:text-gray-400"
                                    >
                                        {{ srv.start_date || '—' }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono text-[11px] text-gray-600 dark:text-gray-400"
                                    >
                                        {{ srv.due_date || '—' }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-end font-mono font-bold text-gray-900 dark:text-gray-100"
                                    >
                                        {{ formatMoney(srv.service_price) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-end font-mono text-amber-600 dark:text-amber-400"
                                    >
                                        {{ formatMoney(srv.service_discount) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-end font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ formatMoney(srv.service_cost) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-end font-mono text-purple-600 dark:text-purple-400"
                                    >
                                        {{ formatMoney(srv.service_vat) }}
                                        <span
                                            v-if="srv.is_vat_inclusive"
                                            class="text-[9px] text-teal-600 block"
                                        >
                                            {{ $t('reports.services_report.inclusive_vat') }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-end font-mono font-bold text-teal-600 dark:text-teal-400"
                                    >
                                        {{ formatMoney(srv.total_amount) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center print:hidden">
                                        <a
                                            :href="'/app/work-orders/' + srv.work_order_id"
                                            class="px-2 py-1 rounded bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 hover:bg-teal-100 text-[10px] font-bold inline-block"
                                        >
                                            {{ $t('reports.services_report.view_work_order') }}
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>

            <!-- Standard Flat Table (when group_by === 'none') -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-xs text-start">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700"
                        >
                            <tr>
                                <th class="py-3 px-3 text-center">#</th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.services_report.center') }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.services_report.vehicle') }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.services_report.service') }}
                                </th>
                                <th class="py-3 px-3 text-start">
                                    {{ $t('reports.services_report.customer') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.services_report.start_date') }}
                                </th>
                                <th class="py-3 px-3 text-center">
                                    {{ $t('reports.services_report.due_date') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.services_report.service_price') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.services_report.service_discount') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.services_report.service_cost') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.services_report.service_vat') }}
                                </th>
                                <th class="py-3 px-3 text-end">
                                    {{ $t('reports.services_report.total_amount') }}
                                </th>
                                <th class="py-3 px-3 text-center print:hidden"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr v-if="!services?.data || services.data.length === 0">
                                <td
                                    colspan="13"
                                    class="py-12 text-center text-gray-500 dark:text-gray-400"
                                >
                                    <div
                                        class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400"
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
                                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-sm">
                                        {{ $t('reports.services_report.no_records') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $t('reports.services_report.no_records_hint') }}
                                    </p>
                                </td>
                            </tr>
                            <tr
                                v-for="(srv, i) in services?.data"
                                :key="srv.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition-colors"
                            >
                                <td class="py-3 px-3 text-center text-gray-400 font-mono">
                                    {{ (services.current_page - 1) * services.per_page + i + 1 }}
                                </td>
                                <td
                                    class="py-3 px-3 text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                >
                                    {{ srv.center_name }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900 dark:text-gray-100">
                                        {{ srv.vehicle.display_name }}
                                    </div>
                                    <div
                                        class="font-mono text-[11px] text-gray-500 dir-ltr inline-block"
                                    >
                                        {{ srv.vehicle.plate_number }}
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <a
                                            :href="'/app/work-orders/' + srv.work_order_id"
                                            class="font-mono font-bold text-teal-600 dark:text-teal-400 hover:underline"
                                        >
                                            {{ srv.ticket_no }}
                                        </a>
                                        <!-- Overdue Badge -->
                                        <span
                                            v-if="srv.is_overdue"
                                            class="inline-flex items-center text-amber-500"
                                            :title="$t('reports.services_report.overdue_badge')"
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
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </span>
                                        <!-- Warranty Badge -->
                                        <span
                                            v-if="srv.is_warranty"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800"
                                            :title="
                                                srv.warranty_remaining_days > 0
                                                    ? srv.warranty_remaining_days +
                                                      ' ' +
                                                      $t('reports.services_report.days') +
                                                      ' ' +
                                                      $t(
                                                          'reports.services_report.warranty_remaining'
                                                      )
                                                    : $t('reports.services_report.under_warranty')
                                            "
                                        >
                                            🛡️
                                            {{
                                                srv.warranty_remaining_days > 0
                                                    ? srv.warranty_remaining_days +
                                                      ' ' +
                                                      $t('reports.services_report.days')
                                                    : $t('reports.services_report.under_warranty')
                                            }}
                                        </span>
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white mt-0.5">
                                        {{ srv.service_name }}
                                        <span class="text-gray-400 text-[10px] font-normal">
                                            ({{ srv.department_name }})
                                        </span>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-block px-1.5 py-0.2 rounded text-[10px] font-bold mt-1',
                                            getServiceStatusBadgeClass(srv.status),
                                        ]"
                                    >
                                        {{ getServiceStatusLabel(srv.status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">
                                        {{ srv.customer.name }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-mono">
                                        {{ srv.customer.phone }}
                                    </div>
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono text-[11px] text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                >
                                    {{ srv.start_date || '—' }}
                                </td>
                                <td
                                    class="py-3 px-3 text-center font-mono text-[11px] text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                >
                                    {{ srv.due_date || '—' }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-gray-900 dark:text-gray-100"
                                >
                                    {{ formatMoney(srv.service_price) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono text-amber-600 dark:text-amber-400"
                                >
                                    {{ formatMoney(srv.service_discount) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatMoney(srv.service_cost) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono text-purple-600 dark:text-purple-400"
                                >
                                    {{ formatMoney(srv.service_vat) }}
                                    <span
                                        v-if="srv.is_vat_inclusive"
                                        class="text-[9px] text-teal-600 block"
                                    >
                                        {{ $t('reports.services_report.inclusive_vat') }}
                                    </span>
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-teal-600 dark:text-teal-400"
                                >
                                    {{ formatMoney(srv.total_amount) }}
                                </td>
                                <td class="py-3 px-3 text-center print:hidden">
                                    <a
                                        :href="'/app/work-orders/' + srv.work_order_id"
                                        class="px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 hover:bg-teal-100 text-[11px] font-bold inline-block"
                                    >
                                        {{ $t('reports.services_report.view_work_order') }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="services?.links && services.links.length > 3"
                    class="p-4 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs print:hidden"
                >
                    <div class="text-gray-500 dark:text-gray-400">
                        {{ $t('reports.payments_report.showing') }}
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ services.from || 0 }}
                        </span>
                        {{ $t('reports.payments_report.to') }}
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ services.to || 0 }}
                        </span>
                        {{ $t('reports.payments_report.of') }}
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ services.total || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in services.links" :key="i">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-bold transition-all',
                                    link.active
                                        ? 'bg-teal-600 text-white shadow-sm'
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
        </PrintEngine>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { printDocument } from '@/Composables/usePrint';
import { reactive, computed, watch, ref } from 'vue';
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
        default: () => ({}),
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    groupedServices: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    servicesList: {
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
    models: {
        type: Array,
        default: () => [],
    },
    centers: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    date_type: props.filters.date_type || 'due_date',
    center_id: props.filters.center_id || 'all',
    department_id: props.filters.department_id || 'all',
    service_id: props.filters.service_id || 'all',
    customer_id: props.filters.customer_id || 'all',
    customer_type: props.filters.customer_type || 'all',
    make_id: props.filters.make_id || 'all',
    model_id: props.filters.model_id || 'all',
    ticket_status: props.filters.ticket_status || 'all',
    service_status: props.filters.service_status || 'all',
    is_overdue: Boolean(props.filters.is_overdue),
    is_warranty: Boolean(props.filters.is_warranty),
    deduct_ticket_discount: Boolean(props.filters.deduct_ticket_discount),
    group_by: props.filters.group_by || 'none',
    search: props.filters.search || '',
});

const openGroups = reactive({});

// Expand all groups initially
watch(
    () => props.groupedServices,
    (newGroups) => {
        if (newGroups?.length) {
            newGroups.forEach((g) => {
                openGroups[g.group_key] = true;
            });
        }
    },
    { immediate: true }
);

const toggleGroup = (key) => {
    openGroups[key] = !openGroups[key];
};

const expandAllGroups = () => {
    props.groupedServices.forEach((g) => {
        openGroups[g.group_key] = true;
    });
};

const collapseAllGroups = () => {
    props.groupedServices.forEach((g) => {
        openGroups[g.group_key] = false;
    });
};

const datePresets = computed(() => [
    { id: 'today', label: t('reports.services_report.today') },
    { id: 'yesterday', label: t('reports.services_report.yesterday') },
    { id: 'this_week', label: t('reports.services_report.this_week') },
    { id: 'this_month', label: t('reports.services_report.this_month') },
    { id: 'this_year', label: t('reports.services_report.this_year') },
    { id: 'all_time', label: t('reports.services_report.all_time') },
]);

const dateTypeOptions = computed(() => [
    {
        id: 'due_date',
        name: t('reports.services_report.date_due'),
        label: t('reports.services_report.date_due'),
    },
    {
        id: 'started_at',
        name: t('reports.services_report.date_started'),
        label: t('reports.services_report.date_started'),
    },
    {
        id: 'completed_at',
        name: t('reports.services_report.date_completed'),
        label: t('reports.services_report.date_completed'),
    },
    {
        id: 'created_at',
        name: t('reports.services_report.date_created'),
        label: t('reports.services_report.date_created'),
    },
    {
        id: 'entry_date',
        name: t('reports.services_report.date_entry'),
        label: t('reports.services_report.date_entry'),
    },
]);

const centerOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_centers'),
        label: t('reports.services_report.all_centers'),
    },
    ...props.centers.map((c) => ({ id: String(c.id), name: c.name, label: c.name })),
]);

const departmentOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_departments'),
        label: t('reports.services_report.all_departments'),
    },
    ...props.departments.map((d) => {
        const text = d.name_ar || d.name_en;
        return { id: String(d.id), name: text, label: text };
    }),
]);

const filteredServiceOptions = computed(() => {
    let list = props.servicesList;
    if (filterForm.department_id !== 'all' && filterForm.department_id !== '') {
        list = list.filter((s) => String(s.department_id) === String(filterForm.department_id));
    }
    return [
        {
            id: 'all',
            name: t('reports.services_report.all_services'),
            label: t('reports.services_report.all_services'),
        },
        ...list.map((s) => {
            const text = s.name_ar || s.name_en;
            return { id: String(s.id), name: text, label: text };
        }),
    ];
});

const customerOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_customers'),
        label: t('reports.services_report.all_customers'),
    },
    ...props.customers.map((c) => {
        const text = c.phone ? `${c.name} (${c.phone})` : c.name;
        return { id: String(c.id), name: text, label: text };
    }),
]);

const customerTypeOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_customer_types'),
        label: t('reports.services_report.all_customer_types'),
    },
    {
        id: 'individual',
        name: t('reports.tickets_report.type_individual'),
        label: t('reports.tickets_report.type_individual'),
    },
    {
        id: 'company',
        name: t('reports.tickets_report.type_company'),
        label: t('reports.tickets_report.type_company'),
    },
    {
        id: 'government',
        name: t('reports.tickets_report.type_government'),
        label: t('reports.tickets_report.type_government'),
    },
    {
        id: 'vip',
        name: t('reports.tickets_report.type_vip'),
        label: t('reports.tickets_report.type_vip'),
    },
]);

const makeOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_makes'),
        label: t('reports.services_report.all_makes'),
    },
    ...props.makes.map((m) => {
        const text = m.name_ar || m.name_en;
        return { id: String(m.id), name: text, label: text };
    }),
]);

const filteredModelOptions = computed(() => {
    let list = props.models;
    if (filterForm.make_id !== 'all' && filterForm.make_id !== '') {
        list = list.filter((m) => String(m.make_id) === String(filterForm.make_id));
    }
    return [
        {
            id: 'all',
            name: t('reports.services_report.all_models'),
            label: t('reports.services_report.all_models'),
        },
        ...list.map((m) => {
            const text = m.name_ar || m.name_en;
            return { id: String(m.id), name: text, label: text };
        }),
    ];
});

const ticketStatusOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_ticket_statuses'),
        label: t('reports.services_report.all_ticket_statuses'),
    },
    { id: 'draft', name: 'مسودة', label: 'مسودة' },
    { id: 'open', name: 'مفتوح', label: 'مفتوح' },
    { id: 'in_progress', name: 'قيد التنفيذ', label: 'قيد التنفيذ' },
    { id: 'ready_for_qc', name: 'جاهز للفحص', label: 'جاهز للفحص' },
    { id: 'done', name: 'مكتمل', label: 'مكتمل' },
    { id: 'delivered', name: 'تم التسليم', label: 'تم التسليم' },
    { id: 'cancelled', name: 'ملغي', label: 'ملغي' },
]);

const serviceStatusOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.services_report.all_service_statuses'),
        label: t('reports.services_report.all_service_statuses'),
    },
    {
        id: 'pending',
        name: getServiceStatusLabel('pending'),
        label: getServiceStatusLabel('pending'),
    },
    {
        id: 'in_progress',
        name: getServiceStatusLabel('in_progress'),
        label: getServiceStatusLabel('in_progress'),
    },
    {
        id: 'ready_for_qc',
        name: getServiceStatusLabel('ready_for_qc'),
        label: getServiceStatusLabel('ready_for_qc'),
    },
    {
        id: 'completed',
        name: getServiceStatusLabel('completed'),
        label: getServiceStatusLabel('completed'),
    },
    {
        id: 'on_hold',
        name: getServiceStatusLabel('on_hold'),
        label: getServiceStatusLabel('on_hold'),
    },
    {
        id: 'cancelled',
        name: getServiceStatusLabel('cancelled'),
        label: getServiceStatusLabel('cancelled'),
    },
]);

const groupByOptions = computed(() => [
    {
        id: 'none',
        name: t('reports.services_report.group_none'),
        label: t('reports.services_report.group_none'),
    },
    {
        id: 'department',
        name: t('reports.services_report.group_department'),
        label: t('reports.services_report.group_department'),
    },
    {
        id: 'service',
        name: t('reports.services_report.group_service'),
        label: t('reports.services_report.group_service'),
    },
    {
        id: 'center',
        name: t('reports.services_report.group_center'),
        label: t('reports.services_report.group_center'),
    },
    {
        id: 'customer_type',
        name: t('reports.services_report.group_customer_type'),
        label: t('reports.services_report.group_customer_type'),
    },
    {
        id: 'customer',
        name: t('reports.services_report.group_customer'),
        label: t('reports.services_report.group_customer'),
    },
    {
        id: 'make',
        name: t('reports.services_report.group_make'),
        label: t('reports.services_report.group_make'),
    },
    {
        id: 'model',
        name: t('reports.services_report.group_model'),
        label: t('reports.services_report.group_model'),
    },
]);

const setPreset = (presetId) => {
    filterForm.range = presetId;
    if (presetId !== 'custom') {
        filterForm.start_date = '';
        filterForm.end_date = '';
    }
    applyFilters();
};

const applyFilters = () => {
    router.get(
        '/app/reports/services',
        {
            range: filterForm.range,
            start_date: filterForm.start_date || undefined,
            end_date: filterForm.end_date || undefined,
            date_type: filterForm.date_type,
            center_id: filterForm.center_id || 'all',
            department_id: filterForm.department_id || 'all',
            service_id: filterForm.service_id || 'all',
            customer_id: filterForm.customer_id || 'all',
            customer_type: filterForm.customer_type || 'all',
            make_id: filterForm.make_id || 'all',
            model_id: filterForm.model_id || 'all',
            ticket_status: filterForm.ticket_status || 'all',
            service_status: filterForm.service_status || 'all',
            is_overdue: filterForm.is_overdue ? 1 : undefined,
            is_warranty: filterForm.is_warranty ? 1 : undefined,
            deduct_ticket_discount: filterForm.deduct_ticket_discount ? 1 : undefined,
            group_by: filterForm.group_by,
            search: filterForm.search || undefined,
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
    filterForm.date_type = 'due_date';
    filterForm.center_id = 'all';
    filterForm.department_id = 'all';
    filterForm.service_id = 'all';
    filterForm.customer_id = 'all';
    filterForm.customer_type = 'all';
    filterForm.make_id = 'all';
    filterForm.model_id = 'all';
    filterForm.ticket_status = 'all';
    filterForm.service_status = 'all';
    filterForm.is_overdue = false;
    filterForm.is_warranty = false;
    filterForm.deduct_ticket_discount = false;
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
    if (filterForm.center_id) params.append('center_id', filterForm.center_id);
    if (filterForm.department_id) params.append('department_id', filterForm.department_id);
    if (filterForm.service_id) params.append('service_id', filterForm.service_id);
    if (filterForm.customer_id) params.append('customer_id', filterForm.customer_id);
    if (filterForm.customer_type) params.append('customer_type', filterForm.customer_type);
    if (filterForm.make_id) params.append('make_id', filterForm.make_id);
    if (filterForm.model_id) params.append('model_id', filterForm.model_id);
    if (filterForm.ticket_status) params.append('ticket_status', filterForm.ticket_status);
    if (filterForm.service_status) params.append('service_status', filterForm.service_status);
    if (filterForm.is_overdue) params.append('is_overdue', '1');
    if (filterForm.is_warranty) params.append('is_warranty', '1');
    if (filterForm.deduct_ticket_discount) params.append('deduct_ticket_discount', '1');
    if (filterForm.search) params.append('search', filterForm.search);

    return `/app/reports/services/export?${params.toString()}`;
});

const handlePrint = () => {
    printDocument();
};

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const getServiceStatusLabel = (status) => {
    const key = `work_orders.item_status.${status}`;
    if (te(key)) {
        return t(key);
    }
    const fallbacks = {
        pending: 'قيد الانتظار',
        in_progress: 'جاري العمل',
        ready_for_qc: 'فحص الجودة',
        completed: 'مكتمل',
        on_hold: 'معلق',
        cancelled: 'ملغي',
    };
    return fallbacks[status] || status;
};

const getServiceStatusBadgeClass = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'ready_for_qc':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300';
        case 'on_hold':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<style scoped>
@media print {
    body {
        background: white !important;
        color: black !important;
        font-size: 10pt;
    }

    .print\:hidden {
        display: none !important;
    }

    .print-header {
        display: block !important;
    }

    .print-section {
        display: block !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    th,
    td {
        border: 1px solid #ddd !important;
        padding: 4px 6px !important;
    }
}
</style>
