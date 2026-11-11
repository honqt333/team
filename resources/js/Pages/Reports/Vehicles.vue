<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.vehicles_report.title')"
                :subtitle="$t('reports.vehicles_report.subtitle')"
                gradientFrom="from-cyan-600"
                gradientTo="to-sky-700"
                glowFrom="from-cyan-500"
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
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            @click="handlePrint"
                            :label="$t('reports.vehicles_report.print_report')"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-600 hover:from-cyan-700 hover:to-sky-700 text-white text-xs font-bold transition-all shadow-lg shadow-cyan-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.vehicles_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.vehicles_report.official_report')"
                    :subtitle="dateRangeSubtitle"
                    :centerName="selectedCenterName"
                />

                <!-- Print Totals Summary -->
                <div
                    class="my-4 p-3 bg-gray-50 border border-gray-300 rounded text-xs grid grid-cols-6 gap-2 text-center"
                >
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.vehicles_report.total_records') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatNumber(kpis.total_records) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.vehicles_report.total_vehicles') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatNumber(kpis.total_vehicles) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.vehicles_report.total_tickets') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatNumber(kpis.total_tickets) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.vehicles_report.total_service_cost') }}
                        </span>
                        <span class="font-bold text-cyan-800">
                            {{ formatCurrency(kpis.total_service_cost) }}
                            {{ $t('reports.vehicles_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.vehicles_report.total_spare_part_cost') }}
                        </span>
                        <span class="font-bold text-emerald-800">
                            {{ formatCurrency(kpis.total_spare_part_cost) }}
                            {{ $t('reports.vehicles_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block font-bold">
                            {{ $t('reports.vehicles_report.grand_total') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatCurrency(kpis.grand_total) }}
                            {{ $t('reports.vehicles_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Print Table Content -->
                <div v-if="groups && groups.length > 0" class="space-y-4">
                    <div
                        v-for="group in groups"
                        :key="'print-grp-' + group.group_key"
                        class="space-y-2"
                    >
                        <div
                            class="bg-gray-100 p-2 font-bold text-xs flex justify-between items-center border border-gray-300"
                        >
                            <span>{{ group.group_name }}</span>
                            <span>
                                {{ $t('reports.vehicles_report.total_records') }}:
                                {{ group.summary.total_records }} |
                                {{ $t('reports.vehicles_report.total_service_cost') }}:
                                {{ formatCurrency(group.summary.total_service_cost) }} |
                                {{ $t('reports.vehicles_report.total_spare_part_cost') }}:
                                {{ formatCurrency(group.summary.total_spare_part_cost) }} |
                                {{ $t('reports.vehicles_report.grand_total') }}:
                                {{ formatCurrency(group.summary.grand_total) }}
                                {{ $t('reports.vehicles_report.currency') }}
                            </span>
                        </div>
                        <DataTable
                            structured
                            class="w-full text-[10px] border-collapse border border-gray-300"
                        >
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-300">
                                    <th class="border border-gray-300 p-1 text-center">#</th>
                                    <th class="border border-gray-300 p-1 text-right">
                                        {{ $t('reports.vehicles_report.vehicle') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-center">
                                        {{ $t('reports.vehicles_report.plate_no') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-center">
                                        {{ $t('reports.vehicles_report.ticket') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-right">
                                        {{ $t('reports.vehicles_report.section') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-right">
                                        {{ $t('reports.vehicles_report.service') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-center">
                                        {{ $t('reports.vehicles_report.due_date') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-right">
                                        {{ $t('reports.vehicles_report.service_cost') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-right">
                                        {{ $t('reports.vehicles_report.spare_part_cost') }}
                                    </th>
                                    <th class="border border-gray-300 p-1 text-right">
                                        {{ $t('reports.vehicles_report.total') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, idx) in group.items"
                                    :key="'print-row-' + row.id"
                                    class="border-b border-gray-200"
                                >
                                    <td class="border border-gray-300 p-1 text-center">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-right">
                                        {{ row.vehicle_display_name }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-center font-mono">
                                        {{ row.plate_no }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-center font-mono">
                                        {{ row.ticket_no }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-right">
                                        {{ row.section_name }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-right">
                                        {{ row.service_name }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-center font-mono">
                                        {{ row.due_date }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-right font-mono">
                                        {{ formatCurrency(row.service_cost) }}
                                    </td>
                                    <td class="border border-gray-300 p-1 text-right font-mono">
                                        {{ formatCurrency(row.spare_part_cost) }}
                                    </td>
                                    <td
                                        class="border border-gray-300 p-1 text-right font-mono font-bold"
                                    >
                                        {{ formatCurrency(row.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>

                <div v-else>
                    <DataTable
                        structured
                        class="w-full text-[10px] border-collapse border border-gray-300"
                    >
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-300">
                                <th class="border border-gray-300 p-1 text-center">#</th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.center') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.vehicle') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-center">
                                    {{ $t('reports.vehicles_report.plate_no') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-center">
                                    {{ $t('reports.vehicles_report.ticket') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.section') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.service') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-center">
                                    {{ $t('reports.vehicles_report.due_date') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.service_cost') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.spare_part_cost') }}
                                </th>
                                <th class="border border-gray-300 p-1 text-right">
                                    {{ $t('reports.vehicles_report.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, idx) in items.data"
                                :key="'print-flat-' + row.id"
                                class="border-b border-gray-200"
                            >
                                <td class="border border-gray-300 p-1 text-center">
                                    {{ idx + 1 }}
                                </td>
                                <td class="border border-gray-300 p-1 text-right">
                                    {{ row.shop_name }}
                                </td>
                                <td class="border border-gray-300 p-1 text-right">
                                    {{ row.vehicle_display_name }}
                                </td>
                                <td class="border border-gray-300 p-1 text-center font-mono">
                                    {{ row.plate_no }}
                                </td>
                                <td class="border border-gray-300 p-1 text-center font-mono">
                                    {{ row.ticket_no }}
                                </td>
                                <td class="border border-gray-300 p-1 text-right">
                                    {{ row.section_name }}
                                </td>
                                <td class="border border-gray-300 p-1 text-right">
                                    {{ row.service_name }}
                                </td>
                                <td class="border border-gray-300 p-1 text-center font-mono">
                                    {{ row.due_date }}
                                </td>
                                <td class="border border-gray-300 p-1 text-right font-mono">
                                    {{ formatCurrency(row.service_cost) }}
                                </td>
                                <td class="border border-gray-300 p-1 text-right font-mono">
                                    {{ formatCurrency(row.spare_part_cost) }}
                                </td>
                                <td
                                    class="border border-gray-300 p-1 text-right font-mono font-bold"
                                >
                                    {{ formatCurrency(row.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <StandardReportFooter />
            </div>

            <!-- Screen KPI Summary Cards (Audit & UX Standards) -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 print:hidden">
                <!-- Total Records -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm transition-all hover:shadow-md"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-2"
                    >
                        <span class="text-xs font-semibold">
                            {{ $t('reports.vehicles_report.total_records') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center"
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
                </div>

                <!-- Unique Vehicles -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm transition-all hover:shadow-md"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-2"
                    >
                        <span class="text-xs font-semibold">
                            {{ $t('reports.vehicles_report.total_vehicles') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 flex items-center justify-center"
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
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-gray-900 dark:text-white font-mono">
                        {{ formatNumber(kpis.total_vehicles) }}
                    </div>
                </div>

                <!-- Total Tickets -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm transition-all hover:shadow-md"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-2"
                    >
                        <span class="text-xs font-semibold">
                            {{ $t('reports.vehicles_report.total_tickets') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center"
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
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="text-xl font-black text-gray-900 dark:text-white font-mono">
                        {{ formatNumber(kpis.total_tickets) }}
                    </div>
                </div>

                <!-- Total Service Cost -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm transition-all hover:shadow-md"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-2"
                    >
                        <span class="text-xs font-semibold">
                            {{ $t('reports.vehicles_report.total_service_cost') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 flex items-center justify-center"
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
                    <div class="text-xl font-black text-cyan-600 dark:text-cyan-400 font-mono">
                        {{ formatCurrency(kpis.total_service_cost) }}
                        <span class="text-[10px] font-normal text-gray-400">
                            {{ $t('reports.vehicles_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Total Spare Part Cost -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm transition-all hover:shadow-md"
                >
                    <div
                        class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-2"
                    >
                        <span class="text-xs font-semibold">
                            {{ $t('reports.vehicles_report.total_spare_part_cost') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center"
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
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono"
                    >
                        {{ formatCurrency(kpis.total_spare_part_cost) }}
                        <span class="text-[10px] font-normal text-gray-400">
                            {{ $t('reports.vehicles_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Grand Total -->
                <div
                    class="bg-gradient-to-br from-cyan-600 to-sky-700 rounded-2xl p-4 text-white shadow-lg shadow-cyan-600/20 transition-all hover:shadow-xl"
                >
                    <div class="flex items-center justify-between text-cyan-100 mb-2">
                        <span class="text-xs font-semibold">
                            {{ $t('reports.vehicles_report.grand_total') }}
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center"
                        >
                            <svg
                                class="w-4 h-4 text-white"
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
                    <div class="text-xl font-black text-white font-mono">
                        {{ formatCurrency(kpis.grand_total) }}
                        <span class="text-[10px] font-normal text-cyan-200">
                            {{ $t('reports.vehicles_report.currency') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Standard Unified Filter Console -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-5 shadow-sm space-y-4 print:hidden"
            >
                <!-- Top Row: Date Presets & Custom Range -->
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4"
                >
                    <!-- Quick Date Range Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 me-2">
                            {{ $t('reports.vehicles_report.date_range') }}:
                        </span>
                        <button
                            v-for="preset in datePresets"
                            :key="preset.id"
                            type="button"
                            @click="selectDatePreset(preset.id)"
                            :class="[
                                currentRange === preset.id
                                    ? 'bg-cyan-600 text-white shadow-sm shadow-cyan-600/30'
                                    : 'bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                            ]"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <!-- Reset Filters Button -->
                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all border border-gray-200 dark:border-gray-700"
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
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>{{ $t('reports.vehicles_report.reset_filters') }}</span>
                    </button>
                </div>

                <!-- Custom Date Range Pickers (if custom selected) -->
                <div
                    v-if="currentRange === 'custom'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200/60 dark:border-gray-700/40"
                >
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.date_from') }}
                        </label>
                        <CustomDatePicker
                            v-model="customStartDate"
                            class="w-full text-xs"
                            @update:modelValue="applyFilters"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.date_to') }}
                        </label>
                        <CustomDatePicker
                            v-model="customEndDate"
                            class="w-full text-xs"
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Filter Controls Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Center/Shop Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="selectedCenter"
                            :options="centerOptions"
                            :placeholder="$t('reports.vehicles_report.all_centers')"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Vehicle Make Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.make') }}
                        </label>
                        <SearchableSelect
                            v-model="selectedMake"
                            :options="makeOptions"
                            :placeholder="$t('reports.vehicles_report.all_makes')"
                            @update:modelValue="onMakeChange"
                        />
                    </div>

                    <!-- Vehicle Model Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.model') }}
                        </label>
                        <SearchableSelect
                            v-model="selectedModel"
                            :options="filteredModelOptions"
                            :placeholder="$t('reports.vehicles_report.all_models')"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Service Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.service_status') }}
                        </label>
                        <SearchableSelect
                            v-model="selectedServiceStatus"
                            :options="serviceStatusOptions"
                            :placeholder="$t('reports.vehicles_report.all_service_statuses')"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Group By Selector -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="selectedGroupBy"
                            :options="groupByOptions"
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Ticket Number -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.ticket_no') }}
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="ticketNoInput"
                                @keyup.enter="applyFilters"
                                placeholder="WO-..."
                                class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 font-mono"
                            />
                        </div>
                    </div>

                    <!-- Plate Number -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1"
                        >
                            {{ $t('reports.vehicles_report.plate_no') }}
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="plateNoInput"
                                @keyup.enter="applyFilters"
                                placeholder="1234 ABC"
                                class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            />
                        </div>
                    </div>

                    <!-- Overdue & Warehouse Parts Toggles -->
                    <div class="flex flex-wrap items-center gap-4 pt-5 sm:col-span-2 lg:col-span-2">
                        <!-- Overdue Toggle -->
                        <AppToggle
                            v-model="isOverdueInput"
                            size="sm"
                            color="danger"
                            :label="$t('reports.vehicles_report.overdue')"
                            @change="applyFilters"
                        />

                        <!-- Warehouse Parts Only Toggle -->
                        <AppToggle
                            v-model="warehousePartsOnlyInput"
                            size="sm"
                            color="success"
                            :label="$t('reports.vehicles_report.warehouse_parts_only')"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <!-- Bottom Row: Direct Search Bar -->
                <div class="relative pt-1">
                    <div
                        class="absolute inset-y-0 start-0 pt-1 ps-3.5 flex items-center pointer-events-none"
                    >
                        <svg
                            class="w-4 h-4 text-gray-400"
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
                    <input
                        type="text"
                        v-model="searchInput"
                        @keyup.enter="applyFilters"
                        :placeholder="$t('reports.vehicles_report.search_placeholder')"
                        class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white ps-10 pe-20 py-2.5 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    />
                    <button
                        type="button"
                        @click="applyFilters"
                        class="absolute end-1.5 top-2.5 px-3 py-1 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm"
                    >
                        {{ $t('reports.vehicles_report.apply_filters') }}
                    </button>
                </div>
            </div>

            <!-- Group By Controls & Accordions (When Grouped) -->
            <div v-if="groups && groups.length > 0" class="space-y-4 print:hidden">
                <!-- Group Controls Bar -->
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.vehicles_report.group_summary') }}:
                        </span>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300"
                        >
                            {{ groups.length }} {{ $t('reports.vehicles_report.group_summary') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAllGroups"
                            class="px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                        >
                            {{ $t('reports.vehicles_report.expand_all') }}
                        </button>
                        <button
                            type="button"
                            @click="collapseAllGroups"
                            class="px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                        >
                            {{ $t('reports.vehicles_report.collapse_all') }}
                        </button>
                    </div>
                </div>

                <!-- Accordions List -->
                <div class="space-y-3">
                    <div
                        v-for="group in groups"
                        :key="'grp-' + group.group_key"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 overflow-hidden shadow-sm transition-all"
                    >
                        <!-- Accordion Header -->
                        <button
                            type="button"
                            @click="toggleGroup(group.group_key)"
                            class="w-full px-5 py-4 flex flex-wrap items-center justify-between gap-3 text-start hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ group.group_name }}
                                </span>
                                <span
                                    class="text-xs px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono"
                                >
                                    {{ group.summary.total_records }}
                                    {{ $t('reports.vehicles_report.record') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-4 text-xs font-mono">
                                <div
                                    class="hidden sm:flex items-center gap-3 text-gray-500 dark:text-gray-400"
                                >
                                    <span>
                                        {{ $t('reports.vehicles_report.service_cost') }}:
                                        <strong class="text-cyan-600 dark:text-cyan-400">
                                            {{ formatCurrency(group.summary.total_service_cost) }}
                                        </strong>
                                    </span>
                                    <span>|</span>
                                    <span>
                                        {{ $t('reports.vehicles_report.spare_part_cost') }}:
                                        <strong class="text-emerald-600 dark:text-emerald-400">
                                            {{
                                                formatCurrency(group.summary.total_spare_part_cost)
                                            }}
                                        </strong>
                                    </span>
                                    <span>|</span>
                                    <span>
                                        {{ $t('reports.vehicles_report.total') }}:
                                        <strong class="text-gray-900 dark:text-white">
                                            {{ formatCurrency(group.summary.grand_total) }}
                                        </strong>
                                        {{ $t('reports.vehicles_report.currency') }}
                                    </span>
                                </div>

                                <svg
                                    class="w-5 h-5 text-gray-400 transform transition-transform duration-200"
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
                            </div>
                        </button>

                        <!-- Accordion Body -->
                        <div
                            v-show="openGroups[group.group_key]"
                            class="border-t border-gray-100 dark:border-gray-700/60 overflow-x-auto"
                        >
                            <DataTable structured class="w-full text-xs text-start border-collapse">
                                <thead>
                                    <tr
                                        class="bg-gray-50/75 dark:bg-gray-700/30 text-gray-600 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700/60"
                                    >
                                        <th class="py-3 px-3 text-center w-12">
                                            {{ $t('reports.vehicles_report.row_number') }}
                                        </th>
                                        <th class="py-3 px-3 text-start">
                                            {{ $t('reports.vehicles_report.vehicle') }}
                                        </th>
                                        <th class="py-3 px-3 text-center">
                                            {{ $t('reports.vehicles_report.ticket') }}
                                        </th>
                                        <th class="py-3 px-3 text-start">
                                            {{ $t('reports.vehicles_report.section') }}
                                        </th>
                                        <th class="py-3 px-3 text-start">
                                            {{ $t('reports.vehicles_report.service') }}
                                        </th>
                                        <th class="py-3 px-3 text-center">
                                            {{ $t('reports.vehicles_report.due_date') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.vehicles_report.service_cost') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.vehicles_report.spare_part_cost') }}
                                        </th>
                                        <th class="py-3 px-3 text-end">
                                            {{ $t('reports.vehicles_report.total') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/40">
                                    <tr
                                        v-for="(row, idx) in group.items"
                                        :key="'row-' + row.id"
                                        class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20 transition-colors"
                                    >
                                        <td class="py-3 px-3 text-center text-gray-400 font-mono">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="flex items-center gap-2">
                                                <div>
                                                    <div
                                                        class="font-bold text-gray-900 dark:text-white"
                                                    >
                                                        {{ row.vehicle_display_name }}
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <SaudiPlateDisplay
                                                            v-if="row.plate_no"
                                                            :plateNumber="row.plate_no"
                                                            size="sm"
                                                        />
                                                        <span
                                                            v-if="row.odometer_km"
                                                            class="text-[11px] text-gray-500 font-mono"
                                                        >
                                                            {{ formatNumber(row.odometer_km) }}
                                                            {{
                                                                $t(
                                                                    'reports.vehicles_report.odometer_km'
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <div class="space-y-1">
                                                <span
                                                    :class="
                                                        ticketStatusBadgeClass(row.ticket_status)
                                                    "
                                                    class="inline-block px-2 py-0.5 rounded text-[10px] font-bold"
                                                >
                                                    {{
                                                        $t(
                                                            'reports.vehicles_report.ticket_status_' +
                                                                row.ticket_status
                                                        ) || row.ticket_status
                                                    }}
                                                </span>
                                                <div>
                                                    <a
                                                        :href="getWorkOrderUrl(row.work_order_id)"
                                                        class="font-mono font-bold text-cyan-600 dark:text-cyan-400 hover:underline"
                                                    >
                                                        {{ row.ticket_no }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="py-3 px-3 text-gray-600 dark:text-gray-300 font-medium"
                                        >
                                            {{ row.section_name }}
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="font-bold text-gray-800 dark:text-gray-200">
                                                {{ row.service_name }}
                                            </div>
                                            <span
                                                :class="serviceStatusBadgeClass(row.service_status)"
                                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold mt-1"
                                            >
                                                {{
                                                    $t(
                                                        'reports.vehicles_report.status_' +
                                                            row.service_status
                                                    ) || row.service_status
                                                }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-center font-mono text-[11px]">
                                            <div
                                                :class="{
                                                    'text-red-600 font-bold': row.is_overdue,
                                                }"
                                            >
                                                {{ row.due_date }}
                                            </div>
                                            <span
                                                v-if="row.is_overdue"
                                                class="inline-block px-1.5 py-0.2 rounded text-[9px] bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 font-bold"
                                            >
                                                {{ $t('reports.vehicles_report.overdue') }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-bold text-cyan-700 dark:text-cyan-300"
                                        >
                                            {{ formatCurrency(row.service_cost) }}
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-bold text-emerald-700 dark:text-emerald-300"
                                        >
                                            {{ formatCurrency(row.spare_part_cost) }}
                                        </td>
                                        <td
                                            class="py-3 px-3 text-end font-mono font-black text-gray-900 dark:text-white"
                                        >
                                            {{ formatCurrency(row.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flat Table (When Group By === None) -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 overflow-hidden shadow-sm print:hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-xs text-start border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-50/75 dark:bg-gray-700/30 text-gray-600 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700/60"
                            >
                                <th class="py-3.5 px-3 text-center w-12">
                                    {{ $t('reports.vehicles_report.row_number') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.vehicles_report.center') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.vehicles_report.vehicle') }}
                                </th>
                                <th class="py-3.5 px-3 text-center">
                                    {{ $t('reports.vehicles_report.ticket') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.vehicles_report.section') }}
                                </th>
                                <th class="py-3.5 px-3 text-start">
                                    {{ $t('reports.vehicles_report.service') }}
                                </th>
                                <th class="py-3.5 px-3 text-center">
                                    {{ $t('reports.vehicles_report.due_date') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.vehicles_report.service_cost') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.vehicles_report.spare_part_cost') }}
                                </th>
                                <th class="py-3.5 px-3 text-end">
                                    {{ $t('reports.vehicles_report.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            v-if="items.data && items.data.length > 0"
                            class="divide-y divide-gray-100 dark:divide-gray-700/40"
                        >
                            <tr
                                v-for="(row, idx) in items.data"
                                :key="'flat-' + row.id"
                                class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20 transition-colors"
                            >
                                <td class="py-3 px-3 text-center text-gray-400 font-mono">
                                    {{ idx + 1 + (items.current_page - 1) * items.per_page }}
                                </td>
                                <td class="py-3 px-3 text-gray-600 dark:text-gray-300 font-medium">
                                    {{ row.shop_name }}
                                </td>
                                <td class="py-3 px-3">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ row.vehicle_display_name }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <SaudiPlateDisplay
                                                v-if="row.plate_no"
                                                :plateNumber="row.plate_no"
                                                size="sm"
                                            />
                                            <span
                                                v-if="row.odometer_km"
                                                class="text-[11px] text-gray-500 font-mono"
                                            >
                                                {{ formatNumber(row.odometer_km) }}
                                                {{ $t('reports.vehicles_report.odometer_km') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <div class="space-y-1">
                                        <span
                                            :class="ticketStatusBadgeClass(row.ticket_status)"
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-bold"
                                        >
                                            {{
                                                $t(
                                                    'reports.vehicles_report.ticket_status_' +
                                                        row.ticket_status
                                                ) || row.ticket_status
                                            }}
                                        </span>
                                        <div>
                                            <a
                                                :href="getWorkOrderUrl(row.work_order_id)"
                                                class="font-mono font-bold text-cyan-600 dark:text-cyan-400 hover:underline"
                                            >
                                                {{ row.ticket_no }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-gray-600 dark:text-gray-300 font-medium">
                                    {{ row.section_name }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">
                                        {{ row.service_name }}
                                    </div>
                                    <span
                                        :class="serviceStatusBadgeClass(row.service_status)"
                                        class="inline-block px-2 py-0.5 rounded text-[10px] font-bold mt-1"
                                    >
                                        {{
                                            $t(
                                                'reports.vehicles_report.status_' +
                                                    row.service_status
                                            ) || row.service_status
                                        }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono text-[11px]">
                                    <div :class="{ 'text-red-600 font-bold': row.is_overdue }">
                                        {{ row.due_date }}
                                    </div>
                                    <span
                                        v-if="row.is_overdue"
                                        class="inline-block px-1.5 py-0.2 rounded text-[9px] bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 font-bold"
                                    >
                                        {{ $t('reports.vehicles_report.overdue') }}
                                    </span>
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-cyan-700 dark:text-cyan-300"
                                >
                                    {{ formatCurrency(row.service_cost) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-bold text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ formatCurrency(row.spare_part_cost) }}
                                </td>
                                <td
                                    class="py-3 px-3 text-end font-mono font-black text-gray-900 dark:text-white"
                                >
                                    {{ formatCurrency(row.total) }}
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="10" class="py-12 text-center text-gray-400">
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
                                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"
                                            />
                                        </svg>
                                        <span
                                            class="font-bold text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {{ $t('reports.vehicles_report.no_records') }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $t('reports.vehicles_report.no_records_hint') }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Flat Table Pagination Footer -->
                <div
                    v-if="items.data && items.data.length > 0 && items.last_page > 1"
                    class="px-5 py-3.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500"
                >
                    <div>
                        {{ $t('reports.vehicles_report.showing') }} {{ items.from }}
                        {{ $t('reports.vehicles_report.to') }} {{ items.to }}
                        {{ $t('reports.vehicles_report.of') }} {{ items.total }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            v-for="link in items.links"
                            :key="link.label"
                            @click="changePage(link.url)"
                            :disabled="!link.url || link.active"
                            v-html="link.label"
                            :class="[
                                link.active
                                    ? 'bg-cyan-600 text-white font-bold'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
                                !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                            ]"
                            class="px-2.5 py-1 rounded-lg text-xs transition-all font-mono"
                        ></button>
                    </div>
                </div>
            </div>
        </PrintEngine>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import { printDocument } from '@/Composables/usePrint';

const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
    groups: {
        type: Array,
        default: null,
    },
    kpis: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    lookups: {
        type: Object,
        required: true,
    },
});

// Print Setup
const handlePrint = () => {
    printDocument({
        title: 'Vehicles Report',
    });
};

// State
const currentRange = ref(props.filters.range || 'this_month');
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');
const selectedCenter = ref(props.filters.center_id || 'all');
const selectedMake = ref(props.filters.make_id || 'all');
const selectedModel = ref(props.filters.model_id || 'all');
const selectedServiceStatus = ref(props.filters.service_status || 'all');
const selectedGroupBy = ref(props.filters.group_by || 'none');
const ticketNoInput = ref(props.filters.ticket_no || '');
const plateNoInput = ref(props.filters.plate_no || '');
const isOverdueInput = ref(Boolean(props.filters.is_overdue));
const warehousePartsOnlyInput = ref(Boolean(props.filters.warehouse_parts_only));
const searchInput = ref(props.filters.search || '');

// Group Collapse State (All open by default)
const openGroups = ref({});
if (props.groups && Array.isArray(props.groups)) {
    props.groups.forEach((g) => {
        openGroups.value[g.group_key] = true;
    });
}

const toggleGroup = (key) => {
    openGroups.value[key] = !openGroups.value[key];
};

const expandAllGroups = () => {
    if (props.groups) {
        props.groups.forEach((g) => {
            openGroups.value[g.group_key] = true;
        });
    }
};

const collapseAllGroups = () => {
    if (props.groups) {
        props.groups.forEach((g) => {
            openGroups.value[g.group_key] = false;
        });
    }
};

// Date Presets
const datePresets = [
    { id: 'today', label: 'اليوم' },
    { id: 'yesterday', label: 'أمس' },
    { id: 'this_week', label: 'هذا الأسبوع' },
    { id: 'this_month', label: 'هذا الشهر' },
    { id: 'this_year', label: 'هذا العام' },
    { id: 'all_time', label: 'كل الفترات' },
    { id: 'custom', label: 'تاريخ مخصص' },
];

const selectDatePreset = (presetId) => {
    currentRange.value = presetId;
    if (presetId !== 'custom') {
        applyFilters();
    }
};

// Select Options
const centerOptions = computed(() => [
    { id: 'all', label: 'كافة الفروع' },
    ...(props.lookups.centers || []).map((c) => ({ id: String(c.id), label: c.name })),
]);

const makeOptions = computed(() => [
    { id: 'all', label: 'كافة الشركات المصنعة' },
    ...(props.lookups.makes || []).map((m) => ({
        id: String(m.id),
        label: m.name_ar || m.name_en,
    })),
]);

const filteredModelOptions = computed(() => {
    let models = props.lookups.models || [];
    if (selectedMake.value !== 'all' && selectedMake.value) {
        models = models.filter((m) => String(m.make_id) === String(selectedMake.value));
    }
    return [
        { id: 'all', label: 'كافة الموديلات' },
        ...models.map((m) => ({ id: String(m.id), label: m.name_ar || m.name_en })),
    ];
});

const onMakeChange = () => {
    selectedModel.value = 'all';
    applyFilters();
};

const serviceStatusOptions = computed(() => [
    { id: 'all', label: 'كافة حالات الخدمة' },
    ...(props.lookups.service_statuses || []),
]);

const groupByOptions = computed(
    () =>
        props.lookups.groups || [
            { id: 'none', label: 'بدون تجميع (قائمة مسطحة)' },
            { id: 'shopName', label: 'الفرع / المركز' },
            { id: 'plateNo', label: 'المركبة' },
            { id: 'serviceStatusName', label: 'حالة الخدمة' },
        ]
);

// Helper for Safe Work Order Link (avoids Ziggy route error)
const getWorkOrderUrl = (id) => {
    if (!id) return '#';
    try {
        if (typeof route === 'function' && route().has('work-orders.show')) {
            return route('work-orders.show', id);
        }
    } catch (e) {
        // Fallback safely to URL string
    }
    return `/app/work-orders/${id}`;
};

// Formatters
const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US').format(Number(num) || 0);
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(val) || 0);
};

// Badge Styling
const serviceStatusBadgeClass = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'ready_for_qc':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
        case 'on_hold':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }
};

const ticketStatusBadgeClass = (status) => {
    switch (status) {
        case 'done':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'ready_for_qc':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
        case 'on_hold':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        case 'open':
            return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Filter Application
const applyFilters = () => {
    router.get(
        route('app.reports.vehicles'),
        {
            range: currentRange.value,
            start_date: currentRange.value === 'custom' ? customStartDate.value : null,
            end_date: currentRange.value === 'custom' ? customEndDate.value : null,
            center_id: selectedCenter.value !== 'all' ? selectedCenter.value : null,
            make_id: selectedMake.value !== 'all' ? selectedMake.value : null,
            model_id: selectedModel.value !== 'all' ? selectedModel.value : null,
            service_status:
                selectedServiceStatus.value !== 'all' ? selectedServiceStatus.value : null,
            group_by: selectedGroupBy.value,
            ticket_no: ticketNoInput.value || null,
            plate_no: plateNoInput.value || null,
            is_overdue: isOverdueInput.value ? 1 : null,
            warehouse_parts_only: warehousePartsOnlyInput.value ? 1 : null,
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
    selectedMake.value = 'all';
    selectedModel.value = 'all';
    selectedServiceStatus.value = 'all';
    selectedGroupBy.value = 'none';
    ticketNoInput.value = '';
    plateNoInput.value = '';
    isOverdueInput.value = false;
    warehousePartsOnlyInput.value = false;
    searchInput.value = '';

    router.get(
        route('app.reports.vehicles'),
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

// Subtitles & Exports
const selectedCenterName = computed(() => {
    if (selectedCenter.value === 'all') return 'كافة الفروع';
    const c = (props.lookups.centers || []).find(
        (item) => String(item.id) === String(selectedCenter.value)
    );
    return c ? c.name : 'المركز الرئيسي';
});

const dateRangeSubtitle = computed(() => {
    if (props.filters.start_date && props.filters.end_date) {
        return `من ${props.filters.start_date} إلى ${props.filters.end_date}`;
    }
    return 'الشهر الحالي';
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    params.set('range', currentRange.value);
    if (currentRange.value === 'custom') {
        if (customStartDate.value) params.set('start_date', customStartDate.value);
        if (customEndDate.value) params.set('end_date', customEndDate.value);
    }
    if (selectedCenter.value !== 'all') params.set('center_id', selectedCenter.value);
    if (selectedMake.value !== 'all') params.set('make_id', selectedMake.value);
    if (selectedModel.value !== 'all') params.set('model_id', selectedModel.value);
    if (selectedServiceStatus.value !== 'all')
        params.set('service_status', selectedServiceStatus.value);
    if (ticketNoInput.value) params.set('ticket_no', ticketNoInput.value);
    if (plateNoInput.value) params.set('plate_no', plateNoInput.value);
    if (isOverdueInput.value) params.set('is_overdue', '1');
    if (warehousePartsOnlyInput.value) params.set('warehouse_parts_only', '1');
    if (searchInput.value) params.set('search', searchInput.value);

    return route('app.reports.vehicles.export') + '?' + params.toString();
});
</script>
