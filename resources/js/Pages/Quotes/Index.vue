<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('quotes.title')"
                :subtitle="$t('quotes.subtitle')"
                :totalCount="quotes ? toEnglish(quotes.total) : null"
                :countLabel="$t('quotes.total_count')"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div
                        class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/70 backdrop-blur-md rounded-2xl border border-border-default shadow-inner"
                    >
                        <Tooltip :text="$t('common.export_excel')">
                            <button
                                @click="exportQuotes"
                                class="p-2.5 text-content-secondary hover:text-action-primary hover:bg-surface-primary rounded-xl transition-all shadow-sm hover:shadow-md"
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
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </button>
                        </Tooltip>

                        <AppPrintButton variant="icon" @click="printQuotes" />

                        <div class="w-px h-6 bg-border-default mx-1"></div>

                        <AppViewModeToggle v-model="viewMode" storage-key="quotes" />
                    </div>

                    <button
                        v-if="can('quotes.create')"
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-action-primary hover:bg-action-primary-hover text-white rounded-2xl font-bold shadow-sm transition-all group/add"
                    >
                        <div
                            class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300"
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
                                    stroke-width="3"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('quotes.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="space-y-4">
                        <!-- Search & Date Row -->
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Search -->
                            <div class="relative flex-1 group">
                                <div
                                    class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-content-secondary group-focus-within:text-action-primary transition-colors"
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
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="tableFilters.search"
                                    class="block w-full ps-11 pe-4 py-3.5 text-sm text-content-primary bg-surface-primary border border-border-default rounded-2xl focus:ring-4 focus:ring-action-primary/10 focus:border-action-primary transition-all outline-none shadow-sm"
                                    :placeholder="$t('quotes.search')"
                                />
                            </div>

                            <!-- Date Pickers & Reset -->
                            <div class="flex items-center gap-3">
                                <div class="w-40 sm:w-44">
                                    <CustomDatePicker
                                        v-model="tableFilters.date_from"
                                        :placeholder="$t('common.date_from')"
                                        @change="() => applyFilters()"
                                    />
                                </div>
                                <div class="w-40 sm:w-44">
                                    <CustomDatePicker
                                        v-model="tableFilters.date_to"
                                        :placeholder="$t('common.date_to')"
                                        @change="() => applyFilters()"
                                    />
                                </div>
                                <Tooltip :text="$t('common.reset')">
                                    <button
                                        @click="handleResetFilters"
                                        class="p-3.5 text-content-secondary hover:text-danger hover:bg-danger-soft rounded-2xl border border-border-default bg-surface-primary transition-all shadow-sm"
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
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            />
                                        </svg>
                                    </button>
                                </Tooltip>
                            </div>
                        </div>

                        <!-- Status Tabs -->
                        <AppTabs
                            :model-value="activeStatusTab"
                            :tabs="tabs"
                            variant="pills"
                            @change="onTabChange"
                        />
                    </div>
                </template>
            </PageHeader>

            <!-- Loading State -->
            <AppTableSkeleton v-if="!quotes" :rows="6" :cols="8" />

            <!-- Empty State -->
            <AppEmptyState
                v-else-if="quotes.data.length === 0"
                :title="$t('quotes.empty')"
                :description="$t('quotes.empty_hint')"
                :action-text="can('quotes.create') ? $t('quotes.add') : ''"
                @action="openCreateModal"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-action-primary"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>
            </AppEmptyState>

            <!-- Grid View: Modern Elegant Cards -->
            <div v-else-if="viewMode === 'grid'" class="space-y-6">
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4"
                >
                    <div
                        v-for="quote in quotes.data"
                        :key="quote.id"
                        @click="navigateToShow(quote)"
                        class="group relative bg-surface-primary rounded-3xl border border-border-default hover:border-action-primary hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden flex flex-col"
                    >
                        <!-- Top Status Accent Line -->
                        <div class="h-1.5 w-full" :class="getStatusLineClass(quote.status)"></div>

                        <!-- Card Header: Code & Date -->
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] font-black text-content-secondary tracking-widest uppercase"
                                        >
                                            #{{ toEnglish(quote.code) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="quote.created_at"
                                        class="text-[9px] font-bold text-content-secondary"
                                    >
                                        {{ formatDate(quote.created_at) }}
                                    </div>
                                    <!-- Converted WO Link -->
                                    <div
                                        v-if="
                                            ['converted', 'approved'].includes(quote.status) &&
                                            (quote.converted_work_order?.id ||
                                                quote.converted_work_order_id)
                                        "
                                        class="flex items-center gap-1"
                                        @click.stop
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'work-orders.show',
                                                    quote.converted_work_order?.id ||
                                                        quote.converted_work_order_id
                                                )
                                            "
                                            class="text-[10px] font-bold text-action-primary bg-surface-secondary px-2 py-0.5 rounded-lg flex items-center gap-1 hover:bg-surface-secondary/80 border border-border-default transition-all"
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                                />
                                            </svg>
                                            {{ quote.converted_work_order?.code }}
                                        </Link>
                                    </div>
                                </div>
                                <div
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                        getStatusClass(quote.status),
                                    ]"
                                >
                                    {{ $t(`quotes.status.${quote.status}`) }}
                                </div>
                            </div>
                        </div>

                        <!-- Main Vehicle Info Section -->
                        <div class="px-4 py-3 flex-1">
                            <div class="flex items-center justify-center gap-3 mb-4">
                                <div
                                    v-if="quote.vehicle?.make?.logo_path"
                                    class="w-10 h-10 flex items-center justify-center p-1 bg-surface-secondary rounded-xl border border-border-default shadow-sm"
                                >
                                    <img
                                        :src="`/storage/${quote.vehicle.make.logo_path}`"
                                        class="w-full h-full object-contain filter dark:brightness-110"
                                        alt=""
                                    />
                                </div>

                                <SaudiPlateDisplay
                                    :plate-number="quote.vehicle?.plate_number"
                                    size="sm"
                                />
                            </div>

                            <div class="space-y-3">
                                <div class="text-center">
                                    <div
                                        class="flex flex-col items-center justify-center gap-1 mb-1"
                                    >
                                        <h3
                                            class="text-sm font-bold text-content-primary leading-tight group-hover:text-action-primary transition-colors line-clamp-1"
                                        >
                                            {{ getVehicleName(quote.vehicle) || $t('common.na') }}
                                        </h3>
                                        <div
                                            v-if="quote.vehicle?.color"
                                            class="flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-bold bg-surface-secondary text-content-secondary rounded-md border border-border-default"
                                        >
                                            <span
                                                class="w-2 h-2 rounded-full border border-border-default shadow-sm"
                                                :style="{
                                                    backgroundColor: getColorHex(
                                                        quote.vehicle.color
                                                    ),
                                                }"
                                            ></span>
                                            {{ quote.vehicle.color }}
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-center gap-1.5">
                                        <span
                                            class="text-xs font-bold text-content-secondary truncate max-w-[140px]"
                                        >
                                            {{ quote.customer?.name || $t('common.unknown') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Stats Grid -->
                                <div
                                    class="grid grid-cols-3 gap-1 py-2 border-y border-border-default"
                                >
                                    <!-- KM -->
                                    <div
                                        class="flex flex-col items-center justify-center border-e border-border-default py-1"
                                    >
                                        <span
                                            class="text-[9px] font-bold text-content-secondary uppercase"
                                        >
                                            {{ $t('work_orders.form.odometer') }}
                                        </span>
                                        <div class="flex items-center gap-0.5">
                                            <span
                                                class="text-[11px] font-bold text-content-primary"
                                            >
                                                {{
                                                    formatNumber(
                                                        quote.odometer ||
                                                            quote.vehicle?.odometer ||
                                                            0
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Services Count -->
                                    <div
                                        class="flex flex-col items-center justify-center border-e border-border-default py-1"
                                    >
                                        <span
                                            class="text-[9px] font-bold text-content-secondary uppercase"
                                        >
                                            {{ $t('quotes.card.services') }}
                                        </span>
                                        <span class="text-[11px] font-bold text-action-primary">
                                            {{ toEnglish(quote.lines_count || 0) }}
                                        </span>
                                    </div>

                                    <!-- Parts Count -->
                                    <div class="flex flex-col items-center justify-center py-1">
                                        <span
                                            class="text-[9px] font-bold text-content-secondary uppercase"
                                        >
                                            {{ $t('quotes.card.parts') }}
                                        </span>
                                        <span class="text-[11px] font-bold text-content-primary">
                                            {{ toEnglish(quote.parts_count || 0) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer: Total Section -->
                        <div
                            class="px-5 py-3.5 bg-surface-secondary/50 border-t border-border-default flex items-center justify-between mt-auto"
                        >
                            <span
                                class="text-[10px] font-bold text-content-secondary uppercase tracking-wider"
                            >
                                {{ $t('quotes.form.grand_total') }}
                            </span>
                            <div class="flex items-baseline gap-1 whitespace-nowrap">
                                <span class="text-base font-bold text-content-primary">
                                    {{ formatCurrencyValue(quote.total) }}
                                </span>
                                <span class="text-[9px] font-bold text-content-secondary uppercase">
                                    {{ activeCurrencySymbol }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid Pagination -->
                <AppPagination
                    :links="quotes.links"
                    :from="quotes.from"
                    :to="quotes.to"
                    :total="quotes.total"
                />
            </div>

            <!-- List View: DataTable -->
            <div v-else class="space-y-4">
                <DataTable
                    :data="quotes"
                    :columns="columns"
                    :loading="isLoading"
                    :sort-column="sortColumn"
                    :sort-direction="sortDirection"
                    @sort="toggleSort"
                    @row-click="navigateToShow"
                >
                    <template #cell-code="{ item }">
                        <div class="flex flex-col items-center gap-1">
                            <span
                                class="text-sm font-bold text-action-primary group-hover:underline"
                            >
                                #{{ toEnglish(item.code) }}
                            </span>
                            <div
                                v-if="
                                    ['converted', 'approved'].includes(item.status) &&
                                    (item.converted_work_order?.id || item.converted_work_order_id)
                                "
                                class="flex items-center gap-1"
                                @click.stop
                            >
                                <Link
                                    :href="
                                        route(
                                            'work-orders.show',
                                            item.converted_work_order?.id ||
                                                item.converted_work_order_id
                                        )
                                    "
                                    class="text-[10px] font-bold text-action-primary bg-surface-secondary px-2 py-0.5 rounded-lg flex items-center gap-1 hover:bg-surface-secondary/80 border border-border-default"
                                >
                                    <svg
                                        class="w-3 h-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                        />
                                    </svg>
                                    {{ item.converted_work_order?.code }}
                                </Link>
                            </div>
                        </div>
                    </template>

                    <template #cell-vehicle="{ item }">
                        <div class="flex flex-col items-center gap-1">
                            <SaudiPlateDisplay
                                :plate-number="item.vehicle?.plate_number"
                                size="sm"
                            />
                            <span
                                class="text-[10px] text-content-secondary font-bold truncate max-w-[120px]"
                            >
                                {{ getVehicleName(item.vehicle) }}
                            </span>
                        </div>
                    </template>

                    <template #cell-customer="{ item }">
                        <span class="text-sm font-medium text-content-primary">
                            {{ item.customer?.name || '-' }}
                        </span>
                    </template>

                    <template #cell-services="{ item }">
                        <span
                            class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-surface-secondary text-content-primary text-xs font-bold border border-border-default"
                        >
                            {{ toEnglish(item.lines_count || 0) }}
                        </span>
                    </template>

                    <template #cell-subtotal="{ item }">
                        <span class="text-sm font-medium text-content-secondary">
                            {{ formatCurrencyValue(item.subtotal || 0) }}
                        </span>
                    </template>

                    <template #cell-discount="{ item }">
                        <span class="text-sm font-medium text-danger">
                            {{ formatCurrencyValue(item.total_discount || 0) }}
                        </span>
                    </template>

                    <template #cell-total="{ item }">
                        <span class="text-sm font-bold text-content-primary">
                            {{ formatCurrencyValue(item.total || 0) }}
                        </span>
                    </template>

                    <template #cell-created_at="{ item }">
                        <span
                            class="text-[11px] text-content-secondary font-medium whitespace-nowrap"
                        >
                            {{ formatDate(item.created_at) }}
                        </span>
                    </template>

                    <template #cell-status="{ item }">
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                getStatusClass(item.status),
                            ]"
                        >
                            {{ $t(`quotes.status.${item.status}`) }}
                        </span>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-center gap-1.5" @click.stop>
                            <Tooltip :text="$t('common.view')">
                                <button
                                    @click="navigateToShow(item)"
                                    class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors"
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
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                </button>
                            </Tooltip>

                            <Tooltip
                                v-if="
                                    can('quotes.update') &&
                                    (item.status === 'draft' || item.status === 'sent')
                                "
                                :text="$t('common.edit')"
                            >
                                <button
                                    @click="openEditModal(item)"
                                    class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors"
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
                                </button>
                            </Tooltip>
                        </div>
                    </template>

                    <template #pagination>
                        <AppPagination
                            :links="quotes.links"
                            :from="quotes.from"
                            :to="quotes.to"
                            :total="quotes.total"
                        />
                    </template>
                </DataTable>
            </div>
        </PrintEngine>

        <!-- Quote Form Modal -->
        <QuoteFormModal
            :show="showModal"
            :quote="selectedQuote"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="closeModal"
            @saved="handleSaved"
        />

        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader :title="$t('quotes.title')">
                    <template #extra>
                        <div class="text-[11px] text-content-secondary">
                            {{ $t('common.count') || 'العدد' }}:
                            <span class="font-mono font-bold">{{ quotes.data.length }}</span>
                        </div>
                    </template>
                </StandardReportHeader>

                <DataTable structured class="print-table">
                    <thead>
                        <tr>
                            <th class="align-middle text-center">#</th>
                            <th class="align-middle text-center">
                                {{ $t('quotes.columns.code') }}
                            </th>
                            <th class="align-middle text-center">{{ $t('common.date') }}</th>
                            <th class="align-middle text-center">
                                {{ $t('quotes.columns.vehicle') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('quotes.columns.customer') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('quotes.card.services') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('quotes.columns.total') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(quote, index) in quotes.data" :key="quote.id">
                            <td class="align-middle">{{ toEnglish(index + 1) }}</td>
                            <td class="align-middle">{{ toEnglish(quote.code) }}</td>
                            <td class="align-middle">{{ formatDate(quote.created_at) }}</td>
                            <td dir="ltr" class="text-left font-sans align-middle">
                                {{ toEnglish(quote.vehicle?.plate_number) || '-' }}
                            </td>
                            <td class="align-middle">{{ quote.customer?.name || '-' }}</td>
                            <td class="align-middle">{{ toEnglish(quote.lines_count || 0) }}</td>
                            <td class="align-middle">
                                {{ formatCurrencyValue(quote.total || 0) }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <StandardReportFooter class="mt-8 border-t pt-4" />
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import Tooltip from '@/Components/Tooltip.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    quotes: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    departments: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    colors: {
        type: Array,
        default: () => [],
    },
    modelsByMake: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterCounts: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const { success } = useToast();
const { formatCurrency: formatCurrencyEN, toEnglish, activeCurrencySymbol } = useNumberFormat();
const { can } = usePermission();

const showModal = ref(false);
const selectedQuote = ref(null);
const viewMode = ref(localStorage.getItem('quotesViewMode') || 'grid');

const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
    resetFilters,
    cancelDebouncedSearch,
} = useServerTable({
    routeUrl: '/app/quotes',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || 'pending',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
    },
    initialSort: {
        column: props.filters?.sort || 'created_at',
        direction: props.filters?.direction || 'desc',
    },
    debounceMs: 350,
});

const activeStatusTab = computed(() => tableFilters.status || 'pending');

const onTabChange = (tabId) => {
    cancelDebouncedSearch?.();
    tableFilters.status = tabId;
    applyFilters();
};

const handleResetFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.date_from = '';
    tableFilters.date_to = '';
    applyFilters();
};

watch([() => tableFilters.date_from, () => tableFilters.date_to], () => {
    applyFilters();
});

watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
            tableFilters.search = newFilters.search || '';
        }
        if (newFilters.status !== undefined && newFilters.status !== tableFilters.status) {
            tableFilters.status = newFilters.status || 'pending';
        }
        if (newFilters.date_from !== undefined && newFilters.date_from !== tableFilters.date_from) {
            tableFilters.date_from = newFilters.date_from || '';
        }
        if (newFilters.date_to !== undefined && newFilters.date_to !== tableFilters.date_to) {
            tableFilters.date_to = newFilters.date_to || '';
        }
        if (newFilters.sort !== undefined && newFilters.sort !== sortColumn.value) {
            sortColumn.value = newFilters.sort || 'created_at';
        }
        if (newFilters.direction !== undefined && newFilters.direction !== sortDirection.value) {
            sortDirection.value = newFilters.direction || 'desc';
        }
    },
    { deep: true }
);

const tabs = computed(() => [
    {
        id: 'pending',
        label: t('quotes.tabs.without_card'),
        count: props.filterCounts?.pending || 0,
    },
    {
        id: 'all',
        label: t('quotes.tabs.all'),
        count: props.filterCounts?.all || 0,
    },
    {
        id: 'approved',
        label: t('quotes.status.approved'),
        count: props.filterCounts?.approved || 0,
    },
    {
        id: 'converted',
        label: t('quotes.status.converted'),
        count: props.filterCounts?.converted || 0,
    },
    {
        id: 'rejected',
        label: t('quotes.status.rejected'),
        count: props.filterCounts?.rejected || 0,
    },
]);

const columns = computed(() => [
    { key: 'code', label: t('quotes.columns.code'), sortable: true, align: 'center' },
    { key: 'vehicle', label: t('quotes.columns.vehicle'), sortable: true, align: 'center' },
    { key: 'customer', label: t('quotes.columns.customer'), sortable: true, align: 'center' },
    { key: 'services', label: t('quotes.card.services'), sortable: true, align: 'center' },
    { key: 'subtotal', label: t('quotes.form.subtotal'), sortable: true, align: 'center' },
    { key: 'discount', label: t('quotes.form.discount'), sortable: true, align: 'center' },
    { key: 'total', label: t('quotes.columns.total'), sortable: true, align: 'center' },
    { key: 'created_at', label: t('common.created_at'), sortable: true, align: 'center' },
    { key: 'status', label: t('common.status'), sortable: false, align: 'center' },
    { key: 'actions', label: t('common.actions'), sortable: false, align: 'center' },
]);

function getStatusClass(status) {
    const classes = {
        draft: 'bg-surface-secondary text-content-secondary border border-border-default',
        sent: 'bg-info-soft text-info',
        approved: 'bg-success-soft text-success',
        rejected: 'bg-danger-soft text-danger',
        converted: 'bg-action-primary/10 text-action-primary',
    };
    return classes[status] || classes.draft;
}

function getStatusLineClass(status) {
    const classes = {
        draft: 'bg-border-default',
        sent: 'bg-info',
        approved: 'bg-success',
        rejected: 'bg-danger',
        converted: 'bg-action-primary',
    };
    return classes[status] || classes.draft;
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    const parts = [];
    if (vehicle.make?.name_ar || vehicle.make?.name_en || vehicle.make?.name) {
        parts.push(vehicle.make.name_ar || vehicle.make.name_en || vehicle.make.name);
    }
    if (vehicle.model?.name_ar || vehicle.model?.name_en || vehicle.model?.name) {
        parts.push(vehicle.model.name_ar || vehicle.model.name_en || vehicle.model.name);
    }
    if (vehicle.year) parts.push(vehicle.year);
    return parts.join(' ') || vehicle.make_other || '';
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const colors = {
        أبيض: '#ffffff',
        White: '#ffffff',
        أسود: '#000000',
        Black: '#000000',
        فضي: '#c0c0c0',
        Silver: '#c0c0c0',
        رمادي: '#808080',
        Gray: '#808080',
        أحمر: '#ff0000',
        Red: '#ff0000',
        أزرق: '#0000ff',
        Blue: '#0000ff',
        أصفر: '#ffff00',
        Yellow: '#ffff00',
        أخضر: '#008000',
        Green: '#008000',
    };
    return colors[colorName] || '#9ca3af';
}

function formatCurrencyValue(value) {
    return formatCurrencyEN(value);
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('en-US');
}

function openCreateModal() {
    selectedQuote.value = null;
    showModal.value = true;
}

function openEditModal(quote) {
    if (quote.status !== 'draft' && quote.status !== 'sent') {
        return;
    }
    selectedQuote.value = quote;
    showModal.value = true;
}

function navigateToShow(quote) {
    router.visit(route('app.quotes.show', quote.id));
}

function closeModal() {
    showModal.value = false;
    selectedQuote.value = null;
}

function exportQuotes() {
    const params = new URLSearchParams();
    if (tableFilters.search) params.append('search', tableFilters.search);
    if (tableFilters.status && tableFilters.status !== 'all')
        params.append('status', tableFilters.status);
    if (tableFilters.date_from) params.append('date_from', tableFilters.date_from);
    if (tableFilters.date_to) params.append('date_to', tableFilters.date_to);

    window.location.href =
        route('app.quotes.export') + (params.toString() ? '?' + params.toString() : '');
}

function printQuotes() {
    printDocument();
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['quotes'] });
}
</script>

<style scoped>
@media print {
    .print-section {
        display: block !important;
        font-family:
            'IBM Plex Sans Arabic',
            system-ui,
            -apple-system,
            sans-serif !important;
        padding: 20px;
        background: white;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #e5e7eb;
        padding: 8px;
        text-align: center !important;
    }

    .print-table th {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        font-weight: bold;
    }
}
</style>
