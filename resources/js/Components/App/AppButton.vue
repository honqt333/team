<script setup>
/**
 * AppButton — Carag V2 Design System primitive.
 *
 * Variants: primary (gold) | secondary (slate outline) | ghost | danger
 * Sizes:    sm | md | lg
 * Slots:    default (label), iconLeft, iconRight
 * Props:    as — "button" | "a" | "link" (Inertia Link)
 *           type — only meaningful when as === "button"
 *           loading — replaces icons with spinner, sets disabled
 *           disabled — applies disabled styles
 *           iconLeftPath / iconRightPath — raw SVG path strings
 *
 * All colors and spacings come from CSS variables in
 * resources/js/Design/tokens.css. Do not hard-code hex.
 */
import { computed, useSlots } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'ghost', 'danger'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    type: {
        type: String,
        default: 'button',
    },
    as: {
        type: String,
        default: 'button',
        validator: (v) => ['button', 'a', 'link'].includes(v),
    },
    href: {
        type: [String, Object],
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
    iconLeftPath: {
        type: String,
        default: null,
    },
    iconRightPath: {
        type: String,
        default: null,
    },
});

const slots = useSlots();

const isDisabled = computed(() => props.disabled || props.loading);
const showLabel = computed(() => !!slots.default);

const rootClass = computed(() => [
    'app-btn',
    `app-btn--${props.variant}`,
    `app-btn--${props.size}`,
    {
        'app-btn--block': props.block,
        'app-btn--loading': props.loading,
        'app-btn--icon-only': !showLabel.value,
    },
]);

const iconSize = computed(() => {
    switch (props.size) {
        case 'sm':
            return 14;
        case 'lg':
            return 20;
        default:
            return 16;
    }
});
</script>

<template>
    <component
        :is="as === 'link' ? Link : as"
        :href="as === 'button' ? undefined : href"
        :to="as === 'link' ? href : undefined"
        :type="as === 'button' ? type : undefined"
        :class="rootClass"
        :disabled="as === 'button' ? isDisabled : undefined"
        :aria-disabled="isDisabled || undefined"
        :aria-busy="loading || undefined"
    >
        <span v-if="loading" class="app-btn__spinner" aria-hidden="true">
            <svg
                :width="iconSize"
                :height="iconSize"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                    opacity="0.25"
                />
                <path
                    d="M22 12a10 10 0 0 1-10 10"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                />
            </svg>
        </span>
        <span v-else-if="iconLeftPath || slots.iconLeft" class="app-btn__icon app-btn__icon--left">
            <slot name="iconLeft">
                <svg
                    :width="iconSize"
                    :height="iconSize"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path :d="iconLeftPath" />
                </svg>
            </slot>
        </span>

        <span v-if="showLabel" class="app-btn__label">
            <slot />
        </span>

        <span
            v-if="!loading && (iconRightPath || slots.iconRight)"
            class="app-btn__icon app-btn__icon--right"
        >
            <slot name="iconRight">
                <svg
                    :width="iconSize"
                    :height="iconSize"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path :d="iconRightPath" />
                </svg>
            </slot>
        </span>
    </component>
</template>

<style scoped>
/* ============================================================
 * AppButton — token-driven styles. No hard-coded colors.
 * ============================================================ */
.app-btn {
    --app-btn-bg: transparent;
    --app-btn-fg: var(--color-text-primary);
    --app-btn-border: transparent;
    --app-btn-bg-hover: transparent;
    --app-btn-bg-active: transparent;
    --app-btn-border-hover: transparent;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    border: 1px solid var(--app-btn-border);
    background: var(--app-btn-bg);
    color: var(--app-btn-fg);
    border-radius: var(--radius-lg);
    font-family: var(--font-family-sans);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
    transition:
        background-color var(--motion-duration-fast) var(--motion-ease-standard),
        color var(--motion-duration-fast) var(--motion-ease-standard),
        border-color var(--motion-duration-fast) var(--motion-ease-standard),
        box-shadow var(--motion-duration-fast) var(--motion-ease-standard),
        transform var(--motion-duration-fast) var(--motion-ease-standard);
    box-shadow: var(--shadow-none);
}

