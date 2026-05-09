<template>
    <AppLayout :title="$t('invoices.hub.title')">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('invoices.hub.title') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $t('invoices.hub.subtitle') }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl">
                <!-- Sales Invoices Card -->
                <Link
                    :href="route('app.invoices.sales.index')"
                    class="group relative bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-8 border border-blue-200 dark:border-blue-800 hover:border-blue-400 dark:hover:border-blue-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:shadow-xl group-hover:shadow-blue-500/40 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        {{ $t('invoices.hub.sales_invoices') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('invoices.hub.sales_invoices_hint') }}
                    </p>
                    <!-- Count Badge -->
                    <div v-if="salesCount > 0" class="absolute top-4 end-4 px-3 py-1 bg-blue-500 text-white text-sm font-bold rounded-full shadow-lg">
                        {{ toEnglish(salesCount) }}
                    </div>
                </Link>

                <!-- Purchase Invoices Card -->
                <Link
                    :href="route('app.invoices.purchases.index')"
                    class="group relative bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-8 border border-amber-200 dark:border-amber-800 hover:border-amber-400 dark:hover:border-amber-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30 group-hover:shadow-xl group-hover:shadow-amber-500/40 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                        {{ $t('invoices.hub.purchase_invoices') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('invoices.hub.purchase_invoices_hint') }}
                    </p>
                    <!-- Count Badge -->
                    <div v-if="purchasesCount > 0" class="absolute top-4 end-4 px-3 py-1 bg-amber-500 text-white text-sm font-bold rounded-full shadow-lg">
                        {{ toEnglish(purchasesCount) }}
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

defineProps({
    salesCount: {
        type: Number,
        default: 0,
    },
    purchasesCount: {
        type: Number,
        default: 0,
    },
});
</script>
