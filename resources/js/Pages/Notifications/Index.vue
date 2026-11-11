<template>
    <AppLayout>
        <Head :title="$t('notifications.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('notifications.title')"
                :subtitle="$t('system.notifications.all_subtitle')"
                :total-count="unreadCount > 0 ? toEnglish(unreadCount) : null"
                :count-label="$t('notifications.unread_count', { count: toEnglish(unreadCount) })"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="notifications_view_mode"
                            />
                        </div>

                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            @click="markAllRead"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span>{{ $t('notifications.mark_all_read') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Empty State -->
            <AppEmptyState
                v-if="allNotifications.length === 0"
                :title="$t('notifications.empty')"
                :description="$t('notifications.empty_description')"
            />

            <!-- Content Views -->
            <template v-else>
                <!-- Table View -->
                <DataTable
                    v-if="viewMode === 'list'"
                    :items="notifications"
                    :columns="columns"
                    @rowClick="handleClick"
                >
                    <template #cell-title="{ item }">
                        <div class="flex items-start gap-3">
                            <div
                                :class="[
                                    'shrink-0 w-9 h-9 rounded-xl flex items-center justify-center',
                                    item.read_at
                                        ? 'bg-surface-secondary text-content-muted'
                                        : getIconBg(item.icon),
                                ]"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getIconPath(item.icon)"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'text-sm',
                                            item.read_at
                                                ? 'font-medium text-content-primary'
                                                : 'font-bold text-content-primary',
                                        ]"
                                    >
                                        {{ item.title }}
                                    </span>
                                    <span
                                        v-if="!item.read_at"
                                        class="w-2 h-2 rounded-full bg-action-primary shrink-0"
                                    ></span>
                                </div>
                                <p
                                    v-if="item.body"
                                    class="text-xs text-content-muted mt-0.5 line-clamp-2"
                                >
                                    {{ item.body }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <template #cell-actor="{ item }">
                        <div v-if="item.actor" class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-full bg-action-primary/10 text-action-primary flex items-center justify-center text-[10px] font-bold shrink-0"
                            >
                                {{ item.actor.name?.charAt(0) }}
                            </div>
                            <span class="text-xs text-content-secondary">
                                {{ item.actor.name }}
                            </span>
                        </div>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <template #cell-created_at="{ item }">
                        <span class="text-xs text-content-muted whitespace-nowrap">
                            {{ timeAgo(item.created_at) }}
                        </span>
                    </template>
                </DataTable>

                <!-- Card / Feed View -->
                <div v-else class="space-y-3">
                    <div
                        v-for="notification in allNotifications"
                        :key="notification.id"
                        @click="handleClick(notification)"
                        class="group cursor-pointer bg-surface-primary rounded-2xl border transition-all duration-200 hover:shadow-md shadow-2xs"
                        :class="
                            notification.read_at
                                ? 'border-border-subtle'
                                : 'border-action-primary/40 bg-action-primary/5'
                        "
                    >
                        <div class="flex gap-3 p-4 items-start">
                            <!-- Icon -->
                            <div
                                :class="[
                                    'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                                    notification.read_at
                                        ? 'bg-surface-secondary text-content-muted'
                                        : getIconBg(notification.icon),
                                ]"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getIconPath(notification.icon)"
                                    />
                                </svg>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-bold text-content-primary">
                                        {{ notification.title }}
                                    </p>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs text-content-muted whitespace-nowrap">
                                            {{ timeAgo(notification.created_at) }}
                                        </span>
                                        <div
                                            v-if="!notification.read_at"
                                            class="w-2 h-2 rounded-full bg-action-primary shrink-0"
                                        ></div>
                                    </div>
                                </div>
                                <p
                                    v-if="notification.body"
                                    class="text-sm text-content-secondary mt-1"
                                >
                                    {{ notification.body }}
                                </p>
                                <div v-if="notification.actor" class="flex items-center gap-2 mt-2">
                                    <div
                                        class="w-5 h-5 rounded-full bg-action-primary/10 text-action-primary flex items-center justify-center text-[9px] font-bold"
                                    >
                                        {{ notification.actor.name?.charAt(0) }}
                                    </div>
                                    <span class="text-xs text-content-muted">
                                        {{ notification.actor.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <AppPagination
                        v-if="notifications.links && notifications.links.length > 3"
                        :links="notifications.links"
                        :from="notifications.from"
                        :to="notifications.to"
                        :total="notifications.total"
                    />
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import { openNotification } from '@/Utils/notifications';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    notifications: Object,
});

const viewMode = ref('grid');

const columns = computed(() => [
    { key: 'title', label: t('common.details') },
    { key: 'actor', label: t('common.user') },
    { key: 'created_at', label: t('common.date') },
]);

const allNotifications = computed(() => props.notifications?.data || []);
const unreadCount = computed(() => allNotifications.value.filter((n) => !n.read_at).length);

function handleClick(notification) {
    // The mark as read is awaited BEFORE the navigation: the browser can
    // cancel a request that is still in flight when the page is left,
    // and the notification then stays unread in the badge.
    openNotification(notification, {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,
        navigate: (url) => {
            window.location.href = url;
        },
    });
}

async function markAllRead() {
    try {
        await fetch('/app/notifications/read-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
            },
        });
    } catch (error) {
        console.error('Could not mark the notifications as read:', error);
    }

    router.reload({ only: ['notifications'] });
}

function timeAgo(dateStr) {
    const now = new Date();
    const date = new Date(dateStr);
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return t('notifications.just_now');
    if (diffMins < 60) return t('notifications.minutes_ago', { count: toEnglish(diffMins) });
    if (diffHours < 24) return t('notifications.hours_ago', { count: toEnglish(diffHours) });
    return t('notifications.days_ago', { count: toEnglish(diffDays) });
}

function getIconPath(icon) {
    const icons = {
        clipboard:
            'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        receipt:
            'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        document:
            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
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
        clipboard: 'bg-info-soft text-info',
        receipt: 'bg-success-soft text-success',
        document: 'bg-warning-soft text-warning',
        cash: 'bg-success-soft text-success',
        users: 'bg-action-primary/10 text-action-primary',
        check: 'bg-success-soft text-success',
        x: 'bg-danger-soft text-danger',
    };
    return colors[icon] || 'bg-action-primary/10 text-action-primary';
}
</script>
