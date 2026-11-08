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

                <!-- Auto-redirect notice -->
                <div
                    class="flex items-center justify-center gap-2 mb-4 text-sm text-gray-500 dark:text-gray-400"
                >
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>
                        {{ $t('subscription.redirecting_in', { seconds: countdown }) }}
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link :href="route('app.billing.index')" class="px-6 py-2.5 btn-primary">
                        {{ $t('subscription.try_again') }}
                    </Link>
                    <a
                        href="mailto:support@khidmh.pro"
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
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payment: { type: Object, default: null },
    reason: { type: String, default: null },
});

// Auto-redirect to billing hub (Stripe-style "you can also view this in
// the dashboard" pattern) so the tenant ends up at the unified place
// where they can retry with a different gateway or update their plan.
const countdown = ref(5);
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        countdown.value -= 1;
        if (countdown.value <= 0) {
            clearInterval(timer);
            router.visit(route('app.billing.index'));
        }
    }, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>
