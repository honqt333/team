<template>
    <AppLayout>
        <div class="space-y-6 pb-12">
            <!-- Header Section -->
            <PageHeader
                :title="$t('feedback.title')"
                :subtitle="$t('feedback.subtitle')"
                gradientFrom="from-indigo-600"
                gradientTo="to-purple-700"
                glowFrom="from-indigo-500"
                badgeBg="bg-indigo-50/50 dark:bg-indigo-900/30"
                badgeText="text-indigo-600 dark:text-indigo-400"
                badgeBorder="border-indigo-100/50 dark:border-indigo-800/30"
                badgeDot="bg-indigo-500"
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
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <PrimaryButton
                        type="button"
                        @click="showCreateModal = true"
                        class="w-full sm:w-auto"
                    >
                        <template #iconLeft>
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </template>
                        {{ $t('feedback.add_feedback') }}
                    </PrimaryButton>
                </template>
            </PageHeader>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-border shadow-sm"
                >
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ $t('feedback.stats.total') }}
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-border shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                            {{ $t('feedback.stats.pending') }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                        {{ stats.pending }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-border shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-sky-600 dark:text-sky-400">
                            {{ $t('feedback.stats.in_progress') }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-sky-600 dark:text-sky-400">
                        {{ stats.in_progress }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-border shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $t('feedback.stats.resolved') }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ stats.resolved }}
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div
                class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-border shadow-sm space-y-3"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input
                            type="text"
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            :placeholder="$t('feedback.filters.search_placeholder')"
                            class="input-field text-xs pe-9 ps-3 py-2.5 focus:border-action-primary focus:ring-action-primary"
                        />
                        <div
                            class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none text-gray-400"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Type Filter with SearchableSelect -->
                    <div>
                        <SearchableSelect
                            v-model="selectedType"
                            :options="typeFilterOptions"
                            :placeholder="$t('feedback.filters.all_types')"
                            :search-placeholder="$t('feedback.filters.all_types')"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Status Filter with SearchableSelect -->
                    <div>
                        <SearchableSelect
                            v-model="selectedStatus"
                            :options="statusFilterOptions"
                            :placeholder="$t('feedback.filters.all_statuses')"
                            :search-placeholder="$t('feedback.filters.all_statuses')"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- Module Filter with SearchableSelect -->
                    <div>
                        <SearchableSelect
                            v-model="selectedModule"
                            :options="moduleFilterOptions"
                            :placeholder="$t('feedback.filters.all_modules')"
                            :search-placeholder="$t('feedback.filters.all_modules')"
                            compact
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <div
                    v-if="hasActiveFilters"
                    class="flex justify-end pt-2 border-t border-border/50"
                >
                    <button
                        type="button"
                        @click="clearFilters"
                        class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium flex items-center gap-1"
                    >
                        <span>{{ $t('feedback.filters.clear') }}</span>
                    </button>
                </div>
            </div>

            <!-- Feedbacks List -->
            <div
                v-if="feedbacks.data && feedbacks.data.length > 0"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
            >
                <div
                    v-for="item in feedbacks.data"
                    :key="item.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-border/80 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between overflow-hidden"
                >
                    <!-- Card Top Header -->
                    <div class="p-5 space-y-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <!-- Type Badge -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold',
                                    getTypeBadgeClass(item.type),
                                ]"
                            >
                                <span>{{ getTypeIcon(item.type) }}</span>
                                <span>{{ getTypeName(item.type) }}</span>
                            </span>

                            <!-- Status Badge -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold',
                                    getStatusBadgeClass(item.status),
                                ]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="getStatusDotClass(item.status)"
                                ></span>
                                <span>{{ getStatusName(item.status) }}</span>
                            </span>
                        </div>

                        <!-- Module & Date Info -->
                        <div
                            class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
                        >
                            <span
                                class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700/50 px-2 py-0.5 rounded-md font-medium text-gray-700 dark:text-gray-300"
                            >
                                🏢 {{ getModuleName(item.module) }}
                            </span>
                            <span>{{ formatDate(item.created_at) }}</span>
                        </div>

                        <!-- Comments Text -->
                        <div
                            class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line bg-gray-50/50 dark:bg-gray-900/30 p-3 rounded-xl border border-border/50"
                        >
                            {{ item.comments || $t('feedback.card.no_comments') }}
                        </div>

                        <!-- Video Screen Recording Preview Button -->
                        <div v-if="item.video_url" class="pt-1">
                            <PrimaryButton
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="w-full text-sky-700 dark:text-sky-300 bg-sky-50/50 dark:bg-sky-950/20 hover:bg-sky-100 dark:hover:bg-sky-900/30"
                                @click="
                                    openVideoModal(
                                        item.video_url,
                                        $t('feedback.card.watch_recording')
                                    )
                                "
                            >
                                <template #iconLeft>
                                    <svg
                                        class="w-4 h-4 text-sky-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </template>
                                <span>{{ $t('feedback.card.watch_recording') }}</span>
                                <span
                                    v-if="item.video_duration_seconds"
                                    class="text-[11px] opacity-75"
                                >
                                    ({{ formatTime(item.video_duration_seconds) }})
                                </span>
                            </PrimaryButton>
                        </div>

                        <!-- Admin Access Badge (if permitted) -->
                        <div
                            v-if="item.allow_admin_access"
                            class="flex items-center gap-1.5 text-[11px] text-emerald-600 dark:text-emerald-400"
                        >
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ $t('feedback.card.admin_access_granted') }}</span>
                        </div>

                        <!-- Admin Reply Section (if available) -->
                        <div
                            v-if="item.admin_reply"
                            :class="[
                                'p-3.5 rounded-xl border text-xs space-y-2 relative transition-all',
                                !item.has_user_seen_reply
                                    ? 'bg-gradient-to-br from-indigo-50 to-sky-50 dark:from-indigo-950/40 dark:to-sky-950/40 border-indigo-200 dark:border-indigo-800 ring-2 ring-indigo-500/20'
                                    : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-gray-700',
                            ]"
                            @mouseenter="markAsSeen(item)"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="w-5 h-5 rounded-md bg-indigo-600 text-white flex items-center justify-center text-[10px]"
                                    >
                                        🛠️
                                    </span>
                                    <span class="font-bold text-indigo-900 dark:text-indigo-200">
                                        {{ $t('feedback.card.admin_reply_title') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="!item.has_user_seen_reply"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white"
                                    >
                                        {{ $t('feedback.card.new_reply_badge') }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">
                                        {{ formatDate(item.admin_replied_at) }}
                                    </span>
                                </div>
                            </div>

                            <p
                                class="text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line"
                            >
                                {{ item.admin_reply }}
                            </p>

                            <!-- Reply Video (if admin uploaded response video) -->
                            <PrimaryButton
                                v-if="item.admin_reply_video_url"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30"
                                @click="
                                    openVideoModal(
                                        item.admin_reply_video_url,
                                        $t('feedback.card.watch_reply_video')
                                    )
                                "
                            >
                                <template #iconLeft>
                                    <svg
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                </template>
                                {{ $t('feedback.card.watch_reply_video') }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div
                        class="px-5 py-3 bg-gray-50/80 dark:bg-gray-900/40 border-t border-border/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-[10px]"
                            >
                                {{ item.user?.name?.charAt(0) || 'U' }}
                            </span>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300">
                                {{ item.user?.name || $t('feedback.card.user_default') }}
                            </span>
                        </div>

                        <span v-if="item.created_at" class="text-[11px] text-gray-400 font-mono">
                            {{ formatDate(item.created_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="p-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-border shadow-sm space-y-4"
            >
                <div
                    class="w-16 h-16 mx-auto rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $t('feedback.empty.title') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                        {{ $t('feedback.empty.desc') }}
                    </p>
                </div>
                <div>
                    <PrimaryButton type="button" @click="showCreateModal = true" size="sm">
                        <template #iconLeft>
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </template>
                        {{ $t('feedback.empty.action') }}
                    </PrimaryButton>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="feedbacks.links && feedbacks.links.length > 3"
                class="flex justify-center pt-4"
            >
                <nav class="flex items-center gap-1">
                    <Link
                        v-for="(link, index) in feedbacks.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-lg font-medium transition-colors',
                            link.active
                                ? 'bg-indigo-600 text-white font-bold'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <!-- Modals -->
        <FeedbackFormModal
            :show="showCreateModal"
            :modules="modules"
            @close="showCreateModal = false"
        />

        <VideoPlayerModal
            :show="showVideoModal"
            :video-url="currentVideoUrl"
            :title="currentVideoTitle"
            @close="showVideoModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FeedbackFormModal from './Partials/FeedbackFormModal.vue';
import VideoPlayerModal from './Partials/VideoPlayerModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const { t, locale } = useI18n();

const props = defineProps({
    feedbacks: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, pending: 0, in_progress: 0, resolved: 0, unread_replies: 0 }),
    },
    modules: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', status: '', module: '' }),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const selectedStatus = ref(props.filters.status || '');
const selectedModule = ref(props.filters.module || '');

const showCreateModal = ref(false);
const showVideoModal = ref(false);
const currentVideoUrl = ref(null);
const currentVideoTitle = ref('');

const typeFilterOptions = computed(() => [
    { id: '', name: t('feedback.filters.all_types') },
    { id: 'bug', name: `🐞 ${t('feedback.types.bug')}` },
    { id: 'feature_request', name: `💡 ${t('feedback.types.feature_request')}` },
    { id: 'suggestion', name: `✨ ${t('feedback.types.suggestion')}` },
]);

const statusFilterOptions = computed(() => [
    { id: '', name: t('feedback.filters.all_statuses') },
    { id: 'pending', name: t('feedback.statuses.pending') },
    { id: 'in_progress', name: t('feedback.statuses.in_progress') },
    { id: 'resolved', name: t('feedback.statuses.resolved') },
    { id: 'closed', name: t('feedback.statuses.closed') },
]);

const moduleFilterOptions = computed(() => {
    return [
        { id: '', name: t('feedback.filters.all_modules') },
        ...props.modules.map((m) => ({
            id: m.id,
            name: t(`feedback.modules.${m.id}`, m.name),
        })),
    ];
});

const hasActiveFilters = computed(() => {
    return !!(
        searchQuery.value ||
        selectedType.value ||
        selectedStatus.value ||
        selectedModule.value
    );
});

const applyFilters = () => {
    router.get(
        '/app/feedback',
        {
            search: searchQuery.value || undefined,
            type: selectedType.value || undefined,
            status: selectedStatus.value || undefined,
            module: selectedModule.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedType.value = '';
    selectedStatus.value = '';
    selectedModule.value = '';
    applyFilters();
};

const getModuleName = (moduleId) => {
    return t(`feedback.modules.${moduleId}`, moduleId);
};

const getTypeName = (type) => {
    return t(`feedback.types.${type}`, type);
};

const getTypeIcon = (type) => {
    const map = {
        bug: '🐞',
        feature_request: '💡',
        suggestion: '✨',
    };
    return map[type] || '📝';
};

const getTypeBadgeClass = (type) => {
    const map = {
        bug: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900/50',
        feature_request:
            'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-900/50',
        suggestion:
            'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50',
    };
    return map[type] || 'bg-gray-100 text-gray-700';
};

const getStatusName = (status) => {
    return t(`feedback.statuses.${status}`, status);
};

const getStatusBadgeClass = (status) => {
    const map = {
        pending:
            'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50',
        in_progress:
            'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-900/50',
        resolved:
            'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50',
        closed: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

const getStatusDotClass = (status) => {
    const map = {
        pending: 'bg-amber-500',
        in_progress: 'bg-sky-500',
        resolved: 'bg-emerald-500',
        closed: 'bg-gray-400',
    };
    return map[status] || 'bg-gray-400';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const loc = locale.value === 'ar' ? 'ar-SA' : 'en-US';
    return date.toLocaleDateString(loc, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const openVideoModal = (url, title) => {
    currentVideoUrl.value = url;
    currentVideoTitle.value = title;
    showVideoModal.value = true;
};

const markAsSeen = (item) => {
    if (item.admin_reply && !item.has_user_seen_reply) {
        item.has_user_seen_reply = true;
        router.post(`/app/feedback/${item.id}/seen-reply`, {}, { preserveScroll: true });
    }
};
</script>
