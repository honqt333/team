<template>
    <Head :title="$t('reports.dealers_report.title')" />
    <AppLayout :title="$t('reports.dealers_report.title')">
        <div class="space-y-6">
            <PageHeader
                :title="$t('reports.dealers_report.title')"
                :subtitle="$t('reports.dealers_report.subtitle')"
                gradientFrom="from-indigo-500"
                gradientTo="to-violet-600"
                glowFrom="from-indigo-500"
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
                        <AppPrintButton
                            variant="secondary"
                            :label="$t('reports.dealers_report.print_report')"
                            @click="handlePrint"
                        />
                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-bold transition-all shadow-lg shadow-indigo-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.dealers_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Summary Strip -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700/80 shadow-md flex flex-wrap items-center justify-between gap-3 text-xs"
            >
                <div class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-pulse"></span>
                    <span class="text-sm font-black">
                        {{ $t('reports.dealers_report.total_summary') }}:
                    </span>
                </div>

                <div
                    class="flex flex-wrap items-center gap-3 sm:gap-5 divide-x rtl:divide-x-reverse divide-gray-200 dark:divide-gray-700"
                >
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $t('reports.dealers_report.total_records') }}:
                        </span>
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ kpis.total_records }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold">
                            {{ $t('reports.dealers_report.dealer_billed_total') }}:
                        </span>
                        <span class="font-mono font-bold text-indigo-700 dark:text-indigo-300">
                            {{ formatMoney(kpis.dealer_billed_total) }}
                            {{ $t('reports.dealers_report.currency') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-sky-600 dark:text-sky-400 font-semibold">
                            {{ $t('reports.dealers_report.total_payment_due') }}:
                        </span>
                        <span class="font-mono font-bold text-sky-700 dark:text-sky-300">
                            {{ formatMoney(kpis.total_payment_due) }}
                            {{ $t('reports.dealers_report.currency') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-teal-600 dark:text-teal-400 font-semibold">
                            {{ $t('reports.dealers_report.total_amount_paid') }}:
                        </span>
                        <span class="font-mono font-bold text-teal-700 dark:text-teal-300">
                            {{ formatMoney(kpis.total_amount_paid) }}
                            {{ $t('reports.dealers_report.currency') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 ps-3">
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">
                            {{ $t('reports.dealers_report.total_payment_remaining') }}:
                        </span>
                        <span class="font-mono font-bold text-amber-700 dark:text-amber-300">
                            {{ formatMoney(kpis.total_payment_remaining) }}
                            {{ $t('reports.dealers_report.currency') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm p-4 sm:p-5 space-y-4 print:hidden"
            >
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4"
                >
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
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700',
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <div class="relative w-full lg:w-96">
                        <input
                            v-model="filterForm.search"
                            type="text"
                            :placeholder="$t('reports.dealers_report.search_placeholder')"
                            class="w-full ps-10 pe-4 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
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

                <div
                    v-if="filterForm.range === 'custom'"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-100 dark:border-indigo-900/40"
                >
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.dealers_report.date_from') }}
                        </label>
                        <CustomDatePicker v-model="filterForm.start_date" @change="applyFilters" />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.dealers_report.date_to') }}
                        </label>
                        <CustomDatePicker v-model="filterForm.end_date" @change="applyFilters" />
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700/60"
                >
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.dealers_report.dealer') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.dealer_id"
                            :options="dealerOptions"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.dealers_report.dealer_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.dealer_type"
                            :options="dealerTypeOptions"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.dealers_report.billing_mode') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.billing_mode"
                            :options="billingModeOptions"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.dealers_report.center') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.center_id"
                            :options="centerOptions"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.dealers_report.payment_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.payment_status"
                            :options="paymentStatusOptions"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('reports.dealers_report.group_by') }}
                        </label>
                        <SearchableSelect
                            v-model="filterForm.group_by"
                            :options="groupByOptions"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Grouped View -->
            <div v-if="groupedRecords && groupedRecords.length" class="space-y-4">
                <div class="flex justify-end gap-2 print:hidden">
                    <button
                        type="button"
                        @click="expandAll"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200"
                    >
                        {{ $t('reports.dealers_report.expand_all') }}
                    </button>
                    <button
                        type="button"
                        @click="collapseAll"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200"
                    >
                        {{ $t('reports.dealers_report.collapse_all') }}
                    </button>
                </div>

                <div
                    v-for="group in groupedRecords"
                    :key="group.group_key"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm overflow-hidden"
                >
                    <button
                        type="button"
                        @click="toggleGroup(group.group_key)"
                        class="w-full px-5 py-4 flex flex-wrap items-center justify-between gap-3 bg-gray-50/70 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700 text-start cursor-pointer"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="text-sm font-black text-gray-900 dark:text-white">
                                {{ group.group_label }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300"
                            >
                                {{ group.total_records }}
                                {{ $t('reports.dealers_report.records_count') }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-xs font-mono font-bold">
                            <span>
                                {{ $t('reports.dealers_report.due') }}:
                                {{ formatMoney(group.total_payment_due) }}
                            </span>
                            <span class="text-teal-600">
                                {{ $t('reports.dealers_report.paid_short') }}:
                                {{ formatMoney(group.total_amount_paid) }}
                            </span>
                            <span class="text-amber-600">
                                {{ $t('reports.dealers_report.remaining') }}:
                                {{ formatMoney(group.total_payment_remaining) }}
                            </span>
                        </div>
                    </button>

                    <div v-if="openGroups[group.group_key]" class="overflow-x-auto">
                        <DataTable :columns="tableColumns" :data="group.items">
                            <template #cell-code="{ item: row }">
                                <div class="flex flex-col">
                                    <Link
                                        :href="`/app/work-orders/${row.id}`"
                                        class="font-mono font-bold text-action-primary hover:underline"
                                    >
                                        {{ row.code }}
                                    </Link>
                                    <span
                                        v-if="row.invoice"
                                        class="font-mono text-[11px] text-gray-500"
                                    >
                                        {{ row.invoice.invoice_number }}
                                    </span>
                                </div>
                            </template>
                            <template #cell-dealer="{ item: row }">
                                <Link
                                    v-if="row.dealer?.id"
                                    :href="route('app.dealers.show', row.dealer.id)"
                                    class="font-bold text-gray-900 dark:text-white hover:text-action-primary"
                                >
                                    {{ row.dealer.name }}
                                </Link>
                            </template>
                            <template #cell-billing="{ item: row }">
                                <span
                                    class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="
                                        row.is_paid_by_dealer
                                            ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        row.is_paid_by_dealer
                                            ? $t('reports.dealers_report.billing_dealer')
                                            : $t('reports.dealers_report.billing_customer')
                                    }}
                                </span>
                            </template>
                            <template #cell-customer="{ item: row }">
                                <span class="text-xs text-gray-800 dark:text-gray-200">
                                    {{ row.customer?.name }}
                                </span>
                            </template>
                            <template #cell-vehicle="{ item: row }">
                                <div class="flex flex-col text-xs">
                                    <span>{{ row.vehicle?.name }}</span>
                                    <span class="font-mono text-gray-500">
                                        {{ row.vehicle?.plate_number }}
                                    </span>
                                </div>
                            </template>
                            <template #cell-payment_due="{ item: row }">
                                <span class="font-mono font-bold">
                                    {{ formatMoney(row.payment_due) }}
                                </span>
                            </template>
                            <template #cell-amount_paid="{ item: row }">
                                <span class="font-mono font-bold text-teal-600">
                                    {{ formatMoney(row.amount_paid) }}
                                </span>
                            </template>
                            <template #cell-payment_remaining="{ item: row }">
                                <span class="font-mono font-bold text-amber-600">
                                    {{ formatMoney(row.payment_remaining) }}
                                </span>
                            </template>
                            <template #cell-payment_status="{ item: row }">
                                <span
                                    class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="getPaymentStatusBadgeClass(row.payment_status)"
                                >
                                    {{ $t(`reports.dealers_report.${row.payment_status}`) }}
                                </span>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>

            <!-- Flat Table View -->
            <div v-else-if="records && records.data && records.data.length" class="space-y-4">
                <DataTable :columns="tableColumns" :data="records" :show-pagination="false">
                    <template #cell-code="{ item: row }">
                        <div class="flex flex-col">
                            <Link
                                :href="`/app/work-orders/${row.id}`"
                                class="font-mono font-bold text-action-primary hover:underline"
                            >
                                {{ row.code }}
                            </Link>
                            <span v-if="row.invoice" class="font-mono text-[11px] text-gray-500">
                                {{ row.invoice.invoice_number }}
                            </span>
                        </div>
                    </template>
                    <template #cell-dealer="{ item: row }">
                        <div class="flex flex-col">
                            <Link
                                v-if="row.dealer?.id"
                                :href="route('app.dealers.show', row.dealer.id)"
                                class="font-bold text-gray-900 dark:text-white hover:text-action-primary"
                            >
                                {{ row.dealer.name }}
                            </Link>
                            <span v-if="row.dealer?.type" class="text-[11px] text-gray-500">
                                {{ $t(`reports.dealers_report.types.${row.dealer.type}`) }}
                            </span>
                        </div>
                    </template>
                    <template #cell-billing="{ item: row }">
                        <span
                            class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="
                                row.is_paid_by_dealer
                                    ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{
                                row.is_paid_by_dealer
                                    ? $t('reports.dealers_report.billing_dealer')
                                    : $t('reports.dealers_report.billing_customer')
                            }}
                        </span>
                    </template>
                    <template #cell-customer="{ item: row }">
                        <div class="flex flex-col text-xs">
                            <span class="font-semibold text-gray-800 dark:text-gray-200">
                                {{ row.customer?.name }}
                            </span>
                            <span class="font-mono text-gray-500" dir="ltr">
                                {{ row.customer?.phone }}
                            </span>
                        </div>
                    </template>
                    <template #cell-vehicle="{ item: row }">
                        <div class="flex flex-col text-xs">
                            <span>{{ row.vehicle?.name }}</span>
                            <span class="font-mono text-gray-500">
                                {{ row.vehicle?.plate_number }}
                            </span>
                        </div>
                    </template>
                    <template #cell-payment_due="{ item: row }">
                        <span class="font-mono font-bold text-gray-900 dark:text-white">
                            {{ formatMoney(row.payment_due) }}
                        </span>
                    </template>
                    <template #cell-amount_paid="{ item: row }">
                        <span class="font-mono font-bold text-teal-600 dark:text-teal-400">
                            {{ formatMoney(row.amount_paid) }}
                        </span>
                    </template>
                    <template #cell-payment_remaining="{ item: row }">
                        <span
                            class="font-mono font-bold"
                            :class="
                                row.payment_remaining > 0
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : 'text-gray-400'
                            "
                        >
                            {{ formatMoney(row.payment_remaining) }}
                        </span>
                    </template>
                    <template #cell-payment_status="{ item: row }">
                        <span
                            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="getPaymentStatusBadgeClass(row.payment_status)"
                        >
                            {{ $t(`reports.dealers_report.${row.payment_status}`) }}
                        </span>
                    </template>
                </DataTable>

                <AppPagination
                    class="print:hidden"
                    :links="records.links || []"
                    :from="records.from"
                    :to="records.to"
                    :total="records.total"
                />
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 p-12 text-center print:hidden"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">
                    {{ $t('reports.dealers_report.no_records') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto mb-4">
                    {{ $t('reports.dealers_report.no_records_hint') }}
                </p>
                <button
                    type="button"
                    @click="resetFilters"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer"
                >
                    {{ $t('common.reset_filters') }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { printDocument } from '@/Composables/usePrint';

const { t } = useI18n();

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({}) },
    records: { type: Object, default: null },
    groupedRecords: { type: Array, default: () => [] },
    centers: { type: Array, default: () => [] },
    dealers: { type: Array, default: () => [] },
    dealerTypes: { type: Array, default: () => ['insurance', 'fleet', 'rental', 'corporate'] },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    date_type: props.filters.date_type || 'entry_date',
    center_id: props.filters.center_id || 'all',
    dealer_id: props.filters.dealer_id || 'all',
    dealer_type: props.filters.dealer_type || 'all',
    billing_mode: props.filters.billing_mode || 'all',
    payment_status: props.filters.payment_status || 'all',
    group_by: props.filters.group_by || 'none',
    search: props.filters.search || '',
});

const openGroups = ref({});
onMounted(() => {
    if (props.groupedRecords?.length) {
        props.groupedRecords.forEach((g) => {
            openGroups.value[g.group_key] = true;
        });
    }
});

const toggleGroup = (key) => {
    openGroups.value[key] = !openGroups.value[key];
};
const expandAll = () => {
    props.groupedRecords?.forEach((g) => {
        openGroups.value[g.group_key] = true;
    });
};
const collapseAll = () => {
    openGroups.value = {};
};

const datePresets = computed(() => [
    { id: 'today', label: t('reports.dealers_report.today') },
    { id: 'yesterday', label: t('reports.dealers_report.yesterday') },
    { id: 'this_week', label: t('reports.dealers_report.this_week') },
    { id: 'this_month', label: t('reports.dealers_report.this_month') },
    { id: 'this_quarter', label: t('reports.dealers_report.this_quarter') },
    { id: 'this_year', label: t('reports.dealers_report.this_year') },
    { id: 'all', label: t('reports.dealers_report.all_time') },
    { id: 'custom', label: t('reports.dealers_report.custom_date') },
]);

const dealerOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.dealers_report.all_dealers'),
        label: t('reports.dealers_report.all_dealers'),
    },
    ...props.dealers.map((d) => ({ id: String(d.id), name: d.name, label: d.name })),
]);

const dealerTypeOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.dealers_report.all_types'),
        label: t('reports.dealers_report.all_types'),
    },
    ...props.dealerTypes.map((typeKey) => ({
        id: typeKey,
        name: t(`reports.dealers_report.types.${typeKey}`),
        label: t(`reports.dealers_report.types.${typeKey}`),
    })),
]);

const billingModeOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.dealers_report.all_billing'),
        label: t('reports.dealers_report.all_billing'),
    },
    {
        id: 'dealer_billed',
        name: t('reports.dealers_report.billing_dealer'),
        label: t('reports.dealers_report.billing_dealer'),
    },
    {
        id: 'customer_billed',
        name: t('reports.dealers_report.billing_customer'),
        label: t('reports.dealers_report.billing_customer'),
    },
]);

const centerOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.dealers_report.all_centers'),
        label: t('reports.dealers_report.all_centers'),
    },
    ...props.centers.map((c) => ({ id: String(c.id), name: c.name, label: c.name })),
]);

const paymentStatusOptions = computed(() => [
    {
        id: 'all',
        name: t('reports.dealers_report.all_statuses'),
        label: t('reports.dealers_report.all_statuses'),
    },
    { id: 'paid', name: t('reports.dealers_report.paid'), label: t('reports.dealers_report.paid') },
    {
        id: 'partial',
        name: t('reports.dealers_report.partial'),
        label: t('reports.dealers_report.partial'),
    },
    {
        id: 'unpaid',
        name: t('reports.dealers_report.unpaid'),
        label: t('reports.dealers_report.unpaid'),
    },
]);

const groupByOptions = computed(() => [
    {
        id: 'none',
        name: t('reports.dealers_report.group_none'),
        label: t('reports.dealers_report.group_none'),
    },
    {
        id: 'dealer',
        name: t('reports.dealers_report.group_dealer'),
        label: t('reports.dealers_report.group_dealer'),
    },
    {
        id: 'dealer_type',
        name: t('reports.dealers_report.group_dealer_type'),
        label: t('reports.dealers_report.group_dealer_type'),
    },
    {
        id: 'billing_mode',
        name: t('reports.dealers_report.group_billing_mode'),
        label: t('reports.dealers_report.group_billing_mode'),
    },
    {
        id: 'center',
        name: t('reports.dealers_report.group_center'),
        label: t('reports.dealers_report.group_center'),
    },
]);

