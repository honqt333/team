<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">فواتير الاشتراكات</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة ومتابعة فواتير المستأجرين</p>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي الفواتير</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-amber-600">{{ stats.pending }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">معلقة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.paid }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">مدفوعة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-red-600">{{ stats.overdue }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">متأخرة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-indigo-600">{{ formatCurrency(stats.total_revenue) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">الإيرادات</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-center">
                <input 
                    type="text" 
                    v-model="search" 
                    placeholder="بحث برقم الفاتورة أو اسم المستأجر..."
                    class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                    @keyup.enter="applyFilters"
                />
                <select v-model="status" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">كل الحالات</option>
                    <option value="pending">معلقة</option>
                    <option value="paid">مدفوعة</option>
                    <option value="cancelled">ملغية</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">رقم الفاتورة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المستأجر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الاستحقاق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm text-gray-900 dark:text-white">{{ invoice.invoice_number }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-gray-900 dark:text-white">{{ invoice.tenant?.trade_name || '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(invoice.total) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="isOverdue(invoice) ? 'text-red-600' : 'text-gray-500 dark:text-gray-400'">
                                    {{ formatDate(invoice.due_date) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="getStatusClass(invoice.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(invoice.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a :href="`/system/invoices/${invoice.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm">عرض</a>
                                    <a :href="`/system/invoices/${invoice.id}/download`" class="text-gray-600 hover:text-gray-800 text-sm">PDF</a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا توجد فواتير
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="invoices.last_page > 1" class="flex justify-center gap-2">
                <Link 
                    v-for="page in invoices.last_page" 
                    :key="page"
                    :href="`/system/invoices?page=${page}`"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === invoices.current_page
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                    ]"
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/system/invoices', {
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

const isOverdue = (invoice) => {
    return invoice.status === 'pending' && new Date(invoice.due_date) < new Date();
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'معلقة',
        paid: 'مدفوعة',
        failed: 'فشلت',
        cancelled: 'ملغية',
        refunded: 'مستردة',
    };
    return labels[status] || status;
};
</script>
