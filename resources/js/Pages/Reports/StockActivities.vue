<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.stock_activities_report.title')"
                :subtitle="$t('reports.stock_activities_report.subtitle')"
                gradientFrom="from-amber-600"
                gradientTo="to-orange-700"
                glowFrom="from-amber-500"
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
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            @click="handlePrint"
                            :label="$t('reports.stock_activities_report.print_report')"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-xs font-bold transition-all shadow-lg shadow-amber-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.stock_activities_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.stock_activities_report.official_report')"
                    :subtitle="
                        filters.start_date || filters.end_date
                            ? `${$t('reports.stock_activities_report.date_range')}: ${filters.start_date || 'All'} — ${filters.end_date || 'Now'}`
                            : ''
                    "
                >
                    <template #extra>
                        <div class="text-xs text-gray-600">
                            {{ $t('reports.stock_activities_report.total_records') }}:
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
                            {{ $t('reports.stock_activities_report.total_records') }}:
                        </span>
                        <strong class="font-mono text-xs">{{ kpis.total_records }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.stock_activities_report.total_incoming') }}:
                        </span>
                        <strong class="font-mono text-xs text-emerald-700">
                            +{{ formatNumber(kpis.total_incoming_qty) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.stock_activities_report.total_outgoing') }}:
                        </span>
                        <strong class="font-mono text-xs text-rose-700">
                            -{{ formatNumber(kpis.total_outgoing_qty) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.stock_activities_report.net_qty') }}:
                        </span>
                        <strong
                            class="font-mono text-xs"
                            :class="kpis.net_qty >= 0 ? 'text-emerald-700' : 'text-rose-700'"
                        >
                            {{ formatNumber(kpis.net_qty) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('reports.stock_activities_report.total_cost') }}:
                        </span>
                        <strong class="font-mono text-xs text-amber-800">
                            {{ formatMoney(kpis.total_cost) }}
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
                            class="w-4 h-4 text-amber-500"
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
                        {{ $t('reports.stock_activities_report.date_range') }}:
                    </span>

                    <button
                        v-for="preset in datePresets"
                        :key="preset.id"
                        type="button"
                        @click="selectPreset(preset.id)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                            localFilters.range === preset.id
                                ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/30'
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
                            {{ $t('reports.stock_activities_report.date_from') }}
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
                            {{ $t('reports.stock_activities_report.date_to') }}
                        </label>
                        <CustomDatePicker
                            v-model="localFilters.end_date"
                            @update:modelValue="applyFilters"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Filters Grid: Center, Warehouse, Move Type, Category, Group By -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/50"
                >
                    <!-- Branch / Center Filter (for multi-branch users) -->
                    <div v-if="centers && centers.length > 1">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.stock_activities_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.center_id"
                            :options="centerOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.stock_activities_report.all_centers')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Warehouse Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.stock_activities_report.warehouse') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.warehouse_id"
                            :options="warehouseOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.stock_activities_report.all_warehouses')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Move Type Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.stock_activities_report.move_type') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.move_type"
                            :options="moveTypeOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.stock_activities_report.all_move_types')"
                            compact
                            @update:modelValue="applyFilters"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.stock_activities_report.category') }}
                        </label>
                        <SearchableSelect
                            v-model="localFilters.category_id"
                            :options="categoryOptions"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('reports.stock_activities_report.all_categories')"
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
                            {{ $t('reports.stock_activities_report.group_by') }}
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
                            :placeholder="$t('reports.stock_activities_report.search_placeholder')"
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/50 focus:bg-white dark:focus:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-focus-ring focus:border-transparent pe-8 ps-9 py-2 transition-all"
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
                            <span>{{ $t('reports.stock_activities_report.apply_filters') }}</span>
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
                            <span>{{ $t('reports.stock_activities_report.reset_filters') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards (Screen Only) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 print:hidden">
                <!-- 1. Total Movements -->
                <div
                    class="bg-gradient-to-br from-amber-500/10 to-orange-500/5 dark:from-amber-500/20 dark:to-orange-500/10 rounded-2xl p-4 border border-amber-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.stock_activities_report.total_records') }}
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
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                        {{ kpis.total_records }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        حركة مخزنية مسجلة
                    </div>
                </div>

                <!-- 2. Total Incoming -->
                <div
                    class="bg-gradient-to-br from-emerald-500/10 to-teal-500/5 dark:from-emerald-500/20 dark:to-teal-500/10 rounded-2xl p-4 border border-emerald-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.stock_activities_report.total_incoming') }}
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
                                    d="M7 11l5-5m0 0l5 5m-5-5v12"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono"
                    >
                        +{{ formatNumber(kpis.total_incoming_qty) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        وارد (استلام، شراء، تسوية زيادة)
                    </div>
                </div>

                <!-- 3. Total Outgoing -->
                <div
                    class="bg-gradient-to-br from-rose-500/10 to-red-500/5 dark:from-rose-500/20 dark:to-red-500/10 rounded-2xl p-4 border border-rose-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.stock_activities_report.total_outgoing') }}
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
                                    d="M17 13l-5 5m0 0l-5-5m5 5V6"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400 font-mono"
                    >
                        -{{ formatNumber(kpis.total_outgoing_qty) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        منصرف (كروت عمل، بيع، عجز)
                    </div>
                </div>

                <!-- 4. Net Stock Delta -->
                <div
                    class="bg-gradient-to-br from-blue-500/10 to-action-primary dark:from-blue-500/20 rounded-2xl p-4 border border-blue-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.stock_activities_report.net_qty') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400"
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
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black font-mono"
                        :class="
                            kpis.net_qty >= 0
                                ? 'text-blue-600 dark:text-blue-400'
                                : 'text-rose-600 dark:text-rose-400'
                        "
                    >
                        {{ (kpis.net_qty > 0 ? '+' : '') + formatNumber(kpis.net_qty) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        صافي تغير المخزون
                    </div>
                </div>

                <!-- 5. Total Cost -->
                <div
                    class="bg-gradient-to-br from-purple-500/10 to-fuchsia-500/5 dark:to-fuchsia-500/10 rounded-2xl p-4 border border-purple-500/20 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                            {{ $t('reports.stock_activities_report.total_cost') }}
                        </span>
                        <div
                            class="w-8 h-8 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-600"
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
                    <div class="mt-2 text-2xl font-black text-purple-700 font-mono">
                        {{ formatMoney(kpis.total_cost) }}
                    </div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        تكلفة الحركات المسجلة
                    </div>
                </div>
            </div>

            <!-- Grouped View (When group_by != 'none') -->
            <div v-if="groupedMoves && groupedMoves.length > 0" class="space-y-4">
                <div class="flex items-center justify-between px-1 print:hidden">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                        {{ groupedMoves.length }}
                        {{ $t('reports.stock_activities_report.group_summary') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAllGroups"
                            class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1"
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
                            {{ $t('reports.stock_activities_report.expand_all') }}
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
                            {{ $t('reports.stock_activities_report.collapse_all') }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(grp, idx) in groupedMoves"
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
                                class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xs"
                            >
                                {{ idx + 1 }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ grp.group_name }}
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ grp.total_records }} حركات
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-gray-400 block">الوارد</span>
                                <span class="font-mono font-bold text-xs text-emerald-600">
                                    +{{ formatNumber(grp.total_incoming) }}
                                </span>
                            </div>
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-gray-400 block">المنصرف</span>
                                <span class="font-mono font-bold text-xs text-rose-600">
                                    -{{ formatNumber(grp.total_outgoing) }}
                                </span>
                            </div>
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-gray-400 block">الصافي</span>
                                <span
                                    class="font-mono font-bold text-xs"
                                    :class="grp.net_qty >= 0 ? 'text-blue-600' : 'text-rose-600'"
                                >
                                    {{ (grp.net_qty > 0 ? '+' : '') + formatNumber(grp.net_qty) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-gray-400 block">إجمالي التكلفة</span>
                                <span class="font-mono font-bold text-xs text-purple-700">
                                    {{ formatMoney(grp.total_cost) }}
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
                        <DataTable structured class="w-full text-xs text-right">
                            <thead
                                class="bg-gray-50/75 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-700"
                            >
                                <tr>
                                    <th class="py-2.5 px-3 w-12 text-center">#</th>
                                    <th class="py-2.5 px-3">الفرع / المستودع</th>
                                    <th class="py-2.5 px-3">القطعة (SKU)</th>
                                    <th class="py-2.5 px-3">نوع الحركة</th>
                                    <th class="py-2.5 px-3">التاريخ والوقت</th>
                                    <th class="py-2.5 px-3 text-center">الرصيد السابق</th>
                                    <th class="py-2.5 px-3 text-center">الكمية</th>
                                    <th class="py-2.5 px-3 text-center">الرصيد بعد</th>
                                    <th class="py-2.5 px-3 text-center">تكلفة الوحدة</th>
                                    <th class="py-2.5 px-3 text-center">إجمالي التكلفة</th>
                                    <th class="py-2.5 px-3">المرجع</th>
                                    <th class="py-2.5 px-3">المنفذ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <tr
                                    v-for="(item, itemIdx) in grp.items"
                                    :key="item.id"
                                    class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-3 text-center text-gray-400 font-mono text-[11px]"
                                    >
                                        {{ itemIdx + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ item.center?.name || '—' }}
                                        </div>
                                        <div class="text-[11px] text-gray-500">
                                            {{ item.warehouse?.name || '—' }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ item.part?.name_ar || item.part?.name_en || '—' }}
                                        </div>
                                        <div
                                            class="font-mono text-[11px] text-amber-700 dark:text-amber-400"
                                        >
                                            {{ item.part?.sku || '—' }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span
                                            :class="getMoveBadgeClasses(item.move_type)"
                                            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        >
                                            {{ item.move_type_label }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-2.5 px-3 font-mono text-[11px] text-gray-600 dark:text-gray-300"
                                    >
                                        {{ item.posted_at }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono text-gray-500">
                                        {{ formatNumber(item.balance_before) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono font-bold"
                                        :class="item.qty > 0 ? 'text-emerald-600' : 'text-rose-600'"
                                    >
                                        {{ (item.qty > 0 ? '+' : '') + formatNumber(item.qty) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ formatNumber(item.balance_after) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono text-gray-700 dark:text-gray-300"
                                    >
                                        {{ formatMoney(item.unit_cost) }}
                                    </td>
                                    <td
                                        class="py-2.5 px-3 text-center font-mono font-bold text-purple-700"
                                    >
                                        {{ formatMoney(item.total_cost) }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <a
                                            v-if="item.reference_url"
                                            :href="item.reference_url"
                                            class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-blue-600 hover:text-blue-800 hover:underline"
                                        >
                                            {{ item.reference_label }}: {{ item.reference_code }}
                                        </a>
                                        <span v-else class="text-gray-500 text-[11px]">
                                            {{ item.reference_label }}: {{ item.reference_code }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-500 text-[11px]">
                                        {{ item.posted_by?.name || 'النظام' }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>

            <!-- Detailed Table View (When group_by == 'none') -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-xs text-right">
                        <thead
                            class="bg-gray-50 dark:bg-gray-750 text-gray-500 dark:text-gray-400 font-bold border-b border-gray-100 dark:border-gray-700"
                        >
                            <tr>
                                <th class="py-3 px-3.5 w-12 text-center">#</th>
                                <th class="py-3 px-3.5">الفرع / المستودع</th>
                                <th class="py-3 px-3.5">القطعة (SKU)</th>
                                <th class="py-3 px-3.5">نوع الحركة</th>
                                <th class="py-3 px-3.5">التاريخ والوقت</th>
                                <th class="py-3 px-3.5 text-center">الرصيد السابق</th>
                                <th class="py-3 px-3.5 text-center">الكمية</th>
                                <th class="py-3 px-3.5 text-center">الرصيد بعد</th>
                                <th class="py-3 px-3.5 text-center">تكلفة الوحدة</th>
                                <th class="py-3 px-3.5 text-center">إجمالي التكلفة</th>
                                <th class="py-3 px-3.5">المرجع</th>
                                <th class="py-3 px-3.5">المنفذ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr
                                v-for="(item, idx) in moves?.data || []"
                                :key="item.id"
                                class="hover:bg-amber-50/20 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td
                                    class="py-3 px-3.5 text-center text-gray-400 font-mono text-[11px]"
                                >
                                    {{ (moves.current_page - 1) * moves.per_page + idx + 1 }}
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ item.center?.name || 'المركز الرئيسي' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500">
                                        {{ item.warehouse?.name || '—' }}
                                    </div>
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ item.part?.name_ar || item.part?.name_en || '—' }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span
                                            class="font-mono text-[11px] text-amber-700 dark:text-amber-400"
                                        >
                                            {{ item.part?.sku || '—' }}
                                        </span>
                                        <span
                                            v-if="item.part?.category_name"
                                            class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.2 rounded"
                                        >
                                            {{ item.part.category_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3.5">
                                    <span
                                        :class="getMoveBadgeClasses(item.move_type)"
                                        class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold"
                                    >
                                        {{ item.move_type_label }}
                                    </span>
                                </td>
                                <td
                                    class="py-3 px-3.5 font-mono text-[11px] text-gray-600 dark:text-gray-300"
                                >
                                    {{ item.posted_at }}
                                </td>
                                <td class="py-3 px-3.5 text-center font-mono text-gray-500">
                                    {{ formatNumber(item.balance_before) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono font-bold text-sm"
                                    :class="item.qty > 0 ? 'text-emerald-600' : 'text-rose-600'"
                                >
                                    {{ (item.qty > 0 ? '+' : '') + formatNumber(item.qty) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ formatNumber(item.balance_after) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono text-gray-700 dark:text-gray-300"
                                >
                                    {{ formatMoney(item.unit_cost) }}
                                </td>
                                <td
                                    class="py-3 px-3.5 text-center font-mono font-bold text-purple-700"
                                >
                                    {{ formatMoney(item.total_cost) }}
                                </td>
                                <td class="py-3 px-3.5">
                                    <a
                                        v-if="item.reference_url"
                                        :href="item.reference_url"
                                        class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        {{ item.reference_label }}: {{ item.reference_code }}
                                    </a>
                                    <span v-else class="text-gray-500 text-[11px]">
                                        {{ item.reference_label }}: {{ item.reference_code }}
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-gray-500 text-[11px]">
                                    {{ item.posted_by?.name || 'النظام' }}
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!moves || moves.data.length === 0">
                                <td colspan="12" class="py-12 text-center">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-500 mx-auto flex items-center justify-center mb-3"
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
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                        {{ $t('reports.stock_activities_report.no_records') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $t('reports.stock_activities_report.no_records_hint') }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination (Screen Only) -->
                <div
                    v-if="moves?.links && moves.links.length > 3"
                    class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs print:hidden"
                >
                    <span class="text-gray-500">
                        عرض
                        <span class="font-mono">{{ moves.from || 0 }}</span>
                        إلى
                        <span class="font-mono">{{ moves.to || 0 }}</span>
                        من إجمالي
                        <span class="font-mono font-bold">{{ moves.total }}</span>
                        حركة
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, lIdx) in moves.links"
                            :key="lIdx"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-xl transition-all text-xs font-mono font-bold',
                                link.active
                                    ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Standard Official Print Footer -->
            <div class="print-section hidden">
                <StandardReportFooter />
            </div>
        </PrintEngine>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import { printDocument } from '@/Composables/usePrint';
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
    moves: {
        type: Object,
        default: null,
    },
    groupedMoves: {
        type: Array,
        default: null,
    },
    centers: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    moveTypes: {
        type: Array,
        default: () => [],
    },
    currentCenterId: {
        type: [Number, String],
        default: null,
    },
});

// Local reactive filters matching props
const localFilters = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.range === 'custom' ? props.filters.start_date || '' : '',
    end_date: props.filters.range === 'custom' ? props.filters.end_date || '' : '',
    move_type: String(props.filters.move_type || 'all'),
    warehouse_id: String(props.filters.warehouse_id || 'all'),
    category_id: String(props.filters.category_id || 'all'),
    center_id: String(props.filters.center_id || 'all'),
    group_by: String(props.filters.group_by || 'none'),
    search: props.filters.search || '',
});

// Keep local filters synced when Inertia updates page props
watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        localFilters.range = newFilters.range || 'this_month';
        localFilters.start_date = newFilters.range === 'custom' ? newFilters.start_date || '' : '';
        localFilters.end_date = newFilters.range === 'custom' ? newFilters.end_date || '' : '';
        localFilters.move_type = String(newFilters.move_type || 'all');
        localFilters.warehouse_id = String(newFilters.warehouse_id || 'all');
        localFilters.category_id = String(newFilters.category_id || 'all');
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
    if (props.groupedMoves) {
        props.groupedMoves.forEach((g) => {
            openGroups[g.group_key] = true;
        });
    }
};

const collapseAllGroups = () => {
    if (props.groupedMoves) {
        props.groupedMoves.forEach((g) => {
            openGroups[g.group_key] = false;
        });
    }
};

// Date Presets
const datePresets = computed(() => [
    { id: 'today', label: t('reports.stock_activities_report.today') },
    { id: 'yesterday', label: t('reports.stock_activities_report.yesterday') },
    { id: 'this_week', label: t('reports.stock_activities_report.this_week') },
    { id: 'this_month', label: t('reports.stock_activities_report.this_month') },
    { id: 'this_year', label: t('reports.stock_activities_report.this_year') },
    { id: 'all', label: t('reports.stock_activities_report.all_time') },
    { id: 'custom', label: t('reports.stock_activities_report.custom') },
]);

const selectPreset = (presetId) => {
    localFilters.range = presetId;
    if (presetId !== 'custom') {
        localFilters.start_date = '';
        localFilters.end_date = '';
    }
    applyFilters();
};

// Select Dropdown Options (providing id, value, name, label for maximum compatibility)
const centerOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.stock_activities_report.all_centers'),
        label: t('reports.stock_activities_report.all_centers'),
    },
    ...(props.centers || []).map((c) => ({
        id: String(c.id),
        value: String(c.id),
        name: c.name,
        label: c.name,
    })),
]);

const warehouseOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.stock_activities_report.all_warehouses'),
        label: t('reports.stock_activities_report.all_warehouses'),
    },
    ...(props.warehouses || []).map((w) => {
        const text = w.code ? `${w.name} (${w.code})` : w.name;
        return {
            id: String(w.id),
            value: String(w.id),
            name: text,
            label: text,
        };
    }),
]);

const categoryOptions = computed(() => [
    {
        id: 'all',
        value: 'all',
        name: t('reports.stock_activities_report.all_categories'),
        label: t('reports.stock_activities_report.all_categories'),
    },
    ...(props.categories || []).map((cat) => {
        const text = cat.name_ar || cat.name_en || cat.name || `#${cat.id}`;
        return {
            id: String(cat.id),
            value: String(cat.id),
            name: text,
            label: text,
        };
    }),
]);

const moveTypeOptions = computed(() => {
    return (props.moveTypes || []).map((mt) => ({
        id: String(mt.id ?? mt.value),
        value: String(mt.value ?? mt.id),
        name: mt.name ?? mt.label,
        label: mt.label ?? mt.name,
    }));
});

const groupByOptions = computed(() => [
    {
        id: 'none',
        value: 'none',
        name: t('reports.stock_activities_report.group_none'),
        label: t('reports.stock_activities_report.group_none'),
    },
    {
        id: 'part',
        value: 'part',
        name: t('reports.stock_activities_report.group_part'),
        label: t('reports.stock_activities_report.group_part'),
    },
    {
        id: 'move_type',
        value: 'move_type',
        name: t('reports.stock_activities_report.group_move_type'),
        label: t('reports.stock_activities_report.group_move_type'),
    },
    {
        id: 'warehouse',
        value: 'warehouse',
        name: t('reports.stock_activities_report.group_warehouse'),
        label: t('reports.stock_activities_report.group_warehouse'),
    },
    {
        id: 'center',
        value: 'center',
        name: t('reports.stock_activities_report.group_center'),
        label: t('reports.stock_activities_report.group_center'),
    },
]);

// Apply Filters through Inertia
const applyFilters = () => {
    const isCustom = localFilters.range === 'custom';
    const params = {
        range: localFilters.range,
        start_date: isCustom ? localFilters.start_date || undefined : undefined,
        end_date: isCustom ? localFilters.end_date || undefined : undefined,
        move_type: localFilters.move_type !== 'all' ? localFilters.move_type : undefined,
        warehouse_id: localFilters.warehouse_id !== 'all' ? localFilters.warehouse_id : undefined,
        category_id: localFilters.category_id !== 'all' ? localFilters.category_id : undefined,
        center_id: localFilters.center_id !== 'all' ? localFilters.center_id : undefined,
        group_by: localFilters.group_by !== 'none' ? localFilters.group_by : undefined,
        search: localFilters.search ? localFilters.search.trim() : undefined,
    };

    router.get(route('app.reports.stock-activities'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.range = 'this_month';
    localFilters.start_date = '';
    localFilters.end_date = '';
    localFilters.move_type = 'all';
    localFilters.warehouse_id = 'all';
    localFilters.category_id = 'all';
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
    if (localFilters.move_type && localFilters.move_type !== 'all')
        query.set('move_type', localFilters.move_type);
    if (localFilters.warehouse_id && localFilters.warehouse_id !== 'all')
        query.set('warehouse_id', localFilters.warehouse_id);
    if (localFilters.category_id && localFilters.category_id !== 'all')
        query.set('category_id', localFilters.category_id);
    if (localFilters.center_id && localFilters.center_id !== 'all')
        query.set('center_id', localFilters.center_id);
    if (localFilters.search) query.set('search', localFilters.search);

    return route('app.reports.stock-activities.export') + '?' + query.toString();
});

// Printing
const handlePrint = () => {
    printDocument();
};

// Badge styles based on move type
const getMoveBadgeClasses = (type) => {
    switch (type) {
        case 'receipt':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300';
        case 'issue_to_workorder':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300';
        case 'sale':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300';
        case 'adjustment_in':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-300';
        case 'adjustment_out':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/60 dark:text-orange-300';
        case 'transfer_in':
            return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-300';
        case 'transfer_out':
            return 'bg-purple-100 text-purple-800';
        case 'purchase_return':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300';
        case 'reversal':
            return 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    }
};

// Formatter Helpers
const formatMoney = (val) => {
    const num = Number(val || 0);
    return `${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ر.س`;
};

const formatNumber = (val) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
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
