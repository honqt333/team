<template>
    <div class="space-y-5">
        <!-- Header & Actions Toolbar -->
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-700/50 pb-4 print:hidden"
        >
            <div class="flex items-center gap-3">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ title || $t('company_profile.tabs.revenue_expenses') }}
                        </h3>
                        <span
                            class="px-2.5 py-0.5 text-xs font-mono font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60"
                        >
                            {{ sortedTransactions.length }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ subtitle || $t('company_profile.transactions.subtitle') }}
                    </p>
                </div>
            </div>

            <!-- Glassy Actions Group + Primary CTA on far left (RTL) -->
            <div class="flex flex-wrap items-center gap-2.5">
                <div
                    class="flex items-center gap-1.5 p-1 rounded-xl bg-gray-100/80 dark:bg-gray-800/80 border border-gray-200/60 dark:border-gray-700/60"
                >
                    <AppPrintButton
                        mode="button"
                        :label="$t('common.print')"
                        @click="handlePrint"
                    />
                    <button
                        type="button"
                        @click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-700 rounded-lg transition-all"
                    >
                        <svg
                            class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
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
                        <span>{{ $t('common.export') }}</span>
                    </button>
                </div>

                <button
                    type="button"
                    @click="$emit('add-transaction')"
                    class="px-4 py-2 text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    <span>{{ $t('company_profile.transactions.add') }}</span>
                </button>
            </div>
        </div>

        <!-- 6-Filter Advanced Filter Bar -->
        <div
            class="p-4 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-200/70 dark:border-gray-700/60 space-y-3 print:hidden"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- 1. Search -->
                <div>
                    <label class="block text-2xs font-bold text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('common.search') }}
                    </label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="$t('company_profile.transactions.filters.search_placeholder')"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    />
                </div>

                <!-- 2. Date Preset -->
                <div>
                    <SearchableSelect
                        v-model="datePreset"
                        :options="datePresetOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('company_profile.transactions.table.date')"
                        :placeholder="$t('common.all')"
                    />
                </div>

                <!-- 3. Transaction Type -->
                <div>
                    <SearchableSelect
                        v-model="filterType"
                        :options="typeOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('company_profile.transactions.form.type')"
                        :placeholder="$t('common.all')"
                    />
                </div>

                <!-- 4. Income/Expense Category -->
                <div>
                    <SearchableSelect
                        v-model="filterCategoryId"
                        :options="categoryFilterOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('company_profile.transactions.form.category')"
                        :placeholder="$t('common.all')"
                    />
                </div>

                <!-- 5. Tax Period -->
                <div>
                    <SearchableSelect
                        v-model="filterTaxPeriod"
                        :options="taxPeriodOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('company_profile.transactions.filters.tax_period')"
                        :placeholder="$t('common.all')"
                    />
                </div>

                <!-- 6. VAT Type & Status -->
                <div>
                    <SearchableSelect
                        v-model="filterVatType"
                        :options="vatTypeOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('common.vat')"
                        :placeholder="$t('common.all')"
                    />
                </div>
            </div>

            <!-- Custom Date Range Inputs & Status Pills + Reset Button -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-200/60 dark:border-gray-700/50"
            >
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status Quick Pills -->
                    <div
                        class="flex items-center gap-1 p-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700"
                    >
                        <button
                            v-for="st in statusOptions"
                            :key="st.value"
                            type="button"
                            @click="filterStatus = st.value"
                            :class="[
                                'px-3 py-1 text-2xs font-bold rounded-lg transition-all',
                                filterStatus === st.value
                                    ? 'bg-emerald-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            {{ st.label }}
                        </button>
                    </div>

                    <!-- Custom Date Pickers when datePreset === 'custom' -->
                    <div v-if="datePreset === 'custom'" class="flex flex-wrap items-center gap-2">
                        <div class="w-40">
                            <CustomDatePicker
                                v-model="customStartDate"
                                :placeholder="$t('common.from')"
                            />
                        </div>
                        <span class="text-xs text-gray-400">—</span>
                        <div class="w-40">
                            <CustomDatePicker
                                v-model="customEndDate"
                                :placeholder="$t('common.to')"
                            />
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="resetFilters"
                    class="px-3 py-1.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:text-rose-600 dark:hover:text-rose-400 bg-white dark:bg-gray-800 hover:bg-rose-50 dark:hover:bg-rose-950/30 border border-gray-200 dark:border-gray-700 rounded-xl transition-colors"
                >
                    {{ $t('company_profile.transactions.filters.reset') }}
                </button>
            </div>
        </div>

        <!-- Top Financial Summary Bar (Matching Wrshah + Enhanced) -->
        <div
            class="p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/80 dark:border-gray-700/70 shadow-sm flex flex-wrap items-center justify-center sm:justify-between gap-4 text-xs"
        >
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="font-black text-gray-900 dark:text-white">
                    {{ $t('common.total') }}:
                </span>
                <span class="text-gray-700 dark:text-gray-300">
                    {{ $t('company_profile.transactions.form.type_revenue') }}:
                    <strong class="font-mono text-emerald-600 dark:text-emerald-400">
                        {{ formatMoneyPlain(summaryTotals.revenueAmount) }}
                    </strong>
                </span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span class="text-gray-700 dark:text-gray-300">
                    VAT:
                    <strong class="font-mono text-emerald-600 dark:text-emerald-400">
                        {{ formatMoneyPlain(summaryTotals.revenueVat) }}
                    </strong>
                </span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span class="text-gray-700 dark:text-gray-300">
                    {{ $t('company_profile.transactions.form.type_expense') }}:
                    <strong class="font-mono text-rose-600 dark:text-rose-400" dir="ltr">
                        ({{ formatMoneyPlain(summaryTotals.expenseAmount) }})
                    </strong>
                </span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span class="text-gray-700 dark:text-gray-300">
                    VAT:
                    <strong class="font-mono text-rose-600 dark:text-rose-400" dir="ltr">
                        ({{ formatMoneyPlain(summaryTotals.expenseVat) }})
                    </strong>
                </span>
            </div>

            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200/60 dark:border-gray-700"
            >
                <span class="font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('company_profile.transactions.summary.net_amount') }}:
                </span>
                <strong
                    class="font-mono text-sm font-black"
                    :class="
                        summaryTotals.netAmount >= 0
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400'
                    "
                    dir="ltr"
                >
                    {{
                        summaryTotals.netAmount >= 0
                            ? formatCurrency(summaryTotals.netAmount)
                            : `(${formatMoneyPlain(Math.abs(summaryTotals.netAmount))})`
                    }}
                </strong>
            </div>
        </div>

        <!-- Hidden file input for quick row attachment upload -->
        <input
            ref="quickAttachmentInputRef"
            type="file"
            accept=".jpg,.jpeg,.png,.webp,.pdf"
            class="hidden"
            @change="onQuickAttachmentSelected"
        />

        <!-- Transactions Table -->
        <div
            class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 overflow-hidden shadow-sm"
        >
            <div class="overflow-x-auto">
                <DataTable structured class="w-full text-right border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200/70 dark:border-gray-700"
                        >
                            <th
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase w-12 text-center"
                            >
                                #
                            </th>
                            <th
                                class="px-3 py-3.5 text-xs font-bold text-gray-500 uppercase w-12 text-center print:hidden"
                                :title="$t('company_profile.transactions.form.attachment')"
                            >
                                <svg
                                    class="w-4 h-4 mx-auto text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                    />
                                </svg>
                            </th>
                            <th
                                @click="toggleSort('title')"
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase cursor-pointer hover:text-gray-900 dark:hover:text-white select-none"
                            >
                                {{ $t('company_profile.transactions.table.title') }}
                            </th>
                            <th
                                @click="toggleSort('transaction_date')"
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase text-center cursor-pointer hover:text-gray-900 dark:hover:text-white select-none"
                            >
                                {{ $t('company_profile.transactions.table.date') }}
                            </th>
                            <th
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase text-center"
                            >
                                {{ $t('company_profile.transactions.table.category') }}
                            </th>
                            <th
                                @click="toggleSort('amount')"
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase font-mono text-end cursor-pointer hover:text-gray-900 dark:hover:text-white select-none"
                            >
                                {{ $t('company_profile.transactions.table.amount') }}
                            </th>
                            <th
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase font-mono text-end"
                            >
                                {{ $t('company_profile.transactions.table.vat') }}
                            </th>
                            <th
                                @click="toggleSort('total_amount')"
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase font-mono text-end cursor-pointer hover:text-gray-900 dark:hover:text-white select-none"
                            >
                                {{ $t('company_profile.transactions.table.total') }}
                            </th>
                            <th
                                @click="toggleSort('updated_at')"
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase text-center cursor-pointer hover:text-gray-900 dark:hover:text-white select-none"
                            >
                                {{ $t('company_profile.transactions.table.updated_by') }}
                            </th>
                            <th
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase text-center"
                            >
                                {{ $t('company_profile.transactions.table.approved_by') }}
                            </th>
                            <th
                                class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase text-center print:hidden"
                            >
                                {{ $t('company_profile.transactions.table.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <tr
                            v-for="(trans, index) in paginatedTransactions"
                            :key="trans.id"
                            class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors"
                        >
                            <!-- 1. Row # -->
                            <td class="px-4 py-3.5 text-xs font-mono text-gray-400 text-center">
                                {{ (currentPage - 1) * pageSize + index + 1 }}
                            </td>

                            <!-- 2. Attachment Paperclip Column -->
                            <td class="px-3 py-3.5 text-center print:hidden">
                                <div class="flex items-center justify-center gap-1">
                                    <a
                                        v-if="trans.attachment_url"
                                        :href="trans.attachment_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 transition-colors"
                                        :title="
                                            $t('company_profile.transactions.table.view_attachment')
                                        "
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
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                            />
                                        </svg>
                                    </a>
                                    <button
                                        v-else-if="trans.status === 'draft'"
                                        type="button"
                                        @click="triggerQuickAttachmentUpload(trans)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="$t('company_profile.transactions.form.attachment')"
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
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                            />
                                        </svg>
                                    </button>
                                    <span v-else class="text-gray-300 dark:text-gray-700">—</span>
                                </div>
                            </td>

                            <!-- 3. Title + Invoice Link + Tax Period Icon + Contact + Notes -->
                            <td class="px-4 py-3.5 text-sm">
                                <div class="space-y-0.5">
                                    <!-- Generated Sales Invoice Link -->
                                    <div v-if="trans.invoice_id">
                                        <Link
                                            :href="route('app.invoices.show', trans.invoice_id)"
                                            class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1"
                                        >
                                            <span>
                                                {{
                                                    trans.invoice?.invoice_number ||
                                                    `INV-${trans.invoice_id}`
                                                }}
                                            </span>
                                        </Link>
                                    </div>

                                    <!-- Generated Purchase Invoice Link -->
                                    <div v-if="trans.purchase_invoice_id">
                                        <Link
                                            :href="
                                                route(
                                                    'app.purchases.show',
                                                    trans.purchase_invoice_id
                                                )
                                            "
                                            class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1"
                                        >
                                            <span>
                                                {{
                                                    trans.purchase_invoice?.invoice_number ||
                                                    trans.purchase_invoice?.code ||
                                                    `PI-${trans.purchase_invoice_id}`
                                                }}
                                            </span>
                                        </Link>
                                    </div>

                                    <!-- Title & Tax Period Pie Icon -->
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="trans.tax_period"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 text-2xs font-bold"
                                            :title="trans.tax_period.name"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                                <path
                                                    d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"
                                                />
                                            </svg>
                                            <span>{{ trans.tax_period.name }}</span>
                                        </span>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            {{ trans.title }}
                                        </span>
                                    </div>

                                    <!-- Contact Name -->
                                    <div
                                        v-if="trans.contact"
                                        class="text-xs font-bold text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ trans.contact.name }}
                                    </div>

                                    <!-- Notes in parentheses -->
                                    <div
                                        v-if="trans.notes"
                                        class="text-2xs text-gray-500 dark:text-gray-400"
                                    >
                                        ({{ trans.notes }})
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Transaction Date -->
                            <td
                                class="px-4 py-3.5 text-xs font-mono text-gray-600 dark:text-gray-300 text-center whitespace-nowrap"
                            >
                                {{ trans.transaction_date }}
                            </td>

                            <!-- 5. Category & Type Subtitle -->
                            <td class="px-4 py-3.5 text-center">
                                <div class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                    {{ trans.income_category?.name || '—' }}
                                </div>
                                <div
                                    class="text-2xs font-semibold mt-0.5"
                                    :class="
                                        trans.transaction_type === 'revenue'
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                    "
                                >
                                    ({{
                                        trans.transaction_type === 'revenue'
                                            ? $t('company_profile.transactions.form.type_revenue')
                                            : $t('company_profile.transactions.form.type_expense')
                                    }})
                                </div>
                            </td>

                            <!-- 6. Amount (Before Tax) -->
                            <td
                                class="px-4 py-3.5 text-xs font-mono font-bold text-end whitespace-nowrap"
                                :class="
                                    trans.transaction_type === 'expense'
                                        ? 'text-rose-600 dark:text-rose-400'
                                        : 'text-gray-800 dark:text-gray-200'
                                "
                                dir="ltr"
                            >
                                {{ formatSignedAmount(trans.amount, trans.transaction_type) }}
                            </td>

                            <!-- 7. VAT Amount + Inclusive Badge -->
                            <td
                                class="px-4 py-3.5 text-xs font-mono text-end whitespace-nowrap"
                                dir="ltr"
                            >
                                <div
                                    v-if="trans.is_taxable"
                                    class="font-bold"
                                    :class="
                                        trans.transaction_type === 'expense'
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-emerald-600 dark:text-emerald-400'
                                    "
                                >
                                    {{
                                        formatSignedAmount(trans.tax_amount, trans.transaction_type)
                                    }}
                                </div>
                                <div v-else class="text-gray-400 text-2xs">0.00</div>
                                <span
                                    v-if="trans.is_taxable && trans.is_vat_inclusive"
                                    class="inline-block mt-0.5 px-1.5 py-0.2 text-[10px] font-bold rounded bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                                >
                                    {{
                                        $t('company_profile.transactions.table.vat_inclusive_badge')
                                    }}
                                </span>
                            </td>

                            <!-- 8. Total Amount -->
                            <td
                                class="px-4 py-3.5 text-xs font-mono font-black text-end whitespace-nowrap"
                                :class="
                                    trans.transaction_type === 'expense'
                                        ? 'text-rose-600 dark:text-rose-400'
                                        : 'text-emerald-600 dark:text-emerald-400'
                                "
                                dir="ltr"
                            >
                                {{ formatSignedAmount(trans.total_amount, trans.transaction_type) }}
                            </td>

                            <!-- 9. Updated By -->
                            <td
                                class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 text-center"
                            >
                                <div v-if="trans.updated_by" class="space-y-0.5">
                                    <div class="font-semibold text-gray-700 dark:text-gray-300">
                                        {{ trans.updated_by.name }}
                                    </div>
                                    <div
                                        v-if="trans.updated_at"
                                        class="text-[10px] font-mono text-gray-400"
                                    >
                                        {{ String(trans.updated_at).substring(0, 10) }}
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </td>

                            <!-- 10. Approved By -->
                            <td class="px-4 py-3.5 text-xs text-center">
                                <div v-if="trans.status === 'approved'" class="space-y-0.5">
                                    <div
                                        class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400"
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
                                                stroke-width="2.5"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        <span>
                                            {{
                                                trans.approved_by?.name ||
                                                $t('common.approved_label')
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="trans.approved_at"
                                        class="text-[10px] font-mono text-gray-400"
                                    >
                                        {{ trans.approved_at }}
                                    </div>
                                </div>
                                <span
                                    v-else
                                    class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 uppercase"
                                >
                                    {{ $t('common.status_draft') }}
                                </span>
                            </td>

                            <!-- 11. Actions -->
                            <td class="px-4 py-3.5 text-center print:hidden">
                                <div
                                    v-if="trans.status === 'draft'"
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <button
                                        type="button"
                                        @click="openApproveModal(trans)"
                                        class="px-2.5 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 rounded-lg transition-colors border border-emerald-500/30"
                                    >
                                        {{ $t('hr.payroll.approve') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="$emit('edit-transaction', trans)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 rounded-lg transition-colors"
                                        :title="$t('common.edit')"
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
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="$emit('delete-transaction', trans)"
                                        class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition-colors"
                                        :title="$t('common.delete')"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <span
                                    v-else
                                    class="text-2xs text-gray-400 dark:text-gray-500 font-semibold"
                                >
                                    {{ $t('common.approved_label') }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="paginatedTransactions.length === 0">
                            <td
                                colspan="11"
                                class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 text-sm"
                            >
                                {{ $t('company_profile.transactions.empty_detailed') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>

            <!-- Pagination Footer -->
            <div
                v-if="totalPages > 1"
                class="px-4 py-3 bg-gray-50/50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between print:hidden"
            >
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $t('common.page') }} {{ currentPage }} / {{ totalPages }}
                </div>
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        :disabled="currentPage <= 1"
                        @click="currentPage--"
                        class="px-3 py-1 text-xs font-bold rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 disabled:opacity-40"
                    >
                        {{ $t('common.previous') }}
                    </button>
                    <button
                        type="button"
                        :disabled="currentPage >= totalPages"
                        @click="currentPage++"
                        class="px-3 py-1 text-xs font-bold rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 disabled:opacity-40"
                    >
                        {{ $t('common.next') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Financial Summary Bar (Matching Wrshah) -->
        <div
            class="p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/80 dark:border-gray-700/70 shadow-sm flex flex-wrap items-center justify-center sm:justify-between gap-4 text-xs"
        >
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="font-black text-gray-900 dark:text-white">
                    {{ $t('common.total') }}:
                </span>
                <span class="text-gray-700 dark:text-gray-300">
                    {{ $t('company_profile.transactions.form.type_revenue') }}:
                    <strong class="font-mono text-emerald-600 dark:text-emerald-400">
                        {{ formatMoneyPlain(summaryTotals.revenueAmount) }}
                    </strong>
                </span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span class="text-gray-700 dark:text-gray-300">
                    VAT:
                    <strong class="font-mono text-emerald-600 dark:text-emerald-400">
                        {{ formatMoneyPlain(summaryTotals.revenueVat) }}
                    </strong>
                </span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span class="text-gray-700 dark:text-gray-300">
                    {{ $t('company_profile.transactions.form.type_expense') }}:
                    <strong class="font-mono text-rose-600 dark:text-rose-400" dir="ltr">
                        ({{ formatMoneyPlain(summaryTotals.expenseAmount) }})
                    </strong>
                </span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span class="text-gray-700 dark:text-gray-300">
                    VAT:
                    <strong class="font-mono text-rose-600 dark:text-rose-400" dir="ltr">
                        ({{ formatMoneyPlain(summaryTotals.expenseVat) }})
                    </strong>
                </span>
            </div>

            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200/60 dark:border-gray-700"
            >
                <span class="font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('company_profile.transactions.summary.net_amount') }}:
                </span>
                <strong
                    class="font-mono text-sm font-black"
                    :class="
                        summaryTotals.netAmount >= 0
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400'
                    "
                    dir="ltr"
                >
                    {{
                        summaryTotals.netAmount >= 0
                            ? formatCurrency(summaryTotals.netAmount)
                            : `(${formatMoneyPlain(Math.abs(summaryTotals.netAmount))})`
                    }}
                </strong>
            </div>
        </div>

        <!-- Approve / Post Confirmation Modal with Approval Date Picker -->
        <Modal :show="!!approvingTransaction" unstyled @close="approvingTransaction = null">
            <div
                v-if="approvingTransaction"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="approvingTransaction = null"
                ></div>
                <div
                    class="relative bg-white dark:bg-gray-850 rounded-2xl shadow-2xl w-full max-w-md border border-gray-100 dark:border-gray-800 p-6 space-y-4"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3"
                    >
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('company_profile.transactions.approval_modal.title') }}
                        </h3>
                        <button
                            type="button"
                            @click="approvingTransaction = null"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            ✕
                        </button>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $t('company_profile.transactions.approve_confirm') }}
                    </p>

                    <div
                        v-if="approvingTransaction.is_taxable"
                        class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-300"
                    >
                        {{
                            approvingTransaction.transaction_type === 'revenue'
                                ? $t('company_profile.transactions.form.sales_invoice_notice')
                                : $t('company_profile.transactions.form.purchase_invoice_notice')
                        }}
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-600 dark:text-gray-400 mb-1">
                                {{ $t('company_profile.transactions.table.date') }}
                            </label>
                            <input
                                type="text"
                                :value="approvingTransaction.transaction_date"
                                disabled
                                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-500 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('company_profile.transactions.approval_modal.approved_at') }}
                            </label>
                            <CustomDatePicker
                                v-model="approvalDate"
                                :disabled="approvingTransaction.transaction_type === 'revenue'"
                            />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800"
                    >
                        <button
                            type="button"
                            @click="approvingTransaction = null"
                            class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="button"
                            :disabled="submittingApprove"
                            @click="confirmApproveTransaction"
                            class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md disabled:opacity-50"
                        >
                            {{ $t('hr.payroll.approve') }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DataTable from '@/Components/Common/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import Modal from '@/Components/Modal.vue';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    incomeCategories: { type: Array, default: () => [] },
    taxPeriods: { type: Array, default: () => [] },
    centerId: { type: [Number, String], default: null },
    vatRate: { type: Number, default: 15 },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});

defineEmits(['add-transaction', 'edit-transaction', 'delete-transaction']);

const { t } = useI18n();
const { success, error } = useToast();
const { formatMoney } = useNumberFormat();

// Filter states
const searchQuery = ref('');
const datePreset = ref('all');
const customStartDate = ref('');
const customEndDate = ref('');
const filterType = ref('all');
const filterCategoryId = ref('all');
const filterTaxPeriod = ref('all');
const filterVatType = ref('all');
const filterStatus = ref('all');

// Sorting & Pagination
const sortBy = ref('transaction_date');
const sortDesc = ref(true);
const currentPage = ref(1);
const pageSize = ref(15);

// Quick attachment upload
const quickAttachmentInputRef = ref(null);
const targetAttachmentTransaction = ref(null);

// Approve modal state
const approvingTransaction = ref(null);
const approvalDate = ref(new Date().toISOString().split('T')[0]);
const submittingApprove = ref(false);

const datePresetOptions = computed(() => [
    { value: 'all', label: t('common.all') },
    { value: 'today', label: t('company_profile.transactions.filters.today') },
    { value: 'last_7_days', label: t('company_profile.transactions.filters.last_7_days') },
    { value: 'last_30_days', label: t('company_profile.transactions.filters.last_30_days') },
    { value: 'this_month', label: t('company_profile.transactions.filters.this_month') },
    { value: 'custom', label: t('company_profile.transactions.filters.custom_range') },
]);

const typeOptions = computed(() => [
    { value: 'all', label: t('common.all') },
    { value: 'revenue', label: t('company_profile.transactions.form.type_revenue') },
    { value: 'expense', label: t('company_profile.transactions.form.type_expense') },
]);

const categoryFilterOptions = computed(() => {
    const list =
        filterType.value === 'all'
            ? props.incomeCategories
            : props.incomeCategories.filter((c) => c.transaction_type === filterType.value);
    return [
        { value: 'all', label: t('common.all') },
        ...list.map((c) => ({ value: String(c.id), label: c.name })),
    ];
});

const taxPeriodOptions = computed(() => [
    { value: 'all', label: t('common.all') },
    {
        value: 'without_period',
        label: t('company_profile.transactions.filters.without_tax_period'),
    },
    ...(props.taxPeriods || []).map((tp) => ({
        value: String(tp.id),
        label: tp.period_name || tp.name || `#${tp.id}`,
    })),
]);

const vatTypeOptions = computed(() => [
    { value: 'all', label: t('common.all') },
    { value: 'no_vat', label: t('company_profile.transactions.filters.no_vat') },
    { value: 'vat_exclusive', label: t('company_profile.transactions.table.vat_exclusive_badge') },
    { value: 'vat_inclusive', label: t('company_profile.transactions.table.vat_inclusive_badge') },
]);

const statusOptions = computed(() => [
    { value: 'all', label: t('common.all') },
    { value: 'draft', label: t('common.status_draft') },
    { value: 'approved', label: t('common.approved_label') },
]);

watch(
    [
        searchQuery,
        datePreset,
        customStartDate,
        customEndDate,
        filterType,
        filterCategoryId,
        filterTaxPeriod,
        filterVatType,
        filterStatus,
    ],
    () => {
        currentPage.value = 1;
    }
);

function resetFilters() {
    searchQuery.value = '';
    datePreset.value = 'all';
    customStartDate.value = '';
    customEndDate.value = '';
    filterType.value = 'all';
    filterCategoryId.value = 'all';
    filterTaxPeriod.value = 'all';
    filterVatType.value = 'all';
    filterStatus.value = 'all';
    currentPage.value = 1;
}

const filteredTransactions = computed(() => {
    const todayStr = new Date().toISOString().split('T')[0];

    return (props.transactions || []).filter((tx) => {
        // 1. Search query
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.trim().toLowerCase();
            const matchTitle = String(tx.title || '')
                .toLowerCase()
                .includes(q);
            const matchContact = String(tx.contact?.name || '')
                .toLowerCase()
                .includes(q);
            const matchNotes = String(tx.notes || '')
                .toLowerCase()
                .includes(q);
            const matchInv = String(
                tx.invoice?.invoice_number ||
                    tx.purchase_invoice?.invoice_number ||
                    tx.purchase_invoice?.code ||
                    ''
            )
                .toLowerCase()
                .includes(q);
            if (!matchTitle && !matchContact && !matchNotes && !matchInv) return false;
        }

        // 2. Date preset
        const txDate = String(tx.transaction_date || '').substring(0, 10);
        if (datePreset.value === 'today' && txDate !== todayStr) return false;
        if (datePreset.value === 'last_7_days') {
            const d = new Date();
            d.setDate(d.getDate() - 7);
            if (txDate < d.toISOString().split('T')[0]) return false;
        }
        if (datePreset.value === 'last_30_days') {
            const d = new Date();
            d.setDate(d.getDate() - 30);
            if (txDate < d.toISOString().split('T')[0]) return false;
        }
        if (datePreset.value === 'this_month') {
            if (!txDate.startsWith(todayStr.substring(0, 7))) return false;
        }
        if (datePreset.value === 'custom') {
            if (customStartDate.value && txDate < customStartDate.value) return false;
            if (customEndDate.value && txDate > customEndDate.value) return false;
        }

        // 3. Transaction Type
        if (
            filterType.value &&
            filterType.value !== 'all' &&
            tx.transaction_type !== filterType.value
        ) {
            return false;
        }

        // 4. Category
        if (
            filterCategoryId.value &&
            filterCategoryId.value !== 'all' &&
            String(tx.income_category_id) !== String(filterCategoryId.value)
        ) {
            return false;
        }

        // 5. Tax Period
        if (filterTaxPeriod.value && filterTaxPeriod.value !== 'all') {
            if (filterTaxPeriod.value === 'without_period') {
                if (tx.tax_period) return false;
            } else if (String(tx.tax_period?.id) !== String(filterTaxPeriod.value)) {
                return false;
            }
        }

        // 6. VAT Type
        if (filterVatType.value && filterVatType.value !== 'all') {
            if (filterVatType.value === 'no_vat' && tx.is_taxable) return false;
            if (filterVatType.value === 'vat_exclusive' && (!tx.is_taxable || tx.is_vat_inclusive))
                return false;
            if (filterVatType.value === 'vat_inclusive' && (!tx.is_taxable || !tx.is_vat_inclusive))
                return false;
        }

        // 7. Status
        if (
            filterStatus.value &&
            filterStatus.value !== 'all' &&
            tx.status !== filterStatus.value
        ) {
            return false;
        }

        return true;
    });
});

const sortedTransactions = computed(() => {
    const arr = [...filteredTransactions.value];
    arr.sort((a, b) => {
        let va = a[sortBy.value];
        let vb = b[sortBy.value];
        if (sortBy.value === 'amount' || sortBy.value === 'total_amount') {
            va = parseFloat(va || 0);
            vb = parseFloat(vb || 0);
        } else {
            va = String(va || '');
            vb = String(vb || '');
        }
        if (va < vb) return sortDesc.value ? 1 : -1;
        if (va > vb) return sortDesc.value ? -1 : 1;
        return 0;
    });
    return arr;
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(sortedTransactions.value.length / pageSize.value))
);

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    return sortedTransactions.value.slice(start, start + pageSize.value);
});

