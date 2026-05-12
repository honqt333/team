<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">الأقساط</h1>
                    <p class="text-gray-500 dark:text-gray-400">متابعة أقساط الاشتراكات</p>
                </div>
                <button @click="updateOverdue" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">
                    تحديث المتأخرة
                </button>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي الأقساط</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-amber-600">{{ stats.pending }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">معلقة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-red-600">{{ stats.overdue }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">متأخرة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.paid }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">مدفوعة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-indigo-600">{{ formatCurrency(stats.pending_amount) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">مستحق</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-center">
                <input 
                    type="text" 
                    v-model="search" 
                    placeholder="بحث باسم المستأجر..."
                    class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                    @keyup.enter="applyFilters"
                />
                <select v-model="status" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">كل الحالات</option>
                    <option value="pending">معلقة</option>
                    <option value="overdue">متأخرة</option>
                    <option value="paid">مدفوعة</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المستأجر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الباقة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">القسط</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الاستحقاق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="installment in installments.data" :key="installment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <span class="text-gray-900 dark:text-white">{{ installment.invoice?.tenant?.trade_name || '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">{{ installment.invoice?.subscription?.plan?.name_ar || '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-gray-900 dark:text-white">{{ installment.installment_number }} / {{ installment.invoice?.installment_count }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(installment.amount) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="isOverdue(installment) ? 'text-red-600 font-medium' : 'text-gray-500 dark:text-gray-400'">
                                    {{ formatDate(installment.due_date) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="getStatusClass(installment.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(installment.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button 
                                        v-if="installment.status !== 'paid'"
                                        @click="openMarkPaidModal(installment)" 
                                        class="text-emerald-600 hover:text-emerald-800 text-sm"
                                    >
                                        تأكيد الدفع
                                    </button>
                                    <a :href="`/system/installments/invoice/${installment.invoice?.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                        التفاصيل
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!installments.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا توجد أقساط
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Mark Paid Modal -->
        <Transition name="modal">
            <div v-if="showMarkPaidModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showMarkPaidModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تأكيد دفع القسط</h3>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">القسط {{ selectedInstallment?.installment_number }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(selectedInstallment?.amount) }}</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم المرجع</label>
                                <input type="text" v-model="markPaidForm.reference" placeholder="رقم الحوالة أو الإيصال" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">طريقة الدفع</label>
                                <select v-model="markPaidForm.gateway" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="manual">يدوي</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                    <option value="cash">نقدي</option>
                                </select>
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    installments: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const showMarkPaidModal = ref(false);
const selectedInstallment = ref(null);
const markingPaid = ref(false);
const markPaidForm = ref({ reference: '', gateway: 'manual' });

const applyFilters = () => {
    router.get('/system/installments', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const isOverdue = (installment) => {
    return installment.status === 'overdue' || (installment.status === 'pending' && new Date(installment.due_date) < new Date());
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        overdue: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = { pending: 'معلق', overdue: 'متأخر', paid: 'مدفوع', cancelled: 'ملغي' };
    return labels[status] || status;
};

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

const updateOverdue = () => {
    router.post('/system/installments/update-overdue');
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