const tableColumns = computed(() => [
    { key: 'code', label: t('reports.dealers_report.col_code') },
    { key: 'dealer', label: t('reports.dealers_report.col_dealer') },
    { key: 'billing', label: t('reports.dealers_report.col_billing') },
    { key: 'customer', label: t('reports.dealers_report.col_customer') },
    { key: 'vehicle', label: t('reports.dealers_report.col_vehicle') },
    { key: 'entry_date', label: t('reports.dealers_report.col_entry_date') },
    { key: 'payment_due', label: t('reports.dealers_report.col_due'), align: 'end' },
    { key: 'amount_paid', label: t('reports.dealers_report.col_paid'), align: 'end' },
    { key: 'payment_remaining', label: t('reports.dealers_report.col_remaining'), align: 'end' },
    { key: 'payment_status', label: t('reports.dealers_report.col_status'), align: 'center' },
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
        route('app.reports.dealers'),
        {
            range: filterForm.range,
            start_date: filterForm.start_date || undefined,
            end_date: filterForm.end_date || undefined,
            date_type: filterForm.date_type,
            center_id: filterForm.center_id || 'all',
            dealer_id: filterForm.dealer_id || 'all',
            dealer_type: filterForm.dealer_type || 'all',
            billing_mode: filterForm.billing_mode || 'all',
            payment_status: filterForm.payment_status || 'all',
            group_by: filterForm.group_by,
            search: filterForm.search || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    filterForm.range = 'this_month';
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.center_id = 'all';
    filterForm.dealer_id = 'all';
    filterForm.dealer_type = 'all';
    filterForm.billing_mode = 'all';
    filterForm.payment_status = 'all';
    filterForm.group_by = 'none';
    filterForm.search = '';
    applyFilters();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.entries(filterForm).forEach(([k, v]) => {
        if (v !== '' && v !== undefined && v !== null) params.append(k, v);
    });
    return `${route('app.reports.dealers.export')}?${params.toString()}`;
});

const handlePrint = () => {
    printDocument({ title: t('reports.dealers_report.title') });
};

const formatMoney = (val) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(val) || 0);
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
