<script setup>
import AppButton from '@/Components/App/AppButton.vue';

defineProps({
    type: { type: String, default: 'submit' },
});
</script>

<template>
    <AppButton variant="danger" :type="type">
        <slot />
    </AppButton>
</template>
