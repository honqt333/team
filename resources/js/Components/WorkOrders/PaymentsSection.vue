<template>
    <div>
        <!-- Toolbar: Title & Add Button next to it, Search input on the left (end) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">💵</span>
                    {{ $t('payments.title') }}
                </h3>

                <!-- Add Button (بجانب اسم المدفوعات) -->
                <button
                    v-if="!readOnly || (status === 'done' && balance > 0.01)"
                    @click="showPaymentModal = true"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-green-100 dark:shadow-none"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('payments.add_payment') }}
                </button>

                <button
                    v-if="status === 'done' && balance > 0.01 && !hasInvoice"
                    @click="showCreditInvoiceModal = true"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-amber-100 dark:shadow-none"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ $t('invoices.create_credit_invoice') || 'إنشاء فاتورة آجلة' }}
                </button>
            </div>

            <!-- Search input (اقصى اليسار in RTL, i.e., at the end) -->
            <div class="flex items-center gap-3 self-end sm:self-auto">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        :placeholder="$t('common.search') + '...'"
                        class="w-full ps-9 pe-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all shadow-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400">
                        <th class="py-3 px-2 text-center font-medium">#</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('payments.form.method') }}</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('payments.form.type') }}</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('payments.form.date') }}</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('payments.form.notes') }}</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('payments.form.amount') }}</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('common.updated_by') }}</th>
                        <th class="py-3 px-2 text-center font-medium">{{ $t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr 
                        v-for="(payment, index) in filteredPayments" 
                        :key="payment.id"
                        class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <td class="py-3 px-2 text-gray-500 text-center">{{ index + 1 }}</td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getMethodBadgeClass(payment.payment_method)">
                                {{ $t(`payments.methods.${payment.payment_method}`) }}
                            </span>
                        </td>
                        <td class="py-3 px-2 text-center">
                             <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getTypeBadgeClass(payment.type)">
                                {{ $t(`payments.types.${payment.type || 'payment'}`) }}
                            </span>
                        </td>
                        <td class="py-3 px-2 text-gray-600 dark:text-gray-300 text-center" dir="ltr">
                            {{ formatDate(payment.payment_date) }}
                        </td>
                        <td class="py-3 px-2 text-gray-500 dark:text-gray-400 max-w-xs truncate text-center">
                            {{ payment.notes || '-' }}
                        </td>
                        <td class="py-3 px-2 font-bold text-gray-900 dark:text-white text-center" dir="ltr">
                            {{ formatPrice(payment.amount) }}
                        </td>
                        <td class="py-3 px-2 text-center">
                            <div v-if="payment.received_by" class="flex items-center justify-center gap-2 text-start">
                                <img 
                                    v-if="payment.received_by?.avatar"
                                    :src="payment.received_by.avatar" 
                                    class="w-6 h-6 rounded-full"
                                />
                                <div class="text-xs">
                                    <div class="text-gray-900 dark:text-white">{{ payment.received_by?.name || '-' }}</div>
                                    <div class="text-gray-400" dir="ltr">{{ formatDateTime(payment.created_at) }}</div>
                                </div>
                            </div>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <!-- Print Receipt -->
                                <a
                                    :href="route('work-orders.print.payments', workOrderId) + '?payment=' + payment.id"
                                    target="_blank"
                                    class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                    :title="$t('common.print')"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-if="payments.length === 0" class="text-center py-12 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p>{{ $t('payments.no_payments') }}</p>
        </div>

        <!-- Summary -->
        <div v-if="payments.length > 0" class="mt-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
            <div class="flex justify-center flex-wrap gap-8 text-sm">
                <div>
                    <span class="text-gray-500">{{ $t('work_orders.total') }}:</span>
                    <span class="font-bold text-gray-900 dark:text-white mr-2" dir="ltr">{{ formatPrice(grandTotal) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">{{ $t('work_orders.paid') }}:</span>
                    <span class="font-bold text-green-600 mr-2" dir="ltr">{{ formatPrice(totalPaid) }}</span>
                </div>
                <div v-if="badDebt > 0">
                    <span class="text-amber-600 dark:text-amber-500 font-semibold">{{ $t('payments.types.bad_debt') || 'ديون معدومة' }}:</span>
                    <span class="font-bold text-amber-600 mr-2" dir="ltr">{{ formatPrice(badDebt) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">{{ $t('work_orders.balance') }}:</span>
                    <span class="font-bold mr-2" :class="balance > 0 ? 'text-red-600' : 'text-green-600'" dir="ltr">{{ formatPrice(balance) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <PaymentFormModal
            :show="showPaymentModal"
            :work-order-id="workOrderId"
            :balance="balance"
            :allow-refund="payments.length > 0 && !hasInvoice && balance <= 0.01 && status !== 'done'"
            :total-paid="totalPaid"
            :payment="editingPayment"
            @close="closePaymentModal"
            @saved="onPaymentSaved"
        />

        <!-- Credit Invoice Modal -->
        <Modal :show="showCreditInvoiceModal" @close="showCreditInvoiceModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>📝</span>
                        {{ $t('invoices.create_credit_invoice') || 'إنشاء فاتورة آجلة' }}
                    </h3>
                    <button @click="showCreditInvoiceModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCreditInvoice">
                    <div class="space-y-4">
                        <!-- Remaining Balance (Read Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ $t('work_orders.balance') }} (الباقي)
                            </label>
                            <div class="w-full px-4 py-2.5 text-base border rounded-xl bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold font-mono" dir="ltr">
                                {{ formatPrice(balance) }} SAR
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('invoices.due_date') || 'التاريخ المحدد للدفع' }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="creditInvoiceForm.due_date"
                                required
                                :min="todayDate"
                                dir="ltr"
                                class="w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500 text-left font-mono"
                            />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('payments.form.notes') || 'ملاحظات' }}
                            </label>
                            <textarea
                                v-model="creditInvoiceForm.notes"
                                rows="3"
                                class="w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500 placeholder-gray-400 dark:placeholder-gray-500"
                                :placeholder="$t('payments.form.notes_placeholder') || 'أدخل ملاحظات الفاتورة الآجلة...'"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <button
                            type="button"
                            @click="showCreditInvoiceModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="submittingCreditInvoice"
                            class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="submittingCreditInvoice" class="inline-block w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ submittingCreditInvoice ? $t('common.loading') : ($t('common.create') || 'إنشاء') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation removed - using useConfirm instead -->
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import PaymentFormModal from './PaymentFormModal.vue';
import Modal from '@/Components/Modal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    workOrderId: { type: Number, required: true },
    payments: { type: Array, default: () => [] },
    grandTotal: { type: Number, default: 0 },
    totalPaid: { type: Number, default: 0 },
    badDebt: { type: Number, default: 0 },
    balance: { type: Number, default: 0 },
    readOnly: { type: Boolean, default: false },
    hasInvoice: { type: Boolean, default: false },
    status: { type: String, default: '' },
});

const emit = defineEmits(['refresh']);

const { t, locale } = useI18n();
const { confirm } = useConfirm();

const isRtl = computed(() => locale.value === 'ar');
const searchQuery = ref('');
const showPaymentModal = ref(false);
const editingPayment = ref(null);

const filteredPayments = computed(() => {
    if (!searchQuery.value) return props.payments;
    const query = searchQuery.value.toLowerCase();
    return props.payments.filter(p => 
        p.notes?.toLowerCase().includes(query) ||
        p.reference?.toLowerCase().includes(query)
    );
});

const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US');
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getMethodBadgeClass = (method) => {
    const classes = {
        cash: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        mada: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        visa: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        mastercard: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        transfer: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
        apple_pay: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        stc_pay: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        tabby: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
        tamara: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
        credit: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        other: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[method] || classes.other;
};

const getTypeBadgeClass = (type) => {
    if (type === 'refund') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    } else if (type === 'bad_debt') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    }
    return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    editingPayment.value = null;
};

const onPaymentSaved = () => {
    emit('refresh');
};

// Credit Invoice Form & Action
const showCreditInvoiceModal = ref(false);
const submittingCreditInvoice = ref(false);
const creditInvoiceForm = ref({
    due_date: '',
    notes: '',
});
const todayDate = new Date().toISOString().split('T')[0];

const submitCreditInvoice = () => {
    submittingCreditInvoice.value = true;
    router.post(route('app.work-orders.invoice', props.workOrderId), creditInvoiceForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showCreditInvoiceModal.value = false;
            submittingCreditInvoice.value = false;
            creditInvoiceForm.value = { due_date: '', notes: '' };
            emit('refresh');
        },
        onError: () => {
            submittingCreditInvoice.value = false;
        }
    });
};
</script>
