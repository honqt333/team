<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';
const props = defineProps({ status: String, label: { type: String, required: true } });
const badgeClass = computed(() => {
    const base = 'inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ';
    switch (props.status) {
        case 'paid':
            return (
                base +
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
            );
        case 'partial':
            return base + 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300';
        case 'unpaid':
            return base + 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300';
        default:
            return base + 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }
});
</script>

<template>
    <StatusBadge :class="badgeClass" :label="label" :show-dot="false" />
</template>