const summaryTotals = computed(() => {
    let revenueAmount = 0;
    let revenueVat = 0;
    let revenueTotal = 0;
    let expenseAmount = 0;
    let expenseVat = 0;
    let expenseTotal = 0;

    for (const tx of filteredTransactions.value) {
        const amt = parseFloat(tx.amount || 0);
        const vat = parseFloat(tx.tax_amount || 0);
        const tot = parseFloat(tx.total_amount || 0);

        if (tx.transaction_type === 'revenue') {
            revenueAmount += amt;
            revenueVat += vat;
            revenueTotal += tot;
        } else {
            expenseAmount += amt;
            expenseVat += vat;
            expenseTotal += tot;
        }
    }

    return {
        revenueAmount,
        revenueVat,
        revenueTotal,
        expenseAmount,
        expenseVat,
        expenseTotal,
        netAmount: revenueTotal - expenseTotal,
    };
});

function toggleSort(column) {
    if (sortBy.value === column) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = column;
        sortDesc.value = true;
    }
}

function formatMoneyPlain(val) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(val || 0));
}

function formatCurrency(val) {
    return formatMoney(Number(val || 0));
}

function formatSignedAmount(val, type) {
    const formatted = formatMoneyPlain(Math.abs(Number(val || 0)));
    return type === 'expense' ? `(${formatted})` : formatted;
}

