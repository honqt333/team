<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section -->
            <PageHeader
                :title="$t('reports.customers_report.title')"
                :subtitle="$t('reports.customers_report.subtitle')"
                gradientFrom="from-sky-500"
                gradientTo="to-blue-600"
                glowFrom="from-sky-500"
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
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            variant="secondary"
                            :label="$t('reports.customers_report.print_report')"
                            @click="handlePrint"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-700 hover:to-blue-700 text-white text-xs font-bold transition-all shadow-lg shadow-sky-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.customers_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- UNIFIED CUSTOMER FINANCIAL & OPERATIONAL SUMMARY STRIP -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700/80 shadow-md flex flex-wrap items-center justify-between gap-3 text-xs"
            >
                <div class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                    <span class="w-2.5 h-2.5 rounded-full bg-sky-600 animate-pulse"></span>
                    <span class="text-sm font-black">
                        {{ $t('reports.customers_report.total_summary') }}:
                    </span>
                </div>

                <div
                    class="flex flex-wrap items-center gap-3 sm:gap-6 divide-x rtl:divide-x-reverse divide-gray-200 dark:divide-gray-700"
                >
                    <!-- Total Records -->
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $t('reports.customers_report.total_records') }}:
                        </span>
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ kpis.total_records }}
                        </span>
                    </div>

                    <!-- Payment Due -->
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-sky-600 dark:text-sky-400 font-semibold">
                            {{ $t('reports.customers_report.total_payment_due') }}:
                        </span>
                        <span class="font-mono font-bold text-sky-700 dark:text-sky-300">
                            {{ formatMoney(kpis.total_payment_due) }}
                            {{ $t('reports.customers_report.currency') }}
                        </span>
                    </div>

                    <!-- Amount Paid -->
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-teal-600 dark:text-teal-400 font-semibold">
                            {{ $t('reports.customers_report.total_amount_paid') }}:
                        </span>
                        <span class="font-mono font-bold text-teal-700 dark:text-teal-300">
                            {{ formatMoney(kpis.total_amount_paid) }}
                            {{ $t('reports.customers_report.currency') }}
                        </span>
                    </div>

                    <!-- Amount Adjusted (Discounts) -->
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-action-primary font-semibold">
                            {{ $t('reports.customers_report.total_amount_adjusted') }}:
                        </span>
                        <span class="font-mono font-bold text-selection-text">
                            {{ formatMoney(kpis.total_amount_adjusted) }}
                            {{ $t('reports.customers_report.currency') }}
                        </span>
                    </div>

                    <!-- Payment Remaining -->
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-amber-600 dark:text-amber-400 font-bold">
                            {{ $t('reports.customers_report.total_payment_remaining') }}:
                        </span>
                        <span class="font-mono font-bold text-amber-700 dark:text-amber-300">
                            {{ formatMoney(kpis.total_payment_remaining) }}
                            {{ $t('reports.customers_report.currency') }}
                        </span>
                    </div>

                    <!-- Bad Debt -->
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-rose-600 dark:text-rose-400 font-bold">
                            {{ $t('reports.customers_report.total_bad_debt') }}:
                        </span>
                        <span
                            class="font-mono font-bold px-2 py-0.5 rounded-full"
                            :class="
                                kpis.total_bad_debt > 0
                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
                                    : 'text-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ formatMoney(kpis.total_bad_debt) }}
                            {{ $t('reports.customers_report.currency') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- FILTERS PANEL -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm p-4 sm:p-5 space-y-4 print:hidden"
            >
                <!-- Top Row: Date Presets & Direct Search -->
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4"
                >
                    <!-- Presets Pills -->
                    <div
                        class="flex flex-wrap items-center gap-1.5 bg-gray-50 dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200/80 dark:border-gray-700/60"
                    >
                        <button
                            v-for="preset in datePresets"
                            :key="preset.id"
                            type="button"
                            @click="setPreset(preset.id)"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                filterForm.range === preset.id
                                    ? 'bg-sky-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <!-- Direct Search Input -->
                    <div class="relative w-full lg:w-96">
                        <input
                            v-model="filterForm.search"
                            type="text"
                            :placeholder="$t('reports.customers_report.search_placeholder')"
                            class="w-full ps-10 pe-4 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-focus-ring focus:border-action-primary transition-all placeholder:text-gray-400"
                            @keydown.enter="applyFilters"
                        />
                        <div
                            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400"
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

                <!-- Custom Dates Row (if custom preset) -->
                <div
                    v-if="filterForm.range === 'custom'"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-sky-50/50 dark:bg-sky-950/20 rounded-xl border border-sky-100 dark:border-sky-900/40"
                >
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.customers_report.date_from') }}
                        </label>
                        <CustomDatePicker v-model="filterForm.start_date" @change="applyFilters" />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.customers_report.date_to') }}
                        </label>
                        <CustomDatePicker v-model="filterForm.end_date" @change="applyFilters" />
                    </div>
                </div>

                <!-- Advanced Filters Grid -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700/60"
                >
                    <!-- Date Type Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.customers_report.date_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.date_type"
                            :options="dateTypeOptions"
                            :placeholder="$t('reports.customers_report.date_type')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Center / Branch Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.customers_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.center_id"
                            :options="centerOptions"
                            :placeholder="$t('reports.customers_report.all_centers')"
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
                            {{ $t('reports.customers_report.customer') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_id"
                            :options="customerOptions"
                            :placeholder="$t('reports.customers_report.all_customers')"
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
                            {{ $t('reports.customers_report.customer_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_type"
                            :options="customerTypeOptions"
                            :placeholder="$t('reports.customers_report.all_customer_types')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.customers_report.payment_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.payment_status"
                            :options="paymentStatusOptions"
                            :placeholder="$t('reports.customers_report.all_payment_statuses')"
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
                            {{ $t('reports.customers_report.vehicle_make') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.make_id"
                            :options="makeOptions"
                            :placeholder="$t('reports.customers_report.all_makes')"
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
                            {{ $t('reports.customers_report.vehicle_model') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.model_id"
                            :options="filteredModelOptions"
                            :placeholder="$t('reports.customers_report.all_models')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Group By Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-sky-600 dark:text-sky-400 mb-1.5"
                        >
                            {{ $t('reports.customers_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.group_by"
                            :options="groupByOptions"
                            :placeholder="$t('reports.customers_report.group_none')"
                            class="w-full text-xs"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Action Buttons Row -->
                <div
                    class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between"
                >
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('reports.customers_report.records_count') }}:
                        <strong class="font-mono text-gray-800 dark:text-gray-200">
                            {{ kpis.total_records }}
                        </strong>
                    </span>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        {{ $t('common.reset_filters') }}
                    </button>
                </div>
            </div>

            <!-- DATA SECTION: GROUPED ACCORDION VIEW -->
            <div
                v-if="filterForm.group_by !== 'none' && groupedRecords && groupedRecords.length"
                class="space-y-4 print:hidden"
            >
                <!-- Expand / Collapse All Controls -->
                <div class="flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="expandAll"
                        class="text-sky-600 dark:text-sky-400 hover:underline font-bold"
                    >
                        {{ $t('reports.customers_report.expand_all') }}
                    </button>
                    <span>•</span>
                    <button
                        type="button"
                        @click="collapseAll"
                        class="text-gray-500 hover:underline"
                    >
                        {{ $t('reports.customers_report.collapse_all') }}
                    </button>
                </div>

                <!-- Group Cards -->
                <div
                    v-for="group in groupedRecords"
                    :key="group.group_key"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm overflow-hidden"
                >
                    <!-- Group Header Accordion Bar -->
                    <button
                        type="button"
                        @click="toggleGroup(group.group_key)"
                        class="w-full px-5 py-3.5 bg-gray-50/80 dark:bg-gray-800 hover:bg-gray-100/80 dark:hover:bg-gray-700 flex flex-wrap items-center justify-between gap-3 text-start transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <svg
                                class="w-4 h-4 text-gray-500 transition-transform duration-200"
                                :class="{ 'rotate-180': openGroups[group.group_key] }"
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
                            <span class="text-sm font-black text-gray-900 dark:text-white">
                                {{ group.group_label }}
                            </span>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300"
                            >
                                {{ group.total_records }}
                                {{ $t('reports.customers_report.records_count') }}
                            </span>
                        </div>

                        <!-- Mini Subtotals Strip for Group -->
                        <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs font-mono">
                            <span class="text-gray-500">
                                {{ $t('reports.customers_report.payment_due') }}:
                                <strong class="text-sky-600 dark:text-sky-400">
                                    {{ formatMoney(group.total_payment_due) }}
                                </strong>
                            </span>
                            <span class="text-gray-500">
                                {{ $t('reports.customers_report.amount_paid') }}:
                                <strong class="text-teal-600 dark:text-teal-400">
                                    {{ formatMoney(group.total_amount_paid) }}
                                </strong>
                            </span>
                            <span class="text-gray-500">
                                {{ $t('reports.customers_report.amount_adjusted') }}:
                                <strong class="text-action-primary">
                                    {{ formatMoney(group.total_amount_adjusted) }}
                                </strong>
                            </span>
                            <span class="text-gray-500">
                                {{ $t('reports.customers_report.payment_remaining') }}:
                                <strong class="text-amber-600 dark:text-amber-400">
                                    {{ formatMoney(group.total_payment_remaining) }}
                                </strong>
                            </span>
                            <span v-if="group.total_bad_debt > 0" class="text-rose-600 font-bold">
                                {{ $t('reports.customers_report.bad_debt') }}:
                                {{ formatMoney(group.total_bad_debt) }}
                            </span>
                        </div>
                    </button>

                    <!-- Group Table Body (Collapsible) -->
                    <div v-show="openGroups[group.group_key]" class="overflow-x-auto">
                        <DataTable structured class="w-full text-xs text-start">
                            <thead
                                class="bg-gray-100/50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-y border-gray-200 dark:border-gray-700 font-bold"
                            >
                                <tr>
                                    <th class="py-3 px-3 text-start">#</th>
                                    <th class="py-3 px-3 text-start">
                                        {{ $t('reports.customers_report.center') }}
                                    </th>
                                    <th class="py-3 px-3 text-start">
                                        {{ $t('reports.customers_report.work_order_no') }}
                                    </th>
                                    <th class="py-3 px-3 text-start">
                                        {{ $t('reports.customers_report.vehicle_details') }}
                                    </th>
                                    <th class="py-3 px-3 text-start">
                                        {{ $t('reports.customers_report.customer_party') }}
                                    </th>
                                    <th class="py-3 px-3 text-start">
                                        {{ $t('reports.customers_report.check_in_date') }}
                                    </th>
                                    <th class="py-3 px-3 text-end">
                                        {{ $t('reports.customers_report.payment_due') }}
                                    </th>
                                    <th class="py-3 px-3 text-end">
                                        {{ $t('reports.customers_report.amount_paid') }}
                                    </th>
                                    <th class="py-3 px-3 text-end">
                                        {{ $t('reports.customers_report.amount_adjusted') }}
                                    </th>
                                    <th class="py-3 px-3 text-end">
                                        {{ $t('reports.customers_report.payment_remaining') }}
                                    </th>
                                    <th class="py-3 px-3 text-end">
                                        {{ $t('reports.customers_report.bad_debt') }}
                                    </th>
                                    <th class="py-3 px-3 text-center">
                                        {{ $t('reports.customers_report.payment_status') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <tr
                                    v-for="(row, idx) in group.items"
                                    :key="row.id"
                                    class="hover:bg-sky-50/30 dark:hover:bg-sky-950/20 transition-colors"
                                >
                                    <td class="py-3 px-3 text-gray-400 font-mono">{{ idx + 1 }}</td>
                                    <td
                                        class="py-3 px-3 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ row.center_name }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-1.5">
                                            <a
                                                :href="`/app/work-orders/${row.id}`"
                                                class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline"
                                            >
                                                {{ row.code }}
                                            </a>
                                            <span
                                                v-if="row.is_overdue"
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
                                                :title="
                                                    $t('reports.customers_report.overdue_badge')
                                                "
                                            >
                                                {{ $t('reports.customers_report.overdue_badge') }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="row.invoice"
                                            class="text-[11px] text-gray-400 font-mono mt-0.5"
                                        >
                                            <a
                                                :href="`/app/invoices/${row.invoice.id}`"
                                                class="hover:underline"
                                            >
                                                {{ row.invoice.invoice_number }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ row.vehicle.name || '-' }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span
                                                v-if="row.vehicle.plate_number"
                                                class="inline-block px-1.5 py-0.2 rounded font-mono font-bold text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 dir-ltr text-left"
                                            >
                                                {{ row.vehicle.plate_number }}
                                            </span>
                                            <span
                                                v-if="row.vehicle.odometer"
                                                class="text-[10px] text-gray-400 font-mono"
                                            >
                                                {{ formatMoney(row.vehicle.odometer) }}
                                                {{ $t('reports.customers_report.km') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-gray-900 dark:text-white">
                                                {{ row.customer.name }}
                                            </span>
                                            <span
                                                class="px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                                :class="
                                                    getCustomerTypeBadgeClass(row.customer.type)
                                                "
                                            >
                                                {{ getCustomerTypeLabel(row.customer.type) }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="row.customer.phone"
                                            class="text-[11px] text-gray-400 font-mono mt-0.5 dir-ltr text-end sm:text-start"
                                        >
                                            {{ row.customer.phone }}
                                        </div>
                                    </td>
                                    <td
                                        class="py-3 px-3 text-gray-600 dark:text-gray-400 font-mono whitespace-nowrap"
                                    >
                                        {{ row.entry_date }}
                                    </td>
                                    <td
                                        class="py-3 px-3 text-end font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap"
                                    >
                                        {{ formatMoney(row.payment_due) }}
                                    </td>
                                    <td
                                        class="py-3 px-3 text-end font-mono font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap"
                                    >
                                        {{ formatMoney(row.amount_paid) }}
                                    </td>
                                    <td
                                        class="py-3 px-3 text-end font-mono text-action-primary whitespace-nowrap"
                                    >
                                        {{ formatMoney(row.amount_adjusted) }}
                                    </td>
                                    <td
                                        class="py-3 px-3 text-end font-mono font-bold whitespace-nowrap"
                                        :class="
                                            row.payment_remaining > 0
                                                ? 'text-amber-600 dark:text-amber-400'
                                                : 'text-gray-400'
                                        "
                                    >
                                        {{ formatMoney(row.payment_remaining) }}
                                    </td>
                                    <td
                                        class="py-3 px-3 text-end font-mono whitespace-nowrap"
                                        :class="
                                            row.bad_debt > 0
                                                ? 'font-bold text-rose-600 dark:text-rose-400'
                                                : 'text-gray-400'
                                        "
                                    >
                                        {{ formatMoney(row.bad_debt) }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="getPaymentStatusBadgeClass(row.payment_status)"
                                        >
                                            {{
                                                $t(`reports.customers_report.${row.payment_status}`)
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>

            <!-- DATA SECTION: FLAT TABLE VIEW -->
            <div
                v-else-if="
                    filterForm.group_by === 'none' && records && records.data && records.data.length
                "
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm overflow-hidden print:hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-xs text-start">
                        <thead
                            class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 font-bold"
                        >
                            <tr>
                                <th class="py-3.5 px-3 text-start">#</th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.customers_report.center') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.customers_report.work_order_no') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.customers_report.vehicle_details') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.customers_report.customer_party') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.customers_report.check_in_date') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.customers_report.payment_due') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.customers_report.amount_paid') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.customers_report.amount_adjusted') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.customers_report.payment_remaining') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.customers_report.bad_debt') }}
                                </th>
                                <th class="py-3.5 px-3 text-center">
                                    {{ $t('reports.customers_report.payment_status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr
                                v-for="(row, idx) in records.data"
                                :key="row.id"
                                class="hover:bg-sky-50/30 dark:hover:bg-sky-950/20 transition-colors"
                            >
                                <td class="py-3 px-3 text-gray-400 font-mono">
                                    {{ (records.current_page - 1) * records.per_page + idx + 1 }}
                                </td>
                                <td class="py-3 px-3 text-gray-700 dark:text-gray-300 font-medium">
                                    {{ row.center_name }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <a
                                            :href="`/app/work-orders/${row.id}`"
                                            class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline"
                                        >
                                            {{ row.code }}
                                        </a>
                                        <span
                                            v-if="row.is_overdue"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
                                            :title="$t('reports.customers_report.overdue_badge')"
                                        >
                                            {{ $t('reports.customers_report.overdue_badge') }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="row.invoice"
                                        class="text-[11px] text-gray-400 font-mono mt-0.5"
                                    >
                                        <a
                                            :href="`/app/invoices/${row.invoice.id}`"
                                            class="hover:underline"
                                        >
                                            {{ row.invoice.invoice_number }}
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ row.vehicle.name || '-' }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span
                                            v-if="row.vehicle.plate_number"
                                            class="inline-block px-1.5 py-0.2 rounded font-mono font-bold text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 dir-ltr text-left"
                                        >
                                            {{ row.vehicle.plate_number }}
                                        </span>
                                        <span
                                            v-if="row.vehicle.odometer"
                                            class="text-[10px] text-gray-400 font-mono"
                                        >
                                            {{ formatMoney(row.vehicle.odometer) }}
                                            {{ $t('reports.customers_report.km') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            {{ row.customer.name }}
                                        </span>
                                        <span
                                            class="px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                            :class="getCustomerTypeBadgeClass(row.customer.type)"
                                        >
                                            {{ getCustomerTypeLabel(row.customer.type) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="row.customer.phone"
                                        class="text-[11px] text-gray-400 font-mono mt-0.5 dir-ltr text-end sm:text-start"
                                    >
                                        {{ row.customer.phone }}
                                    </div>
                                </td>
                                <td
                                    class="py-3 px-3 text-gray-600 dark:text-gray-400 font-mono whitespace-nowrap"
                                >
                                    {{ row.entry_date }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap"
                                >
                                    {{ formatMoney(row.payment_due) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap"
                                >
                                    {{ formatMoney(row.amount_paid) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono text-action-primary whitespace-nowrap"
                                >
                                    {{ formatMoney(row.amount_adjusted) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold whitespace-nowrap"
                                    :class="
                                        row.payment_remaining > 0
                                            ? 'text-amber-600 dark:text-amber-400'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{ formatMoney(row.payment_remaining) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono whitespace-nowrap"
                                    :class="
                                        row.bad_debt > 0
                                            ? 'font-bold text-rose-600 dark:text-rose-400'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{ formatMoney(row.bad_debt) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="getPaymentStatusBadgeClass(row.payment_status)"
                                    >
                                        {{ $t(`reports.customers_report.${row.payment_status}`) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination Bar -->
                <AppPagination
                    class="print:hidden"
                    :links="records.links || []"
                    :from="records.from"
                    :to="records.to"
                    :total="records.total"
                />
            </div>

            <!-- EMPTY STATE -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 p-12 text-center print:hidden"
            >
                <div
                    class="w-16 h-16 mx-auto rounded-full bg-sky-50 dark:bg-sky-950/40 text-sky-500 flex items-center justify-center mb-4"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">
                    {{ $t('reports.customers_report.no_records') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto mb-4">
                    {{ $t('reports.customers_report.no_records_hint') }}
                </p>
                <button
                    type="button"
                    @click="resetFilters"
                    class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition-colors"
                >
                    {{ $t('common.reset_filters') }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    kpis: {
        type: Object,
        default: () => ({
            total_records: 0,
            total_payment_due: 0,
            total_amount_paid: 0,
            total_amount_adjusted: 0,
            total_payment_remaining: 0,
            total_bad_debt: 0,
        }),
    },
    records: {
        type: Object,
        default: null,
    },
    groupedRecords: {
        type: Array,
        default: () => [],
    },
    centers: {
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
    companyInfo: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    date_type: props.filters.date_type || 'entry_date',
    center_id: props.filters.center_id || 'all',
    customer_id: props.filters.customer_id || 'all',
    customer_type: props.filters.customer_type || 'all',
    make_id: props.filters.make_id || 'all',
    model_id: props.filters.model_id || 'all',
    payment_status: props.filters.payment_status || 'all',
    group_by: props.filters.group_by || 'none',
    search: props.filters.search || '',
});

// Group Accordions State
const openGroups = ref({});

onMounted(() => {
    if (props.groupedRecords && props.groupedRecords.length) {
        props.groupedRecords.forEach((g) => {
            openGroups.value[g.group_key] = true;
        });
    }
});

const toggleGroup = (key) => {
    openGroups.value[key] = !openGroups.value[key];
};

const expandAll = () => {
    if (props.groupedRecords) {
        props.groupedRecords.forEach((g) => {
            openGroups.value[g.group_key] = true;
        });
    }
};

const collapseAll = () => {
    openGroups.value = {};
};

// Date Presets
const datePresets = computed(() => [
    { id: 'today', label: t('reports.customers_report.today') },
    { id: 'yesterday', label: t('reports.customers_report.yesterday') },
    { id: 'this_week', label: t('reports.customers_report.this_week') },
    { id: 'this_month', label: t('reports.customers_report.this_month') },
    { id: 'this_quarter', label: t('reports.customers_report.this_quarter') },
    { id: 'this_year', label: t('reports.customers_report.this_year') },
    { id: 'all', label: t('reports.customers_report.all_time') },
    { id: 'custom', label: t('reports.customers_report.custom_date') },
]);

// Options Computations
const dateTypeOptions = computed(() => [
    {
        id: 'entry_date',
        name: t('reports.customers_report.date_entry'),
        label: t('reports.customers_report.date_entry'),
    },
    {
        id: 'closed_at',
        name: t('reports.customers_report.date_closed'),
        label: t('reports.customers_report.date_closed'),
    },
    {
        id: 'created_at',
        name: t('reports.customers_report.date_created'),
        label: t('reports.customers_report.date_created'),
    },
]);

const centerOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.customers_report.all_centers'),
        label: t('reports.customers_report.all_centers'),
    },
    ...props.centers.map((c) => ({ id: String(c.id), name: c.name, label: c.name })),
]);

const customerOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.customers_report.all_customers'),
        label: t('reports.customers_report.all_customers'),
    },
    ...props.customers.map((c) => {
        const text = c.phone ? `${c.name} (${c.phone})` : c.name;
        return { id: String(c.id), name: text, label: text };
    }),
]);

const customerTypeOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.customers_report.all_customer_types'),
        label: t('reports.customers_report.all_customer_types'),
    },
    {
        id: 'individual',
        name: t('reports.customers_report.type_individual'),
        label: t('reports.customers_report.type_individual'),
    },
    {
        id: 'company',
        name: t('reports.customers_report.type_company'),
        label: t('reports.customers_report.type_company'),
    },
    {
        id: 'government',
        name: t('reports.customers_report.type_government'),
        label: t('reports.customers_report.type_government'),
    },
    {
        id: 'vip',
        name: t('reports.customers_report.type_vip'),
        label: t('reports.customers_report.type_vip'),
    },
]);

const paymentStatusOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.customers_report.all_payment_statuses'),
        label: t('reports.customers_report.all_payment_statuses'),
    },
    {
        id: 'paid',
        name: t('reports.customers_report.paid'),
        label: t('reports.customers_report.paid'),
    },
    {
        id: 'partial',
        name: t('reports.customers_report.partial'),
        label: t('reports.customers_report.partial'),
    },
    {
        id: 'unpaid',
        name: t('reports.customers_report.unpaid'),
        label: t('reports.customers_report.unpaid'),
    },
]);

const makeOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.customers_report.all_makes'),
        label: t('reports.customers_report.all_makes'),
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
            name: t('reports.customers_report.all_models'),
            label: t('reports.customers_report.all_models'),
        },
        ...list.map((m) => {
            const text = m.name_ar || m.name_en;
            return { id: String(m.id), name: text, label: text };
        }),
    ];
});

const groupByOptions = computed(() => [
    {
        id: 'none',
        name: t('reports.customers_report.group_none'),
        label: t('reports.customers_report.group_none'),
    },
    {
        id: 'customer',
        name: t('reports.customers_report.group_customer'),
        label: t('reports.customers_report.group_customer'),
    },
    {
        id: 'customer_type',
        name: t('reports.customers_report.group_customer_type'),
        label: t('reports.customers_report.group_customer_type'),
    },
    {
        id: 'center',
        name: t('reports.customers_report.group_center'),
        label: t('reports.customers_report.group_center'),
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
        '/app/reports/customers',
        {
            range: filterForm.range,
            start_date: filterForm.start_date || undefined,
            end_date: filterForm.end_date || undefined,
            date_type: filterForm.date_type,
            center_id: filterForm.center_id || 'all',
            customer_id: filterForm.customer_id || 'all',
            customer_type: filterForm.customer_type || 'all',
            make_id: filterForm.make_id || 'all',
            model_id: filterForm.model_id || 'all',
            payment_status: filterForm.payment_status || 'all',
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
    filterForm.date_type = 'entry_date';
    filterForm.center_id = 'all';
    filterForm.customer_id = 'all';
    filterForm.customer_type = 'all';
    filterForm.make_id = 'all';
    filterForm.model_id = 'all';
    filterForm.payment_status = 'all';
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
    if (filterForm.customer_id) params.append('customer_id', filterForm.customer_id);
    if (filterForm.customer_type) params.append('customer_type', filterForm.customer_type);
    if (filterForm.make_id) params.append('make_id', filterForm.make_id);
    if (filterForm.model_id) params.append('model_id', filterForm.model_id);
    if (filterForm.payment_status) params.append('payment_status', filterForm.payment_status);
    if (filterForm.search) params.append('search', filterForm.search);

    return `/app/reports/customers/export?${params.toString()}`;
});

const handlePrint = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.delete('page');
    window.open(url.toString(), '_blank');
};

const printDate = computed(() => {
    return new Date().toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const printRows = computed(() => {
    if (props.groupedRecords && props.groupedRecords.length) {
        return props.groupedRecords.flatMap((g) => g.items || []);
    }
    return props.records?.data || [];
});

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const getCustomerTypeLabel = (type) => {
    switch (type) {
        case 'company':
            return t('reports.customers_report.type_company');
        case 'government':
            return t('reports.customers_report.type_government');
        case 'vip':
            return t('reports.customers_report.type_vip');
        default:
            return t('reports.customers_report.type_individual');
    }
};

const getCustomerTypeBadgeClass = (type) => {
    switch (type) {
        case 'company':
            return 'bg-purple-100 text-purple-800';
        case 'government':
            return 'bg-selection-bg text-selection-text';
        case 'vip':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
    }
};

const getPaymentStatusBadgeClass = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'partial':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        default:
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
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

    .print-section {
        display: block !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    th,
    td {
        border: 1px solid #d1d5db !important;
        padding: 4px 6px !important;
    }

    @page {
        margin: 1.2cm;
        size: landscape;
    }
}
</style>
