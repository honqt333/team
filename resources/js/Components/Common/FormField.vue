<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const generatedId = computed(() => props.id || `field-${Math.random().toString(36).slice(2, 9)}`);
const errorId = computed(() => `${generatedId.value}-error`);
const hintId = computed(() => `${generatedId.value}-hint`);
const describedBy = computed(() =>
    props.error ? errorId.value : props.hint ? hintId.value : undefined
);
</script>

<template>
    <div
        class="flex flex-col gap-1.5 w-full"
        :class="{ 'opacity-60 pointer-events-none': disabled }"
    >
        <!-- Label -->
        <div
            v-if="label || $slots.label || $slots['label-action']"
            class="flex items-center justify-between gap-2"
        >
            <label
                v-if="label || $slots.label"
                :for="generatedId"
                class="block text-sm font-medium text-text-primary"
            >
                <slot name="label">
                    {{ label }}
                </slot>
                <span v-if="required" class="text-danger ms-1" aria-hidden="true">*</span>
            </label>
            <slot name="label-action" />
        </div>

        <!-- Control / Input Slot -->
        <div class="relative">
            <slot
                v-bind="{
                    id: generatedId,
                    hasError: !!error,
                    'has-error': !!error,
                    describedBy,
                    required,
                    disabled,
                }"
            />
        </div>

        <!-- Hint or Error -->
        <div v-if="error || hint || $slots.hint" class="min-h-[1.25rem]">
            <p
                v-if="error"
                :id="errorId"
                class="text-xs text-danger flex items-center gap-1"
                role="alert"
            >
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span>{{ error }}</span>
            </p>
            <p v-else-if="hint || $slots.hint" :id="hintId" class="text-xs text-text-muted">
                <slot name="hint">{{ hint }}</slot>
            </p>
        </div>
    </div>
</template>
