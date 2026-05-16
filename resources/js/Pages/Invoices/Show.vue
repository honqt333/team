<template>
    <AppLayout :title="`${$t('invoices.invoice')} #${invoice.invoice_number}`">
        <div class="space-y-6 pb-20">
            <!-- Premium Glass Header -->
            <PageHeader
                :title="`${$t('invoices.invoice')} #${invoice.invoice_number}`"
                :subtitle="invoice.customer?.name"
                :totalCount="formatCurrency(invoice.total_incl_tax)"
                :countLabel="$t('invoices.grand_total')"
                gradientFrom="from-blue-600"
                gradientTo="to-indigo-600"
                glowFrom="from-blue-500"
                badgeBg="bg-blue-50/50 dark:bg-blue-900/30"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-100/50 dark:border-blue-800/30"
                badgeDot="bg-blue-500"
            >
                <template #back>
                    <Link
                        :href="route('app.invoices.hub')"
                        :title="$t('common.back')"
                        class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-300 text-blue-600 group"
                    >
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <span :class="statusClass(invoice.payment_status)" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm">
                            {{ $t(`invoices.status.${invoice.payment_status}`) }}
                        </span>
                        
                        <Link 
                            :href="route('app.invoices.print', invoice.id)" 
                            target="_blank"
                            class="flex items-center gap-2.5 px-6 py-1.5 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm text-blue-600 dark:text-blue-400 rounded-xl font-black text-[10px] uppercase tracking-widest border border-blue-100 dark:border-blue-800/50 shadow-sm hover:bg-blue-600 hover:text-white transition-all duration-300"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>{{ $t('invoices.print') }}</span>
                        </Link>
                    </div>
                </template>
            </PageHeader>

            <!-- Financial Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Subtotal -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700/50 shadow-sm group hover:shadow-md transition-all">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] mb-1.5">{{ $t('invoices.subtotal') }}</p>
                    <p class="text-xl font-black text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrency(invoice.total_excl_tax) }}</p>
                </div>

                <!-- Tax -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700/50 shadow-sm group hover:shadow-md transition-all">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] mb-1.5">{{ $t('invoices.tax') }} ({{ invoice.tax_rate_snapshot }}%)</p>
                    <p class="text-xl font-black text-gray-900 dark:text-white font-mono" dir="ltr">{{ formatCurrency(invoice.total_tax) }}</p>
                </div>

                <!-- Grand Total -->
                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-3xl p-6 shadow-lg shadow-blue-500/20 group hover:shadow-xl hover:-translate-y-1 transition-all">
                    <p class="text-[10px] font-black text-white/70 uppercase tracking-[0.15em] mb-1.5">{{ $t('invoices.grand_total') }}</p>
                    <p class="text-2xl font-black text-white font-mono" dir="ltr">{{ formatCurrency(invoice.total_incl_tax) }}</p>
                </div>

                <!-- Balance -->
                <div :class="[
                    'rounded-3xl p-6 shadow-sm group hover:shadow-md transition-all border',
                    invoice.balance > 0 ? 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-900/30' : 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-900/30'
                ]">
                    <p :class="[
                        'text-[10px] font-black uppercase tracking-[0.15em] mb-1.5',
                        invoice.balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'
                    ]">{{ $t('invoices.balance') }}</p>
                    <p :class="[
                        'text-xl font-black font-mono',
                        invoice.balance > 0 ? 'text-red-700 dark:text-red-300' : 'text-emerald-700 dark:text-emerald-300'
                    ]" dir="ltr">{{ formatCurrency(invoice.balance) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Items Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/30">
                            <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">{{ $t('common.details') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50/30 dark:bg-gray-900/50">
                                        <th class="px-8 py-5 text-start text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.description') }}</th>
                                        <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.qty') }}</th>
                                        <th class="px-8 py-5 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.unit_price') }}</th>
                                        <th class="px-8 py-5 text-end text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $t('common.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr v-for="line in invoice.lines" :key="line.id" class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors">
                                        <td class="px-8 py-6">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ line.description }}</span>
                                        </td>
                                        <td class="px-8 py-6 text-center text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ toEnglish(line.qty) }}</td>
                                        <td class="px-8 py-6 text-end text-sm font-black text-gray-600 dark:text-gray-400 font-mono">{{ formatCurrency(line.unit_price) }}</td>
                                        <td class="px-8 py-6 text-end text-base font-black text-gray-900 dark:text-white font-mono">{{ formatCurrency(line.line_total_incl_tax) }}</td>
                                    </tr>
                                </tbody>
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
