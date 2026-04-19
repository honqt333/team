<template>
    <Modal
        :show="isOpen"
        max-width="sm"
        @close="handleCancel"
    >
        <div class="overflow-hidden rounded-2xl">

            <!-- Top accent bar -->
            <div class="h-1.5 w-full" :class="accentBar[options.type]"></div>

            <div class="px-6 pt-6 pb-7 text-center">

                <!-- Icon circle -->
                <div
                    class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-5 shadow-sm"
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
                    <!-- Success -->
                    <svg v-else-if="options.type === 'success'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <!-- Info (default) -->
                    <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 leading-snug">
                    {{ options.title }}
                </h3>

                <!-- Message -->
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-7 leading-relaxed max-w-xs mx-auto">
                    {{ options.message }}
                </p>

                <!-- Actions -->
                <div class="flex items-center justify-center gap-3">
                    <!-- Cancel -->
                    <button
                        @click="handleCancel"
                        class="flex-1 px-5 py-2.5 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold text-sm transition-all"
                    >
                        {{ options.cancelText || 'إلغاء' }}
                    </button>
                    <!-- Confirm -->
                    <button
                        @click="handleConfirm"
                        class="flex-1 px-5 py-2.5 text-white rounded-xl font-semibold text-sm shadow-lg transition-all active:scale-95"
                        :class="buttonClasses[options.type]"
                    >
                        {{ options.confirmText }}
                    </button>
                </div>

            </div>
        </div>
    </Modal>
</template>

<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import Modal from '@/Components/Modal.vue';

const { isOpen, options, resolve } = useConfirm();

const accentBar = {
    danger:  'bg-gradient-to-r from-red-500 to-rose-500',
    warning: 'bg-gradient-to-r from-amber-500 to-orange-500',
    success: 'bg-gradient-to-r from-emerald-500 to-green-500',
    info:    'bg-gradient-to-r from-blue-500 to-indigo-500',
};

const iconClasses = {
    danger:  'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    warning: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    success: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    info:    'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
};

const buttonClasses = {
    danger:  'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-500/30',
    warning: 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 shadow-amber-500/30',
    success: 'bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 shadow-emerald-500/30',
    info:    'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/30',
};

function handleConfirm() {
    resolve(true);
}

function handleCancel() {
    resolve(false);
}
</script>
