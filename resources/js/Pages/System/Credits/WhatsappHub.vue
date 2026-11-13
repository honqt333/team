<template>
    <CreditsHub
        channel="whatsapp"
        :tab="tab"
        :packages="packages"
        :balances="balances"
        :purchases="purchases"
        :logs="logs"
        :stats="stats"
        :filters="filters"
    />
</template>

<script setup>
import CreditsHub from './CreditsHub.vue';

defineProps({
    tab: { type: String, default: 'packages' },
    packages: { type: Array, default: () => [] },
    balances: { type: Object, default: () => ({}) },
    purchases: { type: Object, default: () => ({}) },
    logs: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});
</script>
