<script setup>
import { Link } from '@inertiajs/vue3';
import Tooltip from '@/Components/Tooltip.vue';

defineProps({
    href: {
        type: String,
        required: true
    }
});
</script>

<template>
    <Tooltip :text="$t('common.back')">
        <Link :href="href"
            class="inline-flex p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 text-gray-600 dark:text-gray-300">
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </Link>
    </Tooltip>
</template>
