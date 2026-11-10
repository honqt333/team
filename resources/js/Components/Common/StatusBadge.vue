<script setup>
defineProps({
    label: { type: String, required: true },
    showDot: { type: Boolean, default: true },
    dotClass: { type: [String, Array, Object], default: 'bg-current' },
    dotColor: { type: String, default: null },
});
</script>

<template>
    <span class="inline-flex items-center">
        <span
            v-if="showDot"
            aria-hidden="true"
            class="h-1.5 w-1.5 shrink-0 rounded-full"
            :class="dotClass"
            :style="dotColor ? { backgroundColor: dotColor } : undefined"
        />
        <span>{{ label }}</span>
    </span>
</template>
