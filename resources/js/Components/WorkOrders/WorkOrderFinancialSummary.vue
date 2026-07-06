<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full">
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <div class="w-2 h-5 bg-amber-500 rounded-full"></div>
            {{ $t('work_orders.cost_and_payment') }}
        </h3>

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50">
                        <th class="pb-2 text-start font-bold uppercase tracking-wider align-middle text-center">{{
                            $t('common.type') }}</th>
                        <th class="pb-2 text-end font-bold uppercase tracking-wider align-middle text-center">{{
                            $t('work_orders.price') }}</th>
                        <th
                            class="pb-2 text-end font-bold uppercase tracking-wider text-red-500 italic align-middle text-center">
                            {{ $t('work_orders.discount') }}</th>
                        <th class="pb-2 text-end font-bold uppercase tracking-wider align-middle text-center">{{
                            $t('common.amount') }}</th>
                        <th v-if="hasTax"
                            class="pb-2 text-end font-bold uppercase tracking-wider italic align-middle text-center">
                            {{ $t('work_orders.vat_header', { rate: taxRate }) }}</th>
                        <th
                            class="pb-2 text-end font-bold uppercase tracking-wider tracking-widest text-gray-900 dark:text-white align-middle text-center">
                            {{ $t('common.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                    <!-- Services row -->
                    <tr class="group">
                        <td class="py-1.5 font-bold text-gray-900 dark:text-white align-middle">
                            {{ $t('work_orders.services_total') }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.services.price) }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-red-500 italic align-middle">
                            {{ totals.services.discount > 0 ? '-' + formatCurrency(totals.services.discount) : '0.00' }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.services.amount) }}
                        </td>
                        <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.services.tax) }}
                        </td>
                        <td
                            class="py-1.5 text-end font-black text-gray-900 dark:text-white font-mono text-base align-middle">
                            {{ formatCurrency(totals.services.total) }}
                        </td>
                    </tr>

                    <!-- Parts row (only when there are parts) -->
                    <tr class="group" v-if="totals.parts.total > 0">
                        <td class="py-1.5 font-bold text-gray-900 dark:text-white align-middle">
                            {{ $t('work_orders.parts_total') }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.parts.price) }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-red-500 italic align-middle">
                            {{ totals.parts.discount > 0 ? '-' + formatCurrency(totals.parts.discount) : '0.00' }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.parts.amount) }}
                        </td>
                        <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.parts.tax) }}
                        </td>
                        <td
                            class="py-1.5 text-end font-black text-gray-900 dark:text-white font-mono text-base align-middle">
                            {{ formatCurrency(totals.parts.total) }}
                        </td>
                    </tr>

                    <!-- Grand total row -->
                    <tr
                        class="bg-gray-50/50 dark:bg-gray-900/30 font-black border-t-2 border-gray-100 dark:border-gray-700">
                        <td class="py-1.5 text-gray-900 dark:text-white ps-2 uppercase align-middle">
                            {{ $t('work_orders.total') }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.grand.price) }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-red-600 italic align-middle">
                            {{ totals.grand.discount > 0 ? '-' + formatCurrency(totals.grand.discount) : '0.00' }}
                        </td>
                        <td class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.grand.amount) }}
                        </td>
                        <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500 align-middle">
                            {{ formatCurrency(totals.grand.tax) }}
                        </td>
                        <td
                            class="py-1.5 text-end font-black text-indigo-600 dark:text-indigo-400 font-mono text-lg align-middle">
                            {{ formatCurrency(totals.grand.total) }}
                        </td>
                    </tr>

                    <!-- Paid row -->
                    <tr class="bg-gray-50/50 dark:bg-gray-900/30">
                        <td class="py-1.5 font-black text-green-600 dark:text-green-500 ps-2 align-middle"
                            :colspan="hasTax ? 5 : 4">
                            {{ $t('work_orders.paid') }}</td>
                        <td
                            class="py-1.5 text-end font-black text-green-600 dark:text-green-500 font-mono text-base align-middle">
                            {{ formatCurrency(totalPaid) }}
                        </td>
                    </tr>

                    <!-- Bad Debt row -->
                    <tr v-if="badDebt > 0" class="bg-gray-50/50 dark:bg-gray-900/30">
                        <td class="py-1.5 font-black text-amber-600 dark:text-amber-500 ps-2 align-middle"
                            :colspan="hasTax ? 5 : 4">
                            {{ $t('payments.types.bad_debt') || 'الديون المعدومة' }}</td>
                        <td
                            class="py-1.5 text-end font-black text-amber-600 dark:text-amber-500 font-mono text-base align-middle">
                            {{ formatCurrency(badDebt) }}
                        </td>
                    </tr>

                    <!-- Balance row (highlighted when outstanding) -->
                    <tr class="bg-gray-50/50 dark:bg-gray-900/30 border-b-0">
                        <td class="py-1.5 font-black ps-2 align-middle"
                            :class="balance > 0 ? 'text-orange-600 dark:text-orange-500' : 'text-gray-500'"
                            :colspan="hasTax ? 5 : 4">{{ $t('work_orders.balance') }}</td>
                        <td class="py-1.5 text-end font-black font-mono text-base align-middle"
                            :class="balance > 0 ? 'text-orange-600 dark:text-orange-500' : 'text-gray-500'">
                            {{ formatCurrency(balance) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Alerts / Notice boxes at the bottom -->
        <div v-if="workOrder && workOrder.status === 'done' && balance > 0.01" class="mt-4" dir="rtl">
            <!-- Case 1: Has invoice and it is NOT overdue/due yet -->
            <div v-if="workOrder.invoice && !isInvoiceOverdue" class="p-3 rounded-xl bg-violet-50 dark:bg-violet-950/20 border border-violet-100 dark:border-violet-800/50 flex items-center justify-between text-violet-700 dark:text-violet-300">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div class="text-xs text-right">
                        <span class="font-bold block text-sm mb-0.5">
                            {{ $t('invoices.deferred_invoice') || 'فاتورة آجلة' }}
                            <span class="text-xs font-normal font-mono" dir="ltr">#{{ workOrder.invoice.invoice_number }}</span>
                        </span>
                        <span v-if="workOrder.invoice.due_date" class="text-gray-500 dark:text-gray-400">
                            {{ $t('invoices.due_date') || 'تاريخ الاستحقاق' }}: {{ formatDate(workOrder.invoice.due_date) }}
                        </span>
                    </div>
                </div>
                <Link
                    :href="route('app.invoices.show', workOrder.invoice.id)"
                    class="text-xs font-bold px-2.5 py-1.5 bg-violet-100 hover:bg-violet-200 dark:bg-violet-900/50 dark:hover:bg-violet-900 text-violet-700 dark:text-violet-300 rounded-lg transition-colors flex items-center gap-1"
                >
                    {{ $t('common.view') || 'عرض' }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </Link>
            </div>

            <!-- Case 2: Either no invoice, or the invoice is due/overdue -->
            <div v-else class="p-3 rounded-xl bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-800/50 flex items-center justify-between text-red-700 dark:text-red-300">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-xs text-right">
                        <span class="font-bold block text-sm mb-0.5">{{ $t('work_orders.remaining_dues_alert') || 'المبالغ المستحقة للدفع' }}</span>
                        <span v-if="workOrder.invoice" class="text-gray-500 dark:text-gray-400 text-[11px] block mt-0.5">
                            تجاوز تاريخ استحقاق الفاتورة الآجلة رقم <span class="font-mono" dir="ltr">#{{ workOrder.invoice.invoice_number }}</span> (تاريخ الاستحقاق: {{ formatDate(workOrder.invoice.due_date) }})
                        </span>
                        <span v-else class="text-gray-500 dark:text-gray-400 text-[11px] block mt-0.5">
                            يرجى تسجيل الدفعة أو إصدار فاتورة آجلة لتسوية رصيد هذا الكرت.
                        </span>
                    </div>
                </div>
                <Link
                    v-if="workOrder.invoice"
                    :href="route('app.invoices.show', workOrder.invoice.id)"
                    class="text-xs font-bold px-2.5 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg transition-colors flex items-center gap-1"
                >
                    {{ $t('common.view') || 'عرض' }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormatters } from '@/Composables/useFormatters';

const props = defineProps({
    /**
     * Pre-aggregated totals for services, parts, and grand. The parent
     * (Show.vue) computes these once and shares the same `totals` value
     * with WorkOrderItemModal / PaymentsSection, so we don't recompute
     * here. Shape:
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
    workOrder: { type: Object, default: null },
});

const { formatCurrency } = useNumberFormat();
const { formatDate } = useFormatters();

const isInvoiceOverdue = computed(() => {
    if (!props.workOrder?.invoice?.due_date) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDateStr = props.workOrder.invoice.due_date.split('T')[0];
    const dueDate = new Date(dueDateStr);
    dueDate.setHours(0, 0, 0, 0);
    return today >= dueDate;
});
</script>
