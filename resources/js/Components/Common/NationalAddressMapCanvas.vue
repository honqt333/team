<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

defineProps({
    mapReady: {
        type: Boolean,
        default: false,
    },
    heightClass: {
        type: String,
        default: 'h-[260px]',
    },
    hint: {
        type: String,
        default: '',
    },
    loadingText: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['container']);
const containerEl = ref(null);

onMounted(() => {
    emit('container', containerEl.value);
});

onBeforeUnmount(() => {
    emit('container', null);
});
</script>

<template>
    <div>
        <div
            ref="containerEl"
            :class="[
                'w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 overflow-hidden',
                heightClass,
            ]"
        >
            <div
                v-if="!mapReady"
                class="h-full flex flex-col items-center justify-center text-center p-4"
            >
                <svg
                    class="w-8 h-8 text-gray-400 mb-2 animate-pulse"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                    />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ loadingText || $t('common.loading') }}
                </p>
            </div>
        </div>
        <p v-if="hint" class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
            {{ hint }}
        </p>
    </div>
</template>

<style scoped>
:deep(.leaflet-container) {
    z-index: 1;
}
</style>
