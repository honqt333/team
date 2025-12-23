<script setup>
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const model = defineModel();

const props = defineProps({
    error: {
        type: String,
        default: null,
    },
    color: {
        type: String,
        default: 'indigo',
    },
});

const colorClass = computed(() => {
    const colors = {
        indigo: 'focus:ring-indigo-500',
        teal: 'focus:ring-teal-500',
        orange: 'focus:ring-orange-500',
        red: 'focus:ring-red-500',
        green: 'focus:ring-green-500',
        blue: 'focus:ring-blue-500',
        purple: 'focus:ring-purple-500',
    };
    return colors[props.color] || `focus:ring-${props.color}-500`;
});
</script>

<template>
    <div class="relative">
        <select
            v-bind="$attrs"
            v-model="model"
            autocomplete="off"
            class="w-full appearance-none bg-none ps-4 pe-10 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
            :class="[
                colorClass,
                { 'border-red-500': error }
            ]"
        >
            <slot />
        </select>
        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>
</template>
