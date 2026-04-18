<template>
    <Link :href="'/'" class="flex flex-col items-center gap-4">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-500/10 to-blue-500/10 flex items-center justify-center border border-violet-100 dark:border-violet-900/50">
            <img src="/images/logo.png" alt="Khidma Pro" class="w-12 h-12 object-contain">
        </div>
        <div class="flex flex-col items-center">
            <span class="font-bold text-3xl text-gray-900 dark:text-white font-cairo">
                {{ $t('common.app_name') }}
            </span>
             <span class="text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 font-medium mt-1">
                {{ $t('common.app_slogan') }}
            </span>
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
.font-cairo {
    font-family: 'Cairo', system-ui, sans-serif;
}
</style>
