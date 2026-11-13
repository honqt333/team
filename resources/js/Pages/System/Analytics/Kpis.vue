<template>
    <SystemLayout>
        <Head :title="$t('system.analytics.tenant_analytics')" />

        <div class="space-y-6">
            <PageHeader
                :title="$t('system.analytics.tenant_analytics')"
                :subtitle="$t('system.analytics.kpis_subtitle')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <KpisTab :kpis="kpis" />
        </div>
    </SystemLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import KpisTab from '@/Pages/System/Analytics/Tabs/KpisTab.vue';

defineProps({
    kpis: {
        type: Object,
        required: true,
    },
});
</script>
