<script setup>
import { ref } from 'vue';
import { useFormatters } from '@/Composables/useFormatters';

const props = defineProps({ disabled: Boolean });
const file = defineModel();
const emit = defineEmits(['invalid']);
const dragging = ref(false);
const { formatFileSize } = useFormatters();

function select(candidate) {
    dragging.value = false;
    if (!candidate || props.disabled) return;
    if (!/\.(xlsx?|csv)$/i.test(candidate.name)) {
        file.value = null;
        emit('invalid');
        return;
    }
    file.value = candidate;
}

function browse(event) {
    select(event.target.files[0]);
    event.target.value = '';
}
</script>

<template>
    <div
        class="rounded-2xl border-2 border-dashed p-6 text-center"
        :class="dragging ? 'border-action-primary bg-selection-bg' : 'border-border'"
        @dragover.prevent="dragging = !disabled"
        @dragleave.prevent="dragging = false"
        @drop.prevent="select($event.dataTransfer.files[0])"
    >
        <label class="relative block rounded-xl focus-within:ring-2 focus-within:ring-focus-ring">
            <input
                type="file"
                accept=".xlsx,.xls,.csv"
                :disabled="disabled"
                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                :aria-label="$t('customers.import.drag_drop')"
                @change="browse"
            />
            <span class="block text-sm text-text-primary">
                {{ file?.name || $t('customers.import.drag_drop') }}
            </span>
            <span class="text-xs text-text-muted">
                {{ file ? formatFileSize(file.size) : $t('customers.import.supported_formats') }}
            </span>
        </label>
        <button
            v-if="file"
            type="button"
            :disabled="disabled"
            class="mt-3 text-sm text-danger"
            @click="file = null"
        >
            {{ $t('common.cancel') }}
        </button>
    </div>
</template>
