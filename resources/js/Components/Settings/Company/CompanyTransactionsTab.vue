<template>
    <div class="space-y-6">
        <div
            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/50 pb-4"
        >
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $t('company_profile.tabs.revenue_expenses') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $t('company_profile.transactions.subtitle') }}
                </p>
            </div>
            <button
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

        <!-- Filters -->
        <div class="flex items-center gap-2 p-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl w-fit">
            <button
                type="button"
                @click="filterType = 'all'"
                :class="[
                    'px-4 py-1.5 text-xs font-bold rounded-lg transition-all',
                    filterType === 'all'
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 dark:hover:text-white',
                ]"
            >
                {{ $t('common.all') }}
            </button>
            <button
                type="button"
                @click="filterType = 'revenue'"
                :class="[
                    'px-4 py-1.5 text-xs font-bold rounded-lg transition-all',
                    filterType === 'revenue'
                        ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 dark:hover:text-white',
                ]"
            >
                {{ $t('company_profile.transactions.form.type_revenue') }}
            </button>
            <button
                type="button"
                @click="filterType = 'expense'"
                :class="[
                    'px-4 py-1.5 text-xs font-bold rounded-lg transition-all',
                    filterType === 'expense'
                        ? 'bg-white dark:bg-gray-700 text-rose-600 dark:text-rose-400 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 dark:hover:text-white',
                ]"
            >
                {{ $t('company_profile.transactions.form.type_expense') }}
            </button>
        </div>

        <!-- Transactions Table -->
        <div
            class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800"
                        >
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">
                                {{ $t('company_profile.transactions.table.title') }}
                            </th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">
                                {{ $t('company_profile.transactions.table.date') }}
                            </th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">
                                {{ $t('company_profile.transactions.table.category') }}
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase font-mono"
                            >
                                {{ $t('company_profile.transactions.table.amount') }}
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase font-mono"
                            >
                                {{ $t('company_profile.transactions.table.vat') }}
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase font-mono"
                            >
                                {{ $t('company_profile.transactions.table.total') }}
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-center"
                            >
                                {{ $t('company_profile.transactions.table.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                        <tr
                            v-for="(trans, index) in filteredTransactions"
                            :key="trans.id"
                            class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors"
                        >
                            <td class="px-6 py-4 text-xs font-bold text-gray-400">
                                {{ index + 1 }}
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                {{ trans.title }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 font-mono">
                                {{ trans.transaction_date }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300"
                            >
                                {{ trans.income_category?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-bold font-mono">
                                {{ formatCurrency(trans.amount) }}
                            </td>
                            <td class="px-6 py-4 text-xs font-bold font-mono text-gray-500">
                                {{ formatCurrency(trans.vat_amount || 0) }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs font-black font-mono"
                                :class="
                                    trans.transaction_type === 'revenue'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-rose-600 dark:text-rose-400'
                                "
                            >
                                {{ formatCurrency(trans.total_amount) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        v-if="!trans.is_approved"
                                        @click="$emit('approve-transaction', trans)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                    >
                                        {{ $t('hr.payroll.approve') }}
                                    </button>
                                    <button
                                        @click="$emit('edit-transaction', trans)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-action-primary hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="$emit('delete-transaction', trans)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
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
                            </td>
                        </tr>
                        <tr v-if="!filteredTransactions || !filteredTransactions.length">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">
                                {{ $t('company_profile.transactions.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    invoices: { type: Object, default: () => ({ sales: [], purchases: [] }) },
    currency: { type: String, default: 'SAR' },
});

defineEmits(['add-transaction', 'edit-transaction', 'delete-transaction', 'approve-transaction']);

const filterType = ref('all');

const filteredTransactions = computed(() => {
    if (filterType.value === 'all') return props.transactions;
    return props.transactions.filter((t) => t.transaction_type === filterType.value);
});

const formatCurrency = (val) => {
    const num = parseFloat(val || 0);
    const curr = props.currency || 'SAR';
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ` ${curr}`
    );
};
</script>
