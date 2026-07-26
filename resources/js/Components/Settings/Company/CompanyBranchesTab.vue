<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $t('company_profile.branches.title') }}
            </h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ branches?.length || 0 }} {{ $t('company_profile.branches.count_label') }}
            </span>
        </div>

        <div
            v-if="branches && branches.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
        >
            <div
                v-for="branch in branches"
                :key="branch.id"
                class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between mb-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold"
                    >
                        {{ branch.name?.charAt(0) || 'F' }}
                    </div>
                    <span
                        :class="[
                            'px-2.5 py-0.5 rounded-full text-xs font-medium',
                            branch.is_active
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        ]"
                    >
                        {{ branch.is_active ? $t('common.active') : $t('common.inactive') }}
                    </span>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white text-base mb-1">
                    {{ branch.name }}
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                    {{ branch.slug }}
                </p>
            </div>
        </div>

        <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
            {{ $t('company_profile.branches.no_branches') }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    branches: Array,
});
</script>
