<script setup>
/**
 * PortalEmptyState — the shared "nothing here yet" card used by every
 * customer portal list page. An optional default slot renders a CTA.
 */
defineProps({
    message: { type: String, required: true },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div
        class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-10 text-center shadow-sm"
    >
        <div
            class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                />
            </svg>
        </div>
        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ message }}</p>
        <p v-if="hint" class="mt-1 text-xs text-slate-400 max-w-sm mx-auto">{{ hint }}</p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>
