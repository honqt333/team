<template>
    <div>
        <div class="space-y-6">
            <!-- Stats Bar -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm"
                >
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('system.feedbacks.stats.total') }}
                    </div>
                    <div class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                        {{ stats?.total || 0 }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm"
                >
                    <div class="text-xs text-amber-600 dark:text-amber-400">
                        {{ $t('system.feedbacks.stats.pending') }}
                    </div>
                    <div class="mt-2 text-xl font-bold text-amber-600 dark:text-amber-400">
                        {{ stats?.pending || 0 }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm"
                >
                    <div class="text-xs text-sky-600 dark:text-sky-400">
                        {{ $t('system.feedbacks.stats.in_progress') }}
                    </div>
                    <div class="mt-2 text-xl font-bold text-sky-600 dark:text-sky-400">
                        {{ stats?.in_progress || 0 }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm"
                >
                    <div class="text-xs text-emerald-600 dark:text-emerald-400">
                        {{ $t('system.feedbacks.stats.resolved') }}
                    </div>
                    <div class="mt-2 text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ stats?.resolved || 0 }}
                    </div>
                </div>
                <div
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm"
                >
                    <div class="text-xs text-rose-600 dark:text-rose-400">
                        {{ $t('system.feedbacks.stats.bugs') }}
                    </div>
                    <div class="mt-2 text-xl font-bold text-rose-600 dark:text-rose-400">
                        {{ stats?.bugs || 0 }}
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div
                class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-wrap items-center gap-3"
            >
                <input
                    type="text"
                    v-model="search"
                    :placeholder="$t('system.feedbacks.filters.search_placeholder')"
                    class="text-xs rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 py-2 px-3 w-64"
                />
                <div class="w-48">
                    <SearchableSelect
                        v-model="statusFilter"
                        :options="statusFilterOptions"
                        :placeholder="$t('system.feedbacks.filters.all_statuses')"
                        :search-placeholder="$t('system.feedbacks.filters.all_statuses')"
                        compact
                    />
                </div>
                <div class="w-48">
                    <SearchableSelect
                        v-model="typeFilter"
                        :options="typeFilterOptions"
                        :placeholder="$t('system.feedbacks.filters.all_types')"
                        :search-placeholder="$t('system.feedbacks.filters.all_types')"
                        compact
                    />
                </div>
            </div>

            <!-- Feedbacks Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable
                        structured
                        class="w-full text-start text-xs text-gray-700 dark:text-gray-300"
                    >
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-700"
                        >
                            <tr>
                                <th class="p-4">
                                    {{ $t('system.feedbacks.table.workshop_user') }}
                                </th>
                                <th class="p-4">{{ $t('system.feedbacks.table.type_module') }}</th>
                                <th class="p-4">
                                    {{ $t('system.feedbacks.table.notes_attachments') }}
                                </th>
                                <th class="p-4">{{ $t('system.feedbacks.table.admin_access') }}</th>
                                <th class="p-4">{{ $t('system.feedbacks.table.status') }}</th>
                                <th class="p-4">{{ $t('system.feedbacks.table.date') }}</th>
                                <th class="p-4 text-center">
                                    {{ $t('system.feedbacks.table.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="item in feedbacks?.data || []"
                                :key="item.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                            >
                                <td class="p-4">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{
                                            item.tenant?.trade_name ||
                                            item.tenant?.name ||
                                            $t('system.feedbacks.table.unknown_workshop')
                                        }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ item.user?.name }} ({{
                                            item.user?.email || item.user?.phone || '-'
                                        }})
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1.5 font-semibold">
                                        <span>{{ getTypeIcon(item.type) }}</span>
                                        <span>{{ getTypeName(item.type) }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ $t('feedback.modules.' + item.module, item.module) }}
                                    </div>
                                </td>
                                <td class="p-4 max-w-xs">
                                    <div class="line-clamp-2 leading-relaxed">
                                        {{ item.comments || '-' }}
                                    </div>
                                    <button
                                        v-if="item.video_url"
                                        type="button"
                                        @click="
                                            openVideo(
                                                item.video_url,
                                                $t('feedback.card.watch_recording')
                                            )
                                        "
                                        class="mt-1 inline-flex items-center gap-1 text-[11px] text-sky-600 hover:text-sky-700 font-semibold"
                                    >
                                        {{ $t('system.feedbacks.table.watch_screen') }}
                                    </button>
                                </td>
                                <td class="p-4">
                                    <span
                                        v-if="item.allow_admin_access"
                                        class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"
                                    >
                                        {{ $t('system.feedbacks.table.access_granted') }}
                                    </span>
                                    <span v-else class="text-gray-400">
                                        {{ $t('system.feedbacks.table.access_denied') }}
                                    </span>
                                </td>
                                <td class="p-4 min-w-[140px]">
                                    <SearchableSelect
                                        :model-value="item.status"
                                        :options="rowStatusOptions"
                                        compact
                                        @update:model-value="updateStatus(item, $event)"
                                    />
                                </td>
                                <td class="p-4 text-gray-400 whitespace-nowrap">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            @click="openReplyModal(item)"
                                            class="px-3 py-1 bg-selection-bg text-action-primary hover:bg-selection-bg rounded-lg font-semibold text-xs"
                                        >
                                            {{
                                                item.admin_reply
                                                    ? $t('system.feedbacks.table.edit_reply_btn')
                                                    : $t('system.feedbacks.table.reply_btn')
                                            }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteFeedback(item)"
                                            class="p-1 text-gray-400 hover:text-rose-600 rounded-lg"
                                            :title="$t('system.feedbacks.table.delete_btn')"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!feedbacks?.data || feedbacks.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-400">
                                    {{ $t('system.feedbacks.table.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>

            <!-- Pagination -->
            <AppPagination
                :links="feedbacks?.links || []"
                :from="feedbacks?.from"
                :to="feedbacks?.to"
                :total="feedbacks?.total"
            />
        </div>

        <!-- Admin Reply Modal -->
        <BaseModal
            :show="showReplyModal"
            :title="`${$t('system.feedbacks.reply_modal.title')} ${activeFeedback?.tenant?.trade_name || activeFeedback?.tenant?.name || ''}`"
            size="lg"
            @close="showReplyModal = false"
        >
            <div class="p-6 space-y-4">
                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-xs space-y-1">
                    <div class="font-semibold text-gray-700 dark:text-gray-300">
                        {{ $t('system.feedbacks.reply_modal.notes_label') }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        {{ activeFeedback?.comments }}
                    </div>
                </div>

                <form @submit.prevent="submitReply" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.feedbacks.reply_modal.reply_label') }}
                            <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="replyForm.admin_reply"
                            rows="4"
                            :placeholder="$t('system.feedbacks.reply_modal.reply_placeholder')"
                            class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-3"
                            required
                        ></textarea>
                    </div>

                    <div>
                        <SearchableSelect
                            v-model="replyForm.status"
                            :options="rowStatusOptions"
                            :label="$t('system.feedbacks.reply_modal.status_label')"
                            compact
                        />
                    </div>

                    <AppFileUpload
                        v-model="replyForm.reply_video"
                        :label="$t('system.feedbacks.reply_modal.video_label')"
                        accept="video/*"
                        :max-size-mb="50"
                        @invalid="toastError"
                    />

                    <div
                        class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700"
                    >
                        <button
                            type="button"
                            @click="showReplyModal = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-semibold"
                        >
                            {{ $t('system.feedbacks.reply_modal.cancel') }}
                        </button>
                        <AppButton
                            variant="action-xs"
                            type="submit"
                            :disabled="replyForm.processing"
                            :loading="replyForm.processing"
                        >
                            {{ $t('system.feedbacks.reply_modal.submit') }}
                        </AppButton>
                    </div>
                </form>
            </div>
        </BaseModal>

        <!-- Video Modal -->
        <VideoPlayerModal
            :show="showVideoModal"
            :video-url="videoUrl"
            :title="videoTitle"
            @close="showVideoModal = false"
        />
    </div>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { ref, watch, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import VideoPlayerModal from '@/Pages/Feedback/Partials/VideoPlayerModal.vue';
import { debounce } from 'lodash-es';
import AppButton from '@/Components/App/AppButton.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import { useToast } from '@/Composables/useToast';

const { t, locale } = useI18n();
const { toEnglish } = useNumberFormat();
const { confirm } = useConfirm();
const { error: toastError } = useToast();

const props = defineProps({
    feedbacks: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const typeFilter = ref(props.filters.type || '');

const statusFilterOptions = computed(() => [
    { id: '', name: t('system.feedbacks.filters.all_statuses') },
    { id: 'pending', name: t('feedback.statuses.pending') },
    { id: 'in_progress', name: t('feedback.statuses.in_progress') },
    { id: 'resolved', name: t('feedback.statuses.resolved') },
    { id: 'closed', name: t('feedback.statuses.closed') },
]);

const typeFilterOptions = computed(() => [
    { id: '', name: t('system.feedbacks.filters.all_types') },
    { id: 'bug', name: t('system.feedbacks.filters.type_bugs') },
    { id: 'feature_request', name: t('system.feedbacks.filters.type_features') },
    { id: 'suggestion', name: t('system.feedbacks.filters.type_suggestions') },
]);

const rowStatusOptions = computed(() => [
    { id: 'pending', name: t('feedback.statuses.pending') },
    { id: 'in_progress', name: t('feedback.statuses.in_progress') },
    { id: 'resolved', name: t('feedback.statuses.resolved') },
    { id: 'closed', name: t('feedback.statuses.closed') },
]);

const showReplyModal = ref(false);
const activeFeedback = ref(null);
const replyForm = useForm({
    admin_reply: '',
    status: 'resolved',
    reply_video: null,
});

const showVideoModal = ref(false);
const videoUrl = ref(null);
const videoTitle = ref('');

const applyFilters = () => {
    router.get(
        route('system.feedbacks.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            type: typeFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

watch(search, debounce(applyFilters, 300));
watch([statusFilter, typeFilter], applyFilters);

const getTypeName = (type) => {
    return t(`feedback.types.${type}`, type);
};

const getTypeIcon = (type) => {
    const map = { bug: '🐞', feature_request: '💡', suggestion: '✨' };
    return map[type] || '📝';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const loc = locale.value === 'ar' ? 'ar-SA' : 'en-US';
    return new Date(dateStr).toLocaleDateString(loc, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const openVideo = (url, title) => {
    videoUrl.value = url;
    videoTitle.value = title;
    showVideoModal.value = true;
};

const openReplyModal = (feedback) => {
    activeFeedback.value = feedback;
    replyForm.admin_reply = feedback.admin_reply || '';
    replyForm.status = feedback.status === 'pending' ? 'resolved' : feedback.status;
    replyForm.reply_video = null;
    showReplyModal.value = true;
};

const submitReply = () => {
    if (!activeFeedback.value) return;
    replyForm.post(route('system.feedbacks.reply', activeFeedback.value.id), {
        forceFormData: true,
        onSuccess: () => {
            showReplyModal.value = false;
        },
    });
};

const updateStatus = (item, newStatus) => {
    router.put(
        route('system.feedbacks.update-status', item.id),
        { status: newStatus },
        { preserveScroll: true }
    );
};

const deleteFeedback = async (item) => {
    const ok = await confirm({
        title: t('common.delete'),
        message: t('system.feedbacks.confirm_delete'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.delete(route('system.feedbacks.destroy', item.id), {
            preserveScroll: true,
        });
    }
};
</script>