function triggerQuickAttachmentUpload(trans) {
    targetAttachmentTransaction.value = trans;
    if (quickAttachmentInputRef.value) {
        quickAttachmentInputRef.value.value = '';
        quickAttachmentInputRef.value.click();
    }
}

function onQuickAttachmentSelected(e) {
    const file = e.target.files?.[0];
    if (!file || !targetAttachmentTransaction.value) return;

    const formData = new FormData();
    formData.append('attachment', file);

    router.post(
        route(
            'settings.company.transactions.attachment.upload',
            targetAttachmentTransaction.value.id
        ),
        formData,
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('company_profile.transactions.attachment_uploaded'));
                targetAttachmentTransaction.value = null;
            },
        }
    );
}

function openApproveModal(trans) {
    approvingTransaction.value = trans;
    approvalDate.value = new Date().toISOString().split('T')[0];
}

function confirmApproveTransaction() {
    if (!approvingTransaction.value) return;
    submittingApprove.value = true;

    router.post(
        route('settings.company.transactions.approve', approvingTransaction.value.id),
        { approved_at: approvalDate.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('company_profile.transactions.approved'));
                approvingTransaction.value = null;
            },
            onError: (errs) => {
                error(Object.values(errs).flat().join('\n') || t('common.error'));
            },
            onFinish: () => {
                submittingApprove.value = false;
            },
        }
    );
}

