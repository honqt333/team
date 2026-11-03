<template>
    <button
        class="inline-flex items-center rounded-lg border border-transparent px-4 py-2 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 active:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed"
        :style="{
            backgroundColor: 'var(--color-danger)',
            color: 'var(--color-text-inverse)',
            '--tw-ring-color': 'var(--color-danger)',
        }"
    >
        <slot />
    </button>
</template>

<style scoped>
button:hover:not(:disabled) {
    background-color: #b91c1c;
}
button:focus-visible {
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.4);
}
</style>
