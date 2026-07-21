<template>
    <Head :title="$t('subscription.success_title')" />

    <AppLayout>
        <div class="max-w-2xl mx-auto py-12 px-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center"
            >
                <!-- Success icon -->
                <div
                    class="mx-auto mb-6 inline-flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30"
                >
                    <svg
                        class="h-10 w-10 text-emerald-600 dark:text-emerald-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $t('subscription.success_heading') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ $t('subscription.success_subtitle') }}
                </p>

                <!-- Payment details -->
                <div
                    v-if="payment"
                    class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6 text-right"
                >
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $t('subscription.plan') }}
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{
                                    payment.subscription?.plan?.name_ar ||
                                    payment.subscription?.plan?.name_en ||
                                    '-'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $t('subscription.amount_paid') }}
                            </span>
                            <span class="font-mono font-semibold text-gray-900 dark:text-white">
                                {{ formatCurrency(payment.amount) }} {{ payment.currency || 'SAR' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $t('subscription.payment_method') }}
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ payment.payment_method || payment.gateway || '-' }}
                            </span>
                        </div>
                        <div v-if="payment.paid_at" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $t('subscription.paid_at') }}
                            </span>
                            <span class="font-mono text-gray-900 dark:text-white">
                                {{ formatDate(payment.paid_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        :href="route('dashboard')"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
                    >
                        {{ $t('subscription.go_to_dashboard') }}
                    </Link>
                    <Link
                        :href="route('app.subscription.index')"
                        class="px-6 py-2.5 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg font-medium transition-colors"
                    >
                        {{ $t('subscription.view_subscription') }}
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payment: { type: Object, default: null },
});

function formatCurrency(value) {
    if (value === null || value === undefined) return '0.00';
    return Number(value).toFixed(2);
}

function formatDate(value) {
    if (!value) return '-';
    try {
        return new Date(value).toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch (e) {
        return value;
    }
}
</script>
