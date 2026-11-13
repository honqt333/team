<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.cash_register_report.title')"
                :subtitle="$t('reports.cash_register_report.subtitle')"
                gradientFrom="from-slate-700"
                gradientTo="to-gray-900"
                glowFrom="from-slate-600"
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
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            @click="handlePrint"
                            :label="$t('reports.cash_register_report.print_report')"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-slate-700 to-gray-800 hover:from-slate-800 hover:to-gray-900 text-white text-xs font-bold transition-all shadow-lg shadow-gray-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.cash_register_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.cash_register_report.official_report')"
                    :subtitle="dateRangeSubtitle"
                    :centerName="selectedCenterName"
                />

                <!-- Print Totals Summary -->
                <div
                    class="my-4 p-3 bg-gray-50 border border-gray-300 rounded text-xs grid grid-cols-4 gap-2 text-center"
                >
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.cash_register_report.total_records') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ kpis.total_records }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.cash_register_report.total_debit') }}
                        </span>
                        <span class="font-bold text-emerald-700">
                            {{ formatCurrency(kpis.total_debit) }}
                            {{ $t('reports.cash_register_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.cash_register_report.total_credit') }}
                        </span>
                        <span class="font-bold text-rose-700">
                            {{ formatCurrency(kpis.total_credit) }}
                            {{ $t('reports.cash_register_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.cash_register_report.net_balance') }}
                        </span>
                        <span
                            class="font-black"
                            :class="kpis.net_balance >= 0 ? 'text-emerald-700' : 'text-rose-700'"
                        >
                            {{ formatCurrency(kpis.net_balance) }}
                            {{ $t('reports.cash_register_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Printable Table -->
                <DataTable
                    structured
                    class="w-full text-[10px] border-collapse border border-gray-400 mb-4"
                >
                    <thead>
                        <tr class="bg-gray-100 font-bold">
                            <th class="border border-gray-400 p-1 text-center">#</th>
                            <th class="border border-gray-400 p-1 text-start">
                                {{ $t('reports.cash_register_report.reference_no') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-start">
                                {{ $t('reports.cash_register_report.description') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-center">
                                {{ $t('reports.cash_register_report.category') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-center">
                                {{ $t('reports.cash_register_report.payment_mode') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-center">
                                {{ $t('reports.cash_register_report.transaction_date') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-end">
                                {{ $t('reports.cash_register_report.debit_amount') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-end">
                                {{ $t('reports.cash_register_report.credit_amount') }}
                            </th>
                            <th class="border border-gray-400 p-1 text-center">
                                {{ $t('reports.cash_register_report.user_modified') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in items.data" :key="'print-row-' + row.id">
                            <td class="border border-gray-400 p-1 text-center">{{ idx + 1 }}</td>
                            <td class="border border-gray-400 p-1 font-mono font-bold">
                                {{ row.ref_no }}
                            </td>
                            <td class="border border-gray-400 p-1">{{ row.description }}</td>
                            <td class="border border-gray-400 p-1 text-center">
                                {{ getCategoryLabel(row.category, row.category_label) }}
                            </td>
                            <td class="border border-gray-400 p-1 text-center">
                                {{ getMethodLabel(row.payment_mode, row.payment_mode_label) }}
                            </td>
                            <td class="border border-gray-400 p-1 text-center font-mono">
                                {{ row.transaction_date }}
                            </td>
                            <td class="border border-gray-400 p-1 text-end font-mono">
                                {{ row.debit > 0 ? formatCurrency(row.debit) : '-' }}
                            </td>
                            <td class="border border-gray-400 p-1 text-end font-mono">
                                {{ row.credit > 0 ? formatCurrency(row.credit) : '-' }}
                            </td>
                            <td class="border border-gray-400 p-1 text-center">
                                {{ row.user_name }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <StandardReportFooter
                    :userName="$page.props.auth?.user?.name || ''"
                    :companyName="companyInfo.name || ''"
                />
            </div>

            <!-- Screen View Content (print:hidden) -->
            <div class="print:hidden space-y-6">
                <!-- Top 6 KPI Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- Total Records -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.cash_register_report.total_records') }}
                        </div>
                        <div class="mt-2 text-xl font-black text-gray-900 dark:text-white">
                            {{ kpis.total_records }}
                        </div>
                    </div>

                    <!-- Total Inflow (Debit) -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.cash_register_report.total_debit') }}
                        </div>
                        <div
                            class="mt-2 text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono"
                        >
                            {{ formatCurrency(kpis.total_debit) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.cash_register_report.currency') }}
                        </div>
                    </div>

                    <!-- Total Outflow (Credit) -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.cash_register_report.total_credit') }}
                        </div>
                        <div
                            class="mt-2 text-xl font-black text-rose-600 dark:text-rose-400 font-mono"
                        >
                            {{ formatCurrency(kpis.total_credit) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.cash_register_report.currency') }}
                        </div>
                    </div>

                    <!-- Cash Balance (In Drawer) -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.cash_register_report.cash_balance') }}
                        </div>
                        <div
                            class="mt-2 text-xl font-black text-amber-600 dark:text-amber-400 font-mono"
                        >
                            {{ formatCurrency(kpis.cash_balance) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.cash_register_report.currency') }}
                        </div>
                    </div>

                    <!-- Electronic Payments -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.cash_register_report.electronic_balance') }}
                        </div>
                        <div
                            class="mt-2 text-xl font-black text-blue-600 dark:text-blue-400 font-mono"
                        >
                            {{ formatCurrency(kpis.electronic_balance) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.cash_register_report.currency') }}
                        </div>
                    </div>

                    <!-- Net Balance (Hero KPI) -->
                    <div
                        class="p-4 rounded-2xl border shadow-sm"
                        :class="
                            kpis.net_balance >= 0
                                ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800'
                                : 'bg-red-50/60 dark:bg-red-950/20 border-red-200 dark:border-red-800'
                        "
                    >
                        <div
                            class="text-[11px] font-bold truncate"
                            :class="
                                kpis.net_balance >= 0
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : 'text-red-700 dark:text-red-300'
                            "
                        >
                            {{ $t('reports.cash_register_report.net_balance') }}
                        </div>
                        <div
                            class="mt-2 text-xl font-black font-mono"
                            :class="
                                kpis.net_balance >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            {{ formatCurrency(kpis.net_balance) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.cash_register_report.currency') }}
                        </div>
                    </div>
                </div>

                <!-- Filters Console -->
                <div
                    class="p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm space-y-4"
                >
                    <!-- Date Presets Row -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.cash_register_report.date_range') }}
                        </label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="preset in datePresets"
                                :key="preset.id"
                                type="button"
                                @click="selectPreset(preset.id)"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                                :class="
                                    currentRange === preset.id
                                        ? 'bg-slate-800 text-white font-bold shadow-sm dark:bg-slate-200 dark:text-gray-900'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                "
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Custom Date Picker when range is 'custom' -->
                    <div
                        v-if="currentRange === 'custom'"
                        class="pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('reports.cash_register_report.date_from') }}
                            </label>
                            <CustomDatePicker
                                v-model="customStartDate"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('reports.cash_register_report.date_to') }}
                            </label>
                            <CustomDatePicker
                                v-model="customEndDate"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Secondary Filters Grid -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end pt-2 border-t border-gray-100 dark:border-gray-700"
                    >
                        <!-- Center Filter -->
                        <div class="md:col-span-3">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('reports.cash_register_report.center') }}
                            </label>
                            <SearchableSelect
                                v-model="selectedCenter"
                                :options="centerOptions"
                                :placeholder="$t('reports.cash_register_report.all_centers')"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Payment Method Filter -->
                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('reports.cash_register_report.payment_mode') }}
                            </label>
                            <SearchableSelect
                                v-model="selectedMethod"
                                :options="methodOptions"
                                :placeholder="$t('reports.cash_register_report.all_methods')"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Transaction Type Filter -->
                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('reports.cash_register_report.transaction_type') }}
                            </label>
                            <SearchableSelect
                                v-model="selectedType"
                                :options="typeOptions"
                                :placeholder="$t('reports.cash_register_report.all_types')"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- User / Cashier Filter -->
                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('reports.cash_register_report.user') }}
                            </label>
                            <SearchableSelect
                                v-model="selectedUser"
                                :options="userOptions"
                                :placeholder="$t('reports.cash_register_report.all_users')"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Search Input -->
                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('common.search') }}
                            </label>
                            <input
                                type="text"
                                v-model="searchInput"
                                @keydown.enter="applyFilters"
                                :placeholder="$t('reports.cash_register_report.search')"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-focus-ring"
                            />
                        </div>

                        <!-- Reset Button -->
                        <div class="md:col-span-1">
                            <button
                                type="button"
                                @click="resetFilters"
                                class="w-full p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold transition-all shadow-sm flex items-center justify-center"
                                :title="$t('reports.cash_register_report.reset_filters')"
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
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Table Section -->
                <div
                    class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden"
                >
                    <!-- Empty State -->
                    <div v-if="!items.data || items.data.length === 0" class="p-12 text-center">
                        <svg
                            class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400">
                            {{ $t('reports.cash_register_report.no_records') }}
                        </p>
                    </div>

                    <!-- Table with Records -->
                    <div v-else class="overflow-x-auto">
                        <DataTable structured class="w-full text-xs text-start">
                            <thead>
                                <tr
                                    class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 text-gray-500 dark:text-gray-400 font-bold"
                                >
                                    <th class="py-3 px-4 text-center">#</th>
                                    <th class="py-3 px-4 text-start">
                                        {{ $t('reports.cash_register_report.reference_no') }}
                                    </th>
                                    <th class="py-3 px-4 text-start">
                                        {{ $t('reports.cash_register_report.description') }}
                                    </th>
                                    <th class="py-3 px-4 text-center">
                                        {{ $t('reports.cash_register_report.category') }}
                                    </th>
                                    <th class="py-3 px-4 text-center">
                                        {{ $t('reports.cash_register_report.payment_mode') }}
                                    </th>
                                    <th class="py-3 px-4 text-center">
                                        {{ $t('reports.cash_register_report.transaction_date') }}
                                    </th>
                                    <th
                                        class="py-3 px-4 text-end text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ $t('reports.cash_register_report.debit_amount') }}
                                    </th>
                                    <th class="py-3 px-4 text-end text-rose-600 dark:text-rose-400">
                                        {{ $t('reports.cash_register_report.credit_amount') }}
                                    </th>
                                    <th class="py-3 px-4 text-center">
                                        {{ $t('reports.cash_register_report.user_modified') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <tr
                                    v-for="(row, idx) in items.data"
                                    :key="row.id"
                                    class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
                                >
                                    <!-- Row Number -->
                                    <td class="py-3 px-4 text-center text-gray-400 font-mono">
                                        {{ (items.current_page - 1) * items.per_page + idx + 1 }}
                                    </td>

                                    <!-- Ref No -->
                                    <td
                                        class="py-3 px-4 text-start font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ row.ref_no }}
                                    </td>

                                    <!-- Description -->
                                    <td
                                        class="py-3 px-4 text-start font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        <div>{{ row.description }}</div>
                                        <div class="text-[10px] text-gray-400 mt-0.5">
                                            {{ row.center_name }}
                                        </div>
                                    </td>

                                    <!-- Category Badge -->
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                            :class="categoryBadgeClass(row.category)"
                                        >
                                            {{ getCategoryLabel(row.category, row.category_label) }}
                                        </span>
                                    </td>

                                    <!-- Payment Mode Badge -->
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                getMethodLabel(
                                                    row.payment_mode,
                                                    row.payment_mode_label
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <!-- Date & Time -->
                                    <td
                                        class="py-3 px-4 text-center font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                    >
                                        {{ row.transaction_date }}
                                    </td>

                                    <!-- Debit (Inflow) -->
                                    <td
                                        class="py-3 px-4 text-end font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ row.debit > 0 ? formatCurrency(row.debit) : '-' }}
                                    </td>

                                    <!-- Credit (Outflow) -->
                                    <td
                                        class="py-3 px-4 text-end font-mono font-bold text-rose-600 dark:text-rose-400"
                                    >
                                        {{ row.credit > 0 ? formatCurrency(row.credit) : '-' }}
                                    </td>

                                    <!-- User -->
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-gray-700 dark:text-gray-300 text-[11px] font-medium"
                                        >
                                            <span
                                                class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center text-[10px] font-black"
                                            >
                                                {{ (row.user_name || 'U')[0] }}
                                            </span>
                                            <span>{{ row.user_name }}</span>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>

                    <!-- Pagination -->
                    <AppPagination
                        class="print:hidden"
                        :links="items.links || []"
                        :from="items.from"
                        :to="items.to"
                        :total="items.total"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { usePrint } from '@/Composables/usePrint';

const { t } = useI18n();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    kpis: {
        type: Object,
        default: () => ({}),
    },
    items: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    centers: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    companyInfo: {
        type: Object,
        default: () => ({}),
    },
});

// Print Setup
const { printDocument, formatCurrency: printFormatCurrency } = usePrint();
const handlePrint = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.delete('page');
    window.open(url.toString(), '_blank');
};

const formatCurrency = (val) => {
    return printFormatCurrency(Number(val) || 0);
};

// Filter State
const currentRange = ref(props.filters.range || 'this_month');
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');
const selectedCenter = ref(props.filters.center_id || 'all');
const selectedMethod = ref(props.filters.payment_method || 'all');
const selectedType = ref(props.filters.transaction_type || 'all');
const selectedUser = ref(props.filters.user_id || 'all');
const searchInput = ref(props.filters.search || '');

// Date Presets
const datePresets = computed(() => [
    { id: 'today', label: t('reports.cash_register_report.today') },
    { id: 'yesterday', label: t('reports.cash_register_report.yesterday') },
    { id: 'last_7_days', label: t('reports.cash_register_report.last_7_days') },
    { id: 'this_week', label: t('reports.cash_register_report.this_week') },
    { id: 'this_month', label: t('reports.cash_register_report.this_month') },
    { id: 'last_month', label: t('reports.cash_register_report.last_month') },
    { id: 'this_year', label: t('reports.cash_register_report.this_year') },
    { id: 'all_time', label: t('reports.cash_register_report.all_time') },
    { id: 'custom', label: t('reports.cash_register_report.custom') },
]);

const selectPreset = (presetId) => {
    currentRange.value = presetId;
    if (presetId !== 'custom') {
        applyFilters();
    }
};

// Category and Payment Method Translation Helpers
const getCategoryLabel = (category, fallback) => {
    if (!category) return fallback || '';
    const key = 'reports.cash_register_report.categories.' + category;
    const translated = t(key);
    if (translated && translated !== key) return translated;
    return fallback || category;
};

const getMethodLabel = (method, fallback) => {
    if (!method) return fallback || '';
    const key = 'reports.cash_register_report.methods.' + method;
    const translated = t(key);
    if (translated && translated !== key) return translated;
    return fallback || method;
};

// Center Options
const centerOptions = computed(() => [
    { value: 'all', label: t('reports.cash_register_report.all_centers') },
    ...props.centers.map((c) => ({
        value: c.id,
        label: c.name,
    })),
]);

// Payment Method Options
const methodOptions = computed(() => [
    { value: 'all', label: t('reports.cash_register_report.all_methods') },
    { value: 'cash', label: t('reports.cash_register_report.methods.cash') },
    { value: 'mada', label: t('reports.cash_register_report.methods.mada') },
    { value: 'visa', label: t('reports.cash_register_report.methods.visa') },
    { value: 'transfer', label: t('reports.cash_register_report.methods.transfer') },
    { value: 'credit', label: t('reports.cash_register_report.methods.credit') },
    { value: 'cheque', label: t('reports.cash_register_report.methods.cheque') },
    { value: 'apple_pay', label: t('reports.cash_register_report.methods.apple_pay') },
    { value: 'stc_pay', label: t('reports.cash_register_report.methods.stc_pay') },
    { value: 'tabby', label: t('reports.cash_register_report.methods.tabby') },
    { value: 'tamara', label: t('reports.cash_register_report.methods.tamara') },
    { value: 'other', label: t('reports.cash_register_report.methods.other') },
]);

// Transaction Type Options
const typeOptions = computed(() => [
    { value: 'all', label: t('reports.cash_register_report.all_types') },
    { value: 'sales_payment', label: t('reports.cash_register_report.categories.sales_payment') },
    {
        value: 'purchase_payment',
        label: t('reports.cash_register_report.categories.purchase_payment'),
    },
    { value: 'refund', label: t('reports.cash_register_report.categories.refund') },
    { value: 'revenue', label: t('reports.cash_register_report.categories.revenue') },
    { value: 'expense', label: t('reports.cash_register_report.categories.expense') },
    { value: 'bad_debt', label: t('reports.cash_register_report.categories.bad_debt') },
]);

// User Options
const userOptions = computed(() => [
    { value: 'all', label: t('reports.cash_register_report.all_users') },
    ...props.users.map((u) => ({
        value: u.id,
        label: u.name,
    })),
]);

const selectedCenterName = computed(() => {
    if (selectedCenter.value === 'all') {
        return t('reports.cash_register_report.all_centers');
    }
    const found = props.centers.find((c) => String(c.id) === String(selectedCenter.value));
    return found ? found.name : '';
});

const dateRangeSubtitle = computed(() => {
    if (props.filters.start_date && props.filters.end_date) {
        return t('reports.date_from_to', {
            from: props.filters.start_date,
            to: props.filters.end_date,
        });
    }
    const preset = datePresets.value.find((p) => p.id === currentRange.value);
    if (preset) return preset.label;
    const key = 'reports.cash_register_report.' + currentRange.value;
    const translated = t(key);
    return translated && translated !== key
        ? translated
        : t('reports.cash_register_report.this_month');
});

// Category Badges
const categoryBadgeClass = (category) => {
    switch (category) {
        case 'sales_payment':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'purchase_payment':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'refund':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
        case 'revenue':
            return 'bg-selection-bg text-selection-text';
        case 'expense':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300';
        case 'payroll':
            return 'bg-purple-100 text-purple-800';
        case 'bad_debt':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Filter Application
const applyFilters = () => {
    router.get(
        route('app.reports.cash-register'),
        {
            range: currentRange.value,
            start_date: currentRange.value === 'custom' ? customStartDate.value : null,
            end_date: currentRange.value === 'custom' ? customEndDate.value : null,
            center_id: selectedCenter.value !== 'all' ? selectedCenter.value : null,
            payment_method: selectedMethod.value !== 'all' ? selectedMethod.value : null,
            transaction_type: selectedType.value !== 'all' ? selectedType.value : null,
            user_id: selectedUser.value !== 'all' ? selectedUser.value : null,
            search: searchInput.value || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const resetFilters = () => {
    currentRange.value = 'this_month';
    customStartDate.value = '';
    customEndDate.value = '';
    selectedCenter.value = 'all';
    selectedMethod.value = 'all';
    selectedType.value = 'all';
    selectedUser.value = 'all';
    searchInput.value = '';

    router.get(
        route('app.reports.cash-register'),
        {},
        {
            preserveState: false,
            replace: true,
        }
    );
};

const changePage = (url) => {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// Export CSV URL
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    params.set('range', currentRange.value);
    if (currentRange.value === 'custom') {
        if (customStartDate.value) params.set('start_date', customStartDate.value);
        if (customEndDate.value) params.set('end_date', customEndDate.value);
    }
    if (selectedCenter.value !== 'all') params.set('center_id', selectedCenter.value);
    if (selectedMethod.value !== 'all') params.set('payment_method', selectedMethod.value);
    if (selectedType.value !== 'all') params.set('transaction_type', selectedType.value);
    if (selectedUser.value !== 'all') params.set('user_id', selectedUser.value);
    if (searchInput.value) params.set('search', searchInput.value);

    return route('app.reports.cash-register.export') + '?' + params.toString();
});
</script>
