<script setup>
/**
 * AppSelect — Carag V2 Design System primitive.
 *
 * Wraps SelectInput.vue to keep existing form behavior but
 * exposes the same field API as AppInput (label, hint, error,
 * aria-*). Visual styling is token-driven.
 */
import { computed, ref, useAttrs } from 'vue';

const props = defineProps({
    id: { type: String, default: null },
    modelValue: { type: [String, Number, Boolean, null], default: null },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    placeholder: { type: String, default: null },
});

defineOptions({ inheritAttrs: false });
const emit = defineEmits(['update:modelValue', 'change', 'blur', 'focus']);
const attrs = useAttrs();

const selectEl = ref(null);
const fieldId = computed(() => props.id || `app-select-${Math.random().toString(36).slice(2, 10)}`);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorId = computed(() => `${fieldId.value}-error`);
const describedBy = computed(() => {
    const ids = [];
    if (props.hint) ids.push(hintId.value);
    if (props.error) ids.push(errorId.value);
    return ids.length ? ids.join(' ') : undefined;
});

const onChange = (e) => {
    emit('update:modelValue', e.target.value);
    emit('change', e);
};
const onBlur = (e) => emit('blur', e);
const onFocus = (e) => emit('focus', e);
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

        <div class="app-field__control app-select__control">
            <select
                :id="fieldId"
                ref="selectEl"
                :value="modelValue"
                :required="required"
                :disabled="disabled"
                :aria-invalid="error ? 'true' : undefined"
                :aria-required="required ? 'true' : undefined"
                :aria-describedby="describedBy"
                v-bind="attrs"
                class="app-field__input app-select__input"
                @change="onChange"
                @blur="onBlur"
                @focus="onFocus"
            >
                <option v-if="placeholder" :value="''" disabled hidden>{{ placeholder }}</option>
                <slot />
            </select>

            <span class="app-select__chevron" aria-hidden="true">
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M6 9l6 6 6-6" />
                </svg>
            </span>
        </div>

        <p v-if="hint && !error" :id="hintId" class="app-field__hint">{{ hint }}</p>
        <p v-if="error" :id="errorId" class="app-field__error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
/* AppField base styles are duplicated here (scoped) so this
 * component can be used standalone. Tokens are identical. */
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
    align-items: center;
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
    padding: 0 var(--space-3);
    height: var(--size-control-md);
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

/* Select-specific */
.app-select__input {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-inline-end: var(--space-10);
    background: transparent;
    cursor: pointer;
}
.app-select__chevron {
    position: absolute;
    inset-inline-end: var(--space-3);
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-text-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
</style>
