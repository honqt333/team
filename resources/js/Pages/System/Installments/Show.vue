<template>
    <SystemLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <Link href="/system/installments" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 block">
                    ← العودة للأقساط
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">تفاصيل الأقساط</h1>
            </div>
            
            <!-- Invoice Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</h2>
                        <p class="text-gray-500 dark:text-gray-400">{{ invoice.tenant?.trade_name }}</p>
                    </div>
                    <span :class="getInvoiceStatusClass(invoice.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                        {{ invoice.status === 'paid' ? 'مكتمل' : 'قيد السداد' }}
                    </span>
                </div>
                
                <!-- Progress -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">التقدم</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ summary.paid_count }} / {{ summary.total_count }} أقساط</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div 
                            class="bg-emerald-500 h-3 rounded-full transition-all duration-500"
                            :style="{ width: summary.progress_percent + '%' }"
                        ></div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(summary.paid_amount) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">المدفوع</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-amber-600">{{ formatCurrency(summary.pending_amount) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">المتبقي</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-indigo-600">{{ formatCurrency(invoice.total) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الإجمالي</p>
                    </div>
                </div>
            </div>
            
            <!-- Installments List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">جدول الأقساط</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div 
                        v-for="installment in invoice.installments" 
                        :key="installment.id"
                        class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div class="flex items-center gap-4">
                            <div :class="getInstallmentIconClass(installment.status)" class="w-10 h-10 rounded-full flex items-center justify-center">
                                <svg v-if="installment.status === 'paid'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span v-else class="text-sm font-medium">{{ installment.installment_number }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">القسط {{ installment.installment_number }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">استحقاق: {{ formatDate(installment.due_date) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-left">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(installment.amount) }}</p>
                                <span :class="getStatusClass(installment.status)" class="text-xs">
                                    {{ getStatusLabel(installment.status) }}
                                </span>
                            </div>
                            <button 
                                v-if="installment.status !== 'paid'"
                                @click="openMarkPaidModal(installment)" 
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium"
                            >
                                تأكيد الدفع
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mark Paid Modal -->
        <Transition name="modal">
            <div v-if="showMarkPaidModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showMarkPaidModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تأكيد دفع القسط {{ selectedInstallment?.installment_number }}</h3>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 mb-4 text-center">
                            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ formatCurrency(selectedInstallment?.amount) }}</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم المرجع</label>
                                <input type="text" v-model="markPaidForm.reference" placeholder="رقم الحوالة" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button @click="markAsPaid" :disabled="markingPaid" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50">
                                تأكيد
                            </button>
                            <button @click="showMarkPaidModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    invoice: Object,
    summary: Object,
});

const showMarkPaidModal = ref(false);
const selectedInstallment = ref(null);
const markingPaid = ref(false);
const markPaidForm = ref({ reference: '', gateway: 'manual' });

const formatCurrency = (amount) => new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('ar-SA') : '-';

const getInvoiceStatusClass = (status) => status === 'paid' 
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';

const getInstallmentIconClass = (status) => ({
    paid: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    overdue: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    pending: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
}[status] || 'bg-gray-100 text-gray-600');

const getStatusClass = (status) => ({ pending: 'text-amber-600', overdue: 'text-red-600', paid: 'text-emerald-600' }[status] || 'text-gray-600');
const getStatusLabel = (status) => ({ pending: 'معلق', overdue: 'متأخر', paid: 'مدفوع' }[status] || status);

const openMarkPaidModal = (installment) => {
    selectedInstallment.value = installment;
    markPaidForm.value = { reference: '', gateway: 'manual' };
    showMarkPaidModal.value = true;
};

const markAsPaid = () => {
    markingPaid.value = true;
    router.post(`/system/installments/${selectedInstallment.value.id}/mark-paid`, markPaidForm.value, {
        onSuccess: () => showMarkPaidModal.value = false,
        onFinish: () => markingPaid.value = false,
    });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
