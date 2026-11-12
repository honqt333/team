<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full"
    >
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
            {{ $t('work_orders.cost_and_payment') }}
        </h3>

        <!-- Mobile Card Breakdown (Visible on mobile < sm) -->
        <div class="block sm:hidden space-y-3 mb-2">
            <!-- Services breakdown if present -->
            <div
                v-if="totals.services.total > 0"
                class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl space-y-1.5 border border-gray-100 dark:border-gray-700/50"
            >
                <div
                    class="flex justify-between items-center text-xs font-bold text-gray-900 dark:text-white"
                >
                    <span>{{ servicesLabel || $t('work_orders.services_total') }}</span>
                    <span class="font-mono">{{ formatCurrency(totals.services.total) }}</span>
                </div>
                <div class="flex justify-between items-center text-[11px] text-gray-500">
                    <span>
                        {{ $t('work_orders.price') }}: {{ formatCurrency(totals.services.price) }}
                    </span>
                    <span v-if="totals.services.discount > 0" class="text-red-500 italic">
                        -{{ formatCurrency(totals.services.discount) }}
                    </span>
                </div>
            </div>

            <!-- Spare Parts breakdown if present -->
            <div
                v-if="totals.parts.total > 0"
                class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl space-y-1.5 border border-gray-100 dark:border-gray-700/50"
            >
                <div
                    class="flex justify-between items-center text-xs font-bold text-gray-900 dark:text-white"
                >
                    <span>{{ partsLabel || $t('work_orders.parts_total') }}</span>
                    <span class="font-mono">{{ formatCurrency(totals.parts.total) }}</span>
                </div>
                <div class="flex justify-between items-center text-[11px] text-gray-500">
                    <span>
                        {{ $t('work_orders.price') }}: {{ formatCurrency(totals.parts.price) }}
                    </span>
                    <span v-if="totals.parts.discount > 0" class="text-red-500 italic">
                        -{{ formatCurrency(totals.parts.discount) }}
                    </span>
                </div>
            </div>

            <!-- Grand Total, Paid & Balance Summary Card -->
            <div
                class="p-3.5 bg-amber-50/70 dark:bg-amber-950/20 rounded-xl border border-amber-200/60 dark:border-amber-800/40 space-y-2"
            >
                <div
                    class="flex justify-between items-center text-sm font-black text-amber-900 dark:text-amber-300"
                >
                    <span>{{ grandLabel || $t('work_orders.total') }}</span>
                    <span class="font-mono text-base text-amber-600 dark:text-amber-400">
                        {{ formatCurrency(totals.grand.total) }}
                    </span>
                </div>
                <div
                    v-if="!showPayments && hasTax"
                    class="flex justify-between items-center text-xs text-gray-500"
                >
                    <span>{{ $t('work_orders.vat_header', { rate: taxRate }) }}</span>
                    <span class="font-mono">{{ formatCurrency(totals.grand.tax) }}</span>
                </div>
                <template v-if="showPayments">
                    <div
                        v-if="creditNotesTotal > 0"
                        class="flex justify-between items-center text-xs font-bold text-red-600 dark:text-red-400"
                    >
                        <span>{{ $t('invoices.credit_notes_total') }}</span>
                        <span class="font-mono">-{{ formatCurrency(creditNotesTotal) }}</span>
                    </div>
                    <div
                        class="flex justify-between items-center text-xs font-bold text-green-600 dark:text-green-500"
                    >
                        <span>{{ $t('work_orders.paid') }}</span>
                        <span class="font-mono">{{ formatCurrency(totalPaid) }}</span>
                    </div>
                    <div
                        v-if="badDebt > 0"
                        class="flex justify-between items-center text-xs font-bold text-amber-600 dark:text-amber-500"
                    >
                        <span>{{ $t('payments.types.bad_debt') }}</span>
                        <span class="font-mono">{{ formatCurrency(badDebt) }}</span>
                    </div>
                    <div
                        v-if="excessPaid > 0.01"
                        class="flex justify-between items-center text-xs font-bold text-red-600 dark:text-red-400"
                    >
                        <span>{{ $t('work_orders.excess_paid') }}</span>
                        <span class="font-mono">{{ formatCurrency(excessPaid) }}</span>
                    </div>
                    <div
                        class="pt-2 border-t border-amber-200/60 dark:border-amber-800/40 flex justify-between items-center text-sm font-black"
                        :class="
                            balance > 0 ? 'text-orange-600 dark:text-orange-500' : 'text-gray-500'
                        "
                    >
                        <span>{{ $t('work_orders.balance') }}</span>
                        <span class="font-mono text-base">{{ formatCurrency(balance) }}</span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Desktop Table (Visible on sm and larger) -->
        <div class="hidden sm:block overflow-x-auto flex-1">
            <table class="w-full text-xs">
                <thead>
                    <tr
                        class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/60"
                    >
                        <th
                            class="pb-2 text-start font-bold uppercase tracking-wider align-middle text-[11px]"
                        >
                            {{ $t('common.type') }}
                        </th>
                        <th
                            class="pb-2 text-center font-bold uppercase tracking-wider align-middle text-[11px]"
                        >
                            {{ $t('work_orders.price') }}
                        </th>
                        <th
                            class="pb-2 text-center font-bold uppercase tracking-wider text-red-500 italic align-middle text-[11px]"
                        >
                            {{ $t('work_orders.discount') }}
                        </th>
                        <th
                            class="pb-2 text-center font-bold uppercase tracking-wider align-middle text-[11px]"
                        >
                            {{ $t('common.amount') }}
                        </th>
                        <th
                            v-if="hasTax"
                            class="pb-2 text-center font-bold uppercase tracking-wider italic align-middle text-[11px]"
                        >
                            {{ $t('work_orders.vat_header', { rate: taxRate }) }}
                        </th>
                        <th
                            class="pb-2 text-end font-bold uppercase tracking-wider tracking-widest text-gray-900 dark:text-white align-middle text-[11px]"
                        >
                            {{ $t('common.total') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                    <!-- Services row -->
                    <tr class="group" v-if="showServicesRow">
                        <td class="py-2 font-bold text-gray-900 dark:text-white align-middle">
                            {{ servicesLabel || $t('work_orders.services_total') }}
                        </td>
                        <td class="py-2 text-center font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.services.price) }}
                        </td>
                        <td class="py-2 text-center font-mono text-red-500 italic align-middle">
                            {{
                                totals.services.discount > 0
                                    ? '-' + formatCurrency(totals.services.discount)
                                    : '0.00'
                            }}
                        </td>
                        <td class="py-2 text-center font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.services.amount) }}
                        </td>
                        <td
                            v-if="hasTax"
                            class="py-2 text-center font-mono text-gray-500 align-middle"
                        >
                            {{ formatCurrency(totals.services.tax) }}
                        </td>
                        <td
                            class="py-2 text-end font-bold text-gray-900 dark:text-white font-mono text-sm align-middle"
                        >
                            {{ formatCurrency(totals.services.total) }}
                        </td>
                    </tr>

                    <!-- Parts row (when there are parts) -->
                    <tr class="group" v-if="showPartsRow">
                        <td class="py-2 font-bold text-gray-900 dark:text-white align-middle">
                            {{ partsLabel || $t('work_orders.parts_total') }}
                        </td>
                        <td class="py-2 text-center font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.parts.price) }}
                        </td>
                        <td class="py-2 text-center font-mono text-red-500 italic align-middle">
                            {{
                                totals.parts.discount > 0
                                    ? '-' + formatCurrency(totals.parts.discount)
                                    : '0.00'
                            }}
                        </td>
                        <td class="py-2 text-center font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.parts.amount) }}
                        </td>
                        <td
                            v-if="hasTax"
                            class="py-2 text-center font-mono text-gray-500 align-middle"
                        >
                            {{ formatCurrency(totals.parts.tax) }}
                        </td>
                        <td
                            class="py-2 text-end font-bold text-gray-900 dark:text-white font-mono text-sm align-middle"
                        >
                            {{ formatCurrency(totals.parts.total) }}
                        </td>
                    </tr>

                    <!-- Fallback single breakdown row if both services and parts are hidden/zero -->
                    <tr class="group" v-if="showFallbackRow">
                        <td class="py-2 font-bold text-gray-900 dark:text-white align-middle">
                            {{ partsLabel || $t('common.amount') }}
                        </td>
                        <td class="py-2 text-center font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.grand.price) }}
                        </td>
                        <td class="py-2 text-center font-mono text-red-500 italic align-middle">
                            {{
                                totals.grand.discount > 0
                                    ? '-' + formatCurrency(totals.grand.discount)
                                    : '0.00'
                            }}
                        </td>
                        <td class="py-2 text-center font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.grand.amount) }}
                        </td>
                        <td
                            v-if="hasTax"
                            class="py-2 text-center font-mono text-gray-500 align-middle"
                        >
                            {{ formatCurrency(totals.grand.tax) }}
                        </td>
                        <td
                            class="py-2 text-end font-bold text-gray-900 dark:text-white font-mono text-sm align-middle"
                        >
                            {{ formatCurrency(totals.grand.total) }}
                        </td>
                    </tr>

                    <!-- Grand total row -->
                    <tr
                        class="bg-gray-50/70 dark:bg-gray-900/40 font-bold border-t-2 border-gray-100 dark:border-gray-700/80"
                    >
                        <td
                            class="py-2 text-gray-900 dark:text-white ps-2 font-bold uppercase align-middle text-xs"
                        >
                            {{ grandLabel || $t('work_orders.total') }}
                        </td>
                        <td
                            class="py-2 text-center font-mono text-gray-600 dark:text-gray-300 align-middle"
                        >
                            {{ formatCurrency(totals.grand.price) }}
                        </td>
                        <td class="py-2 text-center font-mono text-red-600 italic align-middle">
                            {{
                                totals.grand.discount > 0
                                    ? '-' + formatCurrency(totals.grand.discount)
                                    : '0.00'
                            }}
                        </td>
                        <td
                            class="py-2 text-center font-mono text-gray-600 dark:text-gray-300 align-middle"
                        >
                            {{ formatCurrency(totals.grand.amount) }}
                        </td>
                        <td
                            v-if="hasTax"
                            class="py-2 text-center font-mono text-gray-600 dark:text-gray-300 align-middle"
                        >
                            {{ formatCurrency(totals.grand.tax) }}
                        </td>
                        <td
                            class="py-2 text-end font-black text-action-primary font-mono text-sm align-middle"
                        >
                            {{ formatCurrency(totals.grand.total) }}
                        </td>
                    </tr>

                    <!-- Payment rows (hidden for quotes via showPayments = false) -->
                    <template v-if="showPayments">
                        <!-- Credit Notes (Returns) row if any -->
                        <tr v-if="creditNotesTotal > 0" class="bg-red-50/50 dark:bg-red-950/20">
                            <td
                                class="py-2 font-bold text-red-600 dark:text-red-400 ps-2 align-middle text-xs"
                                :colspan="hasTax ? 5 : 4"
                            >
                                {{ $t('invoices.credit_notes_total') }}
                            </td>
                            <td
                                class="py-2 text-end font-bold text-red-600 dark:text-red-400 font-mono text-sm align-middle"
                            >
                                -{{ formatCurrency(creditNotesTotal) }}
                            </td>
                        </tr>

                        <!-- Net Total after Credit Notes -->
                        <tr v-if="creditNotesTotal > 0" class="bg-gray-50/50 dark:bg-gray-900/30">
                            <td
                                class="py-2 font-bold text-gray-900 dark:text-white ps-2 align-middle text-xs"
                                :colspan="hasTax ? 5 : 4"
                            >
                                {{ $t('work_orders.net_total') }}
                            </td>
                            <td
                                class="py-2 text-end font-bold text-gray-900 dark:text-white font-mono text-sm align-middle"
                            >
                                {{
                                    formatCurrency(
                                        Math.max(0, totals.grand.total - creditNotesTotal)
                                    )
                                }}
                            </td>
                        </tr>

                        <!-- Paid row -->
                        <tr class="bg-gray-50/50 dark:bg-gray-900/30">
                            <td
                                class="py-2 font-bold text-green-600 dark:text-green-500 ps-2 align-middle text-xs"
                                :colspan="hasTax ? 5 : 4"
                            >
                                {{ $t('work_orders.paid') }}
                            </td>
                            <td
                                class="py-2 text-end font-bold text-green-600 dark:text-green-500 font-mono text-sm align-middle"
                            >
                                {{ formatCurrency(totalPaid) }}
                            </td>
                        </tr>

                        <!-- Bad Debt row -->
                        <tr v-if="badDebt > 0" class="bg-gray-50/50 dark:bg-gray-900/30">
                            <td
                                class="py-2 font-bold text-amber-600 dark:text-amber-500 ps-2 align-middle text-xs"
                                :colspan="hasTax ? 5 : 4"
                            >
                                {{ $t('payments.types.bad_debt') }}
                            </td>
                            <td
                                class="py-2 text-end font-bold text-amber-600 dark:text-amber-500 font-mono text-sm align-middle"
                            >
                                {{ formatCurrency(badDebt) }}
                            </td>
                        </tr>

                        <!-- Excess Paid row (when overpayment exists) -->
                        <tr v-if="excessPaid > 0.01" class="bg-red-50/50 dark:bg-red-950/20">
                            <td
                                class="py-2 font-bold text-red-600 dark:text-red-400 ps-2 align-middle text-xs"
                                :colspan="hasTax ? 5 : 4"
                            >
                                {{ $t('work_orders.excess_paid') }}
                            </td>
                            <td
                                class="py-2 text-end font-black text-red-600 dark:text-red-400 font-mono text-sm align-middle"
                            >
                                {{ formatCurrency(excessPaid) }}
                            </td>
                        </tr>

                        <!-- Balance row (highlighted when outstanding) -->
                        <tr class="bg-gray-50/50 dark:bg-gray-900/30 border-b-0">
                            <td
                                class="py-2 font-bold ps-2 align-middle text-xs"
                                :class="
                                    balance > 0
                                        ? 'text-orange-600 dark:text-orange-500'
                                        : 'text-gray-500'
                                "
                                :colspan="hasTax ? 5 : 4"
                            >
                                {{ $t('work_orders.balance') }}
                            </td>
                            <td
                                class="py-2 text-end font-black font-mono text-sm align-middle"
                                :class="
                                    balance > 0
                                        ? 'text-orange-600 dark:text-orange-500'
                                        : 'text-gray-500'
                                "
                            >
                                {{ formatCurrency(balance) }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Alerts / Notice boxes at the bottom -->
        <div
            v-if="workOrder && workOrder.status === 'done' && balance > 0.01"
            class="mt-4"
            dir="rtl"
        >
            <div
                class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/50 flex items-center gap-3 text-amber-800 dark:text-amber-200"
            >
                <svg
                    class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                <div class="text-xs text-right">
                    <span class="font-bold block text-sm mb-0.5">
                        {{ $t('work_orders.remaining_dues_alert') }}
                    </span>
                    <span class="text-gray-500 dark:text-gray-400 text-[11px] block mt-0.5">
                        {{ $t('work_orders.financial.settle_balance_hint') }}
                    </span>
                </div>
            </div>
        </div>
        <slot name="footer" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormatters } from '@/Composables/useFormatters';

const props = defineProps({
    /**
     * Pre-aggregated totals for services, parts, and grand.
     * Shape:
     *   { services: { price, discount, amount, tax, total },
     *     parts:    { price, discount, amount, tax, total },
     *     grand:    { price, discount, amount, tax, total } }
     */
    totals: { type: Object, required: true },
    hasTax: { type: Boolean, default: false },
    taxRate: { type: Number, default: 15 },
    totalPaid: { type: Number, default: 0 },
    badDebt: { type: Number, default: 0 },
    balance: { type: Number, default: 0 },
    excessPaid: { type: Number, default: 0 },
    creditNotesTotal: { type: Number, default: 0 },
    showPayments: { type: Boolean, default: true },
    hideZeroRows: { type: Boolean, default: false },
    servicesLabel: { type: String, default: '' },
    partsLabel: { type: String, default: '' },
    grandLabel: { type: String, default: '' },
    workOrder: { type: Object, default: null },
});

const showServicesRow = computed(
    () => !props.hideZeroRows || Number(props.totals?.services?.total || 0) > 0
);
const showPartsRow = computed(() => Number(props.totals?.parts?.total || 0) > 0);
const showFallbackRow = computed(() => !showServicesRow.value && !showPartsRow.value);

const { formatCurrency } = useNumberFormat();
const { formatDate } = useFormatters();
</script>
