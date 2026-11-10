<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    action: { type: String, default: null },
});
defineEmits(['action']);
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs transition-shadow hover:shadow-xs dark:border-slate-800 dark:bg-slate-800/90"
    >
        <header
            class="flex items-center justify-between gap-2 border-b border-slate-100/80 px-5 py-3.5 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50"
        >
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                    {{ title }}
                </h3>
                <p
                    v-if="subtitle"
                    class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400"
                >
                    {{ subtitle }}
                </p>
            </div>
            <button
                v-if="action"
                @click="$emit('action')"
                class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ action }}
            </button>
        </header>
        <div class="p-5">
            <slot />
        </div>
    </section>
</template>
