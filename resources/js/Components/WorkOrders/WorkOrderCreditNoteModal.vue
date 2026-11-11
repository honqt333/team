<template>
    <BaseModal :show="show" @close="handleClose" size="4xl" scroll-entire :overflow-visible="true">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-amber-500 flex items-center justify-center text-white shadow-md shadow-rose-500/20"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">
                        {{ $t('invoices.credit_note.title') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span v-if="effectiveWorkOrder?.code">{{ effectiveWorkOrder.code }} •</span>
                        <span v-if="invoice?.invoice_number" class="font-mono text-action-primary">
                            #{{ invoice.invoice_number }}
                        </span>
                    </p>
                </div>
            </div>
        </template>

        <!-- Loading Spinner -->
        <!-- Loading -->
        <div v-if="loading" class="py-12 flex flex-col items-center justify-center gap-3">
            <div
                class="w-8 h-8 border-3 border-rose-500 border-t-transparent rounded-full animate-spin"
            ></div>
            <p class="text-xs font-bold text-gray-500">{{ $t('common.loading') }}...</p>
        </div>

        <!-- Error Alert -->
        <div
            v-else-if="errorMessage || backendMessage"
            class="p-6 text-center space-y-3 bg-red-50 dark:bg-red-950/20 rounded-2xl border border-red-200 dark:border-red-800"
        >
            <div
                class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center mx-auto text-xl font-bold"
            >
                ⚠️
            </div>
            <h4 class="text-sm font-bold text-red-900 dark:text-red-200">
                {{ errorMessage || backendMessage }}
            </h4>
        </div>

        <!-- No Returnable Items Alert -->
        <div
            v-else-if="!isPossible"
            class="p-6 text-center space-y-3 bg-amber-50 dark:bg-amber-950/20 rounded-2xl border border-amber-200 dark:border-amber-800"
        >
            <div
                class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto text-xl"
            >
                ⚠️
            </div>
            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                {{ $t('invoices.credit_note.no_returnable_items') }}
            </h4>
        </div>

        <!-- Form Body -->
        <form v-else @submit.prevent="submitCreditNote" class="space-y-6">
            <!-- 1. Original Invoice Info Strip -->
            <div
                class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-gray-50 dark:bg-gray-900/60 rounded-2xl border border-gray-200/80 dark:border-gray-800 text-xs"
            >
                <div>
                    <span class="text-gray-400 dark:text-gray-500 block mb-0.5">
                        {{ $t('invoices.credit_note.original_invoice') }}
                    </span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                        #{{ invoice?.invoice_number }}
                    </span>
                    <span
                        v-if="!isTaxEnabled"
                        class="inline-block mt-0.5 px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded"
                    >
                        {{ $t('invoices.credit_note.tax_exempt_badge') }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-400 dark:text-gray-500 block mb-0.5">
                        {{ $t('invoices.issue_date') }}
                    </span>
                    <span class="font-mono font-bold text-gray-700 dark:text-gray-300">
                        {{ invoice?.issue_date }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-400 dark:text-gray-500 block mb-0.5">
                        {{ $t('invoices.total') }}
                    </span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                        {{ formatCurrency(invoice?.total_incl_tax) }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-400 dark:text-gray-500 block mb-0.5">
                        {{ $t('invoices.balance') }}
                    </span>
                    <span
                        class="font-mono font-bold"
                        :class="
                            invoice?.remaining_amount > 0
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-emerald-600 dark:text-emerald-400'
                        "
                    >
                        {{ formatCurrency(invoice?.remaining_amount) }}
                    </span>
                </div>
            </div>

            <!-- 2. Services Table -->
            <div v-if="services.length > 0" class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4
                        class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2"
                    >
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        {{ $t('invoices.credit_note.services_table_title') }}
                    </h4>
                    <span class="text-[11px] text-gray-400">({{ services.length }})</span>
                </div>
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead
                            class="bg-gray-50 dark:bg-gray-800/80 text-gray-500 border-b border-gray-200 dark:border-gray-700 font-bold"
                        >
                            <tr>
                                <th class="p-3 text-start">
                                    {{ $t('invoices.credit_note.service_name') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.original_cost') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.returned_previously') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.remaining_refundable') }}
                                </th>
                                <th class="p-3 text-center w-44">
                                    {{ $t('invoices.credit_note.return_amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="svc in services"
                                :key="svc.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors"
                            >
                                <td class="p-3">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ svc.name }}
                                    </div>
                                    <div
                                        v-if="svc.department_name"
                                        class="text-[10px] text-gray-400"
                                    >
                                        {{ svc.department_name }}
                                    </div>
                                </td>
                                <td class="p-3 text-center font-mono">
                                    {{ formatCurrency(svc.original_subtotal) }}
                                </td>
                                <td class="p-3 text-center font-mono text-gray-400">
                                    {{ formatCurrency(svc.returned_subtotal) }}
                                </td>
                                <td
                                    class="p-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatCurrency(svc.remaining_refundable_subtotal) }}
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-1.5 justify-end">
                                        <input
                                            type="number"
                                            v-model.number="returnServices[svc.id]"
                                            :max="svc.remaining_refundable_subtotal"
                                            min="0"
                                            step="0.01"
                                            class="w-28 text-end px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-xs focus:ring-2 focus:ring-danger focus:border-danger font-bold"
                                            :disabled="svc.remaining_refundable_subtotal <= 0"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                returnServices[svc.id] = Number(
                                                    svc.remaining_refundable_subtotal.toFixed(2)
                                                )
                                            "
                                            :disabled="svc.remaining_refundable_subtotal <= 0"
                                            class="px-2 py-1 text-[10px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 dark:bg-rose-950/30 rounded-lg hover:bg-rose-100 transition-colors disabled:opacity-40"
                                            title="إرجاع كامل"
                                        >
                                            {{ $t('common.all') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Spare Parts Table -->
            <div v-if="parts.length > 0" class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4
                        class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        {{ $t('invoices.credit_note.parts_table_title') }}
                    </h4>
                    <span class="text-[11px] text-gray-400">({{ parts.length }})</span>
                </div>
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead
                            class="bg-gray-50 dark:bg-gray-800/80 text-gray-500 border-b border-gray-200 dark:border-gray-700 font-bold"
                        >
                            <tr>
                                <th class="p-3 text-start">
                                    {{ $t('invoices.credit_note.part_name') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.unit_price') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.original_qty') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.returned_previously') }}
                                </th>
                                <th class="p-3 text-center">
                                    {{ $t('invoices.credit_note.remaining_refundable') }}
                                </th>
                                <th class="p-3 text-center w-52">
                                    {{ $t('invoices.credit_note.return_qty') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="part in parts"
                                :key="part.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors"
                            >
                                <td class="p-3">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ part.name }}
                                    </div>
                                    <div
                                        v-if="part.part_number"
                                        class="text-[10px] font-mono text-gray-400"
                                    >
                                        {{ part.part_number }}
                                    </div>
                                </td>
                                <td class="p-3 text-center font-mono">
                                    {{ formatCurrency(part.unit_price) }}
                                </td>
                                <td class="p-3 text-center font-mono">
                                    {{ part.original_qty }}
                                </td>
                                <td class="p-3 text-center font-mono text-gray-400">
                                    {{ part.returned_qty }}
                                </td>
                                <td
                                    class="p-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ part.remaining_refundable_qty }}
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-col gap-1 items-end">
                                        <div class="flex items-center gap-1.5 justify-end">
                                            <input
                                                type="number"
                                                v-model.number="returnParts[part.id]"
                                                :max="part.remaining_refundable_qty"
                                                min="0"
                                                step="1"
                                                class="w-24 text-end px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-xs focus:ring-2 focus:ring-focus-ring focus:border-action-primary font-bold"
                                                :disabled="part.remaining_refundable_qty <= 0"
                                            />
                                            <button
                                                type="button"
                                                @click="
                                                    returnParts[part.id] =
                                                        part.remaining_refundable_qty
                                                "
                                                :disabled="part.remaining_refundable_qty <= 0"
                                                class="px-2 py-1 text-[10px] font-bold text-amber-600 hover:text-amber-700 bg-amber-50 dark:bg-amber-950/30 rounded-lg hover:bg-amber-100 transition-colors disabled:opacity-40"
                                                title="إرجاع كامل"
                                            >
                                                {{ $t('common.all') }}
                                            </button>
                                        </div>
                                        <div
                                            v-if="returnParts[part.id] > 0 && part.part_id"
                                            class="pt-0.5"
                                        >
                                            <AppCheckbox
                                                v-model="restockParts[part.id]"
                                                :label="$t('invoices.credit_note.return_to_stock')"
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Financial Reconciliation Cards -->
            <div
                class="p-4 bg-gradient-to-br from-gray-50 to-rose-50/30 dark:from-gray-900 dark:to-rose-950/20 rounded-2xl border border-rose-100 dark:border-rose-900/30 space-y-3"
            >
                <h4
                    class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2"
                >
                    <span>⚖️</span>
                    {{ $t('invoices.credit_note.financial_reconciliation') }}
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Total Credit Note -->
                    <div
                        class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <span class="text-[10px] font-bold text-gray-400 block mb-1">
                            {{ $t('invoices.credit_note.total_credit_note') }}
                        </span>
                        <div
                            class="text-base font-black font-mono text-rose-600 dark:text-rose-400"
                        >
                            {{ formatCurrency(totalRefundAmount) }}
                        </div>
                        <span v-if="isTaxEnabled" class="text-[10px] text-gray-400 block mt-0.5">
                            {{ $t('common.base_label') }} {{ formatCurrency(totalRefundSubtotal) }}
                            {{ $t('common.plus_tax_label') }} {{ formatCurrency(totalRefundVat) }})
                        </span>
                        <span
                            v-else
                            class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold block mt-0.5"
                        >
                            ({{ $t('invoices.credit_note.tax_exempt_badge') }})
                        </span>
                    </div>

                    <!-- Offset from remaining -->
                    <div
                        class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <span class="text-[10px] font-bold text-gray-400 block mb-1">
                            {{ $t('invoices.credit_note.debt_offset') }}
                        </span>
                        <div
                            class="text-base font-black font-mono text-amber-600 dark:text-amber-400"
                        >
                            {{ formatCurrency(amountAdjusted) }}
                        </div>
                        <span class="text-[10px] text-gray-400 block mt-0.5">
                            {{ $t('invoices.credit_note.debt_offset_hint') }}
                        </span>
                    </div>

                    <!-- Net Cash Refund Due -->
                    <div
                        class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <span class="text-[10px] font-bold text-gray-400 block mb-1">
                            {{ $t('invoices.credit_note.net_refund_due') }}
                        </span>
                        <div
                            class="text-base font-black font-mono"
                            :class="
                                netRefundDue > 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-gray-500'
                            "
                        >
                            {{ formatCurrency(netRefundDue) }}
                        </div>
                        <span class="text-[10px] text-gray-400 block mt-0.5">
                            {{ $t('common.net_amount_after_offset') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 5. Refund Payment Method / Deferred Credit (Visible if netRefundDue > 0) -->
            <div
                v-if="netRefundDue > 0"
                class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-4"
            >
                <div class="flex items-center justify-between">
                    <h4
                        class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2"
                    >
                        <span>💰</span>
                        {{ $t('invoices.credit_note.refund_payment_section') }}
                    </h4>
                    <span
                        class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ formatCurrency(netRefundDue) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <!-- Option 1: Immediate Refund -->
                    <label
                        class="p-3 rounded-xl border-2 transition-all cursor-pointer flex flex-col gap-1.5"
                        :class="
                            refundMode === 'immediate'
                                ? 'border-rose-500 bg-rose-50/30 dark:bg-rose-950/20 shadow-sm'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                        "
                    >
                        <div class="flex items-center gap-2">
                            <input
                                type="radio"
                                value="immediate"
                                v-model="refundMode"
                                class="w-4 h-4 text-rose-600 border-gray-300 focus:ring-danger"
                            />
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ $t('invoices.credit_note.immediate_refund_option') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 ps-6">
                            {{ $t('invoices.credit_note.immediate_refund_desc') }}
                        </p>
                    </label>

                    <!-- Option 2: Deferred / Customer Credit -->
                    <label
                        class="p-3 rounded-xl border-2 transition-all cursor-pointer flex flex-col gap-1.5"
                        :class="
                            refundMode === 'deferred'
                                ? 'border-amber-500 bg-amber-50/30 dark:bg-amber-950/20 shadow-sm'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                        "
                    >
                        <div class="flex items-center gap-2">
                            <input
                                type="radio"
                                value="deferred"
                                v-model="refundMode"
                                class="w-4 h-4 text-amber-600 border-gray-300 focus:ring-focus-ring"
                            />
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ $t('invoices.credit_note.deferred_refund_option') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 ps-6">
                            {{ $t('invoices.credit_note.deferred_refund_desc') }}
                        </p>
                    </label>
                </div>

                <!-- Immediate Options Fields -->
                <div
                    v-if="refundMode === 'immediate'"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 text-xs"
                >
                    <div>
                        <label class="block text-gray-500 mb-1 font-semibold">
                            {{ $t('invoices.credit_note.refund_method') }}
                        </label>
                        <SearchableSelect
                            v-model="refundMethod"
                            :options="paymentMethodOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('common.select')"
                            :compact="true"
                        />
                    </div>

                    <div>
                        <label class="block text-gray-500 mb-1 font-semibold">
                            {{ $t('invoices.credit_note.refund_reference') }}
                        </label>
                        <input
                            type="text"
                            v-model="refundReference"
                            placeholder="مثال: رقم الحوالة أو إيصال الشبكة"
                            class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-danger"
                        />
                    </div>
                </div>

                <!-- Deferred Options Fields -->
                <div
                    v-else-if="refundMode === 'deferred'"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 text-xs"
                >
                    <div>
                        <label class="block text-gray-500 mb-1 font-semibold">
                            {{ $t('invoices.credit_note.payment_due_date') }}
                        </label>
                        <input
                            type="date"
                            v-model="paymentDueDate"
                            class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-focus-ring font-mono"
                        />
                    </div>
                    <div
                        class="flex items-center text-[11px] text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 p-2.5 rounded-xl border border-amber-200 dark:border-amber-800"
                    >
                        {{ $t('work_orders.credit_note.no_cash_movement_hint') }}
                    </div>
                </div>
            </div>

            <!-- 6. Return Reason -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('invoices.credit_note.reason') }}
                </label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    <button
                        type="button"
                        v-for="preset in reasonPresets"
                        :key="preset"
                        @click="returnReason = preset"
                        class="px-2.5 py-1 text-[11px] rounded-lg border border-gray-200 dark:border-gray-700 hover:border-rose-400 dark:hover:border-rose-600 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-rose-600 transition-colors"
                    >
                        {{ preset }}
                    </button>
                </div>
                <textarea
                    v-model="returnReason"
                    rows="2"
                    :placeholder="$t('invoices.credit_note.reason_placeholder')"
                    class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-danger resize-none"
                ></textarea>
            </div>

            <!-- Error Banner -->
            <div
                v-if="errorMessage"
                class="p-3 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 rounded-xl text-xs font-semibold"
            >
                {{ errorMessage }}
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    @click="handleClose"
                    class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                >
                    {{ $t('common.cancel') }}
                </button>

                <button
                    v-if="isPossible"
                    type="button"
                    @click="submitCreditNote"
                    :disabled="submitting || totalRefundAmount <= 0"
                    class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-rose-600 to-amber-600 hover:from-rose-700 hover:to-amber-700 text-white rounded-xl font-bold text-xs shadow-md shadow-rose-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <div
                        v-if="submitting"
                        class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
                    ></div>
                    <svg
                        v-else
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span>
                        {{
                            submitting
                                ? $t('invoices.credit_note.submitting')
                                : $t('invoices.credit_note.submit')
                        }}
                    </span>
                    <span
                        v-if="totalRefundAmount > 0"
                        class="px-1.5 py-0.5 bg-white/20 rounded font-mono"
                    >
                        {{ formatCurrency(totalRefundAmount) }}
                    </span>
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    workOrder: {
        type: Object,
        default: null,
    },
    invoice: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'created']);
const { t } = useI18n();

const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const backendMessage = ref('');

const invoice = ref(null);
const services = ref([]);
const parts = ref([]);
const isPossible = ref(false);

const returnServices = ref({});
const returnParts = ref({});
const restockParts = ref({});

const refundMode = ref('immediate'); // 'immediate' | 'deferred'
const paymentDueDate = ref(
    new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
);
const recordRefund = ref(true);
const refundMethod = ref('cash');
const refundReference = ref('');
const refundNotes = ref('');
const returnReason = ref('');

const paymentMethodOptions = computed(() => [
    { value: 'cash', label: t('invoices.credit_note.methods.cash') },
    { value: 'card', label: t('invoices.credit_note.methods.card') },
    { value: 'bank_transfer', label: t('invoices.credit_note.methods.bank_transfer') },
]);

const reasonPresets = computed(() => [
    t('invoices.credit_note.reason_quick_1'),
    t('invoices.credit_note.reason_quick_2'),
    t('invoices.credit_note.reason_quick_3'),
    t('invoices.credit_note.reason_quick_4'),
]);

const effectiveWorkOrder = computed(() => {
    return props.workOrder || props.invoice?.work_order || null;
});

const summaryUrl = computed(() => {
    if (props.invoice?.id) {
        return route('app.invoices.credit-note.summary', props.invoice.id);
    }
    if (props.workOrder?.id) {
        return route('app.work-orders.credit-note.summary', props.workOrder.id);
    }
    return null;
});

const storeUrl = computed(() => {
    if (props.invoice?.id) {
        return route('app.invoices.credit-note.store', props.invoice.id);
    }
    if (props.workOrder?.id) {
        return route('app.work-orders.credit-note.store', props.workOrder.id);
    }
    return null;
});

const formatCurrency = (val) => {
    const num = Number(val || 0);
    const currency = effectiveWorkOrder.value?.currency || props.invoice?.currency_code || 'SAR';
    return (
        num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) +
        ' ' +
        currency
    );
};

const fetchSummary = async () => {
    if (!summaryUrl.value) return;
    loading.value = true;
    errorMessage.value = '';
    backendMessage.value = '';
    try {
        const response = await axios.get(summaryUrl.value);
        const data = response.data;
        invoice.value = data.invoice;
        services.value = Array.isArray(data.services)
            ? data.services
            : Object.values(data.services || {});
        parts.value = Array.isArray(data.parts) ? data.parts : Object.values(data.parts || {});
        isPossible.value = Boolean(data.is_possible);
        if (data.message && !isPossible.value) {
            backendMessage.value = data.message;
        }

        // Reset input bindings
        returnServices.value = {};
        services.value.forEach((s) => {
            returnServices.value[s.id] = 0;
        });

        returnParts.value = {};
        restockParts.value = {};
        parts.value.forEach((p) => {
            returnParts.value[p.id] = 0;
            restockParts.value[p.id] = true;
        });
    } catch (e) {
        console.error('Failed to load credit note summary', e);
        errorMessage.value =
            e.response?.data?.message ||
            t('common.error_occurred') ||
            t('work_orders.credit_note.load_error');
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            errorMessage.value = '';
            backendMessage.value = '';
            fetchSummary();
            returnReason.value = '';
            refundMode.value = 'immediate';
            paymentDueDate.value = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0];
            recordRefund.value = true;
            refundMethod.value = 'cash';
            refundReference.value = '';
            refundNotes.value = '';
        }
    },
    { immediate: true }
);

// Financial calculations
const isTaxEnabled = computed(() => {
    return Boolean(invoice.value?.tax_enabled_snapshot);
});

const totalRefundSubtotal = computed(() => {
    let sum = 0;
    // Services subtotal
    services.value.forEach((s) => {
        const val = Number(returnServices.value[s.id] || 0);
        if (val > 0) sum += val;
    });
    // Parts subtotal
    parts.value.forEach((p) => {
        const qty = Number(returnParts.value[p.id] || 0);
        if (qty > 0) sum += qty * Number(p.unit_price || 0);
    });
    return Number(sum.toFixed(2));
});

const totalRefundVat = computed(() => {
    if (!isTaxEnabled.value) {
        return 0;
    }
    let sum = 0;
    // Services vat
    services.value.forEach((s) => {
        const val = Number(returnServices.value[s.id] || 0);
        if (val > 0) {
            const rate = Number(s.tax_rate ?? invoice.value?.tax_rate ?? 0);
            sum += (val * rate) / 100;
        }
    });
    // Parts vat
    parts.value.forEach((p) => {
        const qty = Number(returnParts.value[p.id] || 0);
        if (qty > 0) {
            const rate = Number(p.tax_rate ?? invoice.value?.tax_rate ?? 0);
            const lineSubtotal = qty * Number(p.unit_price || 0);
            sum += (lineSubtotal * rate) / 100;
        }
    });
    return Number(sum.toFixed(2));
});

const totalRefundAmount = computed(() => {
    return Number((totalRefundSubtotal.value + totalRefundVat.value).toFixed(2));
});

const originalInvoiceRemaining = computed(() => {
    return Number(invoice.value?.remaining_amount || 0);
});

const amountAdjusted = computed(() => {
    return Number(Math.min(originalInvoiceRemaining.value, totalRefundAmount.value).toFixed(2));
});

const netRefundDue = computed(() => {
    return Number(Math.max(0, totalRefundAmount.value - amountAdjusted.value).toFixed(2));
});

const handleClose = () => {
    if (!submitting.value) {
        emit('close');
    }
};

const submitCreditNote = async () => {
    if (totalRefundAmount.value <= 0) {
        errorMessage.value = t('invoices.credit_note.validation_no_items');
        return;
    }

    submitting.value = true;
    errorMessage.value = '';

    const payloadServices = Object.keys(returnServices.value)
        .map((id) => ({
            id: Number(id),
            return_amount: Number(returnServices.value[id] || 0),
        }))
        .filter((item) => item.return_amount > 0);

    const payloadParts = Object.keys(returnParts.value)
        .map((id) => ({
            id: Number(id),
            return_qty: Number(returnParts.value[id] || 0),
            return_to_stock: !!restockParts.value[id],
        }))
        .filter((item) => item.return_qty > 0);

    const payload = {
        services: payloadServices,
        parts: payloadParts,
        return_reason: returnReason.value,
        record_refund: refundMode.value === 'immediate' && netRefundDue.value > 0,
        refund_payment_method: refundMethod.value,
        refund_reference: refundReference.value,
        refund_notes: refundNotes.value,
        payment_due_date: refundMode.value === 'deferred' ? paymentDueDate.value : null,
    };

    try {
        const res = await axios.post(storeUrl.value, payload);
        emit('created', res.data);
        emit('close');

        // Redirect to credit note invoice show page
        if (res.data?.credit_note_id) {
            router.visit(route('app.invoices.show', res.data.credit_note_id));
        }
    } catch (e) {
        console.error('Failed to create credit note', e);
        errorMessage.value =
            e.response?.data?.message ||
            (e.response?.data?.errors
                ? Object.values(e.response.data.errors).flat().join(', ')
                : 'Failed to create credit note');
    } finally {
        submitting.value = false;
    }
};
</script>
