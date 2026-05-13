<template>
    <Modal
        :show="isOpen"
        max-width="sm"
        @close="handleCancel"
    >
        <div class="relative overflow-hidden bg-white/90 dark:bg-gray-800/95 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700/50">
            <!-- Animated Background Glow -->
            <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full blur-3xl opacity-20 pointer-events-none" :class="glowClasses[options.type]"></div>
            
            <div class="relative z-10 px-8 pt-10 pb-8 text-center">
                <!-- Icon container with deep shadow and glow -->
                <div
                    class="relative w-20 h-20 mx-auto rounded-[2rem] flex items-center justify-center mb-6 shadow-2xl transition-transform duration-500 hover:scale-110"
                    :class="iconClasses[options.type]"
                >
                    <div class="absolute inset-0 rounded-[2rem] blur-xl opacity-40 animate-pulse" :class="glowClasses[options.type]"></div>
                    
                    <!-- Danger -->
                    <svg v-if="options.type === 'danger'" class="relative w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <!-- Warning -->
                    <svg v-else-if="options.type === 'warning'" class="relative w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <!-- Success -->
                    <svg v-else-if="options.type === 'success'" class="relative w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <!-- Info (default) -->
                    <svg v-else class="relative w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3 leading-tight tracking-tight">
                    {{ options.title }}
                </h3>

                <!-- Message -->
                <p class="text-base font-medium text-gray-500 dark:text-gray-400 mb-8 leading-relaxed max-w-[280px] mx-auto">
                    {{ options.message }}
                </p>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <!-- Cancel -->
                    <button
                        @click="handleCancel"
                        class="w-full sm:flex-1 px-6 py-3.5 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-white rounded-2xl font-black text-sm transition-all duration-300 border border-gray-100 dark:border-gray-600 active:scale-95"
                    >
                        {{ options.cancelText || $t('common.cancel') }}
                    </button>
                    <!-- Confirm -->
                    <button
                        @click="handleConfirm"
                        class="w-full sm:flex-1 px-6 py-3.5 text-white rounded-2xl font-black text-sm shadow-xl transition-all duration-300 hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-2"
                        :class="buttonClasses[options.type]"
                    >
                        <span>{{ options.confirmText || $t('common.confirm') }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Bottom Decoration -->
            <div class="absolute -bottom-12 -right-12 w-32 h-32 rounded-full blur-3xl opacity-10 pointer-events-none" :class="glowClasses[options.type]"></div>
        </div>
    </Modal>
</template>

<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import Modal from '@/Components/Modal.vue';

const { isOpen, options, resolve } = useConfirm();

const glowClasses = {
    danger:  'bg-red-500',
    warning: 'bg-amber-500',
    success: 'bg-emerald-500',
    info:    'bg-indigo-500',
};

const iconClasses = {
    danger:  'bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-red-500/40',
    warning: 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-amber-500/40',
    success: 'bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-emerald-500/40',
    info:    'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-indigo-500/40',
};

const buttonClasses = {
    danger:  'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-500/30',
    warning: 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 shadow-amber-500/30',
    success: 'bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 shadow-emerald-500/30',
    info:    'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 shadow-indigo-500/30',
};

function handleConfirm() {
    resolve(true);
}

function handleCancel() {
    resolve(false);
}
</script>
