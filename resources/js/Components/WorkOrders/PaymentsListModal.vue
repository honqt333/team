<template>
    <Modal :show="show" @close="close">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden max-w-5xl w-full mx-auto">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    {{ $t('payments.title') }}
                </h3>
                <button @click="close" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Summary Strip -->
            <div class="px-6 py-4 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-8 text-sm md:text-base">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('work_orders.total') }}:</span>
                        <span class="font-bold text-gray-900 dark:text-white" dir="ltr">{{ formatPrice(grandTotal) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('work_orders.paid') }}:</span>
                        <span class="font-bold text-gray-900 dark:text-white" dir="ltr">{{ formatPrice(totalPaid) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('work_orders.balance') }}:</span>
                        <span class="font-bold" :class="balance > 0 ? 'text-red-600' : 'text-green-600'" dir="ltr">{{ formatPrice(balance) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Search -->
                     <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                             <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input
                            type="text"
                            v-model="searchQuery"
                            :placeholder="$t('common.search') + '...'"
                            class="pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-shadow"
                        />
                    </div>

                    <!-- Add Payment Button -->
                    <button
                        @click="openAddModal"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg shadow-md flex items-center gap-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="loading"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('payments.add_payment') }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto min-h-[300px] max-h-[60vh]">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                        <tr>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">#</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">{{ $t('payments.form.method') }}</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">{{ $t('payments.form.type') }}</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">{{ $t('payments.form.date') }}</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">{{ $t('payments.form.details') }}</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">{{ $t('payments.form.amount') }}</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400">{{ $t('common.updated_by') }}</th>
                            <th class="py-3 px-4 font-medium text-gray-500 dark:text-gray-400 text-center">{{ $t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr 
                            v-for="(payment, index) in filteredPayments" 
                            :key="payment.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <td class="py-3 px-4 text-gray-500">{{ index + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">
                                {{ $t(`payments.methods.${payment.payment_method}`) }}
                            </td>
                             <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getTypeBadgeClass(payment.type)">
                                    {{ $t(`payments.types.${payment.type}`) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-500" dir="ltr">
                                {{ formatDate(payment.payment_date) }}
                            </td>
                             <td class="py-3 px-4 text-gray-500 truncate max-w-xs" :title="payment.notes">
                                {{ payment.notes || '-' }}
                            </td>
                            <td class="py-3 px-4 font-bold" :class="payment.type === 'refund' ? 'text-red-600' : 'text-gray-900 dark:text-white'" dir="ltr">
                                {{ formatPrice(payment.amount) }}
                            </td>
                            <td class="py-3 px-4">
                                <div v-if="payment.received_by" class="flex items-center gap-2">
                                     <img 
                                        v-if="payment.received_by?.avatar"
                                        :src="payment.received_by.avatar" 
                                        class="w-6 h-6 rounded-full"
                                    />
                                    <div class="text-xs">
                                        <div class="text-gray-900 dark:text-white">{{ payment.received_by?.name }}</div>
                                        <div class="text-gray-400" dir="ltr">{{ formatDateTime(payment.created_at) }}</div>
                                    </div>
                                </div>
                                <span v-else>-</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                 <a
                                    :href="route('work-orders.print.payments', workOrderId) + '?payment=' + payment.id"
                                    target="_blank"
                                    class="text-gray-500 hover:text-indigo-600 transition-colors"
                                    :title="$t('common.print')"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <tr v-if="filteredPayments.length === 0">
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                {{ $t('payments.no_payments') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Payment Modal (Nested) -->
        <PaymentFormModal
            :show="showAddModal"
            :work-order-id="workOrderId"
            :balance="balance"
            :allow-refund="payments.length > 0"
            :total-paid="totalPaid"
            @close="showAddModal = false"
            @saved="onPaymentSaved"
        />
    </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';
import PaymentFormModal from './PaymentFormModal.vue';

const props = defineProps({
    show: Boolean,
    workOrderId: { type: Number, required: true },
    payments: { type: Array, default: () => [] },
    grandTotal: { type: Number, default: 0 },
    totalPaid: { type: Number, default: 0 },
    balance: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'refresh']);

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const searchQuery = ref('');
const showAddModal = ref(false);
const loading = ref(false);

const filteredPayments = computed(() => {
    if (!searchQuery.value) return props.payments;
    const query = searchQuery.value.toLowerCase();
    return props.payments.filter(p => 
        p.notes?.toLowerCase().includes(query) ||
        p.reference?.toLowerCase().includes(query) ||
        t(`payments.methods.${p.payment_method}`).toLowerCase().includes(query)
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

const getTypeBadgeClass = (type) => {
    return type === 'refund' 
        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
        : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
};

const openAddModal = () => {
    showAddModal.value = true;
};

const close = () => {
    emit('close');
};

const onPaymentSaved = () => {
    // We emit refresh to parent (Show.vue) which reloads data
    // Then updated props will flow back down correctly
    emit('refresh');
};
</script>
