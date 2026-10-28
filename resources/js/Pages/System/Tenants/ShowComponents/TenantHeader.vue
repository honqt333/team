<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link
                href="/system/tenants"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                :title="$t('system.tenants.title')"
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
                    {{ tenant.trade_name || tenant.name || $t('common.na') }}
                </h1>
                <p v-if="tenant.legal_name" class="text-gray-500 dark:text-gray-400">
                    {{ tenant.legal_name }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span
                v-if="currentSubscription?.plan"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"
                    />
                </svg>
                {{ currentSubscription.plan.name_ar || currentSubscription.plan.name_en }}
                <span class="text-xs opacity-75">({{ $t('hr.leaves.types.annual') }})</span>
            </span>
            <span
                :class="getStatusClass(tenant.status)"
                class="inline-flex px-3 py-1.5 rounded-full text-sm font-medium"
            >
                {{ getStatusLabel(tenant.status) }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    tenant: { type: Object, required: true },
});

const currentSubscription = computed(() => {
    const subs = props.tenant.subscriptions || [];
    // Prioritize active or trialing, fallback to latest
    return subs.find((s) => ['active', 'trial', 'trialing'].includes(s.status)) || subs[0] || null;
});

const { t } = useI18n();

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const known = ['active', 'trial', 'suspended'];
    if (known.includes(status)) {
        return t(`system.status.${status}`);
    }
    return status || t('common.na');
};
</script>
