<template>
    <SystemLayout>
        <Head :title="$t('system.analytics.hub_title')" />

        <div class="space-y-6">
            <!-- Unified Page Header -->
            <PageHeader
                :title="$t('system.analytics.hub_title')"
                :subtitle="$t('system.analytics.hub_subtitle')"
                gradientFrom="from-blue-600"
                gradientTo="to-action-primary"
                glowFrom="from-blue-500"
                glowTo="to-action-primary"
                badgeBg="bg-blue-50/70 dark:bg-blue-950/40"
                badgeText="text-blue-600 dark:text-blue-400"
                badgeBorder="border-blue-200/50 dark:border-blue-800/40"
                badgeDot="bg-blue-500"
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

            <!-- Standardized Approved Tabs Component -->
            <AppTabs
                v-model="currentTab"
                :tabs="tabs"
                variant="underline"
                @change="handleTabChange"
            />

            <!-- Tab Panels -->
            <div class="mt-4">
                <KpisTab v-if="currentTab === 'kpis'" :kpis="kpis" />
                <ReportsTab v-else-if="currentTab === 'reports'" />
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import KpisTab from './Tabs/KpisTab.vue';
import ReportsTab from './Tabs/ReportsTab.vue';

const { t } = useI18n();

const props = defineProps({
    tab: { type: String, default: 'kpis' },
    kpis: { type: Object, default: () => ({}) },
});

const currentTab = ref(props.tab || 'kpis');

watch(
    () => props.tab,
    (newTab) => {
        if (newTab && newTab !== currentTab.value) {
            currentTab.value = newTab;
        }
    }
);

const tabs = computed(() => [
    {
        id: 'kpis',
        label: t('system.analytics.tabs.kpis'),
        name: t('system.analytics.tabs.kpis'),
    },
    {
        id: 'reports',
        label: t('system.analytics.tabs.reports'),
        name: t('system.analytics.tabs.reports'),
    },
]);

const handleTabChange = (tabId) => {
    currentTab.value = tabId;
    router.get(
        '/system/analytics',
        { tab: tabId },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>