.app-btn:hover:not(:disabled):not([aria-disabled='true']) {
    background: var(--app-btn-bg-hover);
    border-color: var(--app-btn-border-hover);
}

.app-btn:active:not(:disabled):not([aria-disabled='true']) {
    background: var(--app-btn-bg-active);
    transform: translateY(1px);
}

.app-btn:focus-visible {
    box-shadow: var(--shadow-focus);
    outline: none;
}

.app-btn:disabled,
.app-btn[aria-disabled='true'] {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
}

.app-btn--block {
    display: flex;
    width: 100%;
}

/* --- Sizes --- */
.app-btn--sm {
    height: var(--size-control-sm);
    padding-inline: var(--space-3);
    font-size: var(--font-size-sm);
}
.app-btn--md {
    height: var(--size-control-md);
    padding-inline: var(--space-4);
    font-size: var(--font-size-sm);
}
.app-btn--lg {
    height: var(--size-control-lg);
    padding-inline: var(--space-6);
    font-size: var(--font-size-base);
}

/* --- Variants --- */

/* primary: brand gold */
.app-btn--primary {
    --app-btn-bg: var(--color-brand-primary);
    --app-btn-fg: var(--color-brand-on);
    --app-btn-border: var(--color-brand-primary);
    --app-btn-bg-hover: var(--color-brand-primary-hover);
    --app-btn-bg-active: var(--color-brand-primary-active);
    --app-btn-border-hover: var(--color-brand-primary-hover);
    box-shadow: var(--shadow-sm);
}
.app-btn--primary:hover:not(:disabled):not([aria-disabled='true']) {
    box-shadow: var(--shadow-md);
}

/* secondary: slate outline on neutral surface */
.app-btn--secondary {
    --app-btn-bg: var(--color-surface);
    --app-btn-fg: var(--color-text-primary);
    --app-btn-border: var(--color-border-strong);
    --app-btn-bg-hover: var(--color-surface-muted);
    --app-btn-bg-active: var(--color-surface-muted);
    --app-btn-border-hover: var(--color-text-muted);
    box-shadow: var(--shadow-xs);
}

/* ghost: text-only */
.app-btn--ghost {
    --app-btn-bg: transparent;
    --app-btn-fg: var(--color-text-primary);
    --app-btn-border: transparent;
    --app-btn-bg-hover: var(--color-surface-muted);
    --app-btn-bg-active: var(--color-surface-muted);
    --app-btn-border-hover: transparent;
}

/* danger: red */
.app-btn--danger {
    --app-btn-bg: var(--color-danger);
    --app-btn-fg: var(--color-text-inverse);
    --app-btn-border: var(--color-danger);
    --app-btn-bg-hover: color-mix(in srgb, var(--color-danger) 88%, black);
    --app-btn-bg-active: color-mix(in srgb, var(--color-danger) 78%, black);
    --app-btn-border-hover: var(--color-danger);
    box-shadow: var(--shadow-sm);
}

/* --- Loading --- */
.app-btn--loading {
    cursor: progress;
}
.app-btn__spinner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    animation: app-btn-spin 0.7s linear infinite;
}

@keyframes app-btn-spin {
    to {
        transform: rotate(360deg);
    }
}

/* --- Icon-only (no label slot) --- */
.app-btn--icon-only.app-btn--sm {
    width: var(--size-control-sm);
    padding-inline: 0;
}
.app-btn--icon-only.app-btn--md {
    width: var(--size-control-md);
    padding-inline: 0;
}
.app-btn--icon-only.app-btn--lg {
    width: var(--size-control-lg);
    padding-inline: 0;
}

.app-btn__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
