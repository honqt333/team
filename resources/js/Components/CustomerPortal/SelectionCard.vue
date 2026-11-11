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
        class="block w-full rounded-2xl border bg-white p-4 text-start shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4"
        :class="selected ? 'border-transparent' : 'border-slate-200 hover:border-slate-300'"
        :style="
            selected
                ? {
                      backgroundColor:
                          'color-mix(in srgb, var(--tenant-primary, #2563eb) 8%, transparent)',
                      '--tw-ring-color': 'var(--tenant-primary, #2563eb)',
                  }
                : {}
        "
        @click="$emit('select')"
    >
        <div class="flex items-start gap-3">
            <div
                v-if="icon"
                class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="selected ? 'text-white' : 'bg-slate-100 text-slate-500'"
                :style="selected ? { backgroundColor: 'var(--tenant-primary, #2563eb)' } : {}"
                v-html="icon"
            />
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-sm">{{ title }}</span>
                    <span
                        v-if="badge"
                        class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full"
                        :style="
                            selected
                                ? {
                                      backgroundColor:
                                          'color-mix(in srgb, var(--tenant-primary, #2563eb) 18%, transparent)',
                                      color: 'var(--tenant-primary, #2563eb)',
                                  }
                                : {
                                      backgroundColor:
                                          'color-mix(in srgb, var(--tenant-primary, #2563eb) 12%, transparent)',
                                      color: 'var(--tenant-primary, #2563eb)',
                                  }
                        "
                    >
                        {{ badge }}
                    </span>
                </div>
                <p v-if="subtitle" class="mt-0.5 text-xs text-slate-500">
                    {{ subtitle }}
                </p>
                <p v-if="meta" class="mt-1 text-xs text-slate-400">
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
    // When set, render as <a> (rarely — the booking flow
    // usually wants a button). Default is a button.
    href: { type: String, default: '' },
});

defineEmits(['select']);
</script>
