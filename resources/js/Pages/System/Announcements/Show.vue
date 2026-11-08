<template>
    <SystemLayout>
        <Head :title="announcement.title" />

        <div class="space-y-6">
            <PageHeader
                :title="announcement.title"
                :subtitle="`${getTypeLabel(announcement.type)} • ${getTargetLabel(announcement.target)}`"
            >
                <template #back>
                    <Link
                        :href="route('system.announcements.index')"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="$t('common.back')"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                </template>
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <span
                        :class="
                            announcement.is_published
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border-gray-200'
                        "
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold border"
                    >
                        {{
                            announcement.is_published
                                ? $t('system.announcements.published')
                                : $t('common.status_draft')
                        }}
                    </span>
                    <button
                        v-if="!announcement.is_published"
                        @click="publish"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md active:scale-95"
                    >
                        {{ $t('system.announcements.publish_now') }}
                    </button>
                    <button
                        v-else
                        @click="unpublish"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition-all active:scale-95"
                    >
                        {{ $t('system.announcements.stop') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.target_count }}</p>
                    <p class="text-sm text-gray-500">{{ $t('system.announcements.recipients') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-blue-600">{{ stats.reads_count }}</p>
                    <p class="text-sm text-gray-500">{{ $t('system.announcements.viewed') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.sent_count }}</p>
                    <p class="text-sm text-gray-500">{{ $t('system.announcements.delivered') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed_count }}</p>
                    <p class="text-sm text-gray-500">{{ $t('system.announcements.failed') }}</p>
                </div>
            </div>

            <!-- Content -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700"
            >
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                    {{ $t('common.content') }}
                </h3>
                <div
                    class="prose dark:prose-invert max-w-none"
                    v-safe-html="sanitizedContent"
                ></div>
                <div
                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex gap-4 text-sm text-gray-500"
                >
                    <span>
                        {{
                            $t('system.announcements.channels_2') +
                            (announcement.channels || ['in_app']).join(', ')
                        }}
                    </span>
                    <span v-if="announcement.expires_at">
                        {{ $t('system.announcements.ends') + formatDate(announcement.expires_at) }}
                    </span>
                </div>
            </div>

            <!-- Send Logs -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700"
            >
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.announcements.delivery_log') }}
                </h3>
                <div v-if="recentLogs.length" class="space-y-2">
                    <div
                        v-for="log in recentLogs"
                        :key="log.id"
                        class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="log.status === 'sent' ? 'text-emerald-500' : 'text-red-500'"
                            >
                                {{ log.status === 'sent' ? '✓' : '✗' }}
                            </span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ log.tenant?.trade_name }}
                            </span>
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                                {{ log.channel }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ formatDateTime(log.created_at) }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-gray-500 text-sm">
                    {{ $t('system.announcements.not_sent_yet') }}
                </p>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DOMPurify from 'dompurify';

const props = defineProps({
    announcement: Object,
    stats: Object,
    recentLogs: Array,
});

const sanitizedContent = computed(() => DOMPurify.sanitize(props.announcement?.content || ''));

const getTypeLabel = (type) =>
    ({
        info: t('system.announcements.type.info'),
        warning: t('system.announcements.type.warning'),
        important: t('system.announcements.type.important'),
        maintenance: t('system.announcements.type.maintenance'),
    })[type] || type;
const getTargetLabel = (target) =>
    ({
        all: t('system.announcements.all_statuses'),
        active: t('system.announcements.target.active'),
        trial: t('system.announcements.target.trial'),
        expired: t('system.announcements.target.expired'),
        specific: t('system.announcements.target.specific'),
    })[target] || target;
const formatDate = (date) => (date ? new Date(date).toLocaleDateString('ar-SA-u-nu-latn') : '-');
const formatDateTime = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const publish = () => router.post(`/system/announcements/${props.announcement.id}/publish`);
const unpublish = () => router.post(`/system/announcements/${props.announcement.id}/unpublish`);
</script>
