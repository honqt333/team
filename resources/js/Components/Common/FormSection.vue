<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <section class="bg-surface rounded-2xl border border-border p-5 sm:p-6 shadow-xs">
        <!-- Section Header -->
        <header
            v-if="title || $slots.title || description || $slots.description"
            class="mb-5 pb-4 border-b border-border flex items-start justify-between gap-4"
        >
            <div>
                <h3
                    class="text-base sm:text-lg font-bold text-text-primary flex items-center gap-2"
                >
                    <slot name="icon" />
                    <slot name="title">{{ title }}</slot>
                </h3>
                <p
                    v-if="description || $slots.description"
                    class="text-xs sm:text-sm text-text-muted mt-1"
                >
                    <slot name="description">{{ description }}</slot>
                </p>
            </div>
            <div v-if="$slots.actions" class="shrink-0 flex items-center gap-2">
                <slot name="actions" />
            </div>
        </header>

        <!-- Section Content -->
        <div class="space-y-4">
            <slot />
        </div>
    </section>
</template>
