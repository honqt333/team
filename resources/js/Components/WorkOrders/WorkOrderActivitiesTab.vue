<template>
    <div class="space-y-6">
        <div v-if="activities?.length" class="relative">
            <!-- Timeline Line -->
            <div class="absolute start-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            <div class="space-y-8 relative">
                <div v-for="activity in activities" :key="activity.id" class="flex gap-4">
                    <!-- Icon/Marker -->
                    <div class="w-16 flex justify-center flex-shrink-0 relative">
                        <div
                            class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 border-indigo-500 flex items-center justify-center z-10 shadow-sm">
                            <span v-if="activity.action === 'created'">🆕</span>
                            <span v-else-if="activity.action === 'status_changed'">🔄</span>
                            <span v-else-if="activity.action === 'item_added'">🔧</span>
                            <span v-else-if="activity.action === 'item_updated'">📝</span>
                            <span v-else-if="activity.action === 'item_deleted'">🗑️</span>
                            <span v-else-if="activity.action === 'payment_added'">💰</span>
                            <span v-else-if="activity.action === 'condition_updated'">🚗</span>
                            <span v-else-if="activity.action === 'photos_uploaded'">📸</span>
                            <span v-else-if="activity.action === 'attachments_uploaded'">📎</span>
                            <span v-else-if="activity.action.includes('part')">🔩</span>
                            <span v-else-if="activity.action.includes('technician')">👷</span>
                            <span v-else>📝</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div
                        class="flex-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ getActivityDescription(activity) }}
                            </h4>
                            <span class="text-xs text-gray-400 font-medium">{{
                                formatDateTime(activity.created_at)
                                }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold">
                                {{ activity.user?.name?.charAt(0) }}
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ activity.user?.name || $t('common.system') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-12">
            <div
                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <span class="text-2xl">📋</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.activities.no_activities') }}</p>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useFormatters } from '@/Composables/useFormatters';

defineProps({
    activities: { type: Array, default: () => [] },
});

const { t, te } = useI18n();
const { formatDateTime } = useFormatters();

function getActivityDescription(activity) {
    const key = `work_orders.activities.actions.${activity.action}`;
    if (!te(key)) {
        return activity.description;
    }
    const translation = t(key);
    if (translation.includes(':') || translation.includes('{')) {
        return activity.description;
    }
    return translation;
}
</script>
