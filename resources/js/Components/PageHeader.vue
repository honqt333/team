<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    totalCount: {
        type: [Number, String],
        default: null,
    },
    countLabel: {
        type: String,
        default: '',
    },
    gradientFrom: {
        type: String,
        default: 'from-action-primary',
    },
    gradientTo: {
        type: String,
        default: 'to-purple-600',
    },
    glowFrom: {
        type: String,
        default: 'from-action-primary',
    },
    glowTo: {
        type: String,
        default: 'to-purple-600',
    },
    badgeBg: {
        type: String,
        default: 'bg-selection-bg',
    },
    badgeText: {
        type: String,
        default: 'text-action-primary',
    },
    badgeBorder: {
        type: String,
        default: 'border-action-primary/25',
    },
    badgeDot: {
        type: String,
        default: 'bg-action-primary',
    },
});
</script>

<template>
    <div class="relative group">
        <!-- Subtle Glow Layer -->
        <div
            class="absolute -inset-0.5 bg-gradient-to-r rounded-2xl blur-sm opacity-5 group-hover:opacity-10 transition duration-500 pointer-events-none"
            :class="[glowFrom, glowTo]"
        ></div>

        <div
            class="relative z-20 bg-white dark:bg-gray-800/95 backdrop-blur-md rounded-2xl p-4 sm:p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm"
        >
            <!-- Decorative Subtle Accent -->
            <div class="absolute inset-0 rounded-2xl overflow-hidden pointer-events-none">
                <div
                    class="absolute -top-16 -right-16 w-40 h-40 rounded-full blur-2xl opacity-10"
                    :class="glowFrom.replace('from-', 'bg-')"
                ></div>
                <div
                    class="absolute -bottom-16 -left-16 w-40 h-40 rounded-full blur-2xl opacity-10"
                    :class="glowTo.replace('to-', 'bg-')"
                ></div>
            </div>

            <div
                class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3.5 sm:gap-4 flex-1 min-w-0">
                    <!-- Back Slot -->
                    <div v-if="$slots.back" class="shrink-0">
                        <slot name="back" />
                    </div>

                    <!-- Icon Slot -->
                    <div
                        v-if="$slots.icon"
                        class="w-12 h-12 sm:w-13 sm:h-13 rounded-xl bg-gradient-to-br shadow-md flex items-center justify-center text-white shrink-0 ring-2 ring-gray-100 dark:ring-gray-700/60 transition-transform group-hover:scale-105 duration-300"
                        :class="[
                            gradientFrom,
                            gradientTo,
                            glowFrom.replace('from-', 'shadow-') + '/25',
                        ]"
                    >
                        <slot name="icon" />
                    </div>

                    <!-- Title and Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2.5 sm:gap-3 flex-wrap">
                            <h1
                                class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight truncate"
                            >
                                {{ title }}
                            </h1>
                            <!-- Count Badge -->
                            <div
                                v-if="totalCount !== null && totalCount !== undefined"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border shadow-2xs"
                                :class="[badgeBg, badgeText, badgeBorder]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full animate-pulse"
                                    :class="badgeDot"
                                ></span>
                                <span>{{ totalCount }} {{ countLabel }}</span>
                            </div>
                        </div>

                        <!-- Identity Slot (for tags, phone, etc) -->
                        <div v-if="$slots.identity" class="mt-1">
                            <slot name="identity" />
                        </div>
                        <p
                            v-else-if="subtitle"
                            class="mt-0.5 text-xs sm:text-sm text-gray-500 dark:text-gray-400 line-clamp-1"
                        >
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <!-- Extra Slot (for stats or secondary info) -->
                <div v-if="$slots.extra" class="shrink-0">
                    <slot name="extra" />
                </div>

                <!-- Actions Slot -->
                <div
                    v-if="$slots.actions"
                    class="flex flex-wrap items-center gap-2.5 sm:gap-3 shrink-0"
                >
                    <slot name="actions" />
                </div>
            </div>

            <!-- Filters/Footer Slot -->
            <div
                v-if="$slots.filters || $slots.footer"
                class="relative z-10 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60"
            >
                <slot name="filters" />
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
