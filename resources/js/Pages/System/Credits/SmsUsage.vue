<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">سجل استخدام SMS</h1>
                <p class="text-gray-500 dark:text-gray-400">تتبع جميع الرسائل المرسلة</p>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <a href="/system/sms/packages" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الباقات</a>
                <a href="/system/sms/balances" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الأرصدة</a>
                <a href="/system/sms/purchases" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">المشتريات</a>
                <a href="/system/sms/usage" class="px-4 py-2 border-b-2 border-indigo-600 text-indigo-600 font-medium">سجل الاستخدام</a>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.total_sent?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">إجمالي الرسائل</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.delivered?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">تم التسليم</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">فشل</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_credits_used?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">الرصيد المستخدم</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4">
                <select v-model="status" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">كل الحالات</option>
                    <option value="sent">مرسل</option>
                    <option value="delivered">تم التسليم</option>
                    <option value="failed">فشل</option>
                    <option value="pending">قيد الإرسال</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستأجر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الجوال</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ log.tenant?.trade_name || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ log.phone_number }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">
                                    {{ getTypeLabel(log.message_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ log.credits_used }}</td>
                            <td class="px-4 py-3">
                                <span :class="getStatusClass(log.status)" class="px-2 py-1 rounded-full text-xs">
                                    {{ getStatusLabel(log.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-sm">{{ formatDate(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!logs.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">لا توجد بيانات</td>
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
    logs: Object,
    stats: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/system/sms/usage', { status: status.value || undefined }, { preserveState: true });
};

const formatDate = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';

const getTypeLabel = (type) => ({
    reminder: 'تذكير',
    notification: 'إشعار',
    otp: 'رمز تحقق',
    marketing: 'تسويقي',
    invoice: 'فاتورة',
    appointment: 'موعد',
}[type] || type);

const getStatusLabel = (status) => ({ sent: 'مرسل', delivered: 'تم التسليم', failed: 'فشل', pending: 'قيد الإرسال' }[status] || status);

const getStatusClass = (status) => ({
    sent: 'bg-blue-100 text-blue-700',
    delivered: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
    pending: 'bg-amber-100 text-amber-700',
}[status] || 'bg-gray-100 text-gray-700');
</script>
