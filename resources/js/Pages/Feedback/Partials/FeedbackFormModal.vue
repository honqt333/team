<template>
    <Modal :show="show" max-width="2xl" @close="handleClose">
        <div
            class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto"
        >
            <!-- Modal Header -->
            <div
                class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-selection-bg text-action-primary flex items-center justify-center"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('feedback.modal.create_title') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('feedback.modal.create_subtitle') }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="handleClose"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="mt-5 space-y-5">
                <!-- 1. Feedback Type Selection -->
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"
                    >
                        {{ $t('feedback.modal.type_label') }}
                        <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <!-- Bug Option -->
                        <button
                            type="button"
                            @click="form.type = 'bug'"
                            :class="[
                                'flex items-center gap-3 p-3 rounded-xl border text-start transition-all',
                                form.type === 'bug'
                                    ? 'border-rose-500 bg-rose-50/70 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 ring-2 ring-rose-500/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800/60 text-gray-700 dark:text-gray-300',
                            ]"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 text-base"
                            >
                                🐞
                            </span>
                            <div>
                                <div class="text-xs font-bold">{{ $t('feedback.types.bug') }}</div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $t('feedback.types.bug_desc') }}
                                </div>
                            </div>
                        </button>

                        <!-- Feature Request Option -->
                        <button
                            type="button"
                            @click="form.type = 'feature_request'"
                            :class="[
                                'flex items-center gap-3 p-3 rounded-xl border text-start transition-all',
                                form.type === 'feature_request'
                                    ? 'border-purple-500 bg-purple-50/70 text-purple-700 ring-2 ring-purple-500/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800/60 text-gray-700 dark:text-gray-300',
                            ]"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 text-base"
                            >
                                💡
                            </span>
                            <div>
                                <div class="text-xs font-bold">
                                    {{ $t('feedback.types.feature_request') }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $t('feedback.types.feature_request_desc') }}
                                </div>
                            </div>
                        </button>

                        <!-- Suggestion Option -->
                        <button
                            type="button"
                            @click="form.type = 'suggestion'"
                            :class="[
                                'flex items-center gap-3 p-3 rounded-xl border text-start transition-all',
                                form.type === 'suggestion'
                                    ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800/60 text-gray-700 dark:text-gray-300',
                            ]"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 text-base"
                            >
                                ✨
                            </span>
                            <div>
                                <div class="text-xs font-bold">
                                    {{ $t('feedback.types.suggestion') }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $t('feedback.types.suggestion_desc') }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 2. Conditional Admin Access Permission for Bugs -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform opacity-0 -translate-y-2"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform opacity-100 translate-y-0"
                    leave-to-class="transform opacity-0 -translate-y-2"
                >
                    <div
                        v-if="form.type === 'bug'"
                        class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50"
                    >
                        <label class="flex items-start gap-3 cursor-pointer">
                            <AppToggle v-model="form.allow_admin_access" />
                            <div class="text-xs">
                                <span class="font-bold text-amber-900 dark:text-amber-200">
                                    {{ $t('feedback.modal.allow_admin_title') }}
                                </span>
                                <p
                                    class="text-amber-700 dark:text-amber-300/80 mt-0.5 text-[11px] leading-relaxed"
                                >
                                    {{ $t('feedback.modal.allow_admin_desc') }}
                                </p>
                            </div>
                        </label>
                    </div>
                </transition>

                <!-- 3. Module Selection with SearchableSelect -->
                <div>
                    <SearchableSelect
                        v-model="form.module"
                        :options="moduleOptions"
                        :label="$t('feedback.modal.module_label')"
                        :placeholder="$t('feedback.modal.module_placeholder')"
                        :search-placeholder="$t('feedback.modal.module_placeholder')"
                        :error="form.errors.module"
                        required
                    />
                </div>

                <!-- 4. Comments & Details -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ $t('feedback.modal.comments_label') }}
                        </label>
                        <span class="text-[11px] text-gray-400">
                            {{ form.comments?.length || 0 }} / 1000
                            {{ $t('feedback.modal.chars_count') }}
                        </span>
                    </div>
                    <textarea
                        v-model="form.comments"
                        rows="3"
                        maxlength="1000"
                        :placeholder="$t('feedback.modal.comments_placeholder')"
                        class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 text-gray-900 dark:text-gray-100 focus:border-action-primary focus:ring-focus-ring p-3"
                    ></textarea>
                    <p v-if="form.errors.comments" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.comments }}
                    </p>
                </div>

                <!-- 5. Screen Recording Section -->
                <div
                    class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="w-6 h-6 rounded-lg bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xs"
                            >
                                📹
                            </span>
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                {{ $t('feedback.modal.screen_record_title') }}
                            </span>
                        </div>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $t('feedback.modal.max_2_mins') }}
                        </span>
                    </div>

                    <!-- Not Recording and No Video Yet -->
                    <div
                        v-if="!recorder.isRecording.value && !recorder.videoUrl.value"
                        class="space-y-3"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ $t('feedback.modal.screen_record_desc') }}
                            </p>
                            <label
                                class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer"
                            >
                                <AppToggle v-model="recordWithAudio" />
                                <span>{{ $t('feedback.modal.include_mic') }}</span>
                            </label>
                        </div>

                        <div>
                            <button
                                type="button"
                                @click="handleStartRecord"
                                :disabled="!recorder.isSupported"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors"
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
                                <span>{{ $t('feedback.modal.start_record_btn') }}</span>
                            </button>
                            <span v-if="!recorder.isSupported" class="text-xs text-amber-500 ms-2">
                                {{ $t('feedback.modal.not_supported') }}
                            </span>
                        </div>
                    </div>

                    <!-- Active Recording State -->
                    <div
                        v-else-if="recorder.isRecording.value"
                        class="p-3 bg-rose-50 dark:bg-rose-950/40 rounded-xl border border-rose-200 dark:border-rose-900/50 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"
                                ></span>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-rose-800 dark:text-rose-200">
                                    {{ $t('feedback.modal.recording_now') }}
                                </div>
                                <div class="text-[11px] font-mono text-rose-600 dark:text-rose-400">
                                    {{ formatTime(recorder.recordingDuration.value) }} / 02:00
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="recorder.stopRecording"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5"
                        >
                            <span class="w-2 h-2 rounded bg-white"></span>
                            <span>{{ $t('feedback.modal.stop_save_btn') }}</span>
                        </button>
                    </div>

                    <!-- Recorded Video Preview -->
                    <div v-else-if="recorder.videoUrl.value" class="space-y-3">
                        <div
                            class="flex items-center justify-between text-xs text-emerald-700 dark:text-emerald-300 font-semibold"
                        >
                            <span class="flex items-center gap-1.5">
                                <svg
                                    class="w-4 h-4 text-emerald-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ $t('feedback.modal.recorded_success') }} ({{
                                    formatTime(recorder.recordingDuration.value)
                                }})
                            </span>
                            <button
                                type="button"
                                @click="handleRemoveVideo"
                                class="text-rose-600 hover:text-rose-700 dark:text-rose-400 text-xs font-medium"
                            >
                                {{ $t('feedback.modal.retake_delete_btn') }}
                            </button>
                        </div>

                        <div
                            class="rounded-xl overflow-hidden bg-black aspect-video max-h-48 flex items-center justify-center"
                        >
                            <video
                                :src="recorder.videoUrl.value"
                                controls
                                class="w-full h-full object-contain"
                            ></video>
                        </div>
                    </div>

                    <p v-if="recorder.error.value" class="text-xs text-rose-500 mt-2">
                        {{ recorder.error.value }}
                    </p>
                </div>

                <!-- Upload Progress Bar (if submitting with video) -->
                <div v-if="uploadProgress > 0 && uploadProgress < 100" class="space-y-1.5">
                    <div
                        class="flex justify-between text-xs font-semibold text-gray-700 dark:text-gray-300"
                    >
                        <span>{{ $t('feedback.modal.uploading') }}</span>
                        <span>{{ uploadProgress }}%</span>
                    </div>
                    <div
                        class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"
                    >
                        <div
                            class="h-full bg-gradient-to-r from-action-primary to-sky-500 transition-all duration-200"
                            :style="{ width: uploadProgress + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div
                    class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        type="button"
                        @click="handleClose"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('feedback.modal.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || recorder.isRecording.value"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin w-4 h-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>
                            {{
                                form.processing
                                    ? $t('feedback.modal.submitting')
                                    : $t('feedback.modal.submit')
                            }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useScreenRecorder } from '@/Composables/useScreenRecorder';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    modules: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const moduleOptions = computed(() => {
    return props.modules.map((m) => ({
        id: m.id,
        name: t(`feedback.modules.${m.id}`, m.name),
    }));
});

const recorder = useScreenRecorder();
const recordWithAudio = ref(true);
const uploadProgress = ref(0);

const form = useForm({
    type: 'bug',
    module: '',
    comments: '',
    allow_admin_access: true,
    video: null,
    video_duration_seconds: null,
});

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const handleStartRecord = async () => {
    await recorder.startRecording({
        withAudio: recordWithAudio.value,
        maxDurationSeconds: 120,
    });
};

const handleRemoveVideo = () => {
    recorder.resetRecording();
    form.video = null;
    form.video_duration_seconds = null;
};

const handleClose = () => {
    if (recorder.isRecording.value) {
        recorder.stopRecording();
    }
    recorder.resetRecording();
    form.reset();
    form.clearErrors();
    uploadProgress.value = 0;
    emit('close');
};

const submit = () => {
    if (recorder.videoBlob.value) {
        const file = new File([recorder.videoBlob.value], `screen-record-${Date.now()}.webm`, {
            type: recorder.videoBlob.value.type || 'video/webm',
        });
        form.video = file;
        form.video_duration_seconds = recorder.recordingDuration.value;
    }

    form.post('/app/feedback', {
        forceFormData: true,
        onProgress: (progress) => {
            if (progress.percentage) {
                uploadProgress.value = Math.round(progress.percentage);
            }
        },
        onSuccess: () => {
            handleClose();
        },
    });
};
</script>
