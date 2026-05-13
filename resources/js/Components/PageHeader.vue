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
    }
});
</script>

<template>
    <div class="relative group">
        <!-- Main Background with Glow -->
        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2.5rem] blur opacity-[0.04] group-hover:opacity-[0.07] transition duration-700"></div>

        <div class="relative z-30 bg-white dark:bg-gray-800 rounded-[2.25rem] p-6 border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5">
            <!-- Decorative Blobs Container (with overflow-hidden to clip them) -->
            <div class="absolute inset-0 rounded-[2.25rem] overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500/5 dark:bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <!-- Icon Slot -->
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 shadow-xl shadow-indigo-500/35 flex items-center justify-center text-white shrink-0 ring-4 ring-indigo-50 dark:ring-indigo-900/20 transition-transform group-hover:scale-[1.03] duration-500">
                        <slot name="icon" />
                    </div>

                    <!-- Title and Info -->
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                                {{ title }}
                            </h1>
                            <!-- Count Badge -->
                            <div v-if="totalCount !== null" class="flex items-center gap-2 px-3 py-1 bg-indigo-50/50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full text-xs font-black border border-indigo-100/50 dark:border-indigo-800/30 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                {{ totalCount }} {{ countLabel }}
                            </div>
                        </div>
                        <p v-if="subtitle" class="hidden sm:block text-gray-500 dark:text-gray-400 font-bold">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <!-- Actions Slot -->
                <div class="flex flex-wrap items-center gap-4">
                    <slot name="actions" />
                </div>
            </div>

            <!-- Filters Slot -->
            <div v-if="$slots.filters" class="relative z-10 mt-8 pt-6 border-t border-gray-100 dark:border-gray-700/50">
                <slot name="filters" />
            </div>
        </div>
    </div>
</template>
