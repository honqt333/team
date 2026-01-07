<template>
    <SystemLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/system/announcements" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ announcement.title }}</h1>
                        <p class="text-gray-500">{{ getTypeLabel(announcement.type) }} • {{ getTargetLabel(announcement.target) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="announcement.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-3 py-1 rounded-full text-sm">
                        {{ announcement.is_published ? 'منشور' : 'مسودة' }}
                    </span>
                    <button v-if="!announcement.is_published" @click="publish" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm">
                        🚀 نشر الآن
                    </button>
                    <button v-else @click="unpublish" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm">
                        إيقاف
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.target_count }}</p>
                    <p class="text-sm text-gray-500">المستهدفين</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-blue-600">{{ stats.reads_count }}</p>
                    <p class="text-sm text-gray-500">شاهدوا</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.sent_count }}</p>
                    <p class="text-sm text-gray-500">تم الإرسال</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed_count }}</p>
                    <p class="text-sm text-gray-500">فشل</p>
                </div>
            </div>
            
            <!-- Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">المحتوى</h3>
                <div class="prose dark:prose-invert max-w-none" v-html="announcement.content"></div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex gap-4 text-sm text-gray-500">
                    <span>القنوات: {{ (announcement.channels || ['in_app']).join(', ') }}</span>
                    <span v-if="announcement.expires_at">ينتهي: {{ formatDate(announcement.expires_at) }}</span>
                </div>
            </div>
            
            <!-- Send Logs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">سجل الإرسال</h3>
                <div v-if="recentLogs.length" class="space-y-2">
                    <div v-for="log in recentLogs" :key="log.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex items-center gap-3">
                            <span :class="log.status === 'sent' ? 'text-emerald-500' : 'text-red-500'">{{ log.status === 'sent' ? '✓' : '✗' }}</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ log.tenant?.trade_name }}</span>
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ log.channel }}</span>
                        </div>
                        <span class="text-xs text-gray-500">{{ formatDateTime(log.created_at) }}</span>
                    </div>
                </div>
                <p v-else class="text-gray-500 text-sm">لم يتم الإرسال بعد</p>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    announcement: Object,
    stats: Object,
    recentLogs: Array,
});

const getTypeLabel = (type) => ({ info: 'معلومات', warning: 'تحذير', important: 'هام', maintenance: 'صيانة' }[type] || type);
const getTargetLabel = (target) => ({ all: 'الكل', active: 'النشطين', trial: 'التجريبي', expired: 'المنتهية', specific: 'محدد' }[target] || target);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('ar-SA') : '-';
const formatDateTime = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';

const publish = () => router.post(`/system/announcements/${props.announcement.id}/publish`);
const unpublish = () => router.post(`/system/announcements/${props.announcement.id}/unpublish`);
</script>
