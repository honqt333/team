<template>
    <Head :title="$t('invoices.hub.title')" />
    <AppLayout :title="$t('invoices.hub.title')">
        <div class="space-y-8">
            <!-- Header Section -->
            <PageHeader :title="$t('invoices.hub.title')" :subtitle="$t('invoices.hub.subtitle')">
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl">
                <!-- Sales Invoices Card -->
                <Link
                    :href="route('app.invoices.sales.index')"
                    class="group relative bg-surface rounded-2xl p-8 border border-border hover:border-action-primary hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div
                        class="w-20 h-20 mx-auto rounded-2xl bg-action-primary flex items-center justify-center shadow-lg shadow-action-primary/25 group-hover:scale-110 transition-all duration-300"
                    >
                        <svg
                            class="w-10 h-10 text-action-on"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-6 text-xl font-bold text-text-primary group-hover:text-action-primary transition-colors"
                    >
                        {{ $t('invoices.hub.sales_invoices') }}
                    </h3>
                    <p class="mt-2 text-sm text-text-secondary">
                        {{ $t('invoices.hub.sales_invoices_hint') }}
                    </p>
                    <!-- Count Badge -->
                    <div
                        v-if="salesCount > 0"
                        class="absolute top-4 end-4 px-3 py-1 bg-action-primary text-action-on text-sm font-bold font-mono rounded-full shadow-sm"
                    >
                        {{ toEnglish(salesCount) }}
                    </div>
                </Link>

                <!-- Purchase Invoices Card -->
                <Link
                    :href="route('app.invoices.purchases.index')"
                    class="group relative bg-surface rounded-2xl p-8 border border-border hover:border-warning hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div
                        class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/25 group-hover:scale-110 transition-all duration-300"
                    >
                        <svg
                            class="w-10 h-10 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-6 text-xl font-bold text-text-primary group-hover:text-warning transition-colors"
                    >
                        {{ $t('invoices.hub.purchase_invoices') }}
                    </h3>
                    <p class="mt-2 text-sm text-text-secondary">
                        {{ $t('invoices.hub.purchase_invoices_hint') }}
                    </p>
                    <!-- Count Badge -->
                    <div
                        v-if="purchasesCount > 0"
                        class="absolute top-4 end-4 px-3 py-1 bg-warning text-white text-sm font-bold font-mono rounded-full shadow-sm"
                    >
                        {{ toEnglish(purchasesCount) }}
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
