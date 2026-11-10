<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';

defineProps({
    checked: {
        type: [Array, Boolean, String, Number],
        default: false,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
    },
    color: {
        type: String,
        default: 'violet',
    },
});

defineEmits(['update:checked']);
</script>

<template>
    <AppToggle
        :checked="checked"
        :value="value"
        :disabled="disabled"
        :size="size"
        :color="color"
        @update:checked="$emit('update:checked', $event)"
    />
</template>
