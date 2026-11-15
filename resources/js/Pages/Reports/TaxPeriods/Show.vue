<template>
    <AppLayout>
        <Head :title="`${taxPeriod.name} - ${$t('reports.tax_periods_report.title')}`" />

        <div class="space-y-6">
            <!-- Header Section (Screen Only) -->
            <div class="print:hidden">
                <PageHeader
                    :title="`${taxPeriod.name} (${taxPeriod.period_key})`"
                    :subtitle="`${formatDate(taxPeriod.start_date)} ← ${formatDate(taxPeriod.end_date)} • ${
                        taxPeriod.status === 'posted'
                            ? $t('reports.tax_periods_report.status_posted')
                            : $t('reports.tax_periods_report.status_draft')
                    }`"
                    gradientFrom="from-emerald-600"
                    gradientTo="to-teal-700"
                    glowFrom="from-emerald-500"
                    badgeBg="bg-emerald-50/50 dark:bg-emerald-900/30"
                    badgeText="text-emerald-600 dark:text-emerald-400"
                    badgeBorder="border-emerald-100/50 dark:border-emerald-800/30"
                    badgeDot="bg-emerald-500"
                >
                    <template #back>
                        <BackButton :href="route('app.reports.tax-periods.index')" />
                    </template>
                    <template #actions>
                        <div class="flex flex-wrap items-center gap-2">
                            <AppButton
                                as="a"
                                variant="secondary"
                                size="sm"
                                :href="route('app.reports.tax-periods.export-excel', taxPeriod.id)"
                            >
                                {{ $t('reports.tax_periods_report.export_excel') }}
                            </AppButton>
                            <AppButton
                                as="a"
                                variant="secondary"
                                size="sm"
                                :href="route('app.reports.tax-periods.export-zatca', taxPeriod.id)"
                            >
                                {{ $t('reports.tax_periods_report.export_zatca') }}
                            </AppButton>
                            <AppButton
                                v-if="taxPeriod.status === 'draft'"
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="handleRecreatePeriod"
                            >
                                {{ $t('reports.tax_periods_report.recreate_period_btn') }}
                            </AppButton>
                            <AppPrintButton
                                @click="handlePrint"
                                :label="$t('reports.tax_periods_report.print_declaration')"
                            />
                            <AppButton
                                v-if="taxPeriod.status === 'draft'"
                                type="button"
                                size="sm"
                                @click="handlePostPeriod"
                            >
                                {{ $t('reports.tax_periods_report.post_period_btn') }}
                            </AppButton>
                        </div>
                    </template>
                </PageHeader>
            </div>

            <!-- Posted Notice Banner (If Locked) -->
            <div
                v-if="taxPeriod.status === 'posted'"
                class="print:hidden bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 flex items-center gap-4"
            >
                <div
                    class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
                <div class="text-xs text-emerald-900 dark:text-emerald-200">
                    <p class="font-bold text-sm">
                        {{ $t('reports.tax_periods_report.posted_notice') }}
                    </p>
                    <p class="mt-0.5 text-emerald-700 dark:text-emerald-300">
                        {{ $t('reports.tax_periods_report.posted_at') }}:
                        <span class="font-mono font-bold">
                            {{ formatDate(taxPeriod.posted_at) }}
                        </span>
                        <span v-if="taxPeriod.poster">
                            &bull; {{ $t('reports.tax_periods_report.posted_by') }}:
                            <span class="font-bold">{{ taxPeriod.poster.name }}</span>
                        </span>
                        <span v-if="taxPeriod.creator">
                            &bull; {{ $t('reports.tax_periods_report.created_by') }}:
                            <span class="font-bold">{{ taxPeriod.creator.name }}</span>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Printable Declaration Document Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-6"
            >
                <!-- ZATCA Form Header -->
                <div
                    class="border-b border-gray-200 dark:border-gray-700 pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-emerald-500/20"
                            >
                                Z
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $t('reports.tax_periods_report.zatca_official_header') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('reports.tax_periods_report.zatca_summary_subtitle') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-right space-y-1">
                        <div>
                            <span class="text-gray-500">
                                {{ $t('reports.tax_periods_report.facility_name') }}:
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ tenant?.name }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">
                                {{ $t('reports.tax_periods_report.tax_number_label') }}:
                            </span>
                            <span
                                class="font-mono font-bold text-emerald-700 dark:text-emerald-400"
                            >
                                {{ tenant?.vat_number || '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">
                                {{ $t('reports.tax_periods_report.tax_period_label') }}:
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ taxPeriod.name }} ({{ taxPeriod.period_key }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ZATCA Official Return Table (Boxes 1-13) -->
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                        {{ $t('reports.tax_periods_report.zatca_summary_title') }}
                    </h3>
                    <div
                        class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700"
                    >
                        <DataTable structured class="w-full text-right text-xs">
                            <thead
                                class="bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 font-bold"
                            >
                                <tr>
                                    <th class="p-3 w-14 text-center">
                                        {{ $t('reports.tax_periods_report.box_number') }}
                                    </th>
                                    <th class="p-3">
                                        {{ $t('reports.tax_periods_report.box_description') }}
                                    </th>
                                    <th class="p-3 text-center">
                                        {{ $t('reports.tax_periods_report.transactions_count') }}
                                    </th>
                                    <th class="p-3 text-left">
                                        {{
                                            $t('reports.tax_periods_report.taxable_amount_sar', {
                                                currency: activeCurrencySymbol,
                                            })
                                        }}
                                    </th>
                                    <th class="p-3 text-left">
                                        {{
                                            $t('reports.tax_periods_report.adjustment_amount_sar', {
                                                currency: activeCurrencySymbol,
                                            })
                                        }}
                                    </th>
                                    <th class="p-3 text-left">
                                        {{
                                            $t('reports.tax_periods_report.vat_amount_sar', {
                                                currency: activeCurrencySymbol,
                                            })
                                        }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <!-- Box 1: Sales 15% -->
                                <tr class="bg-emerald-50/30 dark:bg-emerald-950/10 font-medium">
                                    <td
                                        class="p-3 text-center font-bold font-mono text-emerald-700 dark:text-emerald-400"
                                    >
                                        1
                                    </td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.zatca_box_sales') }}
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold">
                                        {{ summary.income.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono font-bold">
                                        {{ formatCurrency(summary.income.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        {{ formatCurrency(0) }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(summary.income.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 6: Total Sales -->
                                <tr
                                    class="bg-gray-50/60 dark:bg-gray-900/20 font-bold border-b-2 border-gray-200 dark:border-gray-600"
                                >
                                    <td class="p-3 text-center font-mono">6</td>
                                    <td class="p-3 text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.total_sales_label') }}
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        {{ summary.income.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono">
                                        {{ formatCurrency(summary.income.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        {{ formatCurrency(0) }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(summary.income.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 7: Purchases 15% -->
                                <tr class="bg-rose-50/30 dark:bg-rose-950/10 font-medium">
                                    <td
                                        class="p-3 text-center font-bold font-mono text-rose-700 dark:text-rose-400"
                                    >
                                        7
                                    </td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.zatca_box_purchases') }}
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold">
                                        {{ summary.expense.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono font-bold">
                                        {{ formatCurrency(summary.expense.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        {{ formatCurrency(0) }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono font-bold text-rose-600 dark:text-rose-400"
                                    >
                                        {{ formatCurrency(summary.expense.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 12: Total Purchases -->
                                <tr
                                    class="bg-gray-50/60 dark:bg-gray-900/20 font-bold border-b-2 border-gray-200 dark:border-gray-600"
                                >
                                    <td class="p-3 text-center font-mono">12</td>
                                    <td class="p-3 text-gray-900 dark:text-white">
                                        {{ $t('reports.tax_periods_report.total_purchases_label') }}
                                    </td>
                                    <td class="p-3 text-center font-mono">
                                        {{ summary.expense.count }}
                                    </td>
                                    <td class="p-3 text-left font-mono">
                                        {{ formatCurrency(summary.expense.subtotal) }}
                                    </td>
                                    <td class="p-3 text-left font-mono text-gray-400">
                                        {{ formatCurrency(0) }}
                                    </td>
                                    <td
                                        class="p-3 text-left font-mono text-rose-600 dark:text-rose-400"
                                    >
                                        {{ formatCurrency(summary.expense.vat_amount) }}
                                    </td>
                                </tr>

                                <!-- Box 13: Net VAT Due / Refund -->
                                <tr class="bg-gray-900 text-white font-bold text-sm">
                                    <td
                                        class="p-4 text-center font-mono text-emerald-400 text-base"
                                    >
                                        13
                                    </td>
                                    <td class="p-4" colspan="4">
                                        <div class="font-bold">
                                            {{ $t('reports.tax_periods_report.zatca_box_net') }}
                                        </div>
                                    </td>
                                    <td
                                        class="p-4 text-left font-mono text-base font-black"
                                        :class="
                                            summary.total.net_vat_amount >= 0
                                                ? 'text-emerald-400'
                                                : 'text-cyan-400'
                                        "
                                    >
                                        {{ formatCurrency(summary.total.net_vat_amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>

                <!-- Transactions Detail Table -->
                <div class="pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4"
                    >
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ $t('reports.tax_periods_report.detailed_transactions_title') }}
                                ({{ filteredTransactions.length }})
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{
                                    $t('reports.tax_periods_report.detailed_transactions_subtitle')
                                }}
                            </p>
                        </div>

                        <!-- Filters for Detail Table (Using SearchableSelect) -->
                        <div class="flex flex-wrap items-center gap-3">
                            <AppInput
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('reports.tax_periods_report.search_placeholder')"
                                input-class="py-1.5 text-xs w-60"
                            />

                            <div class="w-52">
                                <SearchableSelect
                                    v-model="filterType"
                                    :options="typeFilterOptions"
                                    :placeholder="$t('reports.tax_periods_report.all_types')"
                                    class="w-full text-xs"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700"
                    >
                        <DataTable structured class="w-full text-right text-xs">
                            <thead
                                class="bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300 font-semibold uppercase"
                            >
                                <tr>
                                    <th class="px-3 py-3 w-10">#</th>
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
                                    v-for="(tx, idx) in paginatedTransactions"
                                    :key="tx.id || idx"
                                    class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors"
                                >
                                    <td class="px-3 py-3 text-gray-500">
                                        {{ (currentPage - 1) * pageSize + idx + 1 }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-bold font-mono text-gray-900 dark:text-white"
                                    >
                                        {{ tx.ref_no }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-gray-500 dark:text-gray-400 font-mono"
                                    >
                                        {{ formatDate(tx.transaction_date) }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold"
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
                                    <td class="px-3 py-3 text-gray-800 dark:text-gray-200">
                                        {{ tx.party_name || '-' }}
                                    </td>
                                    <td class="px-3 py-3 font-mono text-gray-500">
                                        {{ tx.tax_number || '-' }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-mono text-gray-700 dark:text-gray-300"
                                    >
                                        {{ formatCurrency(tx.subtotal) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-bold font-mono"
                                        :class="tx.is_income ? 'text-emerald-600' : 'text-rose-600'"
                                    >
                                        {{ formatCurrency(tx.vat_amount) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 font-bold font-mono text-gray-900 dark:text-white"
                                    >
                                        {{ formatCurrency(tx.total_amount) }}
                                    </td>
                                </tr>

                                <tr v-if="filteredTransactions.length === 0">
                                    <td colspan="9" class="py-10 text-center text-gray-400">
                                        {{ $t('reports.tax_periods_report.no_transactions') }}
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>

                    <!-- Client-Side Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="mt-4 flex items-center justify-between text-xs text-gray-500"
                    >
                        <span>
                            {{
                                $t('reports.tax_periods_report.showing_records', {
                                    from: (currentPage - 1) * pageSize + 1,
                                    to: Math.min(
                                        currentPage * pageSize,
                                        filteredTransactions.length
                                    ),
                                    total: filteredTransactions.length,
                                })
                            }}
                        </span>
                        <div class="flex gap-1">
                            <button
                                type="button"
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                                class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40"
                            >
                                {{ $t('common.previous') }}
                            </button>
                            <span class="px-3 py-1 font-bold">
                                {{ currentPage }} / {{ totalPages }}
                            </span>
                            <button
                                type="button"
                                :disabled="currentPage === totalPages"
                                @click="currentPage++"
                                class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40"
                            >
                                {{ $t('common.next') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Standard Constitutional ConfirmModal -->
            <ConfirmModal />
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { usePrint } from '@/Composables/usePrint';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { formatMoney, activeCurrencySymbol } = useNumberFormat();
const { confirm } = useConfirm();
const { printDocument, formatCurrency: printFormatCurrency } = usePrint();

const props = defineProps({
    taxPeriod: Object,
    summary: Object,
    transactions: Array,
    tenant: Object,
});

const searchQuery = ref('');
const filterType = ref('all');

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

const currentPage = ref(1);
const pageSize = 50;

watch([searchQuery, filterType], () => {
    currentPage.value = 1;
});

const filteredTransactions = computed(() => {
    let list = props.transactions || [];

    if (filterType.value !== 'all') {
        list = list.filter((tr) => tr.transaction_type === filterType.value);
    }

    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (tr) =>
                (tr.ref_no && tr.ref_no.toLowerCase().includes(q)) ||
                (tr.party_name && tr.party_name.toLowerCase().includes(q)) ||
                (tr.tax_number && tr.tax_number.toLowerCase().includes(q))
        );
    }

    return list;
});

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / pageSize));

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredTransactions.value.slice(start, start + pageSize);
});

const getTypeBadgeClass = (type, isIncome) => {
    if (type === 'sales_invoice')
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
    if (type === 'credit_note')
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
    if (type === 'purchase_invoice')
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
    if (type === 'purchase_return') return 'bg-purple-100 text-purple-800';
    return isIncome ? 'bg-teal-100 text-teal-800' : 'bg-rose-100 text-rose-800';
};

const handlePostPeriod = async () => {
    const confirmed = await confirm({
        title: t('reports.tax_periods_report.confirm_post_title'),
        message: t('reports.tax_periods_report.confirm_post_message'),
        confirmText: t('reports.tax_periods_report.confirm_post_action'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });

    if (confirmed) {
        router.post(route('app.reports.tax-periods.post', props.taxPeriod.id));
    }
};

const handleRecreatePeriod = async () => {
    const confirmed = await confirm({
        title: t('reports.tax_periods_report.confirm_recreate_title'),
        message: t('reports.tax_periods_report.confirm_recreate_message'),
        confirmText: t('reports.tax_periods_report.confirm_recreate_action'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });

    if (confirmed) {
        router.post(route('app.reports.tax-periods.recreate', props.taxPeriod.id));
    }
};

const handlePrint = () => {
    printDocument({
        title: `${props.taxPeriod.name} - ${t('reports.tax_periods_report.title')}`,
    });
};

const formatCurrency = (val) => {
    if (printFormatCurrency) {
        return printFormatCurrency(Number(val) || 0);
    }
    return formatMoney(val);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return String(dateStr).split('T')[0];
};
</script>
