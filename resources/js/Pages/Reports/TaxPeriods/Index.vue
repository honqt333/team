<template>
    <AppLayout>
        <Head :title="$t('reports.tax_periods_report.title')" />

        <div class="space-y-6">
            <p class="text-sm text-gray-500 dark:text-gray-400" role="note">
                {{ $t('reports.tax_periods_report.consolidated_notice') }}
            </p>
            <!-- Top Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div
                        class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <Link
                            :href="route('app.reports.hub')"
                            class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                        >
                            {{ $t('reports.tax_periods_report.back_to_reports') }}
                        </Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ $t('reports.tax_periods') }}
                        </span>
                    </div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3"
                    >
                        <span>{{ $t('reports.tax_periods_report.title') }}</span>
                        <span
                            class="text-xs px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 font-bold rounded-full"
                        >
                            ZATCA
                        </span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t('reports.tax_periods_report.subtitle') }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showPeriodModal = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 transition-all duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        <span>{{ $t('reports.tax_periods_report.select_period_to_create') }}</span>
                    </button>
                </div>
            </div>

            <!-- Filters Bar (Using SearchableSelect per Constitution) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                    <!-- Year Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tax_periods_report.filter_year') }}
                        </label>
                        <SearchableSelect
                            v-model="filterYear"
                            :options="yearOptions"
                            :placeholder="$t('reports.tax_periods_report.all_years')"
                            class="w-full text-xs"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            {{ $t('reports.tax_periods_report.filter_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filterStatus"
                            :options="statusOptions"
                            :placeholder="$t('reports.tax_periods_report.all_statuses')"
                            class="w-full text-xs"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <div v-if="filterYear || filterStatus" class="flex items-center">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium py-2"
                        >
                            {{ $t('common.reset') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tax Periods Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
            >
                <div
                    class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                >
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('reports.tax_periods_report.created_periods') }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('reports.tax_periods_report.created_periods_desc') }}
                        </p>
                    </div>
                    <span
                        class="text-xs font-semibold px-2.5 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-300"
                    >
                        {{ taxPeriods.total ?? taxPeriods.data?.length ?? 0 }}
                        {{ $t('reports.tax_periods_report.quarterly_periods') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <DataTable structured class="w-full text-right text-sm">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase"
                        >
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.period_name') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.period_dates') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.status') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.transactions_count') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.sales_vat') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.purchases_vat') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.net_vat') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ $t('reports.tax_periods_report.posted_at') }}
                                </th>
                                <th class="px-4 py-3 text-center">
                                    {{ $t('reports.tax_periods_report.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="(period, idx) in taxPeriods.data"
                                :key="period.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td class="px-4 py-3.5 text-gray-500 text-xs">{{ idx + 1 }}</td>
                                <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span>{{ period.name }}</span>
                                        <span
                                            class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono"
                                        >
                                            {{ period.period_key }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 font-mono"
                                >
                                    {{ formatDate(period.start_date) }} &larr;
                                    {{ formatDate(period.end_date) }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        v-if="period.status === 'posted'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        {{ $t('reports.tax_periods_report.status_posted') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ $t('reports.tax_periods_report.status_draft') }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3.5 text-gray-700 dark:text-gray-300 font-medium"
                                >
                                    {{ period.transactions_count }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-emerald-600 dark:text-emerald-400 font-bold font-mono"
                                >
                                    {{ formatCurrency(period.sum_income_vat_amount) }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-rose-600 dark:text-rose-400 font-bold font-mono"
                                >
                                    {{ formatCurrency(period.sum_expense_vat_amount) }}
                                </td>
                                <td class="px-4 py-3.5 font-bold font-mono">
                                    <span
                                        :class="
                                            period.net_vat_amount >= 0
                                                ? 'text-emerald-700 dark:text-emerald-300'
                                                : 'text-blue-600 dark:text-blue-400'
                                        "
                                    >
                                        {{ formatCurrency(period.net_vat_amount) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ period.posted_at ? formatDate(period.posted_at) : '-' }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link
                                            :href="route('app.reports.tax-periods.show', period.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-300 transition-colors"
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
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.tax_periods_report.view_declaration'
                                                    )
                                                }}
                                            </span>
                                        </Link>

                                        <a
                                            :href="
                                                route(
                                                    'app.reports.tax-periods.export-zatca',
                                                    period.id
                                                )
                                            "
                                            :title="$t('reports.tax_periods_report.export_zatca')"
                                            class="p-1.5 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
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
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!taxPeriods.data || taxPeriods.data.length === 0">
                                <td
                                    colspan="10"
                                    class="py-12 text-center text-gray-400 dark:text-gray-500"
                                >
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg
                                            class="w-12 h-12 text-gray-300 dark:text-gray-600"
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
                                        <p class="text-sm font-medium">
                                            {{ $t('reports.tax_periods_report.no_periods') }}
                                        </p>
                                        <button
                                            type="button"
                                            @click="showPeriodModal = true"
                                            class="mt-2 text-xs font-semibold text-emerald-600 hover:text-emerald-700 underline"
                                        >
                                            {{
                                                $t(
                                                    'reports.tax_periods_report.select_period_to_create'
                                                )
                                            }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Pagination if multiple pages -->
                <AppPagination
                    class="print:hidden"
                    :links="taxPeriods.links || []"
                    :from="taxPeriods.from"
                    :to="taxPeriods.to"
                    :total="taxPeriods.total"
                />
            </div>

            <!-- Tax Period Selection Modal -->
            <div
                v-if="showPeriodModal"
                class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl max-w-4xl w-full border border-gray-100 dark:border-gray-700 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200"
                >
                    <div
                        class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                    >
                        <div>
                            <h2
                                class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span>
                                    {{ $t('reports.tax_periods_report.select_period_to_create') }}
                                </span>
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $t('reports.tax_periods_report.select_period_modal_desc') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="showPeriodModal = false"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Year Tabs -->
                    <div
                        class="px-6 pt-4 border-b border-gray-100 dark:border-gray-700/60 flex gap-2"
                    >
                        <button
                            v-for="yData in periods3Years"
                            :key="yData.year"
                            type="button"
                            @click="selectedModalYear = yData.year"
                            :class="[
                                'px-5 py-2.5 text-sm font-bold rounded-xl transition-all',
                                selectedModalYear === yData.year
                                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200',
                            ]"
                        >
                            {{ yData.year }}
                        </button>
                    </div>

                    <!-- Quarters Grid for Selected Year -->
                    <div class="p-6">
                        <div
                            v-for="yData in periods3Years"
                            :key="yData.year"
                            v-show="selectedModalYear === yData.year"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    v-for="q in yData.quarters"
                                    :key="q.key"
                                    class="p-5 rounded-2xl border transition-all duration-200"
                                    :class="[
                                        q.is_created
                                            ? q.status === 'posted'
                                                ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800'
                                                : 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800'
                                            : 'bg-gray-50/80 dark:bg-gray-700/30 border-gray-200 dark:border-gray-700 hover:border-emerald-300',
                                    ]"
                                >
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <span
                                                class="text-xs font-mono font-bold text-gray-400 dark:text-gray-500"
                                            >
                                                {{ q.key }}
                                            </span>
                                            <h3
                                                class="text-base font-bold text-gray-900 dark:text-white mt-0.5"
                                            >
                                                {{ q.name_ar }}
                                            </h3>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-1"
                                            >
                                                {{ q.start_date }} &larr; {{ q.end_date }}
                                            </p>
                                        </div>

                                        <!-- Badge Status -->
                                        <span
                                            v-if="q.is_created && q.status === 'posted'"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300"
                                        >
                                            {{ $t('reports.tax_periods_report.status_posted') }}
                                        </span>
                                        <span
                                            v-else-if="q.is_created"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300"
                                        >
                                            {{ $t('reports.tax_periods_report.status_draft') }}
                                        </span>
                                        <span
                                            v-else
                                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300"
                                        >
                                            {{ $t('reports.tax_periods_report.status_uncreated') }}
                                        </span>
                                    </div>

                                    <!-- If Created, show snapshot figures -->
                                    <div
                                        v-if="q.is_created"
                                        class="mt-4 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-xs"
                                    >
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ q.transactions_count }}
                                            {{ $t('reports.tax_periods_report.operations_unit') }}
                                        </span>
                                        <span
                                            class="font-bold font-mono"
                                            :class="
                                                q.net_vat_amount >= 0
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-blue-600'
                                            "
                                        >
                                            {{ $t('reports.tax_periods_report.net_vat') }}:
                                            {{ formatCurrency(q.net_vat_amount) }}
                                        </span>
                                    </div>

                                    <!-- Actions for Quarter -->
                                    <div class="mt-4 flex items-center gap-2">
                                        <Link
                                            v-if="q.is_created"
                                            :href="
                                                route(
                                                    'app.reports.tax-periods.show',
                                                    q.tax_period_id
                                                )
                                            "
                                            class="w-full text-center py-2 px-3 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 text-gray-800 dark:text-white border border-gray-200 dark:border-gray-600 hover:bg-gray-50 shadow-sm transition-colors"
                                        >
                                            {{ $t('reports.tax_periods_report.view_declaration') }}
                                        </Link>
                                        <Link
                                            v-else
                                            :href="route('app.reports.tax-periods.create', q.key)"
                                            class="w-full text-center py-2 px-3 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-500/20 transition-colors"
                                        >
                                            +
                                            {{ $t('reports.tax_periods_report.create_period_btn') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="p-4 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700 flex justify-end"
                    >
                        <button
                            type="button"
                            @click="showPeriodModal = false"
                            class="px-5 py-2 text-sm font-semibold rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300"
                        >
                            {{ $t('common.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { formatMoney } = useNumberFormat();

const props = defineProps({
    taxPeriods: Object,
    periods3Years: Array,
    filters: Object,
});

const showPeriodModal = ref(false);
const selectedModalYear = ref(props.periods3Years?.[0]?.year || new Date().getFullYear());

const filterYear = ref(props.filters?.year || '');
const filterStatus = ref(props.filters?.status || '');

const yearOptions = computed(() => {
    const list = [{ value: '', label: t('reports.tax_periods_report.all_years') }];
    if (props.periods3Years) {
        props.periods3Years.forEach((p) => {
            list.push({ value: String(p.year), label: String(p.year) });
        });
    }
    return list;
});

const statusOptions = computed(() => [
    { value: '', label: t('reports.tax_periods_report.all_statuses') },
    { value: 'draft', label: t('reports.tax_periods_report.status_draft') },
    { value: 'posted', label: t('reports.tax_periods_report.status_posted') },
]);

const applyFilters = () => {
    router.get(
        route('app.reports.tax-periods.index'),
        {
            year: filterYear.value || undefined,
            status: filterStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    filterYear.value = '';
    filterStatus.value = '';
    applyFilters();
};

const formatCurrency = (val) => formatMoney(val);

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return String(dateStr).split('T')[0];
};
</script>
