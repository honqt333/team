<template>
    <!--
        SelectionCard — a tappable card that selects an item
        (center, vehicle, service) and shows a checked state.

        Used across all three steps of the booking flow and on
        the reschedule branch picker. The visual style matches
        the existing pages so the redesign is a refactor, not
        a rewrite.
    -->
    <component
        :is="href ? 'a' : 'button'"
        :type="href ? undefined : 'button'"
        :href="href"
        :disabled="disabled && !href ? true : undefined"
        :aria-disabled="disabled ? 'true' : undefined"
        class="block w-full rounded-2xl border bg-surface p-4 text-start shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-sm"
        :class="selected ? 'border-transparent' : 'border-border hover:border-border-strong'"
        :style="
            selected
                ? {
                      backgroundColor:
                          'color-mix(in srgb, var(--tenant-primary, #2563eb) 8%, transparent)',
                      '--tw-ring-color': 'var(--tenant-primary, #2563eb)',
                  }
                : { '--tw-ring-color': 'var(--color-focus-ring, #2563eb)' }
        "
        @click="!disabled && $emit('select')"
    >
        <div class="flex items-start gap-3">
            <div
                v-if="icon"
                class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="selected ? 'text-white' : 'bg-surface-muted text-text-secondary'"
                :style="selected ? { backgroundColor: 'var(--tenant-primary, #2563eb)' } : {}"
                v-safe-html="icon"
            />
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-sm text-text-primary">{{ title }}</span>
                    <span
                        v-if="badge"
                        class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full bg-selection-bg text-selection-text"
                    >
                        {{ badge }}
                    </span>
                </div>
                <p v-if="subtitle" class="mt-0.5 text-xs text-text-secondary">
                    {{ subtitle }}
                </p>
                <p v-if="meta" class="mt-1 text-xs text-text-secondary">
                    {{ meta }}
                </p>
            </div>
        </div>
    </component>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    meta: { type: String, default: '' },
    badge: { type: String, default: '' },
    icon: { type: String, default: '' },
    selected: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    // When set, render as <a> (rarely — the booking flow
    // usually wants a button). Default is a button.
    href: { type: String, default: '' },
});

defineEmits(['select']);
</script>
