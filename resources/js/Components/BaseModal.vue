<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 flex items-center justify-center p-4" :class="zIndexClass">
                <!-- Backdrop -->
                <div 
                    class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 transition-opacity"
                    @click="closeModal"
                ></div>

                <!-- Modal Container -->
                <div 
                    class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] flex flex-col"
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

                    <!-- Body - Scrollable -->
                    <div class="p-6 overflow-y-auto flex-1">
                        <slot></slot>
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch } from 'vue';

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
