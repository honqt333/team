<template>
    <AppLayout :title="`${$t('invoices.invoice')} #${invoice.invoice_number}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('app.invoices.index')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $t('invoices.invoice') }} #{{ invoice.invoice_number }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ formatDate(invoice.issue_date) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="statusClass(invoice.payment_status)" class="inline-flex px-3 py-1.5 rounded-full text-sm font-medium">
                        {{ $t(`invoices.status.${invoice.payment_status}`) }}
                    </span>
                    <Link :href="route('app.invoices.print', invoice.id)" target="_blank" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        {{ $t('invoices.print') }}
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Invoice Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer & Work Order Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ $t('common.customer') }}</h3>
                                <p class="font-medium text-gray-900 dark:text-white">{{ invoice.customer?.name }}</p>
                                <p v-if="invoice.customer?.phone" class="text-sm text-gray-600 dark:text-gray-300">{{ invoice.customer?.phone }}</p>
                            </div>
                            <div v-if="invoice.work_order">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ $t('work_orders.work_order') }}</h3>
                                <Link :href="route('app.work-orders.show', invoice.work_order.id)" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ invoice.work_order.code }}
                                </Link>
                                <p v-if="invoice.work_order.vehicle" class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ invoice.work_order.vehicle.make }} {{ invoice.work_order.vehicle.model }} - {{ invoice.work_order.vehicle.plate_number }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Lines -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $t('common.details') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.description') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.qty') }}</th>
                                        <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.unit_price') }}</th>
                                        <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('invoices.tax') }}</th>
                                        <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="line in invoice.lines" :key="line.id">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ line.description }}</td>
                                        <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-300" dir="ltr">{{ line.qty }}</td>
                                        <td class="px-4 py-3 text-sm text-end text-gray-600 dark:text-gray-300" dir="ltr">{{ formatCurrency(line.unit_price) }}</td>
                                        <td class="px-4 py-3 text-sm text-end text-gray-600 dark:text-gray-300" dir="ltr">{{ formatCurrency(line.tax_amount) }}</td>
                                        <td class="px-4 py-3 text-sm text-end font-medium text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(line.line_total_incl_tax) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-end text-sm font-medium text-gray-600 dark:text-gray-300">{{ $t('invoices.subtotal') }}</td>
                                        <td class="px-4 py-3 text-end text-sm font-medium text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(invoice.total_excl_tax) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-end text-sm font-medium text-gray-600 dark:text-gray-300">{{ $t('invoices.tax') }} ({{ invoice.tax_rate_snapshot }}%)</td>
                                        <td class="px-4 py-3 text-end text-sm font-medium text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(invoice.total_tax) }}</td>
                                    </tr>
                                    <tr class="bg-gray-100 dark:bg-gray-600">
                                        <td colspan="4" class="px-4 py-3 text-end text-base font-bold text-gray-900 dark:text-white">{{ $t('invoices.grand_total') }}</td>
                                        <td class="px-4 py-3 text-end text-base font-bold text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(invoice.total_incl_tax) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Payments -->
                <div class="space-y-6">
                    <!-- Totals Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ $t('payments.title') }}</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">{{ $t('invoices.total') }}</span>
                                <span class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(invoice.total_incl_tax) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">{{ $t('invoices.total_paid') }}</span>
                                <span class="font-medium text-green-600 dark:text-green-400" dir="ltr">{{ formatCurrency(invoice.total_paid) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $t('invoices.balance') }}</span>
                                <span class="font-bold text-lg" :class="balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" dir="ltr">
                                    {{ formatCurrency(balance) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Add Payment Form -->
                    <div v-if="balance > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ $t('payments.add') }}</h3>
                        <form @submit.prevent="submitPayment" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('payments.amount') }}</label>
                                <input
                                    v-model.number="paymentForm.amount"
                                    type="number"
                                    step="0.01"
                                    :max="balance"
                                    min="0.01"
                                    dir="ltr"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('payments.method') }}</label>
                                <select
                                    v-model="paymentForm.payment_method"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    required
                                >
                                    <option value="cash">{{ $t('payments.methods.cash') }}</option>
                                    <option value="card">{{ $t('payments.methods.card') }}</option>
                                    <option value="transfer">{{ $t('payments.methods.transfer') }}</option>
                                    <option value="credit">{{ $t('payments.methods.credit') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('payments.reference') }}</label>
                                <input
                                    v-model="paymentForm.reference"
                                    type="text"
                                    dir="ltr"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    :disabled="paymentForm.processing"
                                    class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium disabled:opacity-50"
                                >
                                    {{ $t('payments.add') }}
                                </button>
                                <button
                                    type="button"
                                    @click="payFullAmount"
                                    :disabled="paymentForm.processing"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium disabled:opacity-50"
                                >
                                    {{ $t('payments.pay_full') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Payments List -->
                    <div v-if="invoice.payments?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $t('payments.payments') }}</h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="payment in invoice.payments" :key="payment.id" class="px-4 py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(payment.amount) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $t(`payments.methods.${payment.payment_method}`) }} • {{ formatDate(payment.payment_date) }}
                                    </p>
                                </div>
                                <button
                                    @click="deletePayment(payment)"
                                    class="p-2 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { confirm } = useConfirm();

const props = defineProps({
    invoice: Object,
});

const balance = computed(() => (props.invoice.total_incl_tax || 0) - (props.invoice.total_paid || 0));

const paymentForm = useForm({
    amount: balance.value,
    payment_method: 'cash',
    reference: '',
});

const submitPayment = () => {
    paymentForm.post(route('app.invoices.payments.store', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset();
            paymentForm.amount = balance.value;
        },
    });
};

const payFullAmount = () => {
    paymentForm.amount = balance.value;
    submitPayment();
};

const deletePayment = async (payment) => {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('app.payments.destroy', payment.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount || 0);
};

const statusClass = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'partial':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'unpaid':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300';
    }
};
</script>
