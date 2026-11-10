<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    actionText: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: '',
    },
    secondaryActionText: {
        type: String,
        default: '',
    },
    secondaryActionHref: {
        type: String,
        default: '',
    },
});

defineEmits(['action', 'secondaryAction']);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center p-8 sm:p-12 text-center rounded-2xl bg-surface-muted/40 border border-dashed border-border transition-all"
    >
        <!-- Icon Container -->
        <div
            class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-selection-bg text-action-primary flex items-center justify-center mb-4 shadow-sm ring-8 ring-selection-ring/15"
        >
            <slot name="icon">
                <svg
                    class="w-7 h-7 sm:w-8 sm:h-8"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.75"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                </svg>
            </slot>
        </div>

        <!-- Title -->
        <h3 class="text-base sm:text-lg font-bold text-text-primary mb-1.5">
            <slot name="title">{{ title }}</slot>
        </h3>

        <!-- Description -->
        <p
            v-if="description || $slots.description"
            class="text-xs sm:text-sm text-text-secondary max-w-md mx-auto mb-6 leading-relaxed"
        >
            <slot name="description">{{ description }}</slot>
        </p>

        <!-- Actions -->
        <div class="flex flex-wrap items-center justify-center gap-3">
            <slot name="actions">
                <!-- Primary Action -->
                <template v-if="actionText">
                    <Link
                        v-if="actionHref"
                        :href="actionHref"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-action-primary hover:bg-action-primary-hover text-action-on rounded-xl text-xs sm:text-sm font-semibold transition-all shadow-sm hover:shadow-md active:scale-95"
                    >
                        <span>{{ actionText }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                    <button
                        v-else
                        type="button"
                        @click="$emit('action')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-action-primary hover:bg-action-primary-hover text-action-on rounded-xl text-xs sm:text-sm font-semibold transition-all shadow-sm hover:shadow-md active:scale-95 cursor-pointer"
                    >
                        <span>{{ actionText }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </template>

                <!-- Secondary Action -->
                <template v-if="secondaryActionText">
                    <Link
                        v-if="secondaryActionHref"
                        :href="secondaryActionHref"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface hover:bg-surface-muted text-text-primary border border-border rounded-xl text-xs sm:text-sm font-semibold transition-all shadow-sm active:scale-95"
                    >
                        <span>{{ secondaryActionText }}</span>
                    </Link>
                    <button
                        v-else
                        type="button"
                        @click="$emit('secondaryAction')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface hover:bg-surface-muted text-text-primary border border-border rounded-xl text-xs sm:text-sm font-semibold transition-all shadow-sm active:scale-95 cursor-pointer"
                    >
                        <span>{{ secondaryActionText }}</span>
                    </button>
                </template>
            </slot>
        </div>

        <!-- Extra slot -->
        <div v-if="$slots.extra" class="mt-6 w-full">
            <slot name="extra" />
        </div>
    </div>
</template>
