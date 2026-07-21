<template>
    <Head :title="$t('subscription.failed_title')" />

    <AppLayout>
        <div class="max-w-2xl mx-auto py-12 px-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center"
            >
                <!-- Error icon -->
                <div
                    class="mx-auto mb-6 inline-flex h-20 w-20 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/30"
                >
                    <svg
                        class="h-10 w-10 text-rose-600 dark:text-rose-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $t('subscription.failed_heading') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    {{ $t('subscription.failed_subtitle') }}
                </p>
                <p
                    v-if="reason"
                    class="text-rose-600 dark:text-rose-400 text-sm font-mono mb-6 p-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg"
                >
                    {{ reason }}
                </p>
                <p v-else class="mb-6"></p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        :href="route('app.subscription.index')"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
                    >
                        {{ $t('subscription.try_again') }}
                    </Link>
                    <a
                        href="mailto:support@carag.app"
                        class="px-6 py-2.5 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg font-medium transition-colors"
                    >
                        {{ $t('subscription.contact_support') }}
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    payment: { type: Object, default: null },
    reason: { type: String, default: null },
});
</script>
