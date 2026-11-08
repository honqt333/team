<template>
    <AppLayout>
        <Head
            :title="`${$t('reports.tax_periods_report.create_period_btn')} - ${periodMeta.name_ar}`"
        />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div
                        class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <Link
                            :href="route('app.reports.tax-periods.index')"
                            class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                        >
                            {{ $t('reports.tax_periods_report.back_to_tax_periods') }}
                        </Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ periodMeta.name_ar }}
                        </span>
                    </div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3"
                    >
                        <span>{{ $t('reports.tax_periods_report.preview_transactions') }}</span>
                        <span
                            class="text-xs px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 font-bold rounded-full"
                        >
                            {{ periodMeta.period_key }}
                        </span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-mono">
                        {{ periodMeta.start_date }} &larr; {{ periodMeta.end_date }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="saveTaxPeriod"
                        :disabled="isSubmitting || selectedCount === 0"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all duration-200"
                    >
                        <svg
                            v-if="isSubmitting"
                            class="w-5 h-5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v8H4z"
                            ></path>
                        </svg>
                        <svg
                            v-else
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
                            />
                        </svg>
                        <span>
                            {{ $t('reports.tax_periods_report.save_period_btn') }} ({{
                                selectedCount
                            }})
                        </span>
                    </button>
                </div>
            </div>

            <!-- Live Calculation Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Sales / Income -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-emerald-100 dark:border-emerald-900/40 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.tax_periods_report.sales_and_income') }}
                        </span>
                        <span
                            class="text-xs px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-bold"
                        >
                            {{ computedSummary.income.count }}
                            {{ $t('reports.tax_periods_report.transactions_unit') }}
                        </span>
                    </div>
                    <div class="mt-4 space-y-1">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $t('reports.tax_periods_report.taxable_subtotal') }}:</span>
                            <span class="font-mono">
                                {{ formatCurrency(computedSummary.income.subtotal) }}
                            </span>
                        </div>
                        <div
                            class="flex justify-between text-base font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            <span>{{ $t('reports.tax_periods_report.sales_vat') }}:</span>
                            <span class="font-mono">
                                {{ formatCurrency(computedSummary.income.vat_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Purchases / Expenses -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-rose-100 dark:border-rose-900/40 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                            {{ $t('reports.tax_periods_report.purchases_and_expenses') }}
                        </span>
                        <span
                            class="text-xs px-2 py-0.5 rounded bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300 font-bold"
                        >
                            {{ computedSummary.expense.count }}
                            {{ $t('reports.tax_periods_report.transactions_unit') }}
                        </span>
                    </div>
                    <div class="mt-4 space-y-1">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $t('reports.tax_periods_report.taxable_subtotal') }}:</span>
                            <span class="font-mono">
                                {{ formatCurrency(computedSummary.expense.subtotal) }}
                            </span>
                        </div>
                        <div
                            class="flex justify-between text-base font-bold text-rose-600 dark:text-rose-400"
                        >
                            <span>{{ $t('reports.tax_periods_report.purchases_vat') }}:</span>
                            <span class="font-mono">
                                {{ formatCurrency(computedSummary.expense.vat_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Net VAT -->
                <div
                    class="bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-2xl p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-300">
                            {{ $t('reports.tax_periods_report.net_vat') }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded bg-white/20 text-white font-bold">
                            {{ $t('reports.tax_periods_report.zatca_box_13_badge') }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <div
                            class="text-2xl font-black font-mono"
                            :class="
                                computedSummary.total.net_vat_amount >= 0
                                    ? 'text-emerald-400'
                                    : 'text-cyan-400'
                            "
                        >
                            {{ formatCurrency(computedSummary.total.net_vat_amount) }}
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            {{
                                computedSummary.total.net_vat_amount >= 0
                                    ? $t('reports.tax_periods_report.net_vat_due')
                                    : $t('reports.tax_periods_report.net_vat_refund')
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Transactions Preview Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
            >
                <div
                    class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="toggleAll"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200"
                        >
                            {{ allSelected ? $t('common.deselect_all') : $t('common.select_all') }}
                        </button>
                        <span class="text-xs text-gray-500">
                            {{
                                $t('reports.tax_periods_report.selected_summary', {
                                    selected: selectedCount,
                                    total: items.length,
                                })
                            }}
                        </span>
                    </div>

                    <!-- Type Filter inside Preview (Using SearchableSelect) -->
                    <div class="w-64">
                        <SearchableSelect
                            v-model="previewTypeFilter"
                            :options="typeFilterOptions"
                            :placeholder="$t('reports.tax_periods_report.all_types')"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase"
                        >
                            <tr>
                                <th class="p-4 w-12 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        @change="toggleAll"
                                        class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                </th>
                                <th class="px-3 py-3">#</th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.ref_no') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.date') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.type') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.party') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.tax_number') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.taxable_subtotal') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.vat_amount') }}
                                </th>
                                <th class="px-3 py-3">
                                    {{ $t('reports.tax_periods_report.total_with_vat') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="(tx, idx) in filteredItems"
                                :key="tx.transaction_type + '_' + (tx.transaction_id || idx)"
                                :class="[
                                    'hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors',
                                    !tx.is_selected
                                        ? 'opacity-40 bg-gray-50/40 dark:bg-gray-900/20'
                                        : '',
                                ]"
                            >
                                <td class="p-4 text-center">
                                    <input
                                        type="checkbox"
                                        v-model="tx.is_selected"
                                        class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                </td>
                                <td class="px-3 py-3.5 text-xs text-gray-500">{{ idx + 1 }}</td>
                                <td
                                    class="px-3 py-3.5 font-bold font-mono text-gray-900 dark:text-white"
                                >
                                    {{ tx.ref_no }}
                                </td>
                                <td
                                    class="px-3 py-3.5 text-xs text-gray-500 dark:text-gray-400 font-mono"
                                >
                                    {{ tx.transaction_date }}
                                </td>
                                <td class="px-3 py-3.5">
                                    <span
                                        class="inline-block px-2.5 py-0.5 rounded text-[11px] font-semibold"
                                        :class="
                                            getTypeBadgeClass(tx.transaction_type, tx.is_income)
                                        "
                                    >
                                        {{
                                            $t(
                                                `reports.tax_periods_report.types.${tx.transaction_type}`
                                            )
                                        }}
                                    </span>
                                </td>
                                <td class="px-3 py-3.5 text-gray-800 dark:text-gray-200">
                                    {{ tx.party_name || '-' }}
                                </td>
                                <td class="px-3 py-3.5 text-xs font-mono text-gray-500">
                                    {{ tx.tax_number || '-' }}
                                </td>
                                <td class="px-3 py-3.5 font-mono text-gray-700 dark:text-gray-300">
                                    {{ formatCurrency(tx.subtotal) }}
                                </td>
                                <td
                                    class="px-3 py-3.5 font-bold font-mono"
                                    :class="tx.is_income ? 'text-emerald-600' : 'text-rose-600'"
                                >
                                    {{ formatCurrency(tx.vat_amount) }}
                                </td>
                                <td
                                    class="px-3 py-3.5 font-bold font-mono text-gray-900 dark:text-white"
                                >
                                    {{ formatCurrency(tx.total_amount) }}
                                </td>
                            </tr>

                            <tr v-if="filteredItems.length === 0">
                                <td colspan="10" class="py-10 text-center text-gray-400 text-xs">
                                    {{ $t('reports.tax_periods_report.no_transactions') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    periodMeta: Object,
    transactions: Array,
    summary: Object,
    existingPeriod: Object,
});

const items = ref(
    props.transactions ? props.transactions.map((item) => ({ ...item, is_selected: true })) : []
);

const previewTypeFilter = ref('all');
const isSubmitting = ref(false);

const typeFilterOptions = computed(() => [
    { value: 'all', label: t('reports.tax_periods_report.all_types') },
    { value: 'sales_invoice', label: t('reports.tax_periods_report.types.sales_invoice') },
    { value: 'credit_note', label: t('reports.tax_periods_report.types.credit_note') },
    { value: 'purchase_invoice', label: t('reports.tax_periods_report.types.purchase_invoice') },
    { value: 'purchase_return', label: t('reports.tax_periods_report.types.purchase_return') },
    {
        value: 'company_transaction',
        label: t('reports.tax_periods_report.types.company_transaction'),
    },
]);

const filteredItems = computed(() => {
    if (previewTypeFilter.value === 'all') {
        return items.value;
    }
    return items.value.filter((it) => it.transaction_type === previewTypeFilter.value);
});

const selectedCount = computed(() => items.value.filter((it) => it.is_selected).length);
const allSelected = computed(
    () => items.value.length > 0 && selectedCount.value === items.value.length
);

const toggleAll = () => {
    const targetState = !allSelected.value;
    items.value.forEach((it) => {
        it.is_selected = targetState;
    });
};

const computedSummary = computed(() => {
    let incCount = 0;
    let incSub = 0;
    let incVat = 0;
    let incTot = 0;

    let expCount = 0;
    let expSub = 0;
    let expVat = 0;
    let expTot = 0;

    items.value.forEach((tx) => {
        if (!tx.is_selected) return;

        const sub = Number(tx.subtotal || 0);
        const vat = Number(tx.vat_amount || 0);
        const tot = Number(tx.total_amount || 0);

        if (tx.is_income) {
            incCount++;
            incSub += sub;
            incVat += vat;
            incTot += tot;
        } else {
            expCount++;
            expSub += sub;
            expVat += vat;
            expTot += tot;
        }
    });

    return {
        income: {
            count: incCount,
            subtotal: Math.round(incSub * 100) / 100,
            vat_amount: Math.round(incVat * 100) / 100,
            total_amount: Math.round(incTot * 100) / 100,
        },
        expense: {
            count: expCount,
            subtotal: Math.round(expSub * 100) / 100,
            vat_amount: Math.round(expVat * 100) / 100,
            total_amount: Math.round(expTot * 100) / 100,
        },
        total: {
            count: incCount + expCount,
            subtotal: Math.round((incSub + expSub) * 100) / 100,
            vat_amount: Math.round((incVat + expVat) * 100) / 100,
            total_amount: Math.round((incTot + expTot) * 100) / 100,
            net_vat_amount: Math.round((incVat - expVat) * 100) / 100,
        },
    };
});

const getTypeBadgeClass = (type, isIncome) => {
    if (type === 'sales_invoice')
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
    if (type === 'credit_note')
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
    if (type === 'purchase_invoice')
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
    if (type === 'purchase_return')
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300';
    return isIncome ? 'bg-teal-100 text-teal-800' : 'bg-rose-100 text-rose-800';
};

const saveTaxPeriod = () => {
    if (selectedCount.value === 0 || isSubmitting.value) return;

    isSubmitting.value = true;
    router.post(
        route('app.reports.tax-periods.store'),
        {
            period_key: props.periodMeta.period_key,
            transactions: items.value,
        },
        {
            onFinish: () => {
                isSubmitting.value = false;
            },
        }
    );
};

const formatCurrency = (val) => {
    const num = Number(val || 0);
    return (
        num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
        ' ' +
        t('common.currency')
    );
};
</script>
