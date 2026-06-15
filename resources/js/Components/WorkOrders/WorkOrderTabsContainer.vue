<template>
    <div>
        <!-- Tab bar (sticky-ish, scrolls horizontally when many tabs) -->
        <div class="px-6 pt-4">
            <div
                class="flex gap-1 border-b border-gray-200 dark:border-gray-700 overflow-x-auto pb-px">
                <button v-for="tab in tabs" :key="tab.key" @click="$emit('update:activeTab', tab.key)" :class="[
                    'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap -mb-px',
                    activeTab === tab.key
                        ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-800 text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                ]">
                    {{ tab.icon }} {{ tab.label }}
                </button>
            </div>
        </div>

        <!-- Tab content is provided by the parent via the default slot. -->
        <div class="p-6">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    /**
     * Tab descriptors. Shape: [{ key: string, label: string, icon: string }, ...]
     * The parent (Show.vue) derives this list from i18n + the
     * `enableSystematicInspections` flag, so the container stays generic.
     */
    tabs: { type: Array, required: true },
    activeTab: { type: String, required: true },
});

defineEmits(['update:activeTab']);
</script>
