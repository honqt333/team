<script setup>
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';

defineProps({ disabled: Boolean });
const file = defineModel();
const emit = defineEmits(['invalid']);
</script>

<template>
    <AppFileUpload
        v-model="file"
        accept=".xlsx,.xls,.csv"
        :disabled="disabled"
        :upload-button-text="$t('customers.import.drag_drop')"
        :hint="$t('customers.import.supported_formats')"
        @invalid="emit('invalid')"
    />
</template>