function handlePrint() {
    window.print();
}

function exportCsv() {
    const headers = [
        '#',
        t('company_profile.transactions.table.title'),
        t('company_profile.transactions.form.linked_to'),
        t('company_profile.transactions.table.date'),
        t('company_profile.transactions.table.category'),
        t('company_profile.transactions.form.type'),
        t('company_profile.transactions.table.amount'),
        t('company_profile.transactions.table.vat'),
        t('company_profile.transactions.table.total'),
        t('company_profile.transactions.table.approved_by'),
    ];

    const rows = sortedTransactions.value.map((tx, idx) => {
        const sign = tx.transaction_type === 'expense' ? -1 : 1;
        return [
            idx + 1,
            `"${String(tx.title || '').replace(/"/g, '""')}"`,
            `"${String(tx.contact?.name || '').replace(/"/g, '""')}"`,
            tx.transaction_date || '',
            `"${String(tx.income_category?.name || '').replace(/"/g, '""')}"`,
            tx.transaction_type === 'revenue'
                ? t('company_profile.transactions.form.type_revenue')
                : t('company_profile.transactions.form.type_expense'),
            (Number(tx.amount || 0) * sign).toFixed(2),
            (Number(tx.tax_amount || 0) * sign).toFixed(2),
            (Number(tx.total_amount || 0) * sign).toFixed(2),
            tx.status === 'approved'
                ? tx.approved_by?.name || t('common.approved_label')
                : t('common.status_draft'),
        ].join(',');
    });

    const csvContent = '\uFEFF' + [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `income_expenses_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>
