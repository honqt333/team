<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        :style="{
            backgroundColor: 'var(--color-surface)',
            color: 'var(--color-text-primary)',
            borderColor: 'var(--color-border-strong)',
            '--tw-ring-color': 'var(--color-action-primary)',
        }"
    >
        <slot />
    </button>
</template>

<style scoped>
button:hover:not(:disabled) {
    background-color: var(--color-surface-muted);
}
button:focus-visible {
    box-shadow: var(--shadow-focus);
}
.dark button {
    background-color: var(--color-surface);
    color: var(--color-text-primary);
    border-color: var(--color-border-strong);
}
.dark button:hover:not(:disabled) {
    background-color: var(--color-surface-muted);
}
</style>
