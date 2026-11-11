<script setup>
defineProps({ result: { type: Object, required: true } });
</script>

<template>
    <section aria-live="polite" class="mt-4 space-y-2">
        <p class="text-sm font-semibold text-text-primary">
            {{ $t('common.imported_rows', { count: result.imported }) }}
        </p>
        <div v-if="result.errors?.length" class="rounded-xl bg-danger/10 p-4 text-danger">
            <h4 class="text-sm font-semibold">{{ $t('customers.import.errors') }}</h4>
            <ul class="mt-2 max-h-32 list-inside list-disc overflow-y-auto text-sm">
                <li v-for="(message, index) in result.errors" :key="index">{{ message }}</li>
            </ul>
        </div>
    </section>
</template>
