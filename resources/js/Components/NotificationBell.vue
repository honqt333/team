<template>
    <div class="relative" ref="bellRef">
        <!-- Bell Button -->
        <button @click="toggleDropdown"
            class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :title="$t('notifications.title')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <!-- Badge -->
            <span v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold px-1 ring-2 ring-white dark:ring-gray-800 animate-pulse">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition name="dropdown">
            <div v-if="isOpen" :class="[
                'absolute top-full mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden',
                isRtl ? 'left-0' : 'right-0'
            ]">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $t('notifications.title') }}
                    </h3>
                    <button v-if="unreadCount > 0" @click="markAllRead"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium transition-colors">
                        {{ $t('notifications.mark_all_read') }}
                    </button>
                </div>

                <!-- Notification List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/50">
                    <template v-if="notifications.length > 0">
                        <button v-for="notification in notifications" :key="notification.id"
                            @click="handleClick(notification)"
                            class="w-full text-start px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex gap-3 items-start"
                            :class="{ 'bg-indigo-50/50 dark:bg-indigo-900/10': !notification.read_at }">
                            <!-- Icon -->
                            <div :class="[
                                'flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center mt-0.5',
                                notification.read_at
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500'
                                    : getIconBg(notification.icon)
                            ]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        :d="getIconPath(notification.icon)" />
                                </svg>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ notification.title }}
                                </p>
                                <p v-if="notification.body" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                    {{ notification.body }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ timeAgo(notification.created_at) }}
                                </p>
                            </div>

                            <!-- Unread dot -->
                            <div v-if="!notification.read_at"
                                class="flex-shrink-0 w-2 h-2 rounded-full bg-indigo-500 mt-2"></div>
                        </button>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="px-4 py-10 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('notifications.empty') }}</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 dark:border-gray-700">
                    <a href="/app/notifications"
                        class="block w-full px-4 py-2.5 text-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        @click="isOpen = false">
                        {{ $t('notifications.view_all') }}
                    </a>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';

const { t, locale } = useI18n();
const page = usePage();

const isOpen = ref(false);
const bellRef = ref(null);
const notifications = ref([]);
const unreadCount = ref(0);
let pollingInterval = null;

const isRtl = computed(() => locale.value === 'ar');

// Initialize from shared data
onMounted(() => {
    unreadCount.value = page.props.auth?.unread_notifications_count || 0;
    startPolling();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    stopPolling();
    document.removeEventListener('click', handleClickOutside);
});

function handleClickOutside(event) {
    if (bellRef.value && !bellRef.value.contains(event.target)) {
        isOpen.value = false;
    }
}

function toggleDropdown() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
}

async function fetchNotifications() {
    try {
        const response = await fetch('/app/api/notifications');
        const data = await response.json();
        notifications.value = data.notifications;
    } catch (e) {
        console.error('Failed to fetch notifications:', e);
    }
}

async function fetchUnreadCount() {
    try {
        const response = await fetch('/app/api/notifications/unread-count');
        const data = await response.json();
        unreadCount.value = data.count;
    } catch (e) {
        // Silent fail for polling
    }
}

function startPolling() {
    pollingInterval = setInterval(fetchUnreadCount, 30000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

async function handleClick(notification) {
    if (!notification.read_at) {
        try {
            const endpoint = notification.is_system 
                ? `/app/announcements/${notification.id}/dismiss`
                : `/app/notifications/${notification.id}/read`;

            await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            });
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e) {
            console.error('Failed to mark notification as read:', e);
        }
    }
    
    if (notification.action_url) {
        isOpen.value = false;
        window.location.href = notification.action_url;
    }
}

async function markAllRead() {
    try {
        await fetch('/app/notifications/read-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });
        notifications.value.forEach(n => n.read_at = new Date().toISOString());
        unreadCount.value = 0;
    } catch (e) {
        console.error('Failed to mark all as read:', e);
    }
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

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
