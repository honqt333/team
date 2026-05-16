<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    totalCount: {
        type: [Number, String],
        default: null
    },
    countLabel: {
        type: String,
        default: ''
    },
    gradientFrom: {
        type: String,
        default: 'from-indigo-600'
    },
    gradientTo: {
        type: String,
        default: 'to-purple-600'
    },
    glowFrom: {
        type: String,
        default: 'from-indigo-500'
    },
    glowTo: {
        type: String,
        default: 'to-purple-600'
    },
    badgeBg: {
        type: String,
        default: 'bg-indigo-50/50 dark:bg-indigo-900/30'
    },
    badgeText: {
        type: String,
        default: 'text-indigo-600 dark:text-indigo-400'
    },
    badgeBorder: {
        type: String,
        default: 'border-indigo-100/50 dark:border-indigo-800/30'
    },
    badgeDot: {
        type: String,
        default: 'bg-indigo-500'
    }
});
</script>

<template>
    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r rounded-[2.5rem] blur opacity-[0.04] group-hover:opacity-[0.07] transition duration-700"
                            :class="[glowFrom, glowTo]"></div>

        <div class="relative z-30 bg-white dark:bg-gray-800 rounded-[2.25rem] p-6 border border-gray-100 dark:border-gray-700 shadow-xl"
            :class="glowFrom.replace('from-', 'shadow-') + '/5'">
            <!-- Decorative Blobs Container -->
            <div class="absolute inset-0 rounded-[2.25rem] overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full blur-3xl opacity-20"
                    :class="glowFrom.replace('from-', 'bg-')"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 rounded-full blur-3xl opacity-20"
                    :class="glowTo.replace('to-', 'bg-')"></div>

                <!-- Decorative SVG Pattern -->
                <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" :class="badgeText">
                    <svg class="h-full w-full" fill="none">
                        <defs>
                            <pattern id="header-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M0 40V.5H40" stroke="currentColor" stroke-width="0.5" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#header-pattern)" />
                    </svg>
                </div>
            </div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-5 flex-1">
                    <!-- Back Slot -->
                    <div v-if="$slots.back">
                        <slot name="back" />
                    </div>

                    <!-- Icon Slot -->
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br shadow-xl flex items-center justify-center text-white shrink-0 ring-4 ring-gray-50 dark:ring-gray-900/20 transition-transform group-hover:scale-[1.03] duration-500"
                        :class="[gradientFrom, gradientTo, glowFrom.replace('from-', 'shadow-') + '/35']">
                        <slot name="icon" />
                    </div>

                    <!-- Title and Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-4 mb-2">
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight truncate">
                                {{ title }}
                            </h1>
                            <!-- Count Badge -->
                            <div v-if="totalCount !== null" class="flex items-center gap-2 px-3 py-1 rounded-full text-xs font-black border shadow-sm"
                                :class="[badgeBg, badgeText, badgeBorder]">
                                <span class="w-2 h-2 rounded-full animate-pulse" :class="badgeDot"></span>
                                {{ totalCount }} {{ countLabel }}
                            </div>
                        </div>
                        
                        <!-- Identity Slot (for tags, phone, etc) -->
                        <div v-if="$slots.identity">
                            <slot name="identity" />
                        </div>
                        <p v-else-if="subtitle" class="hidden sm:block text-gray-500 dark:text-gray-400 font-bold">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <!-- Extra Slot (for stats or secondary info) -->
                <div v-if="$slots.extra" class="flex-shrink-0">
                    <slot name="extra" />
                </div>

                <!-- Actions Slot -->
                <div class="flex flex-wrap items-center gap-4">
                    <slot name="actions" />
                </div>
            </div>

            <!-- Filters/Footer Slot -->
            <div v-if="$slots.filters || $slots.footer" class="relative z-10 mt-8 pt-6 border-t border-gray-100 dark:border-gray-700/50">
                <slot name="filters" />
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
