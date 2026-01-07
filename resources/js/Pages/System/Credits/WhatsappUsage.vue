<template>
    <SystemLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">سجل استخدام WhatsApp</h1>
                <p class="text-gray-500 dark:text-gray-400">تتبع جميع رسائل واتساب المرسلة</p>
            </div>
            
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <a href="/system/whatsapp/packages" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الباقات</a>
                <a href="/system/whatsapp/balances" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الأرصدة</a>
                <a href="/system/whatsapp/usage" class="px-4 py-2 border-b-2 border-emerald-600 text-emerald-600 font-medium">سجل الاستخدام</a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.total_sent?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">إجمالي الرسائل</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-blue-600">{{ stats.delivered?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">تم التسليم</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.read?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">مقروء</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">فشل</p>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستأجر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الجوال</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">القالب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ log.tenant?.trade_name || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ log.phone_number }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">{{ log.template_name || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ log.credits_used }}</td>
                            <td class="px-4 py-3">
                                <span :class="getStatusClass(log.status)" class="px-2 py-1 rounded-full text-xs">{{ getStatusLabel(log.status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-sm">{{ formatDate(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!logs.data?.length"><td colspan="6" class="px-4 py-8 text-center text-gray-500">لا توجد بيانات</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({ logs: Object, stats: Object, filters: Object });

const formatDate = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';
const getStatusLabel = (s) => ({ sent: 'مرسل', delivered: 'تم التسليم', read: 'مقروء', failed: 'فشل', pending: 'قيد الإرسال' }[s] || s);
const getStatusClass = (s) => ({ sent: 'bg-blue-100 text-blue-700', delivered: 'bg-emerald-100 text-emerald-700', read: 'bg-indigo-100 text-indigo-700', failed: 'bg-red-100 text-red-700', pending: 'bg-amber-100 text-amber-700' }[s] || 'bg-gray-100 text-gray-700');
</script>
