<template>
    <SystemLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">سجل النشاط</h1>
                    <p class="text-gray-500 dark:text-gray-400">تتبع جميع إجراءات المسؤولين</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4">
                <select v-model="adminId" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">كل المسؤولين</option>
                    <option v-for="admin in admins" :key="admin.id" :value="admin.id">{{ admin.name }}</option>
                </select>
                <select v-model="action" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">كل الإجراءات</option>
                    <option value="login">تسجيل دخول</option>
                    <option value="create">إنشاء</option>
                    <option value="update">تعديل</option>
                    <option value="delete">حذف</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المسؤول</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراء</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التفاصيل</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">IP</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ log.admin?.name }}</td>
                            <td class="px-4 py-3">
                                <span :class="getActionClass(log.action)" class="px-2 py-1 rounded-full text-xs">{{ log.action_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">{{ log.model_type || '-' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs" dir="ltr">{{ log.ip_address }}</td>
                            <td class="px-4 py-3 text-gray-500 text-sm">{{ formatDate(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!logs.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">لا يوجد سجلات</td>
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
    admins: Array,
    filters: Object,
});

const adminId = ref(props.filters?.admin_id || '');
const action = ref(props.filters?.action || '');

const applyFilters = () => {
    router.get('/system/activity-log', {
        admin_id: adminId.value || undefined,
        action: action.value || undefined,
    }, { preserveState: true });
};

const formatDate = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';

const getActionClass = (action) => ({
    login: 'bg-blue-100 text-blue-700',
    create: 'bg-emerald-100 text-emerald-700',
    update: 'bg-amber-100 text-amber-700',
    delete: 'bg-red-100 text-red-700',
}[action] || 'bg-gray-100 text-gray-700');
</script>
