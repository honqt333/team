<template>
    <AppLayout>
        <Head :title="$t('feedback.title')" />

        <div class="space-y-6 pb-12">
            <!-- Header Section -->
            <PageHeader
                :title="$t('feedback.title')"
                :subtitle="$t('feedback.subtitle')"
                :total-count="toEnglish(stats.total)"
                :count-label="$t('feedback.stats.total')"
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
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="feedback_view_mode"
                            />
                        </div>

                        <button
                            type="button"
                            @click="showCreateModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span class="text-sm">{{ $t('feedback.add_feedback') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="p-4 bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <div class="text-xs font-bold text-content-muted">
                        {{ $t('feedback.stats.total') }}
                    </div>
                    <div class="mt-2 text-2xl font-bold text-content-primary font-mono">
                        {{ toEnglish(stats.total) }}
                    </div>
                </div>
                <div
                    class="p-4 bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-warning">
                            {{ $t('feedback.stats.pending') }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-warning"></span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-warning font-mono">
                        {{ toEnglish(stats.pending) }}
                    </div>
                </div>
                <div
                    class="p-4 bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-info">
                            {{ $t('feedback.stats.in_progress') }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-info"></span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-info font-mono">
                        {{ toEnglish(stats.in_progress) }}
                    </div>
                </div>
                <div
                    class="p-4 bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-success">
                            {{ $t('feedback.stats.resolved') }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-success"></span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-success font-mono">
                        {{ toEnglish(stats.resolved) }}
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div
                class="p-4 bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs space-y-3"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input
                            type="text"
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            :placeholder="$t('feedback.filters.search_placeholder')"
                            class="w-full rounded-xl border border-border-subtle bg-surface-secondary text-content-primary text-xs pe-9 ps-3 py-2.5 focus:border-action-primary focus:ring-2 focus:ring-action-primary/20 transition-colors"
                        />
                        <div
                            class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none text-content-muted"
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
                    class="flex justify-end pt-2 border-t border-border-subtle"
                >
                    <button
                        type="button"
                        @click="clearFilters"
                        class="text-xs text-danger hover:underline font-bold flex items-center gap-1 cursor-pointer"
                    >
                        <span>{{ $t('feedback.filters.clear') }}</span>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <AppEmptyState
                v-if="!feedbacks.data || feedbacks.data.length === 0"
                :title="$t('feedback.empty.title')"
                :description="$t('feedback.empty.desc')"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('feedback.empty.action') }}</span>
                    </button>
                </template>
            </AppEmptyState>

            <!-- Content Views -->
            <template v-else>
                <!-- Table View -->
                <DataTable v-if="viewMode === 'list'" :items="feedbacks" :columns="columns">
                    <template #cell-type="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold',
                                getTypeBadgeClass(item.type),
                            ]"
                        >
                            <span>{{ getTypeIcon(item.type) }}</span>
                            <span>{{ getTypeName(item.type) }}</span>
                        </span>
                    </template>

                    <template #cell-module="{ item }">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-surface-secondary text-content-primary"
                        >
                            {{ getModuleName(item.module) }}
                        </span>
                    </template>

                    <template #cell-comments="{ item }">
                        <div class="max-w-md space-y-1">
                            <p class="text-xs text-content-primary line-clamp-2">
                                {{ item.comments || $t('feedback.card.no_comments') }}
                            </p>
                            <div
                                v-if="item.admin_reply"
                                class="text-[11px] text-action-primary font-semibold flex items-center gap-1"
                            >
                                <span>{{ $t('feedback.card.admin_reply_title') }}:</span>
                                <span
                                    class="truncate max-w-[260px] text-content-secondary font-normal"
                                >
                                    {{ item.admin_reply }}
                                </span>
                                <span
                                    v-if="!item.has_user_seen_reply"
                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-action-primary text-white"
                                >
                                    {{ $t('feedback.card.new_reply_badge') }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold',
                                getStatusBadgeClass(item.status),
                            ]"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full"
                                :class="getStatusDotClass(item.status)"
                            ></span>
                            <span>{{ getStatusName(item.status) }}</span>
                        </span>
                    </template>

                    <template #cell-user="{ item }">
                        <span class="text-xs font-medium text-content-secondary">
                            {{ item.user?.name || $t('feedback.card.user_default') }}
                        </span>
                    </template>

                    <template #cell-created_at="{ item }">
                        <span class="text-xs text-content-muted whitespace-nowrap">
                            {{ formatDate(item.created_at) }}
                        </span>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-center gap-1">
                            <button
                                v-if="item.video_url"
                                type="button"
                                @click="
                                    openVideoModal(
                                        item.video_url,
                                        $t('feedback.card.watch_recording')
                                    )
                                "
                                class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                :title="$t('feedback.card.watch_recording')"
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
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </button>
                            <button
                                v-if="item.admin_reply_video_url"
                                type="button"
                                @click="
                                    openVideoModal(
                                        item.admin_reply_video_url,
                                        $t('feedback.card.watch_reply_video')
                                    )
                                "
                                class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                :title="$t('feedback.card.watch_reply_video')"
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
                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                    />
                                </svg>
                            </button>
                            <span
                                v-if="!item.video_url && !item.admin_reply_video_url"
                                class="text-xs text-content-muted"
                            >
                                —
                            </span>
                        </div>
                    </template>
                </DataTable>

                <!-- Grid View -->
                <div v-else class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div
                            v-for="item in feedbacks.data"
                            :key="item.id"
                            class="bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between overflow-hidden"
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
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold',
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
                                    class="flex items-center justify-between text-xs text-content-muted"
                                >
                                    <span
                                        class="inline-flex items-center gap-1 bg-surface-secondary px-2 py-0.5 rounded-md font-medium text-content-primary"
                                    >
                                        🏢 {{ getModuleName(item.module) }}
                                    </span>
                                    <span>{{ formatDate(item.created_at) }}</span>
                                </div>

                                <!-- Comments Text -->
                                <div
                                    class="text-xs text-content-primary leading-relaxed whitespace-pre-line bg-surface-secondary/50 p-3 rounded-xl border border-border-subtle"
                                >
                                    {{ item.comments || $t('feedback.card.no_comments') }}
                                </div>

                                <!-- Video Screen Recording Preview Button -->
                                <div v-if="item.video_url" class="pt-1">
                                    <button
                                        type="button"
                                        @click="
                                            openVideoModal(
                                                item.video_url,
                                                $t('feedback.card.watch_recording')
                                            )
                                        "
                                        class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-action-primary bg-action-primary/10 hover:bg-action-primary/20 transition-colors cursor-pointer"
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
                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <span>{{ $t('feedback.card.watch_recording') }}</span>
                                        <span
                                            v-if="item.video_duration_seconds"
                                            class="text-[11px] opacity-75"
                                        >
                                            ({{ formatTime(item.video_duration_seconds) }})
                                        </span>
                                    </button>
                                </div>

                                <!-- Admin Access Badge (if permitted) -->
                                <div
                                    v-if="item.allow_admin_access"
                                    class="flex items-center gap-1.5 text-[11px] text-success"
                                >
                                    <svg
                                        class="w-3.5 h-3.5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
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
                                            ? 'bg-action-primary/5 border-action-primary/30 ring-2 ring-action-primary/10'
                                            : 'bg-surface-secondary/60 border-border-subtle',
                                    ]"
                                    @mouseenter="markAsSeen(item)"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="w-5 h-5 rounded-md bg-action-primary text-white flex items-center justify-center text-[10px]"
                                            >
                                                🛠️
                                            </span>
                                            <span class="font-bold text-content-primary">
                                                {{ $t('feedback.card.admin_reply_title') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                v-if="!item.has_user_seen_reply"
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-action-primary text-white"
                                            >
                                                {{ $t('feedback.card.new_reply_badge') }}
                                            </span>
                                            <span class="text-[10px] text-content-muted">
                                                {{ formatDate(item.admin_replied_at) }}
                                            </span>
                                        </div>
                                    </div>

                                    <p
                                        class="text-content-secondary leading-relaxed whitespace-pre-line"
                                    >
                                        {{ item.admin_reply }}
                                    </p>

                                    <!-- Reply Video (if admin uploaded response video) -->
                                    <button
                                        v-if="item.admin_reply_video_url"
                                        type="button"
                                        @click="
                                            openVideoModal(
                                                item.admin_reply_video_url,
                                                $t('feedback.card.watch_reply_video')
                                            )
                                        "
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold text-action-primary bg-action-primary/10 hover:bg-action-primary/20 transition-colors cursor-pointer"
                                    >
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
                                        <span>{{ $t('feedback.card.watch_reply_video') }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Card Footer Actions -->
                            <div
                                class="px-5 py-3 bg-surface-secondary/50 border-t border-border-subtle flex items-center justify-between text-xs text-content-muted"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-action-primary/10 text-action-primary font-bold flex items-center justify-center text-[10px]"
                                    >
                                        {{ item.user?.name?.charAt(0) || 'U' }}
                                    </span>
                                    <span class="text-[11px] font-medium text-content-primary">
                                        {{ item.user?.name || $t('feedback.card.user_default') }}
                                    </span>
                                </div>

                                <span
                                    v-if="item.created_at"
                                    class="text-[11px] text-content-muted font-mono"
                                >
                                    {{ formatDate(item.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Pagination -->
                    <AppPagination
                        v-if="feedbacks.links && feedbacks.links.length > 3"
                        :links="feedbacks.links"
                        :from="feedbacks.from"
                        :to="feedbacks.to"
                        :total="feedbacks.total"
                    />
                </div>
            </template>
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
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import FeedbackFormModal from './Partials/FeedbackFormModal.vue';
import VideoPlayerModal from './Partials/VideoPlayerModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t, locale } = useI18n();
const { toEnglish } = useNumberFormat();

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

const viewMode = ref('grid');
const searchQuery = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const selectedStatus = ref(props.filters.status || '');
const selectedModule = ref(props.filters.module || '');

const showCreateModal = ref(false);
const showVideoModal = ref(false);
const currentVideoUrl = ref(null);
const currentVideoTitle = ref('');

const columns = computed(() => [
    { key: 'type', label: t('feedback.modal.type_label') },
    { key: 'module', label: t('feedback.modal.module_label') },
    { key: 'comments', label: t('feedback.modal.comments_label') },
    { key: 'status', label: t('common.status'), align: 'center' },
    { key: 'user', label: t('feedback.card.user_default') },
    { key: 'created_at', label: t('common.date') },
    { key: 'actions', label: t('common.actions'), align: 'center' },
]);

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
        bug: 'bg-danger-soft text-danger',
        feature_request: 'bg-action-primary/10 text-action-primary',
        suggestion: 'bg-success-soft text-success',
    };
    return map[type] || 'bg-surface-tertiary text-content-secondary';
};

const getStatusName = (status) => {
    return t(`feedback.statuses.${status}`, status);
};

const getStatusBadgeClass = (status) => {
    const map = {
        pending: 'bg-warning-soft text-warning',
        in_progress: 'bg-info-soft text-info',
        resolved: 'bg-success-soft text-success',
        closed: 'bg-surface-tertiary text-content-secondary',
    };
    return map[status] || 'bg-surface-tertiary text-content-secondary';
};

const getStatusDotClass = (status) => {
    const map = {
        pending: 'bg-warning',
        in_progress: 'bg-info',
        resolved: 'bg-success',
        closed: 'bg-content-muted',
    };
    return map[status] || 'bg-content-muted';
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
