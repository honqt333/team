<script setup>
defineProps({
    count: {
        type: Number,
        default: 4,
    },
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
        <div
            v-for="i in count"
            :key="i"
            class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex flex-col justify-between h-32"
        >
            <div class="flex items-center justify-between">
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-md w-24"></div>
                <div class="w-9 h-9 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
            </div>
            <div class="space-y-2">
                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-lg w-20"></div>
                <div class="h-2.5 bg-gray-150 dark:bg-gray-700/60 rounded-md w-32"></div>
            </div>
        </div>
    </div>
</template>
