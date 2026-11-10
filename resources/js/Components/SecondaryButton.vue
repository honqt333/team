<script setup>
import AppButton from '@/Components/App/AppButton.vue';

defineProps({
    type: { type: String, default: 'button' },
});
</script>

<template>
    <AppButton variant="secondary" :type="type">
        <slot />
    </AppButton>
</template>
