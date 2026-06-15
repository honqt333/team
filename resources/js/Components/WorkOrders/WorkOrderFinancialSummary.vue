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
    </div>
</template>

<script setup>
import { useNumberFormat } from '@/Composables/useNumberFormat';

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
    balance: { type: Number, default: 0 },
});

const { formatCurrency } = useNumberFormat();
</script>
