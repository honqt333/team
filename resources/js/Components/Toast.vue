<template>
    <Teleport :to="activeModalElement || 'body'" v-if="isPrimary">
        <TransitionGroup
            tag="div"
            name="toast"
            class="fixed top-4 z-[9999] flex flex-col gap-3 pointer-events-none"
            :class="isRtl ? 'left-4' : 'right-4'"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                role="status"
                aria-live="polite"
                class="pointer-events-auto flex items-start gap-3 min-w-[320px] max-w-md p-4 rounded-2xl shadow-2xl backdrop-blur-md border transition-all duration-200 cursor-pointer select-none hover:scale-[1.01] hover:shadow-3xl active:scale-[0.99]"
                :class="toastClasses[toast.type]"
                @click="handleCardClick(toast)"
                @mouseenter="pauseToast(toast.id)"
                @mouseleave="resumeToast(toast.id)"
                @touchstart.passive="onTouchStart($event, toast.id)"
                @touchend.passive="onTouchEnd($event, toast.id)"
            >
                <!-- Icon -->
                <div class="flex-shrink-0 mt-0.5">
                    <!-- Success -->
                    <svg
                        v-if="toast.type === 'success'"
                        class="w-5 h-5 text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <!-- Error -->
                    <svg
                        v-else-if="toast.type === 'error'"
                        class="w-5 h-5 text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <!-- Warning -->
                    <svg
                        v-else-if="toast.type === 'warning'"
                        class="w-5 h-5 text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <!-- Info -->
                    <svg v-else class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <p v-if="toast.title" class="font-bold text-sm leading-snug">
                        {{ toast.title }}
                    </p>
                    <p class="text-sm font-medium opacity-95 leading-relaxed">
                        {{ toast.message }}
                    </p>

                    <!-- Optional Action Button -->
                    <div v-if="toast.action" class="mt-2">
                        <button
                            type="button"
                            @click.stop="handleAction(toast)"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors cursor-pointer"
                        >
                            {{ toast.action.label }}
                        </button>
                    </div>
                </div>

                <!-- Close Button -->
                <button
                    type="button"
                    @click.stop="removeToast(toast.id)"
                    class="flex-shrink-0 p-1 -m-1 rounded-lg opacity-70 hover:opacity-100 hover:bg-white/10 transition-all cursor-pointer"
                    :aria-label="$t('common.close') || 'Close'"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </Teleport>
</template>

<script setup>
import { activeModalElement } from '@/Support/modalStack';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const {
    toasts,
    removeToast,
    pauseToast,
    resumeToast,
    registerContainer,
    unregisterContainer,
    activeContainers,
} = useToast();

const containerId = ref(null);

onMounted(() => {
    containerId.value = registerContainer();
});

onUnmounted(() => {
    if (containerId.value) {
        unregisterContainer(containerId.value);
    }
});

const isPrimary = computed(() => {
    return activeContainers.value[activeContainers.value.length - 1] === containerId.value;
});

const isRtl = computed(() => {
    return typeof document !== 'undefined' && document.documentElement.dir === 'rtl';
});

// Click anywhere on card to dismiss
function handleCardClick(toast) {
    if (toast.dismissOnClick !== false) {
        removeToast(toast.id);
    }
}

// Action button click
function handleAction(toast) {
    if (toast.action && typeof toast.action.onClick === 'function') {
        toast.action.onClick();
    }
    removeToast(toast.id);
}

// Touch swipe to dismiss
const touchStartX = ref(0);

function onTouchStart(e, id) {
    if (e.touches && e.touches[0]) {
        touchStartX.value = e.touches[0].clientX;
    }
}

function onTouchEnd(e, id) {
    if (e.changedTouches && e.changedTouches[0]) {
        const deltaX = e.changedTouches[0].clientX - touchStartX.value;
        // If swiped more than 45px in either direction, dismiss
        if (Math.abs(deltaX) > 45) {
            removeToast(id);
        }
    }
}

const toastClasses = {
    success:
        'bg-emerald-600/95 dark:bg-emerald-700/95 text-white border-emerald-400/40 shadow-emerald-950/20',
    error: 'bg-rose-600/95 dark:bg-rose-700/95 text-white border-rose-400/40 shadow-rose-950/20',
    warning:
        'bg-amber-600/95 dark:bg-amber-700/95 text-white border-amber-400/40 shadow-amber-950/20',
    info: 'bg-sky-600/95 dark:bg-sky-700/95 text-white border-sky-400/40 shadow-sky-950/20',
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
}
</style>
