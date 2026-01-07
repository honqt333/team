<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">مشتريات SMS</h1>
                <p class="text-gray-500 dark:text-gray-400">سجل شراء رصيد الرسائل النصية</p>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <a href="/system/sms/packages" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الباقات</a>
                <a href="/system/sms/balances" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الأرصدة</a>
                <a href="/system/sms/purchases" class="px-4 py-2 border-b-2 border-indigo-600 text-indigo-600 font-medium">المشتريات</a>
                <a href="/system/sms/usage" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">سجل الاستخدام</a>
            </div>
            
            <!-- Filters -->
            <div>
                <select v-model="status" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">كل الحالات</option>
                    <option value="pending">معلق</option>
                    <option value="paid">مدفوع</option>
                    <option value="failed">فشل</option>
                    <option value="refunded">مسترد</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستأجر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الباقة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">طريقة الدفع</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="purchase in purchases.data" :key="purchase.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ purchase.tenant?.trade_name || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ purchase.package?.name_ar || 'مخصص' }}</td>
                            <td class="px-4 py-3 text-indigo-600 font-semibold">{{ purchase.credits?.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ formatCurrency(purchase.amount) }}</td>
                            <td class="px-4 py-3">
                                <span :class="getStatusClass(purchase.status)" class="px-2 py-1 rounded-full text-xs">
                                    {{ getStatusLabel(purchase.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">{{ purchase.payment_gateway || '-' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-sm">{{ formatDate(purchase.created_at) }}</td>
                        </tr>
                        <tr v-if="!purchases.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا توجد مشتريات</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    purchases: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/system/sms/purchases', { status: status.value || undefined }, { preserveState: true });
};

const formatCurrency = (amount) => new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
const formatDate = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';

const getStatusLabel = (status) => ({ pending: 'معلق', paid: 'مدفوع', failed: 'فشل', refunded: 'مسترد' }[status] || status);

const getStatusClass = (status) => ({
    pending: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
    refunded: 'bg-gray-100 text-gray-700',
}[status] || 'bg-gray-100 text-gray-700');
</script>
