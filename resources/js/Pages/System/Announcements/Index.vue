<template>
    <SystemLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">الإعلانات والإشعارات</h1>
                    <p class="text-gray-500 dark:text-gray-400">إرسال إشعارات لكل المستأجرين أو مجموعة محددة</p>
                </div>
                <a href="/system/announcements/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                    + إنشاء إعلان
                </a>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4">
                <select v-model="status" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">الكل</option>
                    <option value="published">منشور</option>
                    <option value="draft">مسودة</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">العنوان</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة المستهدفة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المشاهدات</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in announcements.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <a :href="`/system/announcements/${item.id}`" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600">{{ item.title }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="getTypeClass(item.type)" class="px-2 py-1 rounded-full text-xs">{{ getTypeLabel(item.type) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">{{ getTargetLabel(item.target) }}</td>
                            <td class="px-4 py-3">
                                <span :class="item.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-1 rounded-full text-xs">
                                    {{ item.is_published ? 'منشور' : 'مسودة' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.reads_count }}</td>
                            <td class="px-4 py-3 text-gray-500 text-sm">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a :href="`/system/announcements/${item.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm">عرض</a>
                                    <button @click="deleteAnnouncement(item)" class="text-red-600 hover:text-red-800 text-sm">حذف</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!announcements.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا توجد إعلانات</td>
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
    announcements: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/system/announcements', { status: status.value || undefined }, { preserveState: true });
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString('ar-SA') : '-';

const getTypeLabel = (type) => ({ info: 'معلومات', warning: 'تحذير', important: 'هام', maintenance: 'صيانة' }[type] || type);
const getTypeClass = (type) => ({
    info: 'bg-blue-100 text-blue-700',
    warning: 'bg-amber-100 text-amber-700',
    important: 'bg-red-100 text-red-700',
    maintenance: 'bg-purple-100 text-purple-700',
}[type] || 'bg-gray-100 text-gray-700');

const getTargetLabel = (target) => ({ all: 'الكل', active: 'النشطين', trial: 'التجريبي', expired: 'المنتهية', specific: 'محدد' }[target] || target);

const deleteAnnouncement = (item) => {
    if (confirm(`هل تريد حذف "${item.title}"؟`)) {
        router.delete(`/system/announcements/${item.id}`);
    }
};
</script>
