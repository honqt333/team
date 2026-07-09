<script setup>
/**
 * AppTextarea — Carag V2 Design System primitive.
 *
 * Multi-line text input that follows the AppField pattern. No
 * legacy wrapper exists for textarea in the project, so this is
 * a clean token-driven implementation.
 */
import { computed, ref, useAttrs } from 'vue';

const props = defineProps({
    id: { type: String, default: null },
    modelValue: { type: String, default: '' },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: String, default: null },
    placeholder: { type: String, default: null },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    rows: { type: Number, default: 4 },
    maxlength: { type: Number, default: null },
});

defineOptions({ inheritAttrs: false });
const emit = defineEmits(['update:modelValue', 'blur', 'focus']);
const attrs = useAttrs();

const textareaEl = ref(null);
const fieldId = computed(
    () => props.id || `app-textarea-${Math.random().toString(36).slice(2, 10)}`
);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorIdReal = computed(() => `${fieldId.value}-error`);
const describedBy = computed(() => {
    const ids = [];
    if (props.hint) ids.push(hintId.value);
    if (props.error) ids.push(errorIdReal.value);
    return ids.length ? ids.join(' ') : undefined;
});

const onInput = (e) => emit('update:modelValue', e.target.value);
const onBlur = (e) => emit('blur', e);
const onFocus = (e) => emit('focus', e);

const focus = () => textareaEl.value?.focus();
defineExpose({ focus });
</script>

<template>
    <div
        class="app-field"
        :class="{ 'app-field--error': !!error, 'app-field--disabled': disabled }"
    >
        <label v-if="label" :for="fieldId" class="app-field__label">
            {{ label }}
            <span v-if="required" class="app-field__required" aria-hidden="true">*</span>
        </label>

        <div class="app-field__control app-textarea__control">
            <textarea
                :id="fieldId"
                ref="textareaEl"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :readonly="readonly"
                :rows="rows"
                :maxlength="maxlength"
                :aria-invalid="error ? 'true' : undefined"
                :aria-required="required ? 'true' : undefined"
                :aria-describedby="describedBy"
                v-bind="attrs"
                class="app-field__input app-textarea__input"
                @input="onInput"
                @blur="onBlur"
                @focus="onFocus"
            />
        </div>

        <div class="flex items-center justify-between gap-2">
            <p v-if="hint && !error" :id="hintId" class="app-field__hint">{{ hint }}</p>
            <p v-else-if="error" :id="errorIdReal" class="app-field__error" role="alert">
                {{ error }}
            </p>
            <p v-if="maxlength" class="app-field__hint app-textarea__count">
                {{ (modelValue || '').length }} / {{ maxlength }}
            </p>
        </div>
    </div>
</template>

<style scoped>
/* AppField base (same as AppInput) */
.app-field {
    --app-field-bg: var(--color-surface);
    --app-field-border: var(--color-border-strong);
    --app-field-fg: var(--color-text-primary);
    --app-field-placeholder: var(--color-text-muted);

    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    font-family: var(--font-family-sans);
    color: var(--app-field-fg);
    width: 100%;
}

.app-field__label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-secondary);
    line-height: var(--line-height-snug);
}
.app-field__required {
    color: var(--color-danger);
    margin-inline-start: var(--space-1);
}

.app-field__control {
    position: relative;
    display: flex;
    align-items: stretch;
    background: var(--app-field-bg);
    border: 1px solid var(--app-field-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xs);
    transition:
        border-color var(--motion-duration-fast) var(--motion-ease-standard),
        box-shadow var(--motion-duration-fast) var(--motion-ease-standard);
}
.app-field__control:hover:not(.app-field--disabled .app-field__control) {
    border-color: var(--color-text-muted);
}
.app-field__control:focus-within {
    border-color: var(--color-focus-ring);
    box-shadow: var(--shadow-focus);
}

.app-field__input {
    flex: 1;
    min-width: 0;
    background: transparent;
    border: 0;
    outline: 0;
    color: var(--app-field-fg);
    font-family: inherit;
    font-size: var(--font-size-sm);
    line-height: var(--line-height-normal);
    padding: var(--space-3);
}
.app-field__input::placeholder {
    color: var(--app-field-placeholder);
}
.app-field__input:disabled {
    cursor: not-allowed;
}

.app-field--error .app-field__control {
    border-color: var(--color-danger);
}
.app-field--error .app-field__control:focus-within {
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-danger) 25%, transparent);
}

.app-field--disabled {
    opacity: 0.7;
}
.app-field--disabled .app-field__control {
    background: var(--color-surface-muted);
}

.app-field__hint {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}
.app-field__error {
    font-size: var(--font-size-xs);
    color: var(--color-danger);
    font-weight: var(--font-weight-medium);
}

/* Textarea-specific */
.app-textarea__control {
    align-items: stretch;
}
.app-textarea__input {
    min-height: calc(var(--space-12) + var(--space-2));
    resize: vertical;
    line-height: var(--line-height-relaxed);
}
.app-textarea__count {
    margin-inline-start: auto;
    font-variant-numeric: tabular-nums;
}
</style>
