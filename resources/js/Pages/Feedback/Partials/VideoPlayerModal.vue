<template>
    <BaseModal :show="show" size="lg" @close="handleClose">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                <div>
                    <div class="text-base font-bold text-gray-900 dark:text-white">
                        {{ title || $t('feedback.video_modal.title') }}
                    </div>
                    <p class="text-xs font-normal text-gray-500 dark:text-gray-400">
                        {{ $t('feedback.video_modal.subtitle') }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Video Player -->
        <div
            class="bg-gray-950 rounded-xl overflow-hidden shadow-inner aspect-video flex items-center justify-center relative"
        >
            <video
                v-if="videoUrl"
                :key="videoUrl"
                ref="videoElement"
                controls
                autoplay
                playsinline
                preload="metadata"
                class="w-full h-full object-contain"
                :src="videoUrl"
                @error="handleVideoError"
            >
                <source :src="videoUrl" type="video/webm" />
                <source :src="videoUrl" type="video/mp4" />
                {{ $t('feedback.video_modal.unsupported_browser') }}
            </video>

            <div
                v-if="hasPlaybackError"
                class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center p-4 text-center space-y-2"
            >
                <p class="text-xs text-rose-400">
                    {{ $t('feedback.video_modal.playback_error') }}
                </p>
                <a
                    :href="videoUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors"
                >
                    {{ $t('feedback.video_modal.open_download') }}
                </a>
            </div>

            <div v-else-if="!videoUrl" class="text-gray-400 text-sm">
                {{ $t('feedback.video_modal.no_video') }}
            </div>
        </div>

        <template #footer>
            <div class="w-full flex items-center justify-between">
                <span class="text-[11px] text-gray-400">
                    {{ $t('feedback.video_modal.tip') }}
                </span>
                <div class="flex items-center gap-2">
                    <a
                        v-if="videoUrl"
                        :href="videoUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        download
                        class="px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-xs font-medium flex items-center gap-1 transition-colors"
                        :title="$t('feedback.video_modal.open_download')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                        <span>{{ $t('feedback.video_modal.open_tab') }}</span>
                    </a>
                    <AppButton variant="secondary" @click="handleClose">
                        {{ $t('feedback.video_modal.close') }}
                    </AppButton>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    videoUrl: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close']);
const videoElement = ref(null);
const hasPlaybackError = ref(false);

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            hasPlaybackError.value = false;
        } else {
            if (videoElement.value) {
                videoElement.value.pause();
            }
        }
    }
);

const handleVideoError = () => {
    hasPlaybackError.value = true;
};

const handleClose = () => {
    if (videoElement.value) {
        videoElement.value.pause();
    }
    hasPlaybackError.value = false;
    emit('close');
};
</script>
