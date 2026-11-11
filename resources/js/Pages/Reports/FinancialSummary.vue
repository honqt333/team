<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <PageHeader
                :title="$t('reports.financial_summary_report.title')"
                :subtitle="$t('reports.financial_summary_report.subtitle')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-700"
                glowFrom="from-emerald-500"
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
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <AppPrintButton
                            @click="handlePrint"
                            :label="$t('reports.financial_summary_report.print_report')"
                        />

                        <a
                            :href="exportUrl"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold transition-all shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5"
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
                            <span>{{ $t('reports.financial_summary_report.export_excel') }}</span>
                        </a>
                    </div>
                </template>
            </PageHeader>

            <!-- Standard Official Print Section -->
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="$t('reports.financial_summary_report.official_report')"
                    :subtitle="dateRangeSubtitle"
                    :centerName="selectedCenterName"
                />

                <!-- Print Totals Summary -->
                <div
                    class="my-4 p-3 bg-gray-50 border border-gray-300 rounded text-xs grid grid-cols-4 gap-2 text-center"
                >
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.financial_summary_report.total_revenue') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatCurrency(overallSummary.invoicesSumNetAmount) }}
                            {{ $t('reports.financial_summary_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.financial_summary_report.net_purchases') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatCurrency(overallSummary.purchaseSparePartsSumNetAmount) }}
                            {{ $t('reports.financial_summary_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.financial_summary_report.total_salaries') }}
                        </span>
                        <span class="font-bold text-gray-900">
                            {{ formatCurrency(overallSummary.totalSalary) }}
                            {{ $t('reports.financial_summary_report.currency') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">
                            {{ $t('reports.financial_summary_report.net_profit') }}
                        </span>
                        <span
                            class="font-black"
                            :class="
                                overallSummary.profit >= 0 ? 'text-emerald-700' : 'text-red-700'
                            "
                        >
                            {{ formatCurrency(overallSummary.profit) }}
                            {{ $t('reports.financial_summary_report.currency') }}
                        </span>
                    </div>
                </div>

                <!-- Printable Statements per branch -->
                <div v-for="item in result" :key="'print-' + item.shop.id" class="mb-8">
                    <h3 class="font-bold text-sm text-gray-800 border-b pb-1 mb-2">
                        {{ item.shop.name }}
                    </h3>
                    <DataTable
                        structured
                        class="w-full text-[10px] border-collapse border border-gray-400 mb-4"
                    >
                        <thead>
                            <tr class="bg-gray-100 font-bold">
                                <th class="border border-gray-400 p-1 text-start">
                                    {{ $t('reports.financial_summary_report.item_description') }}
                                </th>
                                <th class="border border-gray-400 p-1 text-center">
                                    {{ $t('reports.financial_summary_report.gross_amount') }}
                                </th>
                                <th class="border border-gray-400 p-1 text-center">
                                    {{ $t('reports.financial_summary_report.discount') }}
                                </th>
                                <th class="border border-gray-400 p-1 text-center">
                                    {{ $t('reports.financial_summary_report.subtotal') }}
                                </th>
                                <th class="border border-gray-400 p-1 text-center">
                                    {{ $t('reports.financial_summary_report.vat_amount') }}
                                </th>
                                <th class="border border-gray-400 p-1 text-center">
                                    {{ $t('reports.financial_summary_report.total_amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sales Invoices -->
                            <tr class="font-bold bg-gray-50">
                                <td colspan="6" class="border border-gray-400 p-1">
                                    {{ $t('reports.financial_summary_report.invoices_section') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.services') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesServicesSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .invoicesServicesSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesServicesSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesServicesSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end font-bold">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesServicesSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.spare_parts') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .invoicesSparePartsSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .invoicesSparePartsSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSparePartsSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSparePartsSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end font-bold">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSparePartsSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr class="font-bold bg-gray-50">
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.invoices_total') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.invoicesSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>

                            <!-- Sales Returns -->
                            <tr class="font-bold bg-gray-50">
                                <td colspan="6" class="border border-gray-400 p-1">
                                    {{ $t('reports.financial_summary_report.returns_section') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.returned_services') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesServicesSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesServicesSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesServicesSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesServicesSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end font-bold">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesServicesSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{
                                        $t('reports.financial_summary_report.returned_spare_parts')
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesSparePartsSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesSparePartsSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesSparePartsSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesSparePartsSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end font-bold">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesSparePartsSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr class="font-bold bg-gray-50">
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.returns_total') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnInvoicesSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnInvoicesSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnInvoicesSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnInvoicesSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnInvoicesSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>

                            <!-- Inventory & Purchases -->
                            <tr class="font-bold bg-gray-50">
                                <td colspan="6" class="border border-gray-400 p-1">
                                    {{
                                        $t(
                                            'reports.financial_summary_report.inventory_purchases_section'
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{
                                        $t(
                                            'reports.financial_summary_report.received_from_supplier'
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .receivedSparePartsSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .receivedSparePartsSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.receivedSparePartsSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.receivedSparePartsSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end font-bold">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.receivedSparePartsSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{
                                        $t('reports.financial_summary_report.returned_to_supplier')
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnedSparePartsSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .returnedSparePartsSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnedSparePartsSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnedSparePartsSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end font-bold">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.returnedSparePartsSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr class="font-bold bg-gray-50">
                                <td class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.purchases_total') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .purchaseSparePartsSumGrossAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary
                                                .purchaseSparePartsSumDiscountAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.purchaseSparePartsSumSubTotal
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.purchaseSparePartsSumVATAmount
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{
                                        formatCurrency(
                                            item.shopFinancialSummary.purchaseSparePartsSumNetAmount
                                        )
                                    }}
                                </td>
                            </tr>

                            <!-- Operations Total -->
                            <tr class="font-black bg-gray-200">
                                <td class="border border-gray-400 p-1.5">
                                    {{
                                        $t(
                                            'reports.financial_summary_report.total_financial_operations'
                                        )
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1.5 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.sumGrossAmount) }}
                                </td>
                                <td class="border border-gray-400 p-1.5 text-end">
                                    {{
                                        formatCurrency(item.shopFinancialSummary.sumDiscountAmount)
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1.5 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.sumSubTotal) }}
                                </td>
                                <td class="border border-gray-400 p-1.5 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.sumVATAmount) }}
                                </td>
                                <td class="border border-gray-400 p-1.5 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.sumNetAmount) }}
                                </td>
                            </tr>

                            <!-- Salaries -->
                            <tr class="font-bold bg-gray-50">
                                <td colspan="5" class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.salaries_total') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.totalSalary) }}
                                </td>
                            </tr>

                            <!-- Bad Debt -->
                            <tr class="font-bold text-red-700">
                                <td colspan="5" class="border border-gray-400 p-1 ps-4">
                                    {{ $t('reports.financial_summary_report.bad_debt') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.sumBadDebt) }}
                                </td>
                            </tr>

                            <!-- Operating Profit -->
                            <tr
                                class="font-black text-sm"
                                :class="
                                    item.shopFinancialSummary.profit >= 0
                                        ? 'bg-emerald-50 text-emerald-900'
                                        : 'bg-red-50 text-red-900'
                                "
                            >
                                <td colspan="5" class="border border-gray-400 p-1.5">
                                    {{ $t('reports.financial_summary_report.operating_profit') }}
                                </td>
                                <td class="border border-gray-400 p-1.5 text-end">
                                    {{ formatCurrency(item.shopFinancialSummary.profit) }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <StandardReportFooter
                    :userName="$page.props.auth?.user?.name || ''"
                    :companyName="companyInfo.name || ''"
                />
            </div>

            <!-- Screen View Content (print:hidden) -->
            <div class="print:hidden space-y-6">
                <!-- Top 7 KPI Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    <!-- Total Revenue -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.financial_summary_report.total_revenue') }}
                        </div>
                        <div class="mt-2 text-lg font-black text-gray-900 dark:text-white">
                            {{ formatCurrency(overallSummary.invoicesSumNetAmount) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>

                    <!-- Returns -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.financial_summary_report.total_returns') }}
                        </div>
                        <div class="mt-2 text-lg font-black text-rose-600 dark:text-rose-400">
                            {{ formatCurrency(overallSummary.returnInvoicesSumNetAmount) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>

                    <!-- Net Purchases -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.financial_summary_report.net_purchases') }}
                        </div>
                        <div class="mt-2 text-lg font-black text-amber-600 dark:text-amber-400">
                            {{ formatCurrency(overallSummary.purchaseSparePartsSumNetAmount) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>

                    <!-- Expenses -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.financial_summary_report.total_expenses') }}
                        </div>
                        <div class="mt-2 text-lg font-black text-orange-600 dark:text-orange-400">
                            {{ formatCurrency(overallSummary.expenseSumNetAmount) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>

                    <!-- Salaries -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.financial_summary_report.total_salaries') }}
                        </div>
                        <div class="mt-2 text-lg font-black text-purple-600 dark:text-purple-400">
                            {{ formatCurrency(overallSummary.totalSalary) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>

                    <!-- VAT Due -->
                    <div
                        class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate"
                        >
                            {{ $t('reports.financial_summary_report.net_vat') }}
                        </div>
                        <div class="mt-2 text-lg font-black text-blue-600 dark:text-blue-400">
                            {{ formatCurrency(overallSummary.sumVATAmount) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>

                    <!-- Net Operating Profit -->
                    <div
                        class="p-4 rounded-2xl border shadow-sm"
                        :class="
                            overallSummary.profit >= 0
                                ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800'
                                : 'bg-red-50/50 dark:bg-red-950/20 border-red-200 dark:border-red-800'
                        "
                    >
                        <div
                            class="text-[11px] font-bold truncate"
                            :class="
                                overallSummary.profit >= 0
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : 'text-red-700 dark:text-red-300'
                            "
                        >
                            {{ $t('reports.financial_summary_report.net_profit') }}
                        </div>
                        <div
                            class="mt-2 text-lg font-black"
                            :class="
                                overallSummary.profit >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            {{ formatCurrency(overallSummary.profit) }}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            {{ $t('reports.financial_summary_report.currency') }}
                        </div>
                    </div>
                </div>

                <!-- Filters Console -->
                <div
                    class="p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm space-y-4"
                >
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                        <!-- Date Range Quick Select -->
                        <div class="md:col-span-4">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                            >
                                {{ $t('reports.financial_summary_report.date_range') }}
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
                                            ? 'bg-emerald-600 text-white font-bold shadow-sm'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    "
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Center / Branch Filter -->
                        <div class="md:col-span-3">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                            >
                                {{ $t('reports.financial_summary_report.center') }}
                            </label>
                            <SearchableSelect
                                v-model="selectedCenter"
                                :options="centerOptions"
                                :placeholder="$t('reports.financial_summary_report.all_centers')"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Store Sales Only Toggle -->
                        <div class="md:col-span-3">
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                            >
                                {{ $t('reports.financial_summary_report.store_sales_only') }}
                            </label>
                            <label
                                class="inline-flex items-center gap-2 cursor-pointer p-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors w-full"
                            >
                                <AppToggle v-model="storeSalesOnlyInput" @change="applyFilters" />
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ $t('reports.financial_summary_report.store_sales_only') }}
                                </span>
                            </label>
                        </div>

                        <!-- Reset Button -->
                        <div class="md:col-span-2 flex items-end">
                            <button
                                type="button"
                                @click="resetFilters"
                                class="w-full px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
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
                                <span>
                                    {{ $t('reports.financial_summary_report.reset_filters') }}
                                </span>
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
                                {{ $t('reports.financial_summary_report.date_from') }}
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
                                {{ $t('reports.financial_summary_report.date_to') }}
                            </label>
                            <CustomDatePicker
                                v-model="customEndDate"
                                class="w-full text-xs"
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="!result || result.length === 0"
                    class="p-12 text-center rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700"
                >
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="text-sm font-bold text-gray-600 dark:text-gray-400">
                        {{ $t('reports.financial_summary_report.no_records') }}
                    </p>
                </div>

                <!-- Statements per branch -->
                <div
                    v-for="(item, bIndex) in result"
                    :key="'branch-' + item.shop.id"
                    class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden"
                >
                    <!-- Branch Header -->
                    <div
                        class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/60 flex items-center justify-between flex-wrap gap-3"
                    >
                        <div>
                            <h2
                                class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span
                                    class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 inline-flex items-center justify-center text-xs font-black"
                                >
                                    {{ bIndex + 1 }}
                                </span>
                                <span>{{ item.shop.name }}</span>
                            </h2>
                            <div
                                class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap"
                            >
                                <span
                                    v-if="formatShopAddress(item.shop)"
                                    class="inline-flex items-center gap-1"
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
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    {{ formatShopAddress(item.shop) }}
                                </span>
                                <span v-if="item.shop.vat_number" class="font-mono">
                                    {{ $t('reports.financial_summary_report.vat_amount') }}:
                                    {{ item.shop.vat_number }}
                                </span>
                                <span v-if="item.shop.commercial_register" class="font-mono">
                                    CR: {{ item.shop.commercial_register }}
                                </span>
                            </div>
                        </div>

                        <!-- Mini Branch Profit Pill -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $t('reports.financial_summary_report.operating_profit') }}:
                            </span>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-black"
                                :class="
                                    item.shopFinancialSummary.profit >= 0
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
                                "
                            >
                                {{ formatCurrency(item.shopFinancialSummary.profit) }}
                                {{ $t('reports.financial_summary_report.currency') }}
                            </span>
                        </div>
                    </div>

                    <!-- Statement Table -->
                    <div class="overflow-x-auto">
                        <DataTable structured class="w-full text-xs text-start">
                            <thead>
                                <tr
                                    class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 text-gray-500 dark:text-gray-400 font-bold"
                                >
                                    <th class="py-3 px-4 text-start">
                                        {{
                                            $t('reports.financial_summary_report.item_description')
                                        }}
                                    </th>
                                    <th class="py-3 px-4 text-end">
                                        {{ $t('reports.financial_summary_report.gross_amount') }}
                                    </th>
                                    <th class="py-3 px-4 text-end text-rose-500">
                                        {{ $t('reports.financial_summary_report.discount') }}
                                    </th>
                                    <th class="py-3 px-4 text-end">
                                        {{ $t('reports.financial_summary_report.subtotal') }}
                                    </th>
                                    <th class="py-3 px-4 text-end">
                                        {{ $t('reports.financial_summary_report.vat_amount') }}
                                    </th>
                                    <th class="py-3 px-4 text-end">
                                        {{ $t('reports.financial_summary_report.total_amount') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <!-- 1. Sales Invoices Section -->
                                <tr
                                    class="bg-gray-50/80 dark:bg-gray-800/80 font-black text-gray-900 dark:text-white"
                                >
                                    <td colspan="6" class="py-2.5 px-4">
                                        <div
                                            class="flex items-center gap-2 text-primary-600 dark:text-primary-400"
                                        >
                                            <svg
                                                class="w-4 h-4 text-primary-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.financial_summary_report.invoices_section'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('reports.financial_summary_report.services') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesServicesSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesServicesSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesServicesSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesServicesSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesServicesSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('reports.financial_summary_report.spare_parts') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesSparePartsSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesSparePartsSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesSparePartsSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesSparePartsSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .invoicesSparePartsSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="bg-gray-50/40 dark:bg-gray-800/40 font-bold text-gray-900 dark:text-white"
                                >
                                    <td class="py-2.5 px-4 ps-8">
                                        {{ $t('reports.financial_summary_report.invoices_total') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.invoicesSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.invoicesSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.invoicesSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.invoicesSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.invoicesSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>

                                <!-- 2. Sales Returns Section -->
                                <tr
                                    class="bg-gray-50/80 dark:bg-gray-800/80 font-black text-gray-900 dark:text-white"
                                >
                                    <td colspan="6" class="py-2.5 px-4">
                                        <div
                                            class="flex items-center gap-2 text-rose-600 dark:text-rose-400"
                                        >
                                            <svg
                                                class="w-4 h-4 text-rose-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 15v-1a4 4 0 00-4-4H4m0 0l4 4m-4-4l4-4"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.financial_summary_report.returns_section'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{
                                            $t('reports.financial_summary_report.returned_services')
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesServicesSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesServicesSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesServicesSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesServicesSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-rose-600 dark:text-rose-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesServicesSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{
                                            $t(
                                                'reports.financial_summary_report.returned_spare_parts'
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSparePartsSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSparePartsSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSparePartsSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSparePartsSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-rose-600 dark:text-rose-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSparePartsSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="bg-gray-50/40 dark:bg-gray-800/40 font-bold text-gray-900 dark:text-white"
                                >
                                    <td class="py-2.5 px-4 ps-8">
                                        {{ $t('reports.financial_summary_report.returns_total') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnInvoicesSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.returnInvoicesSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.returnInvoicesSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-black text-rose-600 dark:text-rose-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.returnInvoicesSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>

                                <!-- 3. Inventory & Purchasing Operations Section -->
                                <tr
                                    class="bg-gray-50/80 dark:bg-gray-800/80 font-black text-gray-900 dark:text-white"
                                >
                                    <td colspan="6" class="py-2.5 px-4">
                                        <div
                                            class="flex items-center gap-2 text-amber-600 dark:text-amber-400"
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
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.financial_summary_report.inventory_purchases_section'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{
                                            $t(
                                                'reports.financial_summary_report.received_from_supplier'
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .receivedSparePartsSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .receivedSparePartsSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .receivedSparePartsSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .receivedSparePartsSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .receivedSparePartsSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{
                                            $t(
                                                'reports.financial_summary_report.returned_to_supplier'
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnedSparePartsSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnedSparePartsSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnedSparePartsSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnedSparePartsSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .returnedSparePartsSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="bg-gray-50/40 dark:bg-gray-800/40 font-bold text-gray-900 dark:text-white"
                                >
                                    <td class="py-2.5 px-4 ps-8">
                                        {{ $t('reports.financial_summary_report.purchases_total') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .purchaseSparePartsSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .purchaseSparePartsSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .purchaseSparePartsSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .purchaseSparePartsSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-black text-amber-600 dark:text-amber-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .purchaseSparePartsSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>

                                <!-- 4. Other Income & Expenses Section -->
                                <tr
                                    class="bg-gray-50/80 dark:bg-gray-800/80 font-black text-gray-900 dark:text-white"
                                >
                                    <td colspan="6" class="py-2.5 px-4">
                                        <div
                                            class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400"
                                        >
                                            <svg
                                                class="w-4 h-4 text-indigo-500"
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
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.financial_summary_report.other_income_expenses_section'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('reports.financial_summary_report.other_income') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('reports.financial_summary_report.expenses') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.expenseSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.expenseSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.expenseSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.expenseSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-orange-600 dark:text-orange-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.expenseSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="bg-gray-50/40 dark:bg-gray-800/40 font-bold text-gray-900 dark:text-white"
                                >
                                    <td class="py-2.5 px-4 ps-8">
                                        {{ $t('reports.financial_summary_report.other_total') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .incomeExpenseSumGrossAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary
                                                    .incomeExpenseSumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeExpenseSumSubTotal
                                            )
                                        }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeExpenseSumVATAmount
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-black text-indigo-600 dark:text-indigo-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.incomeExpenseSumNetAmount
                                            )
                                        }}
                                    </td>
                                </tr>

                                <!-- 5. Total Financial Operations (Highlighted Row) -->
                                <tr
                                    class="bg-gray-100 dark:bg-gray-700/60 font-black text-sm text-gray-900 dark:text-white border-y-2 border-gray-200 dark:border-gray-600"
                                >
                                    <td class="py-3 px-4">
                                        {{
                                            $t(
                                                'reports.financial_summary_report.total_financial_operations'
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 px-4 text-end font-mono">
                                        {{
                                            formatCurrency(item.shopFinancialSummary.sumGrossAmount)
                                        }}
                                    </td>
                                    <td class="py-3 px-4 text-end font-mono text-rose-500">
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.sumDiscountAmount
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 px-4 text-end font-mono">
                                        {{ formatCurrency(item.shopFinancialSummary.sumSubTotal) }}
                                    </td>
                                    <td class="py-3 px-4 text-end font-mono">
                                        {{ formatCurrency(item.shopFinancialSummary.sumVATAmount) }}
                                    </td>
                                    <td class="py-3 px-4 text-end font-mono">
                                        {{ formatCurrency(item.shopFinancialSummary.sumNetAmount) }}
                                    </td>
                                </tr>

                                <!-- 6. Salaries Section -->
                                <tr
                                    class="bg-gray-50/80 dark:bg-gray-800/80 font-black text-gray-900 dark:text-white"
                                >
                                    <td colspan="6" class="py-2.5 px-4">
                                        <div
                                            class="flex items-center gap-2 text-teal-600 dark:text-teal-400"
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
                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.financial_summary_report.salaries_section'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        colspan="5"
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('reports.financial_summary_report.payroll') }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.totalPayrollSalaryPayments
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                >
                                    <td
                                        colspan="5"
                                        class="py-2.5 px-4 ps-8 text-gray-700 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('reports.financial_summary_report.payroll_other') }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                item.shopFinancialSummary.totalOtherSalaryPayments
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="bg-gray-50/40 dark:bg-gray-800/40 font-bold text-gray-900 dark:text-white"
                                >
                                    <td colspan="5" class="py-2.5 px-4 ps-8">
                                        {{ $t('reports.financial_summary_report.salaries_total') }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-end font-mono font-black text-purple-600 dark:text-purple-400"
                                    >
                                        {{ formatCurrency(item.shopFinancialSummary.totalSalary) }}
                                    </td>
                                </tr>

                                <!-- 7. Bad Debt Section -->
                                <tr
                                    class="bg-rose-50/30 dark:bg-rose-950/10 font-bold text-rose-700 dark:text-rose-400"
                                >
                                    <td colspan="5" class="py-2.5 px-4 ps-8">
                                        {{ $t('reports.financial_summary_report.bad_debt') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-end font-mono font-black">
                                        {{ formatCurrency(item.shopFinancialSummary.sumBadDebt) }}
                                    </td>
                                </tr>

                                <!-- 8. Financial Summary (Profit/Loss & Final Figures) -->
                                <tr
                                    class="bg-gray-50 dark:bg-gray-800/70 font-bold text-gray-700 dark:text-gray-300"
                                >
                                    <td colspan="5" class="py-2 px-4">
                                        {{
                                            $t(
                                                'reports.financial_summary_report.final_total_amount'
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 px-4 text-end font-mono font-bold">
                                        {{
                                            formatCurrency(item.shopFinancialSummary.sumFinalAmount)
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="bg-gray-50 dark:bg-gray-800/70 font-bold text-gray-700 dark:text-gray-300"
                                >
                                    <td colspan="5" class="py-2 px-4">
                                        {{ $t('reports.financial_summary_report.vat_due') }}
                                    </td>
                                    <td
                                        class="py-2 px-4 text-end font-mono font-bold text-blue-600 dark:text-blue-400"
                                    >
                                        {{
                                            formatCurrency(
                                                -1 * item.shopFinancialSummary.sumVATAmount
                                            )
                                        }}
                                    </td>
                                </tr>

                                <!-- Operating Profit (Hero Bottom Row) -->
                                <tr
                                    class="text-base font-black border-t-2"
                                    :class="
                                        item.shopFinancialSummary.profit >= 0
                                            ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-200 border-emerald-300 dark:border-emerald-700'
                                            : 'bg-red-50 dark:bg-red-950/30 text-red-900 dark:text-red-200 border-red-300 dark:border-red-700'
                                    "
                                >
                                    <td colspan="5" class="py-3.5 px-4">
                                        <div class="flex items-center gap-2">
                                            <svg
                                                class="w-5 h-5"
                                                :class="
                                                    item.shopFinancialSummary.profit >= 0
                                                        ? 'text-emerald-600'
                                                        : 'text-red-600'
                                                "
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    $t(
                                                        'reports.financial_summary_report.operating_profit'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-end font-mono text-lg">
                                        {{ formatCurrency(item.shopFinancialSummary.profit) }}
                                        <span class="text-xs font-normal ms-1">
                                            {{ $t('reports.financial_summary_report.currency') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
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
    reportTitle: {
        type: String,
        default: '',
    },
    result: {
        type: Array,
        default: () => [],
    },
    overallSummary: {
        type: Object,
        default: () => ({}),
    },
    centers: {
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
    printDocument({
        title: props.reportTitle || 'Financial Summary Report',
    });
};

const formatCurrency = (val) => {
    return printFormatCurrency(Number(val) || 0);
};

const formatShopAddress = (shop) => {
    if (!shop) return '';
    if (typeof shop.address === 'string' && shop.address.trim()) {
        return shop.address;
    }
    const addr = shop.address_details || (typeof shop.address === 'object' ? shop.address : null);
    if (addr) {
        const parts = [];
        if (addr.city) parts.push(addr.city);
        if (addr.district) parts.push(`${t('common.district')} ${addr.district}`);
        if (addr.street) parts.push(`${t('common.street')} ${addr.street}`);
        if (addr.building_number) parts.push(`${t('common.building')} ${addr.building_number}`);
        if (addr.address_line) parts.push(addr.address_line);
        if (parts.length > 0) return parts.join('، ');
    }
    const fallback = [shop.city, shop.district, shop.postalCode].filter(Boolean);
    return fallback.join('، ');
};

// Filter State
const currentRange = ref(props.filters.range || 'this_month');
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');
const selectedCenter = ref(props.filters.center_id || 'all');
const storeSalesOnlyInput = ref(Boolean(props.filters.store_sales_only));

// Date Presets
const datePresets = computed(() => [
    { id: 'today', label: t('reports.financial_summary_report.today') },
    { id: 'yesterday', label: t('reports.financial_summary_report.yesterday') },
    { id: 'this_week', label: t('reports.financial_summary_report.this_week') },
    { id: 'this_month', label: t('reports.financial_summary_report.this_month') },
    { id: 'this_year', label: t('reports.financial_summary_report.this_year') },
    { id: 'all_time', label: t('reports.financial_summary_report.all_time') },
    { id: 'custom', label: t('reports.financial_summary_report.custom') },
]);

const selectPreset = (presetId) => {
    currentRange.value = presetId;
    if (presetId !== 'custom') {
        applyFilters();
    }
};

// Center Options
const centerOptions = computed(() => [
    { value: 'all', label: t('reports.financial_summary_report.all_centers') },
    ...props.centers.map((c) => ({
        value: c.id,
        label: c.name,
    })),
]);

const selectedCenterName = computed(() => {
    if (selectedCenter.value === 'all') {
        return t('reports.financial_summary_report.all_centers');
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
    const key = 'reports.financial_summary_report.' + currentRange.value;
    const translated = t(key);
    return translated && translated !== key
        ? translated
        : t('reports.financial_summary_report.this_month');
});

// Filter Application
const applyFilters = () => {
    router.get(
        route('app.reports.financial-summary'),
        {
            range: currentRange.value,
            start_date: currentRange.value === 'custom' ? customStartDate.value : null,
            end_date: currentRange.value === 'custom' ? customEndDate.value : null,
            center_id: selectedCenter.value !== 'all' ? selectedCenter.value : null,
            store_sales_only: storeSalesOnlyInput.value ? 1 : null,
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
    storeSalesOnlyInput.value = false;

    router.get(
        route('app.reports.financial-summary'),
        {},
        {
            preserveState: false,
            replace: true,
        }
    );
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
    if (storeSalesOnlyInput.value) params.set('store_sales_only', '1');

    return route('app.reports.financial-summary.export') + '?' + params.toString();
});
</script>
