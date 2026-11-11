<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="border-border-strong bg-surface text-text-primary focus:border-action-primary focus:ring-focus-ring rounded-md shadow-sm"
        v-model="model"
        ref="input"
    />
</template>
