<template>
    <div
        v-if="layout === 'floating'"
        class="fixed bottom-6 start-6 flex items-center gap-3 print:hidden z-50"
    >
        <!-- Back Button -->
        <button
            type="button"
            @click="handleClose"
            class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 rounded-xl text-sm font-semibold transition-all border border-gray-200 shadow-lg flex items-center gap-2 active:scale-95"
        >
            <svg
                class="w-4 h-4 rtl:rotate-180"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                />
            </svg>
            <span>{{ closeLabel || $t('common.back') }}</span>
        </button>

        <!-- Print Button -->
        <AppPrintButton
            @click="handlePrint"
            :style="{ backgroundColor: primaryColor || '#3b82f6' }"
            :label="printLabel || $t('common.print')"
        />

        <!-- Print Scale Slider (70% - 100%) -->
        <div
            v-if="showScale"
            class="flex items-center gap-2 px-3 py-2 bg-white/95 text-gray-700 rounded-xl border border-gray-200 shadow-lg text-xs font-semibold"
        >
            <span class="hidden sm:inline text-gray-500 whitespace-nowrap">
                {{ $t('common.print_scale') }}
            </span>
            <input
                type="range"
                min="70"
                max="100"
                step="5"
                :value="scale"
                :aria-label="$t('common.print_scale')"
                @input="handleScaleInput"
                class="w-20 sm:w-24 accent-blue-600 cursor-pointer"
            />
            <button
                type="button"
                @click="resetScale"
                :title="$t('common.print_scale')"
                class="min-w-[38px] text-center font-mono text-[11px] px-1.5 py-0.5 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer"
            >
                {{ scale }}%
            </button>
        </div>

        <slot name="extra-actions" />
    </div>
    <div
        v-else
        class="print:hidden mb-6 flex flex-wrap justify-between items-center gap-3 bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl border border-gray-200 dark:border-gray-700"
    >
        <div class="flex flex-wrap items-center gap-2">
            <AppPrintButton
                variant="primary"
                @click="handlePrint"
                :label="printLabel || $t('common.print')"
            />
            <div
                v-if="showScale"
                class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl border border-gray-200 dark:border-gray-600 text-xs font-semibold"
            >
                <span class="hidden sm:inline text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    {{ $t('common.print_scale') }}
                </span>
                <input
                    type="range"
                    min="70"
                    max="100"
                    step="5"
                    :value="scale"
                    :aria-label="$t('common.print_scale')"
                    @input="handleScaleInput"
                    class="w-20 sm:w-24 accent-blue-600 cursor-pointer"
                />
                <button
                    type="button"
                    @click="resetScale"
                    :title="$t('common.print_scale')"
                    class="min-w-[38px] text-center font-mono text-[11px] px-1.5 py-0.5 rounded-md bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-100 transition-colors cursor-pointer"
                >
                    {{ scale }}%
                </button>
            </div>
            <slot name="extra-actions" />
        </div>

        <button
            type="button"
            @click="handleClose"
            class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 font-bold transition-all text-xs"
        >
            {{ closeLabel || $t('common.close') }}
        </button>
    </div>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';

import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
const props = defineProps({
    layout: { type: String, default: 'inline' },
    primaryColor: { type: String, default: null },
    printLabel: {
        type: String,
        default: '',
    },
    closeLabel: {
        type: String,
        default: '',
    },
    onPrint: {
        type: Function,
        default: null,
    },
    onClose: {
        type: Function,
        default: null,
    },
    scale: {
        type: Number,
        default: 100,
    },
    showScale: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['print', 'close', 'update:scale']);

function handleScaleInput(event) {
    const raw = Number(event?.target?.value ?? 100);
    const clamped = Math.min(100, Math.max(70, Number.isNaN(raw) ? 100 : Math.round(raw)));
    emit('update:scale', clamped);
}

function resetScale() {
    emit('update:scale', 100);
}

function handlePrint() {
    if (props.onPrint) {
        props.onPrint();
        return;
    }
    printDocument();
    emit('print');
}

function handleClose() {
    if (props.onClose) {
        props.onClose();
        return;
    }
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
    }
    emit('close');
}
</script>
