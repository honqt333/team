<template>
    <a
        :href="href"
        class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-indigo-300 hover:shadow dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="flex items-start gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        :d="icon"
                    />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-gray-900 dark:text-white">
                    {{ title }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ description }}
                </p>
                <p
                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                        />
                    </svg>
                    {{ 'Download .xlsx' }}
                </p>
            </div>
        </div>
    </a>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    icon: { type: String, required: true },
    href: { type: String, required: true },
});
</script>
