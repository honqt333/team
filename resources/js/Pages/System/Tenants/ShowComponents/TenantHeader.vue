<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link
                href="/system/tenants"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <svg
                    class="w-5 h-5 rtl:rotate-180"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ tenant.trade_name || tenant.name }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400">{{ tenant.legal_name }}</p>
            </div>
        </div>
        <span
            :class="getStatusClass(tenant.status)"
            class="px-3 py-1.5 rounded-full text-sm font-medium"
        >
            {{ getStatusLabel(tenant.status) }}
        </span>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    tenant: { type: Object, required: true },
});

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'نشط',
        trial: 'تجريبي',
        suspended: 'معلّق',
    };
    return labels[status] || status || 'غير محدد';
};
</script>
