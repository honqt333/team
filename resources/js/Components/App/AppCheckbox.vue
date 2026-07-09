<script setup>
/**
 * AppCheckbox — Carag V2 Design System primitive.
 *
 * Wraps the legacy Checkbox.vue with token-driven visuals, full
 * a11y (aria-invalid, aria-describedby), and label/hint/error.
 */
import { computed, useSlots } from 'vue';

const props = defineProps({
    id: { type: String, default: null },
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    value: { type: [String, Number, Boolean], default: null },
});

const emit = defineEmits(['update:modelValue', 'change']);
const slots = useSlots();

const fieldId = computed(
    () => props.id || `app-checkbox-${Math.random().toString(36).slice(2, 10)}`
);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorId = computed(() => `${fieldId.value}-error`);
const describedBy = computed(() => {
    const ids = [];
    if (props.hint) ids.push(hintId.value);
    if (props.error) ids.push(errorId.value);
    return ids.length ? ids.join(' ') : undefined;
});

const onInput = (e) => {
    emit('update:modelValue', e.target.checked);
    emit('change', e);
};
</script>

<template>
    <div
        class="app-checkbox"
        :class="{
            'app-checkbox--error': !!error,
            'app-checkbox--disabled': disabled,
            'app-checkbox--checked': modelValue,
        }"
    >
        <label class="app-checkbox__row" :for="fieldId">
            <span class="app-checkbox__visual">
                <input
                    :id="fieldId"
                    type="checkbox"
                    :checked="modelValue"
                    :disabled="disabled"
                    :value="value"
                    :aria-invalid="error ? 'true' : undefined"
                    :aria-required="required ? 'true' : undefined"
                    :aria-describedby="describedBy"
                    class="app-checkbox__input"
                    @change="onInput"
                />
                <span class="app-checkbox__box" aria-hidden="true">
                    <svg
                        v-if="modelValue"
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </span>

            <span v-if="label || $slots.default" class="app-checkbox__label">
                <span v-if="label">{{ label }}</span>
                <slot />
                <span v-if="required" class="app-checkbox__required" aria-hidden="true">*</span>
            </span>
        </label>

        <p v-if="hint && !error" :id="hintId" class="app-checkbox__hint">{{ hint }}</p>
        <p v-if="error" :id="errorId" class="app-checkbox__error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
/* ============================================================
 * AppCheckbox — token-driven. No hex.
 * ============================================================ */
.app-checkbox {
    display: inline-flex;
    flex-direction: column;
    gap: var(--space-1);
    font-family: var(--font-family-sans);
}

.app-checkbox__row {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    cursor: pointer;
    user-select: none;
}
.app-checkbox--disabled .app-checkbox__row {
    cursor: not-allowed;
    opacity: 0.6;
}

.app-checkbox__visual {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
}

.app-checkbox__input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 1;
}
.app-checkbox--disabled .app-checkbox__input {
    cursor: not-allowed;
}

.app-checkbox__box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: var(--color-surface);
    border: 1.5px solid var(--color-border-strong);
    border-radius: var(--radius-sm);
    color: var(--color-text-inverse);
    transition:
        background-color var(--motion-duration-fast) var(--motion-ease-standard),
        border-color var(--motion-duration-fast) var(--motion-ease-standard),
        box-shadow var(--motion-duration-fast) var(--motion-ease-standard);
}
.app-checkbox__input:hover:not(:disabled) + .app-checkbox__box {
    border-color: var(--color-text-muted);
}
.app-checkbox__input:focus-visible + .app-checkbox__box {
    box-shadow: var(--shadow-focus);
    border-color: var(--color-focus-ring);
}
.app-checkbox--checked .app-checkbox__box {
    background: var(--color-brand-primary);
    border-color: var(--color-brand-primary);
}

.app-checkbox--error .app-checkbox__box {
    border-color: var(--color-danger);
}

.app-checkbox__label {
    font-size: var(--font-size-sm);
    color: var(--color-text-primary);
    line-height: var(--line-height-snug);
}
.app-checkbox__required {
    color: var(--color-danger);
    margin-inline-start: var(--space-1);
}

.app-checkbox__hint {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    margin-inline-start: calc(20px + var(--space-3));
}
.app-checkbox__error {
    font-size: var(--font-size-xs);
    color: var(--color-danger);
    font-weight: var(--font-weight-medium);
    margin-inline-start: calc(20px + var(--space-3));
}
</style>
