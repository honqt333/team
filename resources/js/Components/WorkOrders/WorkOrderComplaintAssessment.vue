<template>
    <!-- Only render when at least one of the two free-text fields is filled. -->
    <div v-if="workOrder.customer_complaint || workOrder.initial_assessment"
        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Customer complaint (the car owner's words) -->
            <div v-if="workOrder.customer_complaint"
                class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-700/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">💬</span>
                    <h4 class="font-semibold text-orange-700 dark:text-orange-300">
                        {{ $t('quotes.show.customer_complaint') }}
                    </h4>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    {{ workOrder.customer_complaint }}
                </p>
            </div>

            <!-- Initial assessment (technician's first impressions) -->
            <div v-if="workOrder.initial_assessment"
                class="bg-teal-50 dark:bg-teal-900/20 rounded-xl p-4 border border-teal-200 dark:border-teal-700/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">📋</span>
                    <h4 class="font-semibold text-teal-700 dark:text-teal-300">
                        {{ $t('quotes.show.initial_assessment') }}
                    </h4>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    {{ workOrder.initial_assessment }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    workOrder: { type: Object, required: true },
});
</script>
