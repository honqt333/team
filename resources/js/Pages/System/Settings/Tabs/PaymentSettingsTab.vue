<script setup>
import PaymentSettingsForm from '@/Components/System/PaymentSettingsForm.vue';

defineProps({ settings: Object });
</script>

<template>
    <PaymentSettingsForm :settings="settings" />
</template>
