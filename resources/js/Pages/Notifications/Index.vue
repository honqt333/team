<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $t('notifications.title') }}
                        </h1>
                        <p v-if="unreadCount > 0" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('notifications.unread_count', { count: unreadCount }) }}
                        </p>
                    </div>
                </div>

                <button v-if="unreadCount > 0" @click="markAllRead"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $t('notifications.mark_all_read') }}
                </button>
            </div>

            <!-- Notifications List -->
            <div class="space-y-2">
                <template v-if="allNotifications.length > 0">
                    <div v-for="notification in allNotifications" :key="notification.id"
                        @click="handleClick(notification)"
                        class="group cursor-pointer bg-white dark:bg-gray-800 rounded-xl border transition-all duration-200 hover:shadow-md"
                        :class="notification.read_at
                            ? 'border-gray-200 dark:border-gray-700'
                            : 'border-indigo-200 dark:border-indigo-800 bg-indigo-50/30 dark:bg-indigo-900/10 shadow-sm'">
                        <div class="flex gap-3 p-4 items-start">
                            <!-- Icon -->
                            <div :class="[
                                'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                                notification.read_at
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500'
                                    : getIconBg(notification.icon)
                            ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        :d="getIconPath(notification.icon)" />
                                </svg>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ notification.title }}
                                    </p>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                            {{ timeAgo(notification.created_at) }}
                                        </span>
                                        <div v-if="!notification.read_at"
                                            class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></div>
                                    </div>
                                </div>
                                <p v-if="notification.body"
                                    class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ notification.body }}
                                </p>
                                <div v-if="notification.actor" class="flex items-center gap-2 mt-2">
                                    <div class="w-5 h-5 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white text-[9px] font-bold">
                                        {{ notification.actor.name?.charAt(0) }}
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ notification.actor.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-6 py-16 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">{{ $t('notifications.empty') }}</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ $t('notifications.empty_description') }}</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links && notifications.links.length > 3" class="mt-6 flex justify-center">
                <nav class="inline-flex items-center gap-1">
                    <template v-for="link in notifications.links" :key="link.label">
                        <a v-if="link.url" :href="link.url"
                            class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                            :class="link.active
                                ? 'bg-indigo-600 text-white font-medium'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-sm text-gray-300 dark:text-gray-600" v-html="link.label" />
                    </template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps({
    notifications: Object,
});

const allNotifications = computed(() => props.notifications?.data || []);
const unreadCount = computed(() => allNotifications.value.filter(n => !n.read_at).length);

function handleClick(notification) {
    if (!notification.read_at) {
        fetch(`/app/notifications/${notification.id}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        }).then(() => {
            notification.read_at = new Date().toISOString();
        });
    }

    if (notification.action_url) {
        window.location.href = notification.action_url;
    }
}

function markAllRead() {
    fetch('/app/notifications/read-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json',
        },
    }).then(() => {
        router.reload({ only: ['notifications'] });
    });
}

function timeAgo(dateStr) {
    const now = new Date();
    const date = new Date(dateStr);
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return t('notifications.just_now');
    if (diffMins < 60) return t('notifications.minutes_ago', { count: diffMins });
    if (diffHours < 24) return t('notifications.hours_ago', { count: diffHours });
    return t('notifications.days_ago', { count: diffDays });
}

function getIconPath(icon) {
    const icons = {
        clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        receipt: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        x: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return icons[icon] || icons.bell;
}

function getIconBg(icon) {
    const colors = {
        clipboard: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        receipt: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        document: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        cash: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
        users: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        check: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        x: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    };
    return colors[icon] || 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400';
}
</script>
