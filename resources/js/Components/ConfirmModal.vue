<template>
    <Modal
        :show="isOpen"
        max-width="md"
        @close="handleCancel"
    >
        <div class="p-6 text-center">
            <!-- Icon -->
            <div 
                class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4"
                :class="iconClasses[options.type]"
            >
                <!-- Danger -->
                <svg v-if="options.type === 'danger'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <!-- Warning -->
                <svg v-else-if="options.type === 'warning'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <!-- Info -->
                <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <!-- Title -->
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                {{ options.title }}
            </h3>

            <!-- Message -->
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{ options.message }}
            </p>

            <!-- Actions -->
            <div class="flex items-center justify-center gap-3">
                <button
                    v-if="options.cancelText"
                    @click="handleCancel"
                    class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors"
                >
                    {{ options.cancelText }}
                </button>
                <button
                    @click="handleConfirm"
                    class="px-5 py-2.5 text-white rounded-xl font-medium shadow-lg transition-all"
                    :class="buttonClasses[options.type]"
                >
                    {{ options.confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import Modal from '@/Components/Modal.vue';

const { isOpen, options, resolve } = useConfirm();

const iconClasses = {
    danger: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    warning: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    info: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
};

const buttonClasses = {
    danger: 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-500/30',
    warning: 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 shadow-amber-500/30',
    info: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/30',
};

function handleConfirm() {
    resolve(true);
}

function handleCancel() {
    resolve(false);
}
</script>
