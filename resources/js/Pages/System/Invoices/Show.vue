<template>
    <SystemLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/system/invoices" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 block">
                        ← العودة للفواتير
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</h1>
                </div>
                <div class="flex gap-2">
                    <a :href="`/system/invoices/${invoice.id}/download`" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium">
                        تحميل PDF
                    </a>
                    <button @click="sendInvoice" :disabled="sending" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        {{ sending ? 'جاري الإرسال...' : 'إرسال بالإيميل' }}
                    </button>
                </div>
            </div>
            
            <!-- Invoice Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <!-- Status Badge -->
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <span :class="getStatusClass(invoice.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                            {{ getStatusLabel(invoice.status) }}
                        </span>
                    </div>
                    <div class="text-left">
                        <p class="text-sm text-gray-500 dark:text-gray-400">تاريخ الإصدار</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(invoice.created_at) }}</p>
                    </div>
                </div>
                
                <!-- Tenant Info -->
                <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">المستأجر</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ invoice.tenant?.trade_name }}</p>
                        <p class="text-sm text-gray-500">{{ invoice.tenant?.email }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">تاريخ الاستحقاق</p>
                        <p class="font-semibold" :class="isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-white'">
                            {{ formatDate(invoice.due_date) }}
                        </p>
                    </div>
                </div>
                
                <!-- Items -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">تفاصيل الفاتورة</h3>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex justify-between mb-2">
                            <span>باقة {{ invoice.subscription?.plan?.name_ar }}</span>
                            <span class="font-medium">{{ formatCurrency(invoice.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>{{ invoice.subscription?.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Totals -->
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>المجموع الفرعي</span>
                        <span>{{ formatCurrency(invoice.subtotal) }}</span>
                    </div>
                    <div v-if="invoice.discount > 0" class="flex justify-between text-emerald-600">
                        <span>الخصم</span>
                        <span>- {{ formatCurrency(invoice.discount) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>ضريبة القيمة المضافة ({{ invoice.vat_rate }}%)</span>
                        <span>{{ formatCurrency(invoice.vat_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span>الإجمالي</span>
                        <span class="text-indigo-600">{{ formatCurrency(invoice.total) }}</span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div v-if="invoice.status === 'pending'" class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button @click="showMarkPaidModal = true" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                        تأكيد الدفع يدوياً
                    </button>
                    <button @click="cancelInvoice" class="px-4 py-2 border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg font-medium">
                        إلغاء الفاتورة
                    </button>
                </div>
                
                <!-- Payment Info if Paid -->
                <div v-if="invoice.status === 'paid'" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 mt-4">
                    <p class="text-emerald-700 dark:text-emerald-300 font-medium mb-2">✓ تم الدفع</p>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400 space-y-1">
                        <p>تاريخ الدفع: {{ formatDate(invoice.paid_at) }}</p>
                        <p v-if="invoice.payment_gateway">البوابة: {{ invoice.payment_gateway }}</p>
                        <p v-if="invoice.payment_reference">المرجع: {{ invoice.payment_reference }}</p>
                    </div>
                </div>
            </div>
            
            <!-- PDF Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">معاينة الفاتورة</h3>
                <iframe :src="pdfUrl" class="w-full h-[600px] rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
            </div>
        </div>
        
        <!-- Mark Paid Modal -->
        <Transition name="modal">
            <div v-if="showMarkPaidModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showMarkPaidModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تأكيد الدفع يدوياً</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم المرجع</label>
                                <input type="text" v-model="markPaidForm.reference" placeholder="رقم الحوالة أو الإيصال" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملاحظات</label>
                                <textarea v-model="markPaidForm.notes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button @click="markAsPaid" :disabled="markingPaid" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50">
                                {{ markingPaid ? 'جاري التأكيد...' : 'تأكيد الدفع' }}
                            </button>
                            <button @click="showMarkPaidModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium">
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
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    invoice: Object,
    pdfUrl: String,
});

const sending = ref(false);
const showMarkPaidModal = ref(false);
const markingPaid = ref(false);
const markPaidForm = ref({ reference: '', notes: '' });

const isOverdue = computed(() => {
    return props.invoice.status === 'pending' && new Date(props.invoice.due_date) < new Date();
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = { pending: 'معلقة', paid: 'مدفوعة', cancelled: 'ملغية' };
    return labels[status] || status;
};

const sendInvoice = () => {
    sending.value = true;
    router.post(`/system/invoices/${props.invoice.id}/send`, {}, {
        onFinish: () => sending.value = false,
    });
};

const markAsPaid = () => {
    markingPaid.value = true;
    router.post(`/system/invoices/${props.invoice.id}/mark-paid`, markPaidForm.value, {
        onSuccess: () => showMarkPaidModal.value = false,
        onFinish: () => markingPaid.value = false,
    });
};

const cancelInvoice = () => {
    if (confirm('هل أنت متأكد من إلغاء هذه الفاتورة؟')) {
        router.post(`/system/invoices/${props.invoice.id}/cancel`);
    }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>
