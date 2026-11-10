<template>
    <PurchasingInvoicesIndex v-bind="$props" />
</template>

<script setup>
import PurchasingInvoicesIndex from '@/Pages/Invoices/Purchasing/Index.vue';

defineProps({
    invoices: { type: Object, default: () => ({}) },
    returns: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    defaultWarehouse: { type: Object, default: () => null },
    warehouses: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});
</script>
