<template>
    <button
        class="inline-flex items-center rounded-lg border border-transparent px-4 py-2 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 active:bg-action-primary-active disabled:opacity-50 disabled:cursor-not-allowed"
        :style="{
            backgroundColor: 'var(--color-action-primary)',
            color: 'var(--color-action-on)',
            '--tw-ring-color': 'var(--color-action-primary)',
        }"
    >
        <slot />
    </button>
</template>

<style scoped>
button:hover:not(:disabled) {
    background-color: var(--color-action-primary-hover);
}
button:focus-visible {
    box-shadow: var(--shadow-focus);
}
</style>
