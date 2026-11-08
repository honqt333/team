<template>
    <div
        class="print:hidden mb-6 flex justify-between items-center bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl border border-gray-200 dark:border-gray-700"
    >
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="handlePrint"
                class="px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white rounded-xl font-bold flex items-center gap-2 shadow-sm transition-all active:scale-95"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                    />
                </svg>
                <span>{{ printLabel || $t('common.print') }}</span>
            </button>
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
const props = defineProps({
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
});

const emit = defineEmits(['print', 'close']);

function handlePrint() {
    if (props.onPrint) {
        props.onPrint();
    } else {
        window.print();
    }
    emit('print');
}

function handleClose() {
    if (props.onClose) {
        props.onClose();
    } else if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
    }
    emit('close');
}
</script>
