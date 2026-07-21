<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    userName: { type: String, default: null },
    date: { type: String, default: null },
    greeting: { type: String, default: null },
});
</script>

<template>
    <div
        class="border-b border-slate-200 bg-white px-6 py-5 dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p
                    v-if="greeting"
                    class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                    {{ greeting }}
                </p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-white">
                    {{ title }}
                    <span v-if="userName">
                        ,
                        <span class="text-indigo-600 dark:text-indigo-400">{{ userName }}</span>
                    </span>
                </h1>
                <p v-if="subtitle" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ subtitle }}
                </p>
            </div>
            <div
                v-if="date"
                class="flex items-center gap-2 self-start rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                {{ date }}
            </div>
        </div>
    </div>
</template>
