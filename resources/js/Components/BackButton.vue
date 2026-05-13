<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true
    }
});
</script>

<template>
    <Link :href="href"
        class="group inline-flex items-center gap-2.5 px-4 py-2 text-sm font-bold text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-500/50 transition-all duration-300">
        <svg class="w-5 h-5 rtl:rotate-180 group-hover:-translate-x-1 rtl:group-hover:translate-x-1 transition-transform duration-300" 
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>{{ $t('common.back') }}</span>
    </Link>
</template>
