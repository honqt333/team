<template>
    <SystemLayout>
        <Head :title="locale === 'ar' ? 'كل الإشعارات' : 'All notifications'" />

        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('system.notifications.all_title') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.notifications.all_subtitle') }}
                    </p>
                </div>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ $t('notifications.mark_all_read') }}
                </button>
            </div>

            <div
                class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800"
            >
                <a
                    v-for="n in notifications.data || []"
                    :key="n.id"
                    :href="n.action_url || '#'"
                    @click="markRead(n.id)"
                    class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    :class="{ 'bg-indigo-50/40 dark:bg-indigo-900/10': !n.read_at }"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                            :class="
                                n.read_at
                                    ? 'bg-gray-100 text-gray-400 dark:bg-gray-700'
                                    : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'
                            "
                        >
                            <svg
                                class="h-4 w-4"
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
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ n.title }}
                            </p>
                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                                {{ n.body }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ formatDateTime(n.created_at) }}
                            </p>
                        </div>
                        <span
                            v-if="!n.read_at"
                            class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-500"
                            aria-label="Unread"
                        ></span>
                    </div>
                </a>

                <div
                    v-if="!(notifications.data || []).length"
                    class="p-12 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                    {{ $t('notifications.empty') }}
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links" class="flex items-center justify-center gap-2">
                <template v-for="link in notifications.links" :key="link.label">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-900"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
});

const locale = computed(() => document.documentElement.lang || 'ar');

const unreadCount = computed(
    () => (props.notifications.data || []).filter((n) => !n.read_at).length
);

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function formatDateTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function markRead(id) {
    try {
        await fetch(`/system/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });
        // Optimistic update + reload to refresh the list
        router.reload({ only: ['notifications'] });
    } catch (_) {
        // ignore
    }
}

async function markAllRead() {
    try {
        await fetch('/system/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });
        router.reload({ only: ['notifications'] });
    } catch (_) {
        // ignore
    }
}
</script>
