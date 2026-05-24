<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" :class="[zIndexClass, scrollEntire ? 'fixed inset-0 overflow-y-auto' : 'fixed inset-0 flex items-center justify-center p-4']">
                <!-- Backdrop -->
                <div 
                    class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 transition-opacity"
                    @click="closeModal"
                ></div>

                <!-- Dynamic Wrapper -->
                <div :class="scrollEntire ? 'flex min-h-full items-center justify-center p-4 text-center' : 'contents'">
                    <!-- Modal Container -->
                    <div 
                        :class="[
                            sizeClass,
                            scrollEntire ? 'relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full mx-auto flex flex-col transition-all my-8 text-start' : 'relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full mx-auto max-h-[90vh] flex flex-col transition-all'
                        ]"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <slot name="title">{{ title }}</slot>
                            </h3>
                            <button
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div :class="scrollEntire ? 'p-6' : 'p-6 overflow-y-auto flex-1'">
                            <slot></slot>
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                            <slot name="footer"></slot>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
    },
    zIndexClass: {
        type: String,
        default: 'z-50',
    },
    scrollEntire: {
        type: Boolean,
        default: false,
    },
    overflowVisible: {
        type: Boolean,
        default: false,
    },
});

const sizeClass = computed(() => {
    const map = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        '2xl': 'max-w-5xl',
        '3xl': 'max-w-6xl',
        '4xl': 'max-w-7xl',
        full: 'max-w-full m-4',
    };
    return map[props.size] || 'max-w-2xl';
});

const emit = defineEmits(['close']);

function closeModal() {
    emit('close');
}

// Close on Escape key
watch(() => props.show, (value) => {
    if (value) {
        document.addEventListener('keydown', onEscape);
    } else {
        document.removeEventListener('keydown', onEscape);
    }
});

function onEscape(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
