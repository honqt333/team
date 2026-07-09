<script setup>
/**
 * AppInput — Carag V2 Design System primitive.
 *
 * Wraps the legacy TextInput.vue to keep backwards-compat with
 * every existing form, but exposes a token-styled surface, full
 * a11y (aria-invalid, aria-describedby, aria-required), and
 * helper props (label, hint, error, prefix, suffix, optional
 * currency suffix when type="number").
 *
 * Slot model:
 *   - default: nothing (the input is rendered for you)
 *   - prefix / suffix: positioned in the field's start/end edges
 */
import { computed, ref, useAttrs } from 'vue';

const props = defineProps({
    id: { type: String, default: null },
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: String, default: null },
    type: {
        type: String,
        default: 'text',
        validator: (v) =>
            ['text', 'email', 'tel', 'number', 'password', 'url', 'search'].includes(v),
    },
    placeholder: { type: String, default: null },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    autofocus: { type: Boolean, default: false },
    autocomplete: { type: String, default: null },
    min: { type: [String, Number], default: null },
    max: { type: [String, Number], default: null },
    step: { type: [String, Number], default: null },
    suffix: { type: String, default: null },
    prefix: { type: String, default: null },
});

defineOptions({ inheritAttrs: false });
const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'enter']);
const attrs = useAttrs();

const inputEl = ref(null);
const fieldId = computed(() => props.id || `app-input-${Math.random().toString(36).slice(2, 10)}`);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorId = computed(() => `${fieldId.value}-error`);
const describedBy = computed(() => {
    const ids = [];
    if (props.hint) ids.push(hintId.value);
    if (props.error) ids.push(errorId.value);
    return ids.length ? ids.join(' ') : undefined;
});

const onInput = (e) => emit('update:modelValue', e.target.value);
const onBlur = (e) => emit('blur', e);
const onFocus = (e) => emit('focus', e);
const onKeyup = (e) => {
    if (e.key === 'Enter') emit('enter', e);
};

const focus = () => inputEl.value?.focus();
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

        <div class="app-field__control">
            <span v-if="prefix || $slots.prefix" class="app-field__affix app-field__affix--start">
                <slot name="prefix">{{ prefix }}</slot>
            </span>

            <input
                :id="fieldId"
                ref="inputEl"
                :value="modelValue"
                :type="type"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :readonly="readonly"
                :autocomplete="autocomplete"
                :min="min"
                :max="max"
                :step="step"
                :aria-invalid="error ? 'true' : undefined"
                :aria-required="required ? 'true' : undefined"
                :aria-describedby="describedBy"
                v-bind="attrs"
                class="app-field__input"
                @input="onInput"
                @blur="onBlur"
                @focus="onFocus"
                @keyup="onKeyup"
            />

            <span v-if="type === 'number' && suffix" class="app-field__affix app-field__affix--end">
                {{ suffix }}
            </span>
            <span v-else-if="$slots.suffix" class="app-field__affix app-field__affix--end">
                <slot name="suffix" />
            </span>
        </div>

        <p v-if="hint && !error" :id="hintId" class="app-field__hint">{{ hint }}</p>
        <p v-if="error" :id="errorId" class="app-field__error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
/* ============================================================
 * AppField — token-driven styles. Reused by AppInput, AppSelect,
 * AppTextarea. All colors come from Design/tokens.css.
 * ============================================================ */
.app-field {
    --app-field-bg: var(--color-surface);
    --app-field-border: var(--color-border-strong);
    --app-field-fg: var(--color-text-primary);
    --app-field-placeholder: var(--color-text-muted);
    --app-field-ring: var(--color-focus-ring);
    --app-field-affix-fg: var(--color-text-secondary);

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
        box-shadow var(--motion-duration-fast) var(--motion-ease-standard),
        background-color var(--motion-duration-fast) var(--motion-ease-standard);
}
.app-field__control:hover:not(.app-field--disabled .app-field__control) {
    border-color: var(--color-text-muted);
}
.app-field__control:focus-within {
    border-color: var(--app-field-ring);
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
.app-field__input::placeholder {
    color: var(--app-field-placeholder);
}
.app-field__input:disabled {
    cursor: not-allowed;
}

.app-field__affix {
    display: inline-flex;
    align-items: center;
    color: var(--app-field-affix-fg);
    font-size: var(--font-size-sm);
    padding-inline: var(--space-3);
    white-space: nowrap;
}
.app-field__affix--start {
    border-inline-end: 1px solid var(--color-border);
}
.app-field__affix--end {
    border-inline-start: 1px solid var(--color-border);
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
</style>
